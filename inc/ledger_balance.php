<?php

if (!function_exists('vll_ledger_user_keys')) {

function vll_ledger_user_keys($userRow)
{
    if (!is_array($userRow)) {
        return array();
    }
    $keys = array();
    foreach (array('user_id', 'username', 'phone_number', 'contact_phone') as $f) {
        if (!isset($userRow[$f])) {
            continue;
        }
        $v = trim((string) $userRow[$f]);
        if ($v !== '') {
            $keys[] = $v;
        }
    }
    return array_values(array_unique($keys));
}

} // function_exists

if (!function_exists('vll_ledger_balance_for_user')) {

/**
 * SMS credit balance from `transactions`: SUM(allocated) - SUM(consumed).
 * Includes rows keyed by users.user_id OR legacy keys (username, phone fields) so split ledgers still total correctly.
 */
function vll_ledger_balance_for_user($conn, $userRow = null)
{
    if (!$conn) {
        return 0.0;
    }
    if (!is_array($userRow)) {
        $userRow = array();
    }
    if (count($userRow) < 1 && isset($_SESSION['user_id']) && (string) $_SESSION['user_id'] !== '') {
        $uid = mysqli_real_escape_string($conn, (string) $_SESSION['user_id']);
        $q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $uid . "' LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $userRow = mysqli_fetch_assoc($q);
        } else {
            $userRow = array('user_id' => (string) $_SESSION['user_id']);
        }
    }
    $keys = vll_ledger_user_keys($userRow);
    if (count($keys) < 1) {
        return 0.0;
    }
    $parts = array();
    foreach ($keys as $k) {
        $parts[] = "user_id='" . mysqli_real_escape_string($conn, $k) . "'";
    }
    $where = implode(' OR ', $parts);
    $b = @mysqli_query($conn, "SELECT (SUM(allocated)-SUM(consumed)) AS balance FROM transactions WHERE ($where)");
    if (!$b) {
        return 0.0;
    }
    $row = mysqli_fetch_assoc($b);
    if (!$row || !array_key_exists('balance', $row) || $row['balance'] === null || $row['balance'] === '') {
        return 0.0;
    }
    return (float) $row['balance'];
}

} // function_exists

if (!function_exists('vll_ledger_billing_user_id_for_row')) {

/**
 * Ledger key used for consumed rows — prefers the key that holds the most credit.
 */
function vll_ledger_billing_user_id_for_row($conn, $userRow)
{
    if (!$conn || !is_array($userRow)) {
        return '';
    }
    $keys = vll_ledger_user_keys($userRow);
    if (count($keys) < 1) {
        return '';
    }
    if (count($keys) === 1) {
        return $keys[0];
    }
    $primary = trim((string) ($userRow['user_id'] ?? ''));
    $bestKey = $keys[0];
    $bestNet = null;
    foreach ($keys as $k) {
        $ke = mysqli_real_escape_string($conn, $k);
        $q = mysqli_query(
            $conn,
            "SELECT (COALESCE(SUM(allocated),0)-COALESCE(SUM(consumed),0)) AS net FROM transactions WHERE user_id='" . $ke . "'"
        );
        $net = 0.0;
        if ($q && ($row = mysqli_fetch_assoc($q)) && $row['net'] !== null && $row['net'] !== '') {
            $net = (float) $row['net'];
        }
        if ($bestNet === null || $net > $bestNet || ($net === $bestNet && $k === $primary)) {
            $bestNet = $net;
            $bestKey = $k;
        }
    }
    return $bestKey;
}

} // function_exists

if (!function_exists('vll_ledger_billing_user_row')) {

/**
 * Account whose credits are charged: sender owner for private sender IDs, else session user.
 */
function vll_ledger_billing_user_row($conn, $sessionUserRow, $senderIdRaw = '')
{
    if (!is_array($sessionUserRow)) {
        $sessionUserRow = array();
    }
    if (!$conn) {
        return $sessionUserRow;
    }
    $senderNorm = trim((string) $senderIdRaw);
    if ($senderNorm !== '' && function_exists('vll_normalize_outgoing_sender_id')) {
        $senderNorm = vll_normalize_outgoing_sender_id($senderNorm);
    }
    if ($senderNorm === '') {
        return $sessionUserRow;
    }
    $se = mysqli_real_escape_string($conn, $senderNorm);
    $sessionUid = trim((string) ($sessionUserRow['user_id'] ?? ''));
    $q = mysqli_query(
        $conn,
        "SELECT user_id, id_type FROM senders WHERE id_status='Active' AND sender_id='" . $se . "'"
    );
    if (!$q) {
        return $sessionUserRow;
    }
    $privateRows = array();
    while ($s = mysqli_fetch_assoc($q)) {
        $candidate = trim((string) ($s['user_id'] ?? ''));
        $type = strtolower(trim((string) ($s['id_type'] ?? '')));
        if ($candidate === '' || $type === 'public' || $type === 'global') {
            continue;
        }
        $privateRows[] = $candidate;
    }
    // Prefer the session user's own private sender row (duplicate sender_id rows exist for some clients).
    if ($sessionUid !== '') {
        foreach ($privateRows as $candidate) {
            if ($candidate === $sessionUid) {
                return $sessionUserRow;
            }
        }
    }
    $ownerId = count($privateRows) > 0 ? $privateRows[0] : '';
    if ($ownerId === '' || $ownerId === $sessionUid) {
        return $sessionUserRow;
    }
    $uq = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE user_id='" . mysqli_real_escape_string($conn, $ownerId) . "' AND status='Active' LIMIT 1"
    );
    if ($uq && mysqli_num_rows($uq) > 0) {
        return mysqli_fetch_assoc($uq);
    }
    return array_merge($sessionUserRow, array('user_id' => $ownerId));
}

} // function_exists

if (!function_exists('vll_ledger_record_consumed')) {

function vll_ledger_record_consumed($conn, $userRow, $amount, $tdate)
{
    if (!$conn) {
        return false;
    }
    $amount = (int) $amount;
    if ($amount <= 0) {
        return true;
    }
    $billingId = vll_ledger_billing_user_id_for_row($conn, $userRow);
    if ($billingId === '') {
        return false;
    }
    $bid = mysqli_real_escape_string($conn, $billingId);
    $q = mysqli_query(
        $conn,
        "INSERT INTO transactions(user_id,consumed,tdate) VALUES('" . $bid . "','" . $amount . "','" . (int) $tdate . "')"
    );
    if (!$q) {
        error_log('vll_ledger_record_consumed failed for user_id=' . $billingId . ': ' . mysqli_error($conn));
    }
    return (bool) $q;
}

} // function_exists

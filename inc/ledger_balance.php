<?php

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
    $keys = array_values(array_unique($keys));
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

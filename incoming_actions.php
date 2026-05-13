<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
    exit;
}

$uid = mysqli_real_escape_string($conn, (string) $_SESSION['user_id']);
$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $uid . "' AND status='Active'");
if (!$q || mysqli_num_rows($q) < 1) {
    header("location:signout.php");
    exit;
}
$user = mysqli_fetch_assoc($q);
$redirect = "incoming.php";
$senderFilter = isset($_POST['sender_filter']) ? trim((string) $_POST['sender_filter']) : '';
$segmentFilter = isset($_POST['segment_filter']) ? trim((string) $_POST['segment_filter']) : '';
$readFilter = isset($_POST['read_filter']) ? trim((string) $_POST['read_filter']) : '';
$arStatus = isset($_POST['ar_status']) ? trim((string) $_POST['ar_status']) : 'all';
if ($arStatus !== 'active' && $arStatus !== 'archived' && $arStatus !== 'all') {
    $arStatus = 'all';
}
$qs = array();
if ($senderFilter !== '') {
    $qs[] = 'sender_id=' . urlencode($senderFilter);
}
if ($segmentFilter !== '') {
    $qs[] = 'segment=' . urlencode($segmentFilter);
}
if ($readFilter === '0' || $readFilter === '1') {
    $qs[] = 'is_read=' . urlencode($readFilter);
}
if ($arStatus !== 'all') {
    $qs[] = 'ar_status=' . urlencode($arStatus);
}
if (count($qs) > 0) {
    $redirect .= '?' . implode('&', $qs);
}

function vll_incoming_redirect($redirect, $msg)
{
    $sep = (strpos($redirect, '?') === false) ? '?' : '&';
    header("location:" . $redirect . $sep . "r=" . urlencode($msg));
    exit;
}

function vll_incoming_has_read_col($conn)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = false;
    $chk = @mysqli_query($conn, "SHOW COLUMNS FROM `incoming` LIKE 'is_read'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $cache = true;
    }
    return $cache;
}

function vll_sender_is_allowed($conn, $uid, $senderId)
{
    $sid = mysqli_real_escape_string($conn, $senderId);
    $uidEsc = mysqli_real_escape_string($conn, $uid);
    $q = mysqli_query(
        $conn,
        "SELECT id FROM senders WHERE id_status='Active' AND sender_id='" . $sid . "' AND (user_id='" . $uidEsc . "' OR id_type='Public' OR id_type='Global') LIMIT 1"
    );
    return $q && mysqli_num_rows($q) > 0;
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

if ($action === 'delete_one') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id < 1) {
        vll_incoming_redirect($redirect, "Invalid row.");
    }
    mysqli_query($conn, "DELETE FROM incoming WHERE id='" . $id . "' AND user_id='" . $uid . "' LIMIT 1");
    vll_incoming_redirect($redirect, "Incoming message deleted.");
}

if ($action === 'mark_read_one') {
    if (!vll_incoming_has_read_col($conn)) {
        vll_incoming_redirect($redirect, "Read status is unavailable until incoming table migration is applied.");
    }
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id < 1) {
        vll_incoming_redirect($redirect, "Invalid row.");
    }
    mysqli_query($conn, "UPDATE incoming SET is_read='1' WHERE id='" . $id . "' AND user_id='" . $uid . "' LIMIT 1");
    vll_incoming_redirect($redirect, "Message marked as read.");
}

if ($action === 'mark_read_selected') {
    if (!vll_incoming_has_read_col($conn)) {
        vll_incoming_redirect($redirect, "Read status is unavailable until incoming table migration is applied.");
    }
    if (empty($_POST['selected_ids']) || !is_array($_POST['selected_ids'])) {
        vll_incoming_redirect($redirect, "Select at least one row.");
    }
    $ids = array_map('intval', $_POST['selected_ids']);
    $ids = array_filter($ids, function ($v) {
        return $v > 0;
    });
    if (count($ids) < 1) {
        vll_incoming_redirect($redirect, "Select valid rows.");
    }
    $idCsv = implode(",", $ids);
    mysqli_query($conn, "UPDATE incoming SET is_read='1' WHERE user_id='" . $uid . "' AND id IN (" . $idCsv . ")");
    $changed = (int) mysqli_affected_rows($conn);
    vll_incoming_redirect($redirect, "Marked " . $changed . " message(s) as read.");
}

if ($action === 'mark_read_filtered') {
    if (!vll_incoming_has_read_col($conn)) {
        vll_incoming_redirect($redirect, "Read status is unavailable until incoming table migration is applied.");
    }
    $where = " user_id='" . $uid . "' AND is_read='0' ";
    if ($senderFilter !== '') {
        $where .= " AND recipient='" . mysqli_real_escape_string($conn, $senderFilter) . "'";
    }
    if ($segmentFilter !== '') {
        $where .= " AND segment='" . mysqli_real_escape_string($conn, $segmentFilter) . "'";
    }
    if ($readFilter === '1') {
        vll_incoming_redirect($redirect, "Current filter is read-only; nothing to mark.");
    }
    mysqli_query($conn, "UPDATE incoming SET is_read='1' WHERE " . $where);
    $changed = (int) mysqli_affected_rows($conn);
    vll_incoming_redirect($redirect, "Marked " . $changed . " filtered message(s) as read.");
}

if ($action === 'delete_auto_reply') {
    $autoId = isset($_GET['auto_id']) ? (int) $_GET['auto_id'] : 0;
    if ($autoId < 1) {
        vll_incoming_redirect($redirect, "Invalid template id.");
    }
    $rs = mysqli_query($conn, "SELECT id,sender_id FROM autoreplies WHERE id='" . $autoId . "' LIMIT 1");
    if (!$rs || mysqli_num_rows($rs) < 1) {
        vll_incoming_redirect($redirect, "Auto-reply template not found.");
    }
    $row = mysqli_fetch_assoc($rs);
    if (!vll_sender_is_allowed($conn, $uid, (string) $row['sender_id'])) {
        vll_incoming_redirect($redirect, "You are not allowed to delete this auto-reply template.");
    }
    mysqli_query($conn, "DELETE FROM autoreplies WHERE id='" . $autoId . "' LIMIT 1");
    vll_incoming_redirect($redirect, "Auto-reply template purged from database.");
}

vll_incoming_redirect($redirect, "Unsupported action.");

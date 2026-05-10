<?php
/**
 * Excel-friendly CSV export of incoming listener numbers for contact import + reporting.
 * Query: sender_id, segment (optional), report=all|success|failed, mode=full|phones
 */
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

function vll_incoming_has_extended_cols($conn)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = false;
    $chk = @mysqli_query($conn, "SHOW COLUMNS FROM `incoming` LIKE 'segment'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $cache = true;
    }
    return $cache;
}

$senderFilter = isset($_GET['sender_id']) ? trim((string) $_GET['sender_id']) : '';
$segmentFilter = isset($_GET['segment']) ? trim((string) $_GET['segment']) : '';
$report = isset($_GET['report']) ? strtolower(trim((string) $_GET['report'])) : 'all';
if (!in_array($report, array('all', 'success', 'failed'), true)) {
    $report = 'all';
}
$mode = isset($_GET['mode']) ? strtolower(trim((string) $_GET['mode'])) : 'full';
if (!in_array($mode, array('full', 'phones'), true)) {
    $mode = 'full';
}

$extended = vll_incoming_has_extended_cols($conn);

if (!$extended && $report !== 'all') {
    header("location:incoming.php?r=" . urlencode('Add segment/auto_reply columns (see db/alter_incoming_segment_status.sql) before success/failed reports.'));
    exit;
}

$where = " WHERE user_id='" . $uid . "'";
if ($senderFilter !== '') {
    $where .= " AND recipient='" . mysqli_real_escape_string($conn, $senderFilter) . "'";
}
if ($extended && $segmentFilter !== '') {
    $where .= " AND segment='" . mysqli_real_escape_string($conn, $segmentFilter) . "'";
}
if ($extended && $report === 'success') {
    $where .= " AND auto_reply_status='queued'";
} elseif ($extended && $report === 'failed') {
    $where .= " AND auto_reply_status IN ('insufficient_balance','failed_sender_row')";
}

$rows = array();
$sql = "SELECT id, recipient, sender, message, created_at" .
    ($extended ? ", segment, auto_reply_status" : "") .
    " FROM incoming " . $where . " ORDER BY id DESC LIMIT 50000";
$rs = mysqli_query($conn, $sql);
if ($rs) {
    while ($r = mysqli_fetch_assoc($rs)) {
        $rows[] = $r;
    }
}

$fname = 'incoming_report_' . $report . '_' . date("Ymd_His");
if ($mode === 'phones') {
    $fname .= '_phones_import';
}
$fname .= '.csv';

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=" . $fname);

$out = fopen("php://output", "w");
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

if ($mode === 'phones') {
    fputcsv($out, array('phone', 'name'));
    foreach ($rows as $r) {
        $phone = (string) $r['sender'];
        $name = 'Listener';
        if ($extended && !empty($r['segment'])) {
            $name .= ' (' . $r['segment'] . ')';
        }
        fputcsv($out, array($phone, $name));
    }
} else {
    $head = array('sender_id', 'phone_number', 'message', 'received_at');
    if ($extended) {
        $head[] = 'segment';
        $head[] = 'auto_reply_status';
    }
    fputcsv($out, $head);
    foreach ($rows as $r) {
        $line = array(
            (string) $r['recipient'],
            (string) $r['sender'],
            (string) $r['message'],
            (string) $r['created_at'],
        );
        if ($extended) {
            $line[] = isset($r['segment']) ? (string) $r['segment'] : '';
            $line[] = isset($r['auto_reply_status']) ? (string) $r['auto_reply_status'] : '';
        }
        fputcsv($out, $line);
    }
}
fclose($out);
exit;

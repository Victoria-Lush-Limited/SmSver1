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

$senderFilter = isset($_GET['sender_id']) ? trim((string) $_GET['sender_id']) : '';
$where = " WHERE user_id='" . $uid . "'";
if ($senderFilter !== '') {
    $where .= " AND recipient='" . mysqli_real_escape_string($conn, $senderFilter) . "'";
}

$rows = array();
$rs = mysqli_query($conn, "SELECT recipient,sender,message,created_at FROM incoming " . $where . " ORDER BY id DESC");
if ($rs) {
    while ($r = mysqli_fetch_assoc($rs)) {
        $rows[] = $r;
    }
}

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=incoming_messages_" . date("Ymd_His") . ".csv");

$out = fopen("php://output", "w");
fputcsv($out, array("sender_id", "phone_number", "message", "received_at"));
foreach ($rows as $r) {
    fputcsv($out, array(
        (string) $r['recipient'],
        (string) $r['sender'],
        (string) $r['message'],
        (string) $r['created_at'],
    ));
}
fclose($out);
exit;

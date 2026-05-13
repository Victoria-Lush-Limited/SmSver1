<?php
/**
 * UTF-8 CSV export of audience polls (VLL SMS app sync) for the logged-in portal user.
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

$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'audience_polls'");
if (!$chk || mysqli_num_rows($chk) < 1) {
    header("location:polls.php?r=" . urlencode("Polls table is missing. Run Laravel migrations or db/create_audience_polls_table.sql."));
    exit;
}

$state = isset($_GET['state']) ? trim((string) $_GET['state']) : 'all';
if ($state !== 'active' && $state !== 'ended' && $state !== 'all') {
    $state = 'all';
}
$where = " WHERE user_id='" . $uid . "'";
if ($state === 'active') {
    $where .= " AND active='1'";
} elseif ($state === 'ended') {
    $where .= " AND active='0'";
}

$rows = array();
$rs = mysqli_query(
    $conn,
    "SELECT id, title, opt1, opt2, opt3, opt4, started_at_ms, ended_at_ms, active, tallies_json, created_at, updated_at " .
    "FROM audience_polls" . $where . " ORDER BY id DESC LIMIT 10000"
);
if ($rs) {
    while ($r = mysqli_fetch_assoc($rs)) {
        $rows[] = $r;
    }
}

$fname = 'audience_polls_' . $state . '_' . date("Ymd_His") . '.csv';
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=" . $fname);

$out = fopen("php://output", "w");
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($out, array(
    'id',
    'title',
    'opt1',
    'opt2',
    'opt3',
    'opt4',
    'started_at_ms',
    'ended_at_ms',
    'active',
    'tallies_json',
    'created_at',
    'updated_at',
));

foreach ($rows as $r) {
    fputcsv($out, array(
        (string) $r['id'],
        (string) $r['title'],
        (string) $r['opt1'],
        (string) $r['opt2'],
        (string) $r['opt3'],
        (string) $r['opt4'],
        (string) $r['started_at_ms'],
        $r['ended_at_ms'] === null ? '' : (string) $r['ended_at_ms'],
        ((int) ($r['active'] ?? 0) === 1) ? '1' : '0',
        (string) ($r['tallies_json'] ?? ''),
        (string) ($r['created_at'] ?? ''),
        (string) ($r['updated_at'] ?? ''),
    ));
}
fclose($out);
exit;

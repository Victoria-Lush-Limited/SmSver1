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

$redirect = "polls.php";
$state = isset($_POST['state']) ? trim((string) $_POST['state']) : '';
if ($state === 'active' || $state === 'ended') {
    $redirect .= '?state=' . urlencode($state);
}

function polls_act_redirect($redirect, $msg, $ok = false)
{
    if ($msg === '') {
        header("location:" . $redirect);
        exit;
    }
    $sep = (strpos($redirect, '?') === false) ? '?' : '&';
    $q = "r=" . urlencode($msg);
    if ($ok) {
        $q .= "&t=ok";
    }
    header("location:" . $redirect . $sep . $q);
    exit;
}

$hasTable = false;
$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'audience_polls'");
if ($chk && mysqli_num_rows($chk) > 0) {
    $hasTable = true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    polls_act_redirect("polls.php", "");
}
if (!$hasTable) {
    polls_act_redirect("polls.php", "Polls storage is not available.");
}

$action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

if ($action === 'delete_one') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id < 1) {
        polls_act_redirect($redirect, "Invalid poll id.");
    }
    mysqli_query($conn, "DELETE FROM audience_polls WHERE id='" . $id . "' AND user_id='" . $uid . "' LIMIT 1");
    polls_act_redirect($redirect, "Poll deleted.", true);
}

if ($action === 'delete_selected') {
    if (empty($_POST['selected_ids']) || !is_array($_POST['selected_ids'])) {
        polls_act_redirect($redirect, "Select polls to delete.");
    }
    $ids = array_map('intval', $_POST['selected_ids']);
    $ids = array_filter($ids, function ($v) {
        return $v > 0;
    });
    if (count($ids) < 1) {
        polls_act_redirect($redirect, "Select valid polls.");
    }
    $csv = implode(',', $ids);
    mysqli_query($conn, "DELETE FROM audience_polls WHERE user_id='" . $uid . "' AND id IN (" . $csv . ")");
    $n = (int) mysqli_affected_rows($conn);
    polls_act_redirect($redirect, "Deleted " . $n . " poll(s).", true);
}

polls_act_redirect($redirect, "Unsupported action.");

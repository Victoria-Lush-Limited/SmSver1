<?php
include "db/dblink.php";
include_once "phone_lib.php";
include_once "simple_xlsx.php";

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
if (count($qs) > 0) {
    $redirect .= '?' . implode('&', $qs);
}

function vll_incoming_redirect($redirect, $msg)
{
    $sep = (strpos($redirect, '?') === false) ? '?' : '&';
    header("location:" . $redirect . $sep . "r=" . urlencode($msg));
    exit;
}

function vll_insert_custom_sms($conn, $uid, $senderId, $phone, $message)
{
    $credits = (int) ceil(strlen($message) / 160);
    if ($credits < 1) {
        $credits = 1;
    }
    $now = time();
    $phoneEsc = mysqli_real_escape_string($conn, $phone);
    $senderEsc = mysqli_real_escape_string($conn, $senderId);
    $msgEsc = mysqli_real_escape_string($conn, $message);
    $uidEsc = mysqli_real_escape_string($conn, $uid);
    return (bool) mysqli_query(
        $conn,
        "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" .
        $phoneEsc . "','" . $senderEsc . "','" . $msgEsc . "','" . $credits . "','None','" . $now . "','" . $now . "','" . $now . "','0','Pending','" . $uidEsc . "')"
    );
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

function vll_autoreplies_has_segment_col($conn)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = false;
    $chk = @mysqli_query($conn, "SHOW COLUMNS FROM `autoreplies` LIKE 'segment'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $cache = true;
    }
    return $cache;
}

function vll_parse_uploaded_numbers($fileTmp, $fileName)
{
    $numbers = array();
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($ext === 'csv' && is_readable($fileTmp)) {
        $fh = fopen($fileTmp, "r");
        if ($fh) {
            while (($row = fgetcsv($fh)) !== false) {
                if (!isset($row[0])) {
                    continue;
                }
                $n = normalize_recipient_msisdn($row[0]);
                if ($n !== '') {
                    $numbers[$n] = true;
                }
            }
            fclose($fh);
        }
    } elseif (($ext === 'xls' || $ext === 'xlsx') && is_readable($fileTmp)) {
        $xlsx = new SimpleXLSX($fileTmp);
        foreach ($xlsx->rows() as $idx => $row) {
            if (!isset($row[0])) {
                continue;
            }
            if ($idx === 0 && preg_match('/[a-zA-Z]/', (string) $row[0])) {
                continue;
            }
            $n = normalize_recipient_msisdn($row[0]);
            if ($n !== '') {
                $numbers[$n] = true;
            }
        }
    }
    return array_keys($numbers);
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

if ($action === 'save_auto_reply') {
    $senderId = trim((string) ($_POST['auto_sender_id'] ?? ''));
    $reply = trim((string) ($_POST['auto_reply_text'] ?? ''));
    $start = trim((string) ($_POST['auto_start'] ?? ''));
    $end = trim((string) ($_POST['auto_end'] ?? ''));
    $segment = trim((string) ($_POST['auto_segment'] ?? ''));
    if ($senderId === '' || $reply === '' || $start === '') {
        vll_incoming_redirect($redirect, "Sender ID, auto-reply text and start time are required.");
    }
    $senderEsc = mysqli_real_escape_string($conn, $senderId);
    $replyEsc = mysqli_real_escape_string($conn, $reply);
    $startEsc = mysqli_real_escape_string($conn, $start . ":00");
    $endSql = "NULL";
    if ($end !== '') {
        $endSql = "'" . mysqli_real_escape_string($conn, $end . ":00") . "'";
    }
    if (vll_autoreplies_has_segment_col($conn)) {
        $segSql = "NULL";
        if ($segment !== '') {
            $segSql = "'" . mysqli_real_escape_string($conn, $segment) . "'";
        }
        mysqli_query(
            $conn,
            "INSERT INTO autoreplies(sender_id,reply,scheduled_time,end_schedule,segment,created_at,updated_at) VALUES('" .
            $senderEsc . "','" . $replyEsc . "','" . $startEsc . "'," . $endSql . "," . $segSql . ",NOW(),NOW())"
        );
    } else {
        mysqli_query(
            $conn,
            "INSERT INTO autoreplies(sender_id,reply,scheduled_time,end_schedule,created_at,updated_at) VALUES('" .
            $senderEsc . "','" . $replyEsc . "','" . $startEsc . "'," . $endSql . ",NOW(),NOW())"
        );
    }
    vll_incoming_redirect($redirect, "Auto-reply template saved.");
}

if ($action === 'update_auto_reply') {
    $autoId = (int) ($_POST['update_auto_id'] ?? 0);
    $reply = trim((string) ($_POST['update_auto_reply_text'] ?? ''));
    $start = trim((string) ($_POST['update_auto_start'] ?? ''));
    $end = trim((string) ($_POST['update_auto_end'] ?? ''));
    $segment = trim((string) ($_POST['update_auto_segment'] ?? ''));
    if ($autoId < 1 || $reply === '' || $start === '') {
        vll_incoming_redirect($redirect, "Template ID, new reply text and new start time are required.");
    }
    $rs = mysqli_query($conn, "SELECT id,sender_id FROM autoreplies WHERE id='" . $autoId . "' LIMIT 1");
    if (!$rs || mysqli_num_rows($rs) < 1) {
        vll_incoming_redirect($redirect, "Auto-reply template not found.");
    }
    $row = mysqli_fetch_assoc($rs);
    if (!vll_sender_is_allowed($conn, $uid, (string) $row['sender_id'])) {
        vll_incoming_redirect($redirect, "You are not allowed to update this auto-reply template.");
    }
    $replyEsc = mysqli_real_escape_string($conn, $reply);
    $startEsc = mysqli_real_escape_string($conn, $start . ":00");
    $endSql = "NULL";
    if ($end !== '') {
        $endSql = "'" . mysqli_real_escape_string($conn, $end . ":00") . "'";
    }
    if (vll_autoreplies_has_segment_col($conn)) {
        $segSql = "NULL";
        if ($segment !== '') {
            $segSql = "'" . mysqli_real_escape_string($conn, $segment) . "'";
        }
        mysqli_query(
            $conn,
            "UPDATE autoreplies SET reply='" . $replyEsc . "', scheduled_time='" . $startEsc . "', end_schedule=" . $endSql . ", segment=" . $segSql . ", updated_at=NOW() WHERE id='" . $autoId . "' LIMIT 1"
        );
    } else {
        mysqli_query(
            $conn,
            "UPDATE autoreplies SET reply='" . $replyEsc . "', scheduled_time='" . $startEsc . "', end_schedule=" . $endSql . ", updated_at=NOW() WHERE id='" . $autoId . "' LIMIT 1"
        );
    }
    vll_incoming_redirect($redirect, "Auto-reply template updated.");
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
    vll_incoming_redirect($redirect, "Auto-reply template deleted.");
}

if ($action === 'send_template_selected') {
    $senderId = trim((string) ($_POST['sender_id'] ?? ''));
    $msg = trim((string) ($_POST['template_message'] ?? ''));
    if ($senderId === '' || $msg === '') {
        vll_incoming_redirect($redirect, "Sender ID and template message are required.");
    }

    $phones = array();

    if (!empty($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        $ids = array_filter($ids, function ($v) {
            return $v > 0;
        });
        if (count($ids) > 0) {
            $idCsv = implode(",", $ids);
            $rs = mysqli_query($conn, "SELECT sender FROM incoming WHERE user_id='" . $uid . "' AND id IN (" . $idCsv . ")");
            if ($rs) {
                while ($r = mysqli_fetch_assoc($rs)) {
                    $n = normalize_recipient_msisdn((string) $r['sender']);
                    if ($n !== '') {
                        $phones[$n] = true;
                    }
                }
            }
        }
    }

    $manual = trim((string) ($_POST['manual_numbers'] ?? ''));
    if ($manual !== '') {
        $parts = preg_split('/[\s,;]+/', $manual);
        foreach ($parts as $p) {
            $n = normalize_recipient_msisdn((string) $p);
            if ($n !== '') {
                $phones[$n] = true;
            }
        }
    }

    if (isset($_FILES['numbers_file']) && is_uploaded_file($_FILES['numbers_file']['tmp_name'])) {
        $uploaded = vll_parse_uploaded_numbers($_FILES['numbers_file']['tmp_name'], (string) $_FILES['numbers_file']['name']);
        foreach ($uploaded as $n) {
            $phones[$n] = true;
        }
    }

    if (count($phones) < 1) {
        vll_incoming_redirect($redirect, "No recipient numbers found.");
    }

    $queued = 0;
    foreach (array_keys($phones) as $phone) {
        if (vll_insert_custom_sms($conn, $uid, $senderId, $phone, $msg)) {
            $queued++;
        }
    }

    if ($queued < 1) {
        vll_incoming_redirect($redirect, "Failed to queue template send.");
    }
    header("location:preview.php?r=" . urlencode("Queued " . $queued . " message(s). Review and click Send."));
    exit;
}

vll_incoming_redirect($redirect, "Unsupported action.");

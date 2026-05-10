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
$qs = array();
if ($senderFilter !== '') {
    $qs[] = 'sender_id=' . urlencode($senderFilter);
}
if ($segmentFilter !== '') {
    $qs[] = 'segment=' . urlencode($segmentFilter);
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

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

if ($action === 'delete_one') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id < 1) {
        vll_incoming_redirect($redirect, "Invalid row.");
    }
    mysqli_query($conn, "DELETE FROM incoming WHERE id='" . $id . "' AND user_id='" . $uid . "' LIMIT 1");
    vll_incoming_redirect($redirect, "Incoming message deleted.");
}

if ($action === 'save_auto_reply') {
    $senderId = trim((string) ($_POST['auto_sender_id'] ?? ''));
    $reply = trim((string) ($_POST['auto_reply_text'] ?? ''));
    $start = trim((string) ($_POST['auto_start'] ?? ''));
    $end = trim((string) ($_POST['auto_end'] ?? ''));
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
    mysqli_query(
        $conn,
        "INSERT INTO autoreplies(sender_id,reply,scheduled_time,end_schedule,created_at,updated_at) VALUES('" .
        $senderEsc . "','" . $replyEsc . "','" . $startEsc . "'," . $endSql . ",NOW(),NOW())"
    );
    vll_incoming_redirect($redirect, "Auto-reply template saved.");
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

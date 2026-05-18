<?php
include "db/dblink.php";
include "phone_lib.php";
include_once "outgoing_queue_lib.php";

@set_time_limit(0);
$vll_max_send_seconds = (int) vll_env("VLL_SEND_MAX_SECONDS", "600");
if ($vll_max_send_seconds > 0) {
    @ini_set("max_execution_time", (string) $vll_max_send_seconds);
}

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND status='Active'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

include_once __DIR__ . '/inc/ledger_balance.php';

$contacts = explode(",", mysqli_real_escape_string($conn, $_POST['contacts']));
$groups = explode(",", mysqli_real_escape_string($conn, $_POST['groups']));


$recipients = "";

foreach ($contacts as $contact) {
    if (!empty($contact)) {
        $recipients .= $contact . ",";
    }
}

foreach ($groups as $group_id) {
    if (!empty($group_id)) {
        $q = mysqli_query($conn, "SELECT C.phone_number FROM group_contacts G,contacts C WHERE G.contact_id=C.contact_id AND G.group_id='" . $group_id . "' AND C.user_id='" . $_SESSION['user_id'] . "'");
        while ($group_contact = mysqli_fetch_assoc($q)) {
            $recipients .= $group_contact['phone_number'] . ",";
        }
    }
}

$recipient_list = explode(",", $recipients);
$unique_recipients = array();
foreach ($recipient_list as $recipient) {
    $recipient = normalize_recipient_msisdn($recipient);
    if ($recipient !== "" && is_valid_outgoing_msisdn($recipient)) {
        $unique_recipients[$recipient] = true;
    }
}
$recipient_list_all = array_keys($unique_recipients);
$recipient_list = array();
$skipped_unsupported = 0;
foreach ($recipient_list_all as $r) {
    if (preg_match('/^(255|254|256)/', $r)) {
        $recipient_list[] = $r;
    } elseif ($r !== "") {
        $skipped_unsupported++;
    }
}
$total_recipients = count($recipient_list);


$message = mysqli_real_escape_string($conn, $_POST['message']);
$credits = ceil(strlen($message) / 160);;

$sender_raw = isset($_POST['sender_id']) ? trim((string) $_POST['sender_id']) : '';
$sender_raw = vll_normalize_outgoing_sender_id($sender_raw);
$sender_id = mysqli_real_escape_string($conn, $sender_raw);

$user_id = $user['user_id'];
$billing_user = vll_ledger_billing_user_row($conn, $user, $sender_raw);
$sms_status = "Pending";


$schedule = mysqli_real_escape_string($conn, $_POST['schedule']);

$start_date = strtotime(mysqli_real_escape_string($conn, $_POST['start_date']));
$end_date = strtotime(mysqli_real_escape_string($conn, $_POST['end_date']));

$send_hour = mysqli_real_escape_string($conn, $_POST['send_hour']);
$send_minute = mysqli_real_escape_string($conn, $_POST['send_minute']);


$now = time();

$recipient_list_all_count = count($recipient_list_all);
$sent_ok_msg = $skipped_unsupported > 0
    ? 'Sent. Skipped ' . $skipped_unsupported . ' number(s) with unsupported country code.'
    : 'Sent';
$no_recipients_msg = ($recipient_list_all_count > 0)
    ? 'No supported recipients found. Use numbers with country code 255 (Tanzania), 254 (Kenya), or 256 (Uganda).'
    : 'No valid recipients found';

function queue_outgoing_batch($conn, $recipient_list, $sender_id, $message, $credits, $schedule, $start_date, $end_date, $date_created, $sms_status, $user_id)
{
    $chunk = vll_send_insert_chunk_size();
    $attempts = 0;
    $queued = 0;
    $buf = array();

    for ($i = 0; $i < count($recipient_list); $i++) {
        $phone_number = trim($recipient_list[$i]);
        if ($phone_number === "") {
            continue;
        }
        $p = mysqli_real_escape_string($conn, $phone_number);
        $buf[] = "('" . $p . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "','Queued for provider')";
        if (count($buf) >= $chunk) {
            if (!vll_flush_outgoing_values($conn, $buf)) {
                throw new Exception(mysqli_error($conn));
            }
            $queued += count($buf);
            $buf = array();
        }
    }
    if (count($buf) > 0) {
        if (!vll_flush_outgoing_values($conn, $buf)) {
            throw new Exception(mysqli_error($conn));
        }
        $queued += count($buf);
    }

    return $queued;
}

function queue_messages_and_charge($conn, $recipient_list, $sender_id, $message, $credits, $schedule, $start_date, $end_date, $date_created, $sms_status, $user_id, $billing_user, $consumed, $now)
{
    $billing_user_id = is_array($billing_user) ? vll_ledger_billing_user_id_for_row($conn, $billing_user) : '';
    $lockToken = "vllsmsq_" . md5($user_id . '|' . $billing_user_id);
    $gotLock = false;
    $lr = mysqli_query($conn, "SELECT GET_LOCK('" . $lockToken . "', 50) AS gl");
    $grow = $lr ? mysqli_fetch_assoc($lr) : null;
    if (!$lr || !isset($grow["gl"]) || (int) $grow["gl"] !== 1) {
        error_log("send_sms: GET_LOCK failed for user_id=" . $user_id);
        return false;
    }
    $gotLock = true;

    $ok = false;
    mysqli_begin_transaction($conn);
    try {
        $queued = queue_outgoing_batch($conn, $recipient_list, $sender_id, $message, $credits, $schedule, $start_date, $end_date, $date_created, $sms_status, $user_id);
        if ($queued === 0) {
            throw new Exception("No valid recipients were queued");
        }

        if ($consumed > 0) {
            if (!vll_ledger_record_consumed($conn, $billing_user, $consumed, $now)) {
                throw new Exception('Failed to record consumed credits');
            }
        }

        mysqli_commit($conn);
        $ok = true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("send_sms queue failure for user_id=" . $user_id . ": " . $e->getMessage());
    }
    if ($gotLock) {
        mysqli_query($conn, "SELECT RELEASE_LOCK('" . $lockToken . "')");
    }
    return $ok;
}

function queue_scheduled_runs_and_charge($conn, $recipient_list, $sender_id, $message, $credits, $schedule, $start_date, $end_date, $sms_status, $user_id, $billing_user, $run_dates, $consumed, $now)
{
    if (!is_array($run_dates) || count($run_dates) < 1) {
        return false;
    }

    $billing_user_id = is_array($billing_user) ? vll_ledger_billing_user_id_for_row($conn, $billing_user) : '';
    $lockToken = "vllsmssch_" . md5($user_id . '|' . $billing_user_id);
    $gotLock = false;
    $lr = mysqli_query($conn, "SELECT GET_LOCK('" . $lockToken . "', 50) AS gl");
    $grow = $lr ? mysqli_fetch_assoc($lr) : null;
    if (!$lr || !isset($grow["gl"]) || (int) $grow["gl"] !== 1) {
        error_log("send_sms scheduled: GET_LOCK failed for user_id=" . $user_id);
        return false;
    }
    $gotLock = true;

    $ok = false;
    mysqli_begin_transaction($conn);
    try {
        $queued = 0;
        foreach ($run_dates as $date_created) {
            $queued += queue_outgoing_batch(
                $conn,
                $recipient_list,
                $sender_id,
                $message,
                $credits,
                $schedule,
                $start_date,
                $end_date,
                (int) $date_created,
                $sms_status,
                $user_id
            );
        }
        if ($queued === 0) {
            throw new Exception("No valid recipients were queued for schedule");
        }
        if ((int) $consumed > 0) {
            if (!vll_ledger_record_consumed($conn, $billing_user, $consumed, $now)) {
                throw new Exception('Failed to record consumed credits');
            }
        }
        mysqli_commit($conn);
        $ok = true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("send_sms scheduled failure for user_id=" . $user_id . ": " . $e->getMessage());
    }
    if ($gotLock) {
        mysqli_query($conn, "SELECT RELEASE_LOCK('" . $lockToken . "')");
    }
    return $ok;
}

switch ($schedule) {
    case "None":
        if ($total_recipients <= 0) {
            header("location:compose.php?r=" . urlencode($no_recipients_msg));
            exit;
        }

        $consumed = $credits * $total_recipients;

        $date_created = strtotime(date("d-m-Y", $start_date) . " " . $send_hour . ":" . $send_minute);

        $balance = vll_ledger_balance_for_user($conn, $billing_user);
        if ($consumed <= $balance) {
            if (queue_messages_and_charge($conn, $recipient_list, $sender_id, $message, $credits, $schedule, $start_date, $end_date, $date_created, $sms_status, $user_id, $billing_user, $consumed, $now)) {
                header("location:compose.php?r=" . urlencode($sent_ok_msg));
            } else {
                header("location:compose.php?r=Failed to queue message(s). Please retry.");
            }
        } else {
            header("location:compose.php?r=Insufficient Balance");
        }

        break;

    case "Daily":
        if ($total_recipients <= 0) {
            header("location:compose.php?r=" . urlencode($no_recipients_msg));
            exit;
        }

        $consumed = 0;

        $next_date = $start_date;
        while ($next_date <= $end_date) {
            $consumed += ($credits * $total_recipients);
            $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
            $next_date =  strtotime("+1 days", $next_date);
        }
        $balance = vll_ledger_balance_for_user($conn, $billing_user);
        if ($consumed <= $balance) {
            $run_dates = array();
            $next_date = $start_date;
            while ($next_date <= $end_date) {
                $run_dates[] = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
                $next_date = strtotime("+1 days", $next_date);
            }
            if (!queue_scheduled_runs_and_charge($conn, $recipient_list, $sender_id, $message, $credits, $schedule, $start_date, $end_date, $sms_status, $user_id, $billing_user, $run_dates, $consumed, $now)) {
                header("location:compose.php?r=Failed to queue message(s) or deduct credits. Please retry.");
                exit;
            }
            header("location:compose.php?r=" . urlencode($sent_ok_msg));
        } else {
            header("location:compose.php?r=Insufficient Balance");
        }
        break;


    case "Weekly":
        if ($total_recipients <= 0) {
            header("location:compose.php?r=" . urlencode($no_recipients_msg));
            exit;
        }

        $consumed = 0;

        $next_date = $start_date;
        while ($next_date <= $end_date) {
            $consumed += ($credits * $total_recipients);
            $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
            $next_date =  strtotime("+1 weeks", $next_date);
        }
        $balance = vll_ledger_balance_for_user($conn, $billing_user);
        if ($consumed <= $balance) {
            $run_dates = array();
            $next_date = $start_date;
            while ($next_date <= $end_date) {
                $run_dates[] = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
                $next_date = strtotime("+1 weeks", $next_date);
            }
            if (!queue_scheduled_runs_and_charge($conn, $recipient_list, $sender_id, $message, $credits, $schedule, $start_date, $end_date, $sms_status, $user_id, $billing_user, $run_dates, $consumed, $now)) {
                header("location:compose.php?r=Failed to queue message(s) or deduct credits. Please retry.");
                exit;
            }
            header("location:compose.php?r=" . urlencode($sent_ok_msg));
        } else {
            header("location:compose.php?r=Insufficient Balance");
        }

        break;

    case "Monthly":
        if ($total_recipients <= 0) {
            header("location:compose.php?r=" . urlencode($no_recipients_msg));
            exit;
        }

        $consumed = 0;

        $next_date = $start_date;
        while ($next_date <= $end_date) {
            $consumed += ($credits * $total_recipients);
            $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
            $next_date =  strtotime("+1 months", $next_date);
        }
        $balance = vll_ledger_balance_for_user($conn, $billing_user);
        if ($consumed <= $balance) {
            $run_dates = array();
            $next_date = $start_date;
            while ($next_date <= $end_date) {
                $run_dates[] = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
                $next_date = strtotime("+1 months", $next_date);
            }
            if (!queue_scheduled_runs_and_charge($conn, $recipient_list, $sender_id, $message, $credits, $schedule, $start_date, $end_date, $sms_status, $user_id, $billing_user, $run_dates, $consumed, $now)) {
                header("location:compose.php?r=Failed to queue message(s) or deduct credits. Please retry.");
                exit;
            }
            header("location:compose.php?r=" . urlencode($sent_ok_msg));
        } else {
            header("location:compose.php?r=Insufficient Balance");
        }
        break;
}

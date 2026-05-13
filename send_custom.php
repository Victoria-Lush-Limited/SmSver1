<?php
include "db/dblink.php";
include_once "phone_lib.php";
include_once "outgoing_queue_lib.php";

$uid = mysqli_real_escape_string($conn, $_SESSION['user_id']);

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $uid . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
    exit;
}

$user = mysqli_fetch_assoc($q);

include_once __DIR__ . '/inc/ledger_balance.php';

$q = mysqli_query($conn, "SELECT SUM(credits) AS credits FROM custom_sms WHERE user_id='" . $uid . "'");
$required = mysqli_fetch_assoc($q);
$consumed = (int) ($required["credits"] ?? 0);

$balance = vll_ledger_balance_for_user($conn, $user);

if ($consumed <= $balance && $consumed > 0) {
    $now = time();
    $chunk = vll_send_insert_chunk_size();
    $lockToken = "vllsmscust_" . md5($uid);
    $gotLock = false;
    $lr = mysqli_query($conn, "SELECT GET_LOCK('" . $lockToken . "', 50) AS gl");
    $grow = $lr ? mysqli_fetch_assoc($lr) : null;
    if (!$lr || !isset($grow["gl"]) || (int) $grow["gl"] !== 1) {
        echo "Failed: system busy. Please retry.";
        exit;
    }
    $gotLock = true;

    mysqli_begin_transaction($conn);
    try {
        $qc = mysqli_query($conn, "SELECT * FROM custom_sms WHERE user_id='" . $uid . "'");
        if (!$qc) {
            throw new Exception(mysqli_error($conn));
        }

        $buf = array();
        $queued = 0;
        while ($custom = mysqli_fetch_assoc($qc)) {
            $phone_number = mysqli_real_escape_string($conn, str_replace(" ", "", $custom["phone_number"]));
            $sender_norm = vll_normalize_outgoing_sender_id((string) $custom["sender_id"]);
            $sender_id = mysqli_real_escape_string($conn, $sender_norm);
            $message = mysqli_real_escape_string($conn, $custom["message"]);
            $credits = (int) $custom["credits"];
            $schedule = mysqli_real_escape_string($conn, $custom["schedule"]);
            $start_date = (int) $custom["start_date"];
            $end_date = (int) $custom["end_date"];
            $date_created = (int) $custom["date_created"];
            $attempts = (int) $custom["attempts"];
            $sms_status = mysqli_real_escape_string($conn, $custom["sms_status"]);
            $user_id_esc = mysqli_real_escape_string($conn, $custom["user_id"]);
            $smsc_id = "";

            $buf[] = "('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id_esc . "','" . mysqli_real_escape_string($conn, $smsc_id) . "')";
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

        if ($queued === 0) {
            throw new Exception("No rows queued from custom_sms");
        }

        $q = mysqli_query($conn, "INSERT INTO transactions(user_id,consumed,tdate) VALUES('" . mysqli_real_escape_string($conn, $user["user_id"]) . "','" . (int) $consumed . "','" . (int) $now . "')");
        if (!$q) {
            throw new Exception(mysqli_error($conn));
        }
        $q = mysqli_query($conn, "DELETE FROM custom_sms WHERE user_id='" . $uid . "'");
        if (!$q) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_commit($conn);
        echo "Sent";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("send_custom failure: " . $e->getMessage());
        echo "Failed: could not complete send. Please retry.";
    } finally {
        if ($gotLock) {
            mysqli_query($conn, "SELECT RELEASE_LOCK('" . $lockToken . "')");
        }
    }
} elseif ($consumed <= 0) {
    echo "Failed: nothing to send";
} else {
    echo "Failed: Insufficient Balance";
}

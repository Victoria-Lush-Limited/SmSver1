<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
} else {
    $q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
    $found = mysqli_num_rows($q);
    if ($found) {
        $user = mysqli_fetch_assoc($q);
        if ($user['status'] == "Pending") {
            header("location:verification.php");
        }
    } else {
        header("location:signout.php");
    }
}

include 'simple_xlsx.php';
include_once __DIR__ . '/phone_lib.php';

$uid_del = mysqli_real_escape_string($conn, (string) $_SESSION['user_id']);
mysqli_query($conn, "DELETE FROM custom_sms WHERE user_id='" . $uid_del . "'");

$sender_raw = isset($_POST['sender_id']) ? trim((string) $_POST['sender_id']) : '';
$sender_raw = vll_normalize_outgoing_sender_id($sender_raw);
$sender_id = mysqli_real_escape_string($conn, $sender_raw);
$template_raw = isset($_POST['message']) ? (string) $_POST['message'] : '';

$date_created = time();
$user_id = $user['user_id'];


$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["file_name"]["name"]);
$file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check file size
if ($_FILES["file_name"]["size"] < 2000000) {

    // Allow certain file formats
    if ($file_type == "xls" || $file_type == "xlsx") {
        if (move_uploaded_file($_FILES["file_name"]["tmp_name"], $target_file)) {
            $xlsx = new SimpleXLSX($target_file);

            $columns = array();
            $headers = $xlsx->rows();
            if (!isset($headers[0]) || !is_array($headers[0]) || count($headers[0]) < 1) {
                header("location:file_sms.php?r=" . urlencode("The spreadsheet has no usable header row."));
                exit;
            }

            for ($i = 0; $i < count($headers[0]); $i++) {
                $stub = "{" . $headers[0][$i] . "}";
                array_push($columns, $stub);
            }

            $schedule_raw = isset($_POST['schedule']) ? (string) $_POST['schedule'] : 'None';
            $sched_esc = mysqli_real_escape_string($conn, $schedule_raw);
            $start_date = strtotime(mysqli_real_escape_string($conn, isset($_POST['start_date']) ? (string) $_POST['start_date'] : ''));
            $end_date = strtotime(mysqli_real_escape_string($conn, isset($_POST['end_date']) ? (string) $_POST['end_date'] : ''));
            $h = isset($_POST['send_hour']) ? max(0, min(23, (int) $_POST['send_hour'])) : 0;
            $m = isset($_POST['send_minute']) ? max(0, min(59, (int) $_POST['send_minute'])) : 0;
            $uid_ins = mysqli_real_escape_string($conn, (string) $_SESSION['user_id']);

            $rows = 0;
            foreach ($xlsx->rows() as $fields) {
                if ($rows > 0) {
                    $message = $template_raw;
                    for ($i = 0; $i < count($columns); $i++) {
                        $cell = isset($fields[$i]) ? (string) $fields[$i] : '';
                        $message = str_replace($columns[$i], $cell, $message);
                    }

                    $phone_raw = isset($fields[0]) ? preg_replace('/\s+/', '', (string) $fields[0]) : '';
                    $phone_norm = normalize_recipient_msisdn($phone_raw);
                    if ($phone_norm === '' || !preg_match('/^(255|254|256)/', $phone_norm)) {
                        $rows += 1;
                        continue;
                    }
                    $phone_esc = mysqli_real_escape_string($conn, $phone_norm);
                    $msg_esc = mysqli_real_escape_string($conn, $message);
                    $credits = (int) ceil(strlen($message) / 160);
                    if ($credits < 1) {
                        $credits = 1;
                    }

                    $now = time();
                    $attempts = 0;
                    $sms_status_esc = mysqli_real_escape_string($conn, 'Pending');

                    if ($msg_esc !== '' && $phone_esc !== '') {
                        switch ($schedule_raw) {
                            case "None":
                                $date_created = strtotime(date("d-m-Y", $start_date) . " " . $h . ":" . $m);
                                mysqli_query($conn, "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_esc . "','" . $sender_id . "','" . $msg_esc . "','" . $credits . "','" . $sched_esc . "','" . (int) $start_date . "','" . (int) $end_date . "','" . (int) $date_created . "','" . (int) $attempts . "','" . $sms_status_esc . "','" . $uid_ins . "')");
                                break;

                            case "Daily":
                                $next_date = $start_date;
                                while ($next_date <= $end_date) {
                                    $date_created = strtotime(date("d-m-Y", $next_date) . " " . $h . ":" . $m);
                                    mysqli_query($conn, "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_esc . "','" . $sender_id . "','" . $msg_esc . "','" . $credits . "','" . $sched_esc . "','" . (int) $start_date . "','" . (int) $end_date . "','" . (int) $date_created . "','" . (int) $attempts . "','" . $sms_status_esc . "','" . $uid_ins . "')");
                                    $next_date = strtotime("+1 days", $next_date);
                                }
                                break;

                            case "Weekly":
                                $next_date = $start_date;
                                while ($next_date <= $end_date) {
                                    $date_created = strtotime(date("d-m-Y", $next_date) . " " . $h . ":" . $m);
                                    mysqli_query($conn, "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_esc . "','" . $sender_id . "','" . $msg_esc . "','" . $credits . "','" . $sched_esc . "','" . (int) $start_date . "','" . (int) $end_date . "','" . (int) $date_created . "','" . (int) $attempts . "','" . $sms_status_esc . "','" . $uid_ins . "')");
                                    $next_date = strtotime("+1 weeks", $next_date);
                                }
                                break;

                            case "Monthly":
                                $next_date = $start_date;
                                while ($next_date <= $end_date) {
                                    $date_created = strtotime(date("d-m-Y", $next_date) . " " . $h . ":" . $m);
                                    mysqli_query($conn, "INSERT INTO custom_sms(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_esc . "','" . $sender_id . "','" . $msg_esc . "','" . $credits . "','" . $sched_esc . "','" . (int) $start_date . "','" . (int) $end_date . "','" . (int) $date_created . "','" . (int) $attempts . "','" . $sms_status_esc . "','" . $uid_ins . "')");
                                    $next_date = strtotime("+1 months", $next_date);
                                }
                                break;
                        }
                    }
                }
                $rows += 1;
            }
            header("location:preview.php");
        } else {
            header("location:file_sms.php?r=operation failed, please try again later");
        }
    } else {
        header("location:file_sms.php?r=Invalid file type");
    }
} else {
    header("location:file_sms.php?r=File size is too large");
}

<?php
include "db/dblink.php";


$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND status='Active'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

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
        $q = mysqli_query($conn, "SELECT C.phone_number FROM group_contacts G,contacts C WHERE G.contact_id=C.contact_id AND G.group_id='" . $group_id . "'");
        while ($group_contact = mysqli_fetch_assoc($q)) {
            $recipients .= $group_contact['phone_number'] . ",";
        }
    }
}

$recipient_list = explode(",", $recipients);
$total_recipients = count($recipient_list) - 1;


$message = mysqli_real_escape_string($conn, $_POST['message']);
$credits = ceil(strlen($message) / 160);;

$sender_id = mysqli_real_escape_string($conn, $_POST['sender_id']);

$user_id = $user['user_id'];
$sms_status = "Pending";


$schedule = mysqli_real_escape_string($conn, $_POST['schedule']);

$start_date = strtotime(mysqli_real_escape_string($conn, $_POST['start_date']));
$end_date = strtotime(mysqli_real_escape_string($conn, $_POST['end_date']));

$send_hour = mysqli_real_escape_string($conn, $_POST['send_hour']);
$send_minute = mysqli_real_escape_string($conn, $_POST['send_minute']);


$now = time();

switch ($schedule) {
    case "None":

        $consumed = $credits * $total_recipients;

        $date_created = strtotime(date("d-m-Y", $start_date) . " " . $send_hour . ":" . $send_minute);

        $q = mysqli_query($conn, "SELECT (SUM(allocated)-SUM(consumed)) AS balance FROM transactions WHERE user_id='" . $_SESSION['user_id'] . "'");
        $bal = mysqli_fetch_assoc($q);
        $balance = $bal['balance'];
        if ($consumed <= $balance) {
            for ($i = 0; $i < (count($recipient_list) - 1); $i++) {
                $phone_number = $recipient_list[$i];
                $attempts = 0;

                $q = mysqli_query($conn, "INSERT INTO outgoing(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "')");
            }

            $q = mysqli_query($conn, "INSERT INTO transactions(user_id,consumed,tdate) VALUES('" . $user_id . "','" . $consumed . "','" . $now . "')");
            header("location:compose.php?r=Sent");
        } else {
            header("location:compose.php?r=Insufficient Balance");
        }

        break;

    case "Daily":

        $consumed = 0;

        $next_date = $start_date;
        while ($next_date <= $end_date) {
            $consumed += ($credits * $total_recipients);
            $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
            $next_date =  strtotime("+1 days", $next_date);
        }
        $q = mysqli_query($conn, "SELECT (SUM(allocated)-SUM(consumed)) AS balance FROM transactions WHERE user_id='" . $_SESSION['user_id'] . "'");
        $bal = mysqli_fetch_assoc($q);
        $balance = $bal['balance'];
        if ($consumed <= $balance) {
            $next_date = $start_date;
            while ($next_date <= $end_date) {
                $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
           
                for ($i = 0; $i < (count($recipient_list) - 1); $i++) {
                    $phone_number = $recipient_list[$i];
                    $attempts = 0;

                    $q = mysqli_query($conn, "INSERT INTO outgoing(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "')");
                }
                $next_date =  strtotime("+1 days", $next_date);
            }
            $q = mysqli_query($conn, "INSERT INTO transactions(user_id,consumed,tdate) VALUES('" . $user_id . "','" . $consumed . "','" . $now . "')");
            header("location:compose.php?r=Sent");
        } else {
            header("location:compose.php?r=Insufficient Balance");
        }
        break;


    case "Weekly":

        $consumed = 0;

        $next_date = $start_date;
        while ($next_date <= $end_date) {
            $consumed += ($credits * $total_recipients);
            $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
            $next_date =  strtotime("+1 weeks", $next_date);
        }
        $q = mysqli_query($conn, "SELECT (SUM(allocated)-SUM(consumed)) AS balance FROM transactions WHERE user_id='" . $_SESSION['user_id'] . "'");
        $bal = mysqli_fetch_assoc($q);
        $balance = $bal['balance'];
        if ($consumed <= $balance) {
            $next_date = $start_date;
            while ($next_date <= $end_date) {
                $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
           
                for ($i = 0; $i < (count($recipient_list) - 1); $i++) {
                    $phone_number = $recipient_list[$i];
                    $attempts = 0;

                    $q = mysqli_query($conn, "INSERT INTO outgoing(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "')");
                }
                $next_date =  strtotime("+1 weeks", $next_date);
            }
            $q = mysqli_query($conn, "INSERT INTO transactions(user_id,consumed,tdate) VALUES('" . $user_id . "','" . $consumed . "','" . $now . "')");
            header("location:compose.php?r=Sent");
        } else {
            header("location:compose.php?r=Insufficient Balance");
        }

        break;

    case "Monthly":

        $consumed = 0;

        $next_date = $start_date;
        while ($next_date <= $end_date) {
            $consumed += ($credits * $total_recipients);
            $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
            $next_date =  strtotime("+1 months", $next_date);
        }
        $q = mysqli_query($conn, "SELECT (SUM(allocated)-SUM(consumed)) AS balance FROM transactions WHERE user_id='" . $_SESSION['user_id'] . "'");
        $bal = mysqli_fetch_assoc($q);
        $balance = $bal['balance'];
        if ($consumed <= $balance) {
            $next_date = $start_date;
            while ($next_date <= $end_date) {
                $date_created = strtotime(date("d-m-Y", $next_date) . " " . $send_hour . ":" . $send_minute);
           
                for ($i = 0; $i < (count($recipient_list) - 1); $i++) {
                    $phone_number = $recipient_list[$i];
                    $attempts = 0;

                    $q = mysqli_query($conn, "INSERT INTO outgoing(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "')");
                }
                $next_date =  strtotime("+1 months", $next_date);
            }
            $q = mysqli_query($conn, "INSERT INTO transactions(user_id,consumed,tdate) VALUES('" . $user_id . "','" . $consumed . "','" . $now . "')");
            header("location:compose.php?r=Sent");
        } else {
            header("location:compose.php?r=Insufficient Balance");
        }
        break;
}

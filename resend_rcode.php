<?php

include "db/dblink.php";

$user_id = $_SESSION['temp_user_id'];

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $user_id . "'");
$found = mysqli_num_rows($q);

if ($found) {
    $user = mysqli_fetch_assoc($q);

    $rcode = $user['rcode'];
    $sms_status = "Pending";
    $sender_id = $app['sender_id'];
    $phone_number = $user['user_id'];
    $message = "Your account reset code is: " . $rcode;
    $credits = ceil(strlen($message / 160));
    $schedule = "None";
    $start_date = time();
    $end_date = $start_date;
    $date_created = $start_date;
    $attempts = 0;
    $sms_status = "Pending";
    $user_id = "Administrator";
    $username = "Administrator";
    $smsc_id = "";

    $q = mysqli_query($conn, "INSERT INTO outgoing(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id,username,smsc_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "','" . $username . "','" . $smsc_id . "')");
    header("location:recover.php");
} else {
    header("location:signout.php");
}
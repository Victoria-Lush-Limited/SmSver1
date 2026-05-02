<?php

include "db/dblink.php";

$username = mysqli_real_escape_string($conn, $_POST['username']);
$rcode = mt_rand(100000, 999999);

$q = mysqli_query($conn, "SELECT * FROM users WHERE username='" . $username . "'");
$found = mysqli_num_rows($q);


if ($found) {
    $u = mysqli_query($conn, "UPDATE users SET rcode='" . $rcode . "' WHERE username='" . $username . "'");
    $q = mysqli_query($conn, "SELECT * FROM users WHERE username='" . $username . "'");
    $user = mysqli_fetch_assoc($q);
    
    $_SESSION['temp_user_id']=$user['user_id'];
    $rcode = $user['rcode'];
    $sms_status = "Pending";
    $sender_id = $app['sender_id'];
    $phone_number = $user['user_id'];
    $message = "Your account reset code is: " . $rcode;
    $credits = ceil(strlen($message) / 160);
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
    header("location:forgot.php?r=User account not found");
}

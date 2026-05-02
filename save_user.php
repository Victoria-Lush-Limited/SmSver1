<?php

include "db/dblink.php";

$user_id = mysqli_real_escape_string($conn, $_POST['phone_number']);
$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = md5(mysqli_real_escape_string($conn, $_POST['password']));
$client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$contact_phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
$status = "Pending";
$vcode = mt_rand(100000, 999999);
$rcode = "";
$user_date_created = time();
$reseller_id = "Administrator";
$scheme_id = $app['default_scheme_id'];

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $user_id . "'");
$mobile_registered = mysqli_num_rows($q);

if ($mobile_registered) {
    header("location:register.php?r=Mobile Number already registered");
}

$q = mysqli_query($conn, "SELECT * FROM users WHERE username='" . $username . "'");
$username_registered = mysqli_num_rows($q);

if ($username_registered) {
    header("location:register.php?r=Username already registered");
}


if (!$mobile_registered && !$username_registered) {
    $i = mysqli_query($conn, "INSERT INTO users(user_id,password,client_name,username,email,contact_phone,status,vcode,rcode,user_date_created,reseller_id,scheme_id) VALUES('" . $user_id . "','" . $password . "','" . $client_name . "','" . $username . "','" . $email . "','" . $contact_phone . "','" . $status . "','" . $vcode . "','" . $rcode . "','" . $user_date_created . "','" . $reseller_id . "','" . $scheme_id . "')");
    if ($i) {
        $_SESSION['user_id'] = $user_id;
        $sms_status = "Pending";
        $sender_id = $app['sender_id'];
        $phone_number = $user_id;
        $message = "Your verification code is: " . $vcode;
        $credits = ceil(strlen($message) / 160);
        $schedule = "None";
        $start_date = $user_date_created;
        $end_date = $start_date;
        $date_created = $user_date_created;
        $attempts = 0;
        $sms_status = "Pending";
        $user_id = "Administrator";
        $username="Administrator";
        $smsc_id = "";
        $q = mysqli_query($conn, "INSERT INTO outgoing(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id,username,smsc_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "','" . $username . "','" . $smsc_id . "')");

        header("location:verification.php");
    } else {
        header("location:register.php?r=Something went wrong, please try again later");
    }
}

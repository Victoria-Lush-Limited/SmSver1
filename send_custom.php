<?php
include "db/dblink.php";


$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

$q = mysqli_query($conn, "SELECT SUM(credits) AS credits FROM custom_sms WHERE user_id='" . $_SESSION['user_id'] . "'");
$required = mysqli_fetch_assoc($q);
$consumed=$required['credits'];

$q = mysqli_query($conn, "SELECT (SUM(allocated)-SUM(consumed)) AS balance FROM transactions WHERE user_id='" . $_SESSION['user_id'] . "'");
$bal = mysqli_fetch_assoc($q);
$balance = $bal['balance'];



if ($consumed <= $balance) {
    $now=time();
    $qc=mysqli_query($conn,"SELECT * FROM custom_sms WHERE user_id='".$_SESSION['user_id']."'");
    while($custom=mysqli_fetch_assoc($qc)){
        $phone_number= str_replace(" ","",$custom['phone_number']);
        $sender_id=$custom['sender_id'];
        $message=mysqli_real_escape_string($conn,$custom['message']);
        $credits=$custom['credits'];
        $schedule=$custom['schedule'];
        $start_date=$custom['start_date'];
        $end_date=$custom['end_date'];
        $date_created=$custom['date_created'];
        $attempts=$custom['attempts'];
        $sms_status=$custom['sms_status'];
        $user_id=$custom['user_id'];
        $smsc_id="";
        
        
        $q = mysqli_query($conn, "INSERT INTO outgoing(phone_number,sender_id,message,credits,schedule,start_date,end_date,date_created,attempts,sms_status,user_id,smsc_id) VALUES('" . $phone_number . "','" . $sender_id . "','" . $message . "','" . $credits . "','" . $schedule . "','" . $start_date . "','" . $end_date . "','" . $date_created . "','" . $attempts . "','" . $sms_status . "','" . $user_id . "','".$smsc_id."')");
        
    }
    $q = mysqli_query($conn, "INSERT INTO transactions(user_id,consumed,tdate) VALUES('" . $user['user_id'] . "','" . $consumed . "','" . $now . "')");
    $q=mysqli_query($conn,"DELETE FROM custom_sms WHERE user_id='".$_SESSION['user_id']."'");

    echo "Sent";
} else {
    echo "Failed: Insufficient Balance";
}

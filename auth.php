<?php

include "db/dblink.php";

$user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
$password = md5(mysqli_real_escape_string($conn, $_POST['password']));

$q = mysqli_query($conn, "SELECT * FROM users WHERE (user_id='" . $user_id . "' || username='" . $user_id . "') AND password='" . $password . "'");
$found = mysqli_num_rows($q);

if ($found) {
    $user = mysqli_fetch_assoc($q);
    if($user['status']=="Suspended"){
        header("location:login.php?r=User Account Suspended");    
    }else{
    $_SESSION['user_id'] = $user['user_id'];
    header("location:index.php");
    }
} else {
    header("location:login.php?r=Invalid Mobile Number or Password");
}

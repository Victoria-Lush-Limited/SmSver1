<?php

include "db/dblink.php";

$user_id = $_SESSION['temp_user_id'];
$rcode = mysqli_real_escape_string($conn, $_POST['rcode']);

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $user_id . "' AND rcode='" . $rcode . "'");
$found = mysqli_num_rows($q);

if ($found) {
    $user = mysqli_fetch_assoc($q);
    $_SESSION['temp_user_id']=$user['user_id'];
    header("location:new_password.php");
} else {
    header("location:recover.php?r=Invalid Reset Code");
}

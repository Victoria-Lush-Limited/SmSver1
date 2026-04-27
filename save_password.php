<?php

include "db/dblink.php";

$user_id = $_SESSION['temp_user_id'];
$password = md5(mysqli_real_escape_string($conn, $_POST['new_password']));

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $user_id . "'");
$found = mysqli_num_rows($q);

if ($found) {
    $u = mysqli_query($conn, "UPDATE users SET password='" . $password . "',rcode='' WHERE user_id='" . $user_id . "'");
    $_SESSION['user_id'] = $user_id;
    header("location:index.php");
} else {
    header("location:signout.php");
}

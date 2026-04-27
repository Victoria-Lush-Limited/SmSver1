<?php

include "db/dblink.php";

$user_id = $_SESSION['user_id'];
$vcode = mysqli_real_escape_string($conn, $_POST['vcode']);

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $user_id . "' AND vcode='" . $vcode . "'");
$found = mysqli_num_rows($q);

if ($found) {
    $user = mysqli_fetch_assoc($q);
    $u = mysqli_query($conn, "UPDATE users SET status='Active' WHERE user_id='".$user_id."' AND vcode='".$vcode."'");
    header("location:index.php");
} else {
    header("location:verification.php?r=Invalid Verification Code");
}

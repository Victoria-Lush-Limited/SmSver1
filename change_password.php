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

$current_password = md5(mysqli_real_escape_string($conn, $_POST['current_password']));
$new_password = mysqli_real_escape_string($conn, $_POST['new_password']);



$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND password='" . $current_password . "'");
$found = mysqli_num_rows($q);
if ($found) {
    if (strlen($new_password) >= 6) {
        $new_password = md5($new_password);
        $u = mysqli_query($conn, "UPDATE users SET password='" . $new_password . "' WHERE user_id='" . $_SESSION['user_id'] . "'");
        header("location:account.php?r=Password changed");
    } else {
        header("location:account.php?r=Invalid password");
    }
} else {
    header("location:account.php?r=Incorrect password");
}

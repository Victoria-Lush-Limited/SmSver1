<?php
include  "db/dblink.php";

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

$group_name = mysqli_real_escape_string($conn, $_GET['group_name']);
$date_created = time();

if (empty(trim($group_name))) {
    echo "Empty";
    exit;
}

$q = mysqli_query($conn, "SELECT * FROM groups WHERE group_name='" . $group_name . "' AND user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    $q = mysqli_query($conn, "INSERT INTO groups(group_name,user_id,date_created) VALUES('" . $group_name . "','" . $_SESSION['user_id'] . "','" . $date_created . "')");
    if ($q) {
        echo "Saved";
    } else {
        echo "Error";
    }
} else {
    echo "Duplicate";
}

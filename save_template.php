<?php
include  "db/dblink.php";

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

$title = mysqli_real_escape_string($conn, $_GET['title']);
$message = mysqli_real_escape_string($conn, $_GET['message']);

$q = mysqli_query($conn, "SELECT * FROM templates WHERE title='".$title."' AND user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);

if (!$found) {
    $q = mysqli_query($conn, "INSERT INTO templates(title,message,user_id) VALUES('" . $title . "','" . $message . "','" . $_SESSION['user_id'] . "')");
    echo "Saved";
}else{
    echo "Duplicate";
}
?>
<?php
include  "db/dblink.php";

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

$sender_id = mysqli_real_escape_string($conn, $_GET['sender_id']);
$message = mysqli_real_escape_string($conn, $_GET['message']);

$id_type="Private";

$date_requested = time();
$id_status="Pending";

$q = mysqli_query($conn, "SELECT * FROM senders WHERE (sender_id='" . $sender_id . "' AND user_id='" . $_SESSION['user_id'] . "') || (sender_id='".$sender_id."' AND id_type='Public')");
$found = mysqli_num_rows($q);

if (!$found) {
    $q = mysqli_query($conn, "INSERT INTO senders(sender_id,message,id_type,user_id,date_requested,id_status) VALUES('" . $sender_id . "','" . $message . "','" . $id_type . "','" . $_SESSION['user_id'] . "','" . $date_requested . "','".$id_status."')");
    echo "Saved";
}else{
    echo "Duplicate";
}
?>
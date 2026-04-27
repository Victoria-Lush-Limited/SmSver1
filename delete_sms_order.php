<?php
include "db/dblink.php";

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);


$order_id = mysqli_real_escape_string($conn, $_GET['order_id']);
$q = mysqli_query($conn, "DELETE FROM sms_orders WHERE order_id='" . $order_id . "' AND order_status='Pending' AND user_id='".$_SESSION['user_id']."'");

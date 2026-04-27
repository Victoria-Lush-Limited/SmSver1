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

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND status='Active'");
$user = mysqli_fetch_assoc($q);

$amount = 0;
$price = 0;
$quantity = mysqli_real_escape_string($conn, $_GET['quantity']);

$q = mysqli_query($conn, "SELECT * FROM pricing WHERE min_sms<='" . $quantity . "' AND max_sms>='" . $quantity . "' AND scheme_id='" . $user['scheme_id'] . "'");
$found = mysqli_num_rows($q);
if ($found) {
    $pricing = mysqli_fetch_assoc($q);
    $price = $pricing['price'];
    $amount = $price * $quantity;
} else {
    $q = mysqli_query($conn, "SELECT * FROM pricing WHERE min_sms<='" . $quantity . "' AND max_sms='0' AND scheme_id='" . $user['scheme_id'] . "'");
    $pricing = mysqli_fetch_assoc($q);
    $price = $pricing['price'];
    $amount = $price * $quantity;
}

$order_date = time();
$status = "Pending";
$account_type="Broadcaster";

$q = mysqli_query($conn,"INSERT INTO sms_orders(user_id,account_type,quantity,price,amount,order_date,order_status) VALUES('" . $user['user_id'] . "','" . $account_type. "','" . $quantity . "','" . $price . "','" . $amount . "','" . $order_date . "','" . $status . "')");
if ($q) {
    $t = mysqli_query($conn, "SELECT * FROM sms_orders WHERE user_id='" . $user['user_id'] . "' AND order_date='" . $order_date . "'");
    $order = mysqli_fetch_assoc($t);
    $reference = "VLLSMS-" . $order['order_id'];
    $u = mysqli_query($conn, "UPDATE sms_orders SET reference='" . $reference . "' WHERE order_id='" . $order['order_id'] . "'");
    header("location:pay.php?order_id=" . $order['order_id']);
}
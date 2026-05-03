<?php
include "db/dblink.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_id"] === "") {
    header("location:login.php");
    exit;
}

$uid = mysqli_real_escape_string($conn, $_SESSION["user_id"]);
$q = mysqli_query($conn, "SELECT user_id, status FROM users WHERE user_id='" . $uid . "' LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    header("location:signout.php");
    exit;
}
$row = mysqli_fetch_assoc($q);
if ($row["status"] !== "Active") {
    header("location:account.php?r=key_inactive");
    exit;
}

$key = bin2hex(random_bytes(20));
$keyEsc = mysqli_real_escape_string($conn, $key);
$up = mysqli_query($conn, "UPDATE users SET api_key='" . $keyEsc . "' WHERE user_id='" . $uid . "' LIMIT 1");
if (!$up) {
    header("location:account.php?r=key_err");
    exit;
}

header("location:account.php?r=key_ok");
exit;

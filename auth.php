<?php

include "db/dblink.php";

if (!$conn) {
    header("location:login.php?r=Login temporarily unavailable");
    exit;
}

$user_raw = isset($_POST['user_id']) ? trim((string) $_POST['user_id']) : '';
$pass_raw = isset($_POST['password']) ? (string) $_POST['password'] : '';
$pass_trim = trim($pass_raw);

if ($user_raw === '' || $pass_trim === '') {
    header("location:login.php?r=Invalid Username or Password");
    exit;
}

// Support legacy hash behaviour and normal md5(trim(password)).
$hashes = array(
    md5($pass_trim),
    md5(mysqli_real_escape_string($conn, $pass_trim)),
);
$hashes = array_values(array_unique($hashes));

$user = null;
$user_esc = mysqli_real_escape_string($conn, $user_raw);
$h1 = mysqli_real_escape_string($conn, $hashes[0]);
$h2 = mysqli_real_escape_string($conn, isset($hashes[1]) ? $hashes[1] : $hashes[0]);

$sql = "SELECT * FROM users WHERE (user_id='" . $user_esc . "' OR username='" . $user_esc . "' OR LOWER(TRIM(COALESCE(email,'')))=LOWER('" . $user_esc . "')) AND password IN ('" . $h1 . "','" . $h2 . "') LIMIT 1";
$q = mysqli_query($conn, $sql);
if ($q) {
    $user = mysqli_fetch_assoc($q);
}

if ($user) {
    if ($user['status'] == "Suspended") {
        header("location:login.php?r=User Account Suspended");
    } elseif ($user['status'] == "Pending") {
        $_SESSION['user_id'] = $user['user_id'];
        header("location:verification.php");
    } elseif ($user['status'] == "Active") {
        $_SESSION['user_id'] = $user['user_id'];
        header("location:index.php");
    } else {
        header("location:login.php?r=User Account Inactive");
    }
} else {
    header("location:login.php?r=Invalid Username or Password");
}
exit;

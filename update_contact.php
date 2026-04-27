<?php
include  "db/dblink.php";

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

$contact_id = mysqli_real_escape_string($conn, $_GET['contact_id']);

$phone_number = mysqli_real_escape_string($conn, $_GET['phone_number']);
$contact_name = mysqli_real_escape_string($conn, $_GET['contact_name']);
$email = mysqli_real_escape_string($conn, $_GET['email']);

$group_id = mysqli_real_escape_string($conn, $_GET['group_id']);

$q = mysqli_query($conn, "SELECT * FROM contacts WHERE phone_number='".$phone_number."' AND contact_id!='" . $contact_id . "' AND user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);

if (!$found) {
    $u = mysqli_query($conn, "UPDATE contacts SET phone_number='".$phone_number."',contact_name='".$contact_name."',email='".$email."' WHERE contact_id='".$contact_id."' AND user_id='".$_SESSION['user_id']."'");
    echo "Updated";
}else{
    echo "Duplicate";
}

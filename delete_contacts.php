<?php
include "db/dblink.php";

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

$contact_id=mysqli_real_escape_string($conn,$_GET['contact_id']);
$group_id=mysqli_real_escape_string($conn,$_GET['group_id']);

if(!empty($group_id)){
    $q=mysqli_query($conn,"DELETE FROM group_contacts WHERE contact_id='".$contact_id."' AND group_id='".$group_id."'");
}else{
    $q=mysqli_query($conn,"DELETE FROM contacts WHERE contact_id='".$contact_id."' AND user_id='".$_SESSION['user_id']."'");
}

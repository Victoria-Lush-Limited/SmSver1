<?php
include  "db/dblink.php";

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

$template_id = mysqli_real_escape_string($conn, $_GET['template_id']);

$title = mysqli_real_escape_string($conn, $_GET['title']);
$message = mysqli_real_escape_string($conn, $_GET['message']);

$q = mysqli_query($conn, "SELECT * FROM templates WHERE title='".$title."' AND template_id!='" . $template_id . "' AND user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);

if (!$found) {
    $u = mysqli_query($conn, "UPDATE templates SET title='".$title."',message='".$message."' WHERE template_id='".$template_id."' AND user_id='".$_SESSION['user_id']."'");
    echo "Updated";
}else{
    echo "Duplicate";
}

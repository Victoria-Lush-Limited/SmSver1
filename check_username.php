<?php

include "db/dblink.php";

$username = mysqli_real_escape_string($conn, $_GET['username']);

$q = mysqli_query($conn, "SELECT * FROM users WHERE username='".$username."'");
$found=mysqli_num_rows($q);
if($found){
    echo "Taken";
}else{
    echo "Available";
}
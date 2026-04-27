<?php

session_start();
error_reporting(0);
date_default_timezone_set("Africa/Nairobi");
$dbhost = "127.0.0.1";
$dbname = "anderson_vllsms";
$dbuser = "root";
$dbpass = "";

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$q = mysqli_query($conn, "SELECT * FROM app");
$app = mysqli_fetch_assoc($q);

<?php

session_start();
error_reporting(0);
date_default_timezone_set("Africa/Nairobi");
mysqli_report(MYSQLI_REPORT_OFF);

function vll_env($name, $default = "")
{
    $v = getenv($name);
    if ($v !== false && $v !== null && $v !== "") {
        return $v;
    }
    if (isset($_SERVER[$name]) && $_SERVER[$name] !== "") {
        return (string) $_SERVER[$name];
    }
    if (isset($_ENV[$name]) && $_ENV[$name] !== "") {
        return (string) $_ENV[$name];
    }
    return $default;
}

$dbhost = vll_env("VLL_DB_HOST", "127.0.0.1");
$dbname = vll_env("VLL_DB_NAME", "anderson_vllsms");
$dbuser = vll_env("VLL_DB_USER", "vll_sms_user");
$dbpass = vll_env("VLL_DB_PASS", "StrongPass123!");

$conn = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if ($conn) {
    @mysqli_set_charset($conn, "utf8mb4");
}

$app = array("app_name" => "Victoria Lush SMS");
if ($conn) {
    $q = mysqli_query($conn, "SELECT * FROM app LIMIT 1");
    if ($q) {
        $row = mysqli_fetch_assoc($q);
        if ($row) {
            $app = $row;
        }
    }
}

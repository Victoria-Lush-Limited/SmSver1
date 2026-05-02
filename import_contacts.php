<?php
include "db/dblink.php";
include "phone_lib.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
    exit;
}

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND status='Active'");
if (!mysqli_num_rows($q)) {
    header("location:signout.php");
    exit;
}

$import_region = isset($_POST['import_region']) ? $_POST['import_region'] : 'TZ';

function normalize_phone($phone, $region)
{
    return normalize_contact_phone($phone, $region);
}

if (!isset($_FILES['contacts_file']) || $_FILES['contacts_file']['error'] !== UPLOAD_ERR_OK) {
    header("location:contacts.php?r=Import failed: file upload error");
    exit;
}

$target_dir = "uploads/";
if (!is_dir($target_dir)) {
    @mkdir($target_dir, 0755, true);
}

$original_name = basename($_FILES['contacts_file']['name']);
$target_file = $target_dir . time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
$file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

if (!in_array($file_type, array("csv", "xlsx", "xls"))) {
    header("location:contacts.php?r=Import failed: invalid file type");
    exit;
}

if (!move_uploaded_file($_FILES["contacts_file"]["tmp_name"], $target_file)) {
    header("location:contacts.php?r=Import failed: cannot save uploaded file");
    exit;
}

$rows = array();
if ($file_type == "csv") {
    if (($handle = fopen($target_file, "r")) !== false) {
        while (($data = fgetcsv($handle, 10000, ",")) !== false) {
            $rows[] = $data;
        }
        fclose($handle);
    }
} else {
    include 'simple_xlsx.php';
    $xlsx = new SimpleXLSX($target_file);
    $rows = $xlsx->rows();
}

$created = 0;
$duplicates = 0;
$invalid = 0;

for ($i = 0; $i < count($rows); $i++) {
    $fields = $rows[$i];
    $phone_raw = isset($fields[0]) ? $fields[0] : "";
    $name = isset($fields[1]) ? trim($fields[1]) : "";
    $email = isset($fields[2]) ? trim($fields[2]) : "";
    $phone = normalize_phone($phone_raw, $import_region);

    if ($i == 0 && (stripos($phone_raw, "phone") !== false || stripos($name, "name") !== false)) {
        continue;
    }

    if (!is_valid_contact_msisdn($phone, $import_region)) {
        $invalid++;
        continue;
    }

    $phone = mysqli_real_escape_string($conn, $phone);
    $name = mysqli_real_escape_string($conn, $name);
    $email = mysqli_real_escape_string($conn, $email);

    $q = mysqli_query($conn, "SELECT contact_id FROM contacts WHERE user_id='" . $_SESSION['user_id'] . "' AND phone_number='" . $phone . "' LIMIT 1");
    if (mysqli_num_rows($q)) {
        $duplicates++;
        continue;
    }

    $date_created = time();
    $ok = mysqli_query($conn, "INSERT INTO contacts(phone_number,contact_name,email,user_id,date_created) VALUES('" . $phone . "','" . $name . "','" . $email . "','" . $_SESSION['user_id'] . "','" . $date_created . "')");
    if ($ok) {
        $created++;
    } else {
        $invalid++;
    }
}

@unlink($target_file);
header("location:contacts.php?r=Imported " . $created . " contact(s), duplicates " . $duplicates . ", invalid " . $invalid);
exit;
?>

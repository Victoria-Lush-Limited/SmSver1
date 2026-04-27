<?php
include  "db/dblink.php";

$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
}

$user = mysqli_fetch_assoc($q);

$phone_number = mysqli_real_escape_string($conn, $_GET['phone_number']);
$contact_name = mysqli_real_escape_string($conn, $_GET['contact_name']);
$email = mysqli_real_escape_string($conn, $_GET['email']);

$group_id = mysqli_real_escape_string($conn, $_GET['group_id']);
$date_created = time();

$q = mysqli_query($conn, "SELECT * FROM contacts WHERE phone_number='" . $phone_number . "' AND user_id='" . $_SESSION['user_id'] . "'");
$found = mysqli_num_rows($q);

if (!$found) {
    $q = mysqli_query($conn, "INSERT INTO contacts(phone_number,contact_name,email,user_id,date_created) VALUES('" . $phone_number . "','" . $contact_name . "','" . $email . "','" . $_SESSION['user_id'] . "','" . $date_created . "')");
    if (!empty($group_id)) {
        $q = mysqli_query($conn, "SELECT * FROM contacts WHERE phone_number='" . $phone_number . "' AND user_id='" . $_SESSION['user_id'] . "'");
        $contact = mysqli_fetch_assoc($q);

        $contact_id = $contact['contact_id'];
        $q = mysqli_query($conn, "SELECT * FROM group_contacts WHERE group_id='" . $group_id . "' AND contact_id='" . $contact_id . "'");
        $found = mysqli_num_rows($q);
        if (!$found) {
            $q = mysqli_query($conn, "INSERT INTO group_contacts(group_id,contact_id) VALUES('" . $group_id . "','" . $contact_id . "')");
        }
    }
    echo "Saved";
}else{
    echo "Duplicate";
}

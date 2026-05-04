<?php
include "db/dblink.php";

$items_raw = isset($_GET["items"]) ? (string) $_GET["items"] : "";
$parts = explode(",", $items_raw);
$uid = isset($_SESSION['user_id']) ? mysqli_real_escape_string($conn, (string) $_SESSION['user_id']) : '';

$recipient_list = "";
if ($uid === '' || !$conn) {
    echo $recipient_list;
    return;
}

foreach ($parts as $raw) {
    $phone_number = trim($raw);
    if ($phone_number === '') {
        continue;
    }
    $pn_esc = mysqli_real_escape_string($conn, $phone_number);

    $q = mysqli_query($conn, "SELECT * FROM contacts WHERE phone_number='" . $pn_esc . "' AND user_id='" . $uid . "'");
    $found = $q ? mysqli_num_rows($q) : 0;
    if ($found) {
        $contact = mysqli_fetch_assoc($q);
        $name_disp = htmlspecialchars((string) $contact['contact_name'], ENT_QUOTES, 'UTF-8');
        $num_disp = htmlspecialchars($phone_number, ENT_QUOTES, 'UTF-8');
        $contact_name = "<span>" . $name_disp . "</span> (" . $num_disp . ")";
    } else {
        $contact_name = htmlspecialchars($phone_number, ENT_QUOTES, 'UTF-8');
    }
    $recipient_list .= "<div class='parsed-contact'>" . $contact_name . "<i class=\"fas fa-times fa-x\" onclick=\"remove_recipient_contact(this,'" . addslashes($phone_number) . "')\"></i></div>";
}
echo $recipient_list;

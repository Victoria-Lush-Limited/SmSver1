<?php include "db/dblink.php";

$items_raw = isset($_GET["items"]) ? (string) $_GET["items"] : "";
$contacts = explode(",", mysqli_real_escape_string($conn, $items_raw));

$recipient_list = "";
for ($i = 0; $i < count($contacts) - 1; $i++) {
    $phone_number = $contacts[$i];

    $q = mysqli_query($conn, "SELECT * FROM contacts WHERE phone_number='" . $phone_number . "' AND user_id='" . $_SESSION['user_id'] . "'");
    $found = mysqli_num_rows($q);
    if ($found) {
        $contact = mysqli_fetch_assoc($q);
        $contact_name = "<span>" . $contact['contact_name'] . "</span> (" . $phone_number . ")";
    } else {
        $contact_name = $phone_number;
    }
    $recipient_list .= "<div class='parsed-contact'>" . $contact_name . "<i class='fas fa-times fa-x' onclick='remove_recipient_contact(this,\"" . $phone_number . "\")'></i></div>";
}
echo $recipient_list;

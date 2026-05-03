<?php include "db/dblink.php";

$items_raw = isset($_GET["items"]) ? (string) $_GET["items"] : "";
$groups = explode(",", mysqli_real_escape_string($conn, $items_raw));

$recipient_list = "";
for ($i = 0; $i < count($groups) - 1; $i++) {
    $group_id = $groups[$i];

    $q = mysqli_query($conn, "SELECT * FROM groups WHERE group_id='" . $group_id . "' AND user_id='" . $_SESSION['user_id'] . "'");
    $found = mysqli_num_rows($q);
    if ($found) {
        $group = mysqli_fetch_assoc($q);
        $tc = mysqli_query($conn, "SELECT * FROM group_contacts WHERE group_id='" . $group['group_id'] . "'");
        $total_contacts = mysqli_num_rows($tc);
        $group_name = "<span>" . $group['group_name'] . "</span> (" . $total_contacts . " contacts)";
        $recipient_list .= "<div class='parsed-contact'>" . $group_name . "<i class='fas fa-times fa-x' onclick='remove_recipient_group(this,\"" . $group_id . "\")'></i></div>";
    }
}
echo $recipient_list;

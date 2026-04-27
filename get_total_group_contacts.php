<?php include "db/dblink.php";

$groups = explode(",", mysqli_real_escape_string($conn, $_GET['groups']));

$total_group_contacts = 0;

for ($i = 0; $i < count($groups) - 1; $i++) {
    $group_id = $groups[$i];
    $tc = mysqli_query($conn, "SELECT * FROM group_contacts WHERE group_id='" . $group_id . "'");
    $total_group_contacts += mysqli_num_rows($tc);
}

echo $total_group_contacts;

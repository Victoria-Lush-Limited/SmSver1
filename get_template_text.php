<?php include "db/dblink.php";

$templates = explode(",", mysqli_real_escape_string($conn, $_GET['items']));

$template_list = "";
for ($i = 0; $i < count($templates) - 1; $i++) {
    $template_id = $templates[$i];

    $q = mysqli_query($conn, "SELECT * FROM templates WHERE template_id='" . $template_id . "' AND user_id='" . $_SESSION['user_id'] . "'");
    $template = mysqli_fetch_assoc($q);
    $template_list .= $template['message'];
}
echo $template_list;

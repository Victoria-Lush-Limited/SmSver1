<?php include "db/dblink.php";

$items_raw = isset($_GET["items"]) ? (string) $_GET["items"] : "";
$templates = explode(",", mysqli_real_escape_string($conn, $items_raw));

$template_list = "";
for ($i = 0; $i < count($templates) - 1; $i++) {
    $template_id = $templates[$i];

    $q = mysqli_query($conn, "SELECT * FROM templates WHERE template_id='" . $template_id . "' AND user_id='" . $_SESSION['user_id'] . "'");
    $template = $q ? mysqli_fetch_assoc($q) : null;
    if ($template && isset($template["message"])) {
        $template_list .= $template["message"];
    }
}
echo $template_list;

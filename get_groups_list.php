<?php

include "db/dblink.php";
?>
<option value="">Select Group</option>
<?php
$q = mysqli_query($conn, "SELECT * FROM groups WHERE user_id= '" . $_SESSION['user_id'] . "'");
while ($group = mysqli_fetch_assoc($q)) {
    echo "<option value=\"" . $group['group_id'] . "\">" . $group['group_name'] . "</option>";
}
?>
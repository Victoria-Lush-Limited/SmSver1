<?php
include "db/dblink.php";

$start_row = isset($_GET["start_row"]) ? max(0, (int) $_GET["start_row"] - 1) : 0;
$per_page = isset($_GET["per_page"]) ? max(1, min(500, (int) $_GET["per_page"])) : 20;

$previous_start_row = $start_row - $per_page;
$table_rows = 0;

$keyword = mysqli_real_escape_string($conn, isset($_GET["keyword"]) ? (string) $_GET["keyword"] : "");

$q = mysqli_query($conn, "SELECT * FROM groups WHERE user_id='" . $_SESSION['user_id'] . "' AND group_name LIKE '%" . $keyword . "%'");

$found = mysqli_num_rows($q);

?>
<ul class="modal-buttons">
    <div class="modal-submit-button" onclick="insert_groups()">
        <i class="fas fa-plus fa-s"></i>Insert Groups
    </div>
</ul>
<table id="modal_groups" name="modal_groups">
    <tr class="table-header">
        <td><input type="checkbox" onclick="toggle_all(this.checked,'modal_groups')"></td>
        <td>Group Name</td>
        <td>Contacts</td>
    </tr>
    <?php
    $q = mysqli_query($conn, "SELECT * FROM groups WHERE user_id='" . $_SESSION['user_id'] . "' AND group_name LIKE '%" . $keyword . "%' ORDER BY group_id ASC LIMIT " . $start_row . "," . $per_page);

    if ($found) {

        while ($group = mysqli_fetch_assoc($q)) {
            $tc = mysqli_query($conn, "SELECT * FROM group_contacts WHERE group_id='" . $group['group_id'] . "'");
            $total_contacts = mysqli_num_rows($tc);
            $table_rows += 1;
    ?>
            <tr>
                <td><input type="checkbox" name="item_<?php echo $group['group_id']; ?>" id="item_<?php echo $group['group_id']; ?>"></td>
                <td><?php echo $group['group_name']; ?></td>
                <td><?php echo number_format($total_contacts); ?></td>
            </tr>
    <?php
        }
    }

    $next_start_row = $start_row + $table_rows + 1;
    $previous_start_row = $start_row - $per_page + 1;

    if ($found) {
        $showing_from = $start_row + 1;
    } else {
        $showing_from = 0;
    }

$showing_to = $start_row + $table_rows;
    ?>
</table>

<div class="pagination">
    <div class="page-nav">
        <i class="fas fa-chevron-left fa-s" <?php if ($start_row > 0) { ?> onclick="get_groups_modal(<?php echo $previous_start_row; ?>,<?php echo $per_page; ?>)" <?php } ?>></i>
        <i class="fas fa-chevron-right fa-s" <?php if ($next_start_row <= $found) { ?> onclick="get_groups_modal(<?php echo $next_start_row; ?>,<?php echo $per_page; ?>)" <?php } ?>></i>
        <div class="page-records">
            Showing
            <b><?php echo number_format($showing_from); ?> - <?php echo number_format($showing_to); ?> </b>
            of
            <b><?php echo $found; ?></b>
            records
        </div>
    </div>
    <ul class="page-rows">
        <li><label>Per Page:</label></li>
        <li>
            <select name="groups_modal_per_page" id="groups_modal_per_page" onchange="get_groups_modal(<?php echo ($start_row + 1); ?>,this.value)">
                <option value="10">10 rows</option>
                <option value="25">25 rows</option>
                <option value="50">50 rows</option>
                <option value="100">100 rows</option>
                <option value="250">250 rows</option>
                <option value="500">500 rows</option>
            </select>
        </li>
    </ul>
</div>
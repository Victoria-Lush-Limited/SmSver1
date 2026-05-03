<?php
include "db/dblink.php";

$start_row = isset($_GET["start_row"]) ? max(0, (int) $_GET["start_row"] - 1) : 0;
$per_page = isset($_GET["per_page"]) ? max(1, min(500, (int) $_GET["per_page"])) : 20;

$previous_start_row = $start_row - $per_page;
$table_rows = 0;

$keyword = mysqli_real_escape_string($conn, isset($_GET["keyword"]) ? (string) $_GET["keyword"] : "");

$q = mysqli_query($conn, "SELECT * FROM contacts WHERE user_id='" . $_SESSION['user_id'] . "' AND (contact_name LIKE '%" . $keyword . "%' || phone_number LIKE '%" . $keyword . "%' || email LIKE '%" . $keyword . "%')");

$found = mysqli_num_rows($q);

?>
<ul class="modal-buttons">
    <div class="modal-submit-button" onclick="insert_contacts()">
        <i class="fas fa-plus fa-s"></i>Insert Contacts
    </div>
</ul>
<table id="modal_contacts" name="modal_contacts">
    <tr class="table-header">
        <td><input type="checkbox" onclick="toggle_all(this.checked,'modal_contacts')"></td>
        <td>Phone Number</td>
        <td>Contact Name</td>
    </tr>
    <?php
    $q = mysqli_query($conn, "SELECT * FROM contacts WHERE user_id='" . $_SESSION['user_id'] . "' AND (contact_name LIKE '%" . $keyword . "%' || phone_number LIKE '%" . $keyword . "%' || email LIKE '%" . $keyword . "%') ORDER BY contact_id ASC LIMIT " . $start_row . "," . $per_page);

    if ($found) {

        while ($contact = mysqli_fetch_assoc($q)) {
            $table_rows += 1;
    ?>
            <tr>
                <td><input type="checkbox" name="item_<?php echo $contact['phone_number']; ?>" id="item_<?php echo $contact['phone_number']; ?>"></td>
                <td><?php echo $contact['phone_number']; ?></td>
                <td><?php echo $contact['contact_name']; ?></td>
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
        <i class="fas fa-chevron-left fa-s" <?php if ($start_row > 0) { ?> onclick="get_contacts_modal(<?php echo $previous_start_row; ?>,<?php echo $per_page; ?>)" <?php } ?>></i>
        <i class="fas fa-chevron-right fa-s" <?php if ($next_start_row <= $found) { ?> onclick="get_contacts_modal(<?php echo $next_start_row; ?>,<?php echo $per_page; ?>)" <?php } ?>></i>
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
            <select name="contacts_modal_per_page" id="contacts_modal_per_page" onchange="get_contacts_modal(<?php echo ($start_row + 1); ?>,this.value)">
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
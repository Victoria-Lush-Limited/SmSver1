<?php
include "db/dblink.php";

$start_row = $_GET['start_row'] - 1;
$per_page = $_GET['per_page'];

$previous_start_row = $start_row - $per_page;
$table_rows = 0;

$group_id = mysqli_real_escape_string($conn, $_GET['group_id']);

$keyword = mysqli_real_escape_string($conn, $_GET['keyword']);

if (!empty($group_id)) {
    $q = mysqli_query($conn, "SELECT * FROM contacts C, group_contacts G WHERE C.contact_id=G.contact_id AND G.group_id='" . $group_id . "' AND C.user_id='" . $_SESSION['user_id'] . "' AND (C.contact_name LIKE '%" . $keyword . "%' || C.phone_number LIKE '%" . $keyword . "%' || C.email LIKE '%" . $keyword . "%')");
} else {
    $q = mysqli_query($conn, "SELECT * FROM contacts WHERE user_id='" . $_SESSION['user_id'] . "' AND (contact_name LIKE '%" . $keyword . "%' || phone_number LIKE '%" . $keyword . "%' || email LIKE '%" . $keyword . "%')");
}
$found = mysqli_num_rows($q);

?>
<table id="contacts">
    <tr class="table-header">
        <td><input type="checkbox" onclick="toggle_all(this.checked,'contacts')"></td>
        <td>Phone Number</td>
        <td>Contact Name</td>
        <td>Email</td>
        <td>Date Created</td>
        <td>Options</td>
    </tr>
    <?php
    if (!empty($group_id)) {
        $q = mysqli_query($conn, "SELECT * FROM contacts C, group_contacts G WHERE C.contact_id=G.contact_id AND G.group_id='" . $group_id . "' AND C.user_id='" . $_SESSION['user_id'] . "' AND (C.contact_name LIKE '%" . $keyword . "%' || C.phone_number LIKE '%" . $keyword . "%' || C.email LIKE '%" . $keyword . "%') ORDER BY C.contact_id DESC LIMIT " . $start_row . "," . $per_page);
    } else {
        $q = mysqli_query($conn, "SELECT * FROM contacts WHERE user_id='" . $_SESSION['user_id'] . "' AND (contact_name LIKE '%" . $keyword . "%' || phone_number LIKE '%" . $keyword . "%' || email LIKE '%" . $keyword . "%') ORDER BY contact_id DESC LIMIT " . $start_row . "," . $per_page);
    }
    if ($found) {

        while ($contact = mysqli_fetch_assoc($q)) {
            $table_rows += 1;
    ?>
            <tr>
                <td><input type="checkbox" name="item_<?php echo $contact['contact_id']; ?>" id="item_<?php echo $contact['contact_id']; ?>"></td>
                <td><?php echo $contact['phone_number']; ?></td>
                <td><?php echo $contact['contact_name']; ?></td>
                <td><?php echo $contact['email']; ?></td>
                <td><?php echo date("d-m-Y H:i", $contact['date_created']); ?></td>
                <td>
                    <div class="row-options">
                        <i class="fas fa-edit fa-l" onclick="edit_contact('<?php echo $contact['contact_id'];?>')"></i>
                        <i class="fas fa-trash fa-l" onclick="delete_contacts(<?php echo $contact['contact_id']; ?>,'<?php echo $group_id; ?>')"></i>
                    </div>
                </td>
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
        <i class="fas fa-chevron-left fa-s" <?php if ($start_row > 0) { ?> onclick="get_contacts(<?php echo $previous_start_row; ?>,<?php echo $per_page; ?>)" <?php } ?>></i>
        <i class="fas fa-chevron-right fa-s" <?php if ($next_start_row <= $found) { ?> onclick="get_contacts(<?php echo $next_start_row; ?>,<?php echo $per_page; ?>)" <?php } ?>></i>
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
            <select name="per_page" id="per_page" onchange="get_contacts(<?php echo ($start_row + 1); ?>,this.value)">
                <option value="3">3 rows</option>
                <option value="10">10 rows</option>
                <option value="25">25 rows</option>
                <option value="50">50 rows</option>
                <option value="100">100 rows</option>
                <option value="250">250 rows</option>
                <option value="500">500 rows</option>
            </select>
            <input type="hidden" name="start_row" id="start_row" value="<?php echo $start_row + 1; ?>">
        </li>
    </ul>
</div>
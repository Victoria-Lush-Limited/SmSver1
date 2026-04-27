<?php
include "db/dblink.php";

$start_row = $_GET['start_row'] - 1;
$per_page = $_GET['per_page'];

$previous_start_row = $start_row - $per_page;
$table_rows = 0;

$from_date = strtotime(mysqli_real_escape_string($conn, $_GET['from_date']));
$to_date = strtotime("+1 days", strtotime(mysqli_real_escape_string($conn, $_GET['to_date'])));


$keyword = mysqli_real_escape_string($conn, $_GET['keyword']);

$now = time();
$q = mysqli_query($conn, "SELECT * FROM custom_sms WHERE user_id='" . $_SESSION['user_id'] . "' AND date_created > '" . $from_date . "' AND date_created <='" . $to_date . "' AND (message LIKE '%" . $keyword . "%' || phone_number LIKE '%" . $keyword . "%' || sender_id LIKE '%" . $keyword . "%')");


$found = mysqli_num_rows($q);

?>
<table>
    <tr class="table-header">
        <td><input type="checkbox" name="toggle_all" id="toggle_all"></td>
        <td>Phone</td>
        <td>Sender</td>
        <td>Message</td>
        <td>Date</td>
        <td>Status</td>
    </tr>
    <?php
    $q = mysqli_query($conn, "SELECT * FROM custom_sms WHERE user_id='" . $_SESSION['user_id'] . "' AND date_created > '" . $from_date . "' AND date_created <='" . $to_date . "'  AND (message LIKE '%" . $keyword . "%' || phone_number LIKE '%" . $keyword . "%' || sender_id LIKE '%" . $keyword . "%') ORDER BY date_created ASC LIMIT " . $start_row . "," . $per_page);

    if ($found) {

        while ($preview = mysqli_fetch_assoc($q)) {
            $table_rows += 1;
    ?>
            <tr>
                <td><input type="checkbox" name="preview_<?php echo $preview['sms_id_id']; ?>" id="preview_<?php echo $preview['sms_id']; ?>"></td>
                <td><?php echo $preview['phone_number']; ?></td>
                <td><?php echo $preview['sender_id']; ?></td>
                <td><?php echo $preview['message']; ?></td>
                <td><?php echo date("d-M-Y H:i", $preview['date_created']); ?></td>
                <td><?php echo $preview['sms_status']; ?></td>
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
        <i class="fas fa-chevron-left fa-s" <?php if ($start_row > 0) { ?> onclick="get_scheduled(<?php echo $previous_start_row; ?>,<?php echo $per_page; ?>)" <?php } ?>></i>
        <i class="fas fa-chevron-right fa-s" <?php if ($next_start_row <= $found) { ?> onclick="get_scheduled(<?php echo $next_start_row; ?>,<?php echo $per_page; ?>)" <?php } ?>></i>
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
            <select name="per_page" id="per_page" onchange="get_preview(<?php echo ($start_row + 1); ?>,this.value)">
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
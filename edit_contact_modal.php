<?php include "db/dblink.php";

$contact_id = mysqli_real_escape_string($conn, $_GET['contact_id']);
$q = mysqli_query($conn, "SELECT * FROM contacts WHERE contact_id='" . $contact_id . "' AND user_id='" . $_SESSION['user_id'] . "'");
$contact = mysqli_fetch_assoc($q);
$pn = isset($contact['phone_number']) ? preg_replace('/\D+/', '', $contact['phone_number']) : '';
$defRegion = 'TZ';
$len = strlen($pn);
if ($len === 12 && strpos($pn, '254') === 0) {
    $defRegion = 'KE';
} elseif ($len === 12 && strpos($pn, '256') === 0) {
    $defRegion = 'UG';
} elseif ($len === 12 && strpos($pn, '255') === 0) {
    $defRegion = 'TZ';
} elseif ($len >= 10 && $len <= 15) {
    $defRegion = 'OTHER';
}
?>
<form id="edit_form" name="edit_form" method="GET">
    <div class="form-field">
        <label for="edit_phone_region">Country / number type</label>
        <select name="edit_phone_region" id="edit_phone_region">
            <option value="TZ" <?php echo $defRegion === 'TZ' ? 'selected' : ''; ?>>Tanzania (+255)</option>
            <option value="KE" <?php echo $defRegion === 'KE' ? 'selected' : ''; ?>>Kenya (+254)</option>
            <option value="UG" <?php echo $defRegion === 'UG' ? 'selected' : ''; ?>>Uganda (+256)</option>
            <option value="OTHER" <?php echo $defRegion === 'OTHER' ? 'selected' : ''; ?>>Other (full international)</option>
        </select>
    </div>
    <div class="form-field">
        <label for="edit_phone_number">Phone number</label>
        <input type="text" name="edit_phone_number" id="edit_phone_number" placeholder="0742200333 or full MSISDN" value="<?php echo htmlspecialchars($contact['phone_number'], ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="form-field">
        <label for="">Contact Name</label>
        <input type="text" name="edit_contact_name" id="edit_contact_name" value="<?php echo $contact['contact_name']; ?>">
    </div>
    <div class="form-field">
        <label for="">Email</label>
        <input type="text" name="edit_email" id="edit_email" value="<?php echo $contact['email']; ?>">
    </div>

    <div class="form-field">
        <div class="send-button" onclick="update_contact(document.getElementById('start_row').value,document.getElementById('per_page').value,'<?php echo $contact['contact_id']; ?>')"><i class="fas fa-save"></i>Save Contact</div>
    </div>
    <div class="form-field">
        <div class="form-errors" id="edit_form_errors"></div>
    </div>
</form>
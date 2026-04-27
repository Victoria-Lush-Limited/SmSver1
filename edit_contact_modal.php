<?php include "db/dblink.php";

$contact_id = mysqli_real_escape_string($conn, $_GET['contact_id']);
$q = mysqli_query($conn, "SELECT * FROM contacts WHERE contact_id='" . $contact_id . "' AND user_id='" . $_SESSION['user_id'] . "'");
$contact = mysqli_fetch_assoc($q);
?>
<form id="edit_form" name="edit_form" method="GET">
    <div class="form-field">
        <label for=""> Phone Number</label>
        <input type="text" name="edit_phone_number" id="edit_phone_number" placeholder="eg. 255742200333" value="<?php echo $contact['phone_number']; ?>">
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
        <div class="send-button" onclick="update_contact(document.getElementById('start_row').value,document.getElementById('per_page').value,'<?php echo $contact['contact_id']; ?>')">Save Contact</div>
    </div>
    <div class="form-field">
        <div class="form-errors" id="edit_form_errors"></div>
    </div>
</form>
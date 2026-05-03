<?php include "db/dblink.php";

$template_id = mysqli_real_escape_string($conn, $_GET['template_id']);
$q = mysqli_query($conn, "SELECT * FROM templates WHERE template_id='" . $template_id . "' AND user_id='" . $_SESSION['user_id'] . "'");
$template = mysqli_fetch_assoc($q);
?>
<form id="edit_form" name="edit_form" method="GET">
    <div class="form-field">
        <label for="">Title</label>
        <input type="text" name="edit_title" id="edit_title" placeholder="" value="<?php echo $template['title'];?>">
    </div>
    <div class="form-field">
        <label for="">Message</label>
        <textarea name="edit_message" id="edit_message"><?php echo $template['message'];?></textarea>
    </div>
    <div class="form-field">
        <div class="send-button" onclick="update_template(document.getElementById('start_row').value,document.getElementById('per_page').value,'<?php echo $template['template_id'];?>')"><i class="fas fa-save"></i>Save Changes</div>
    </div>
    <div class="form-field">
        <div class="edit_form-errors" id="edit_form_errors"></div>
    </div>
</form>
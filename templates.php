<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
} else {
    $q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND status='Active'");
    $found = mysqli_num_rows($q);
    if ($found) {
        $user = mysqli_fetch_assoc($q);
        if ($user['status'] == "Pending") {
            header("location:verification.php");
        }
    } else {
        header("location:signout.php");
    }
}

$from_date = strtotime(date("d-m-Y", time()));
$to_date = time();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app['app_name']; ?></title>
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js"></script>
</head>

<body>
    <?php include "header.php"; ?>
    <div class="container">
        <div class="menu">
            <?php include "menu.php"; ?>
        </div>
        <div class="content-wrapper">
            <div class="page-title">Templates</div>
            <div class="page-header">
                <ul class="page-menu">
                    <li><label for="create_template"><i class="fas fa-plus fa-s"></i>Add Template</label></li>
                </ul>
                <div class="page-options">
                    <ul>
                        <li><input type="text" id="keyword" name="keyword" placeholder="Search Keyword" onchange="get_contacts(1,document.getElementById('per_page').value)"><i class="fas fa-search fa-s" onclick="get_templates(1,document.getElementById('per_page').value)"></i></li>
                    </ul>
                </div>
            </div>
            <input type="checkbox" name="create_template" id="create_template">
            <div id="create_template_modal">
                <div class="modal-wrapper">
                    <div class="modal-header">
                        <div class="modal-title">
                            New SMS Template
                        </div>
                        <div class="modal-close">
                            <label for="create_template"><i class="fas fa-times fa-2x"></i></label>
                        </div>
                    </div>
                    <div class="modal-content" id="create_template_modal_content">
                        <div class="form-field">
                            <label for="">Title</label>
                            <input type="text" name="title" id="title" placeholder="" maxlength="11">
                        </div>
                        <div class="form-field">
                            <label for="">Message</label>
                            <textarea name="message" id="message"></textarea>
                        </div>
                        <div class="form-field">
                            <div class="send-button" onclick="save_template(document.getElementById('start_row').value,document.getElementById('per_page').value)">Save Template</div>
                        </div>
                        <div class="form-field">
                            <div class="form-errors" id="form_errors"></div>
                        </div>

                    </div>
                </div>
            </div>
            <input type="checkbox" name="edit_template" id="edit_template">
            <div id="edit_template_modal">
                <div class="modal-wrapper">
                    <div class="modal-header">
                        <div class="modal-title">
                            Edit SMS Template
                        </div>
                        <div class="modal-close">
                            <label for="edit_template"><i class="fas fa-times fa-2x"></i></label>
                        </div>
                    </div>
                    <div class="modal-content" id="edit_template_modal_content">
                     
                    </div>
                </div>
            </div>
            
            <div class="page-content" id="page-content">

            </div>
        </div>
    </div>
    <?php include "footer.php";?>
    <script>
        get_templates(1, 10);
    </script>
</body>

</html>
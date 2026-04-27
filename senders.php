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
            <div class="page-title">Sender ID</div>
            <div class="page-header">
                <ul class="page-menu">
                    <li><label for="create_sender"><i class="fas fa-plus fa-s"></i>Request Sender ID</label></li>
                </ul>
                <div class="page-options">
                    <ul>
                        <li><input type="text" id="keyword" name="keyword" placeholder="Search Keyword" onchange="get_senders(1,document.getElementById('per_page').value)"><i class="fas fa-search fa-s" onclick="get_senders(1,document.getElementById('per_page').value)"></i></li>
                    </ul>
                </div>
            </div>
            <input type="checkbox" name="create_sender" id="create_sender">
            <div id="create_sender_modal">
                <div class="modal-wrapper">
                    <div class="modal-header">
                        <div class="modal-title">
                            Request Sender ID
                        </div>
                        <div class="modal-close">
                            <label for="create_sender"><i class="fas fa-times fa-2x"></i></label>
                        </div>
                    </div>
                    <div class="modal-content" id="create_sender_modal_content">
                        <div class="form-field">
                            <label for="">Sender ID</label>
                            <input type="text" name="sender_id" id="sender_id" placeholder="" maxlength="11">
                        </div>
                        <div class="form-field">
                            <label for="">Sample Message</label>
                            <textarea name="message" id="message"></textarea>
                        </div>
                        <div class="form-field">
                            <div class="send-button" onclick="save_sender(document.getElementById('start_row').value,document.getElementById('per_page').value)">Submit</div>
                        </div>
                        <div class="form-field">
                            <div class="form-errors" id="form_errors"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="page-content" id="page-content">

            </div>
        </div>
    </div>
    <?php include "footer.php";?>
    <script>
        get_senders(1, 10);
    </script>
</body>

</html>
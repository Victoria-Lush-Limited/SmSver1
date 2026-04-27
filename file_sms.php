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
    <link type="text/css" href="css/ui-lightness/jquery-ui-1.8.13.custom.css" rel="Stylesheet" />
    <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.13.custom.min.js"></script>

    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js"></script>
    <script>
        $(function() {
            $("#start_date").datepicker({
                dateFormat: 'dd-mm-yy',
                minDate: '<?php echo date('d-m-Y', time()); ?>',
            });
            $("#end_date").datepicker({
                dateFormat: 'dd-mm-yy',
                minDate: '<?php echo date('d-m-Y', time()); ?>',
            });
        });
    </script>
</head>

<body>
    <?php include "header.php"; ?>
    <div class="container">
        <div class="menu">
            <?php include "menu.php"; ?>
        </div>
        <div class="content-wrapper">

            <div class="page-title">File SMS</div>
            <div class="page-content" id="page-content">
                <div class="page-form">
                    <form id="composer" name="composer" method="post" action="upload_file.php" enctype="multipart/form-data">
                        <div class="form-field">
                            <div class="field-label">File:</div>
                            <div class="custom-input-wrapper">
                                <input type="file" name="file_name" id="file_name">
                                <br>
                                Only <b>.xls</b> and <b>.xlsx</b> files allowed
                            </div>

                        </div>
                        <div class="form-field">
                            <div class="field-label">From:</div>
                            <div class="custom-input-wrapper">
                                <select name="sender_id" id="sender_id">
                                    <option value="">Select Sender ID</option>
                                    <?php
                                    $q = mysqli_query($conn, "SELECT * FROM senders WHERE id_status='Active' AND user_id='" . $_SESSION['user_id'] . "' OR id_type='Public'");
                                    while ($sender = mysqli_fetch_assoc($q)) {
                                    ?>
                                        <option value="<?php echo $sender['sender_id']; ?>"><?php echo $sender['sender_id']; ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <ul class="field-list">
                                <li></li>
                            </ul>
                        </div>

                        <div class="form-field">
                            <div class="field-label">Message:</div>
                            <div class="custom-input-wrapper">
                                <textarea name="message" id="message"></textarea>
                                <div class="custom-counter" style="visibility:hidden;"><span id="message_length">0</span> Characters <span id="sms_count">0</span> SMS Credits </div>
                            </div>

                            <ul class="field-list">
                                <li>
                                    <label for="insert_templates"><i class="fas fa-mail-bulk fa-s"></i>Insert Template</label>
                                    <input type="checkbox" id="insert_templates" name="insert_templates">
                                    <div id="templates_modal">
                                        <div class="modal-wrapper">
                                            <div class="modal-header">
                                                <div class="modal-title">
                                                    SMS Templates
                                                </div>
                                                <div class="modal-close">
                                                    <label for="insert_templates"><i class="fas fa-times fa-2x"></i></label>
                                                </div>
                                            </div>
                                            <div class="modal-content" id="templates_modal_content">

                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="form-field">
                            <div class="field-label">Repeat:</div>
                            <div class="custom-input-wrapper">
                                <select name="schedule" id="schedule">
                                    <option value="None">None (Send Once)</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Weekly">Weekly</option>
                                    <option value="Monthly">Monthly</option>
                                </select>
                            </div>
                            <ul class="field-list">
                                <li></li>
                            </ul>
                        </div>
                        <div class="form-field">
                            <div class="field-label">Start Date:</div>
                            <div class="custom-input-wrapper">
                                <div class="date-time-wrapper">
                                    <input type="text" id="start_date" name="start_date" readonly value="<?php echo date("d-m-Y", time()); ?>">
                                </div>
                            </div>
                            <ul class="field-list">
                                <li></li>
                            </ul>
                        </div>
                        <div class="form-field">
                            <div class="field-label">Time:</div>
                            <div class="custom-input-wrapper">
                                <div class="date-time-wrapper">
                                    <select name="send_hour" id="send_hour">
                                        <?php
                                        for ($hour = 0; $hour < 24; $hour++) {
                                            if ($hour == (date("H", time()))) {
                                                echo "<option selected value=\"" . $hour . "\">" . str_pad($hour, 2, '0', STR_PAD_LEFT) . "</option>";
                                            } else {
                                                echo "<option value=\"" . $hour . "\">" . str_pad($hour, 2, '0', STR_PAD_LEFT) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <div style="padding-top:8px; font-weight:bold;">:</div>
                                    <select name="send_minute" id="send_minute">
                                        <?php
                                        for ($minute = 0; $minute < 60; $minute += 5) {
                                        ?>
                                            <option value="<?php echo $minute; ?>"><?php echo str_pad($minute, 2, '0', STR_PAD_LEFT); ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <ul class="field-list">
                                <li></li>
                            </ul>
                        </div>

                        <div class="form-field">
                            <div class="field-label">End Date:</div>
                            <div class="custom-input-wrapper">
                                <div class="date-time-wrapper">
                                    <input type="text" id="end_date" name="end_date" readonly value="<?php echo date("d-m-Y", time()); ?>">
                                </div>
                            </div>
                            <ul class="field-list">
                                <li></li>
                            </ul>
                        </div>
                        <div class="form-field" id="antispam-wrapper">
                            <input type="checkbox" id="antispam" name="antispam" style="width:100px;">
                            <label for="antispam"> I certify that this message abides by anti-spamming rules, restrictions on political messaging as well as international and local laws and regulations. I have accepted the <a href="tos.php" style="font-weight:bold;">terms of service</a> that govern the use of this platform. </label>
                        </div>
                        <div class="form-field">
                            <div class="field-label"></div>
                            <div class="custom-input-wrapper">
                                <div class="send-button" onclick="upload_file()">Upload File <i class="fas fa-caret-right fa-l"></i></div>
                            </div>
                            <ul class="field-list">
                                <li></li>
                            </ul>
                        </div>

                        <div class="form-field">
                            <div class="field-label"></div>
                            <div class="form-errors" id="file_form_errors"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include "footer.php"; ?>
    <script>
        get_templates_modal(1, 20);
    </script>
</body>

</html>
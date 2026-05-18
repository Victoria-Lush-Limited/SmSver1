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

include_once __DIR__ . '/inc/ledger_balance.php';
$compose_billing_user = $user;
$compose_sender_for_balance = '';
$qs0 = mysqli_query($conn, "SELECT sender_id FROM senders WHERE user_id='" . mysqli_real_escape_string($conn, (string) $_SESSION['user_id']) . "' AND id_status='Active' ORDER BY sender_id ASC LIMIT 1");
if ($qs0 && mysqli_num_rows($qs0) > 0) {
    $sr0 = mysqli_fetch_assoc($qs0);
    $compose_sender_for_balance = (string) ($sr0['sender_id'] ?? '');
    $compose_billing_user = vll_ledger_billing_user_row($conn, $user, $compose_sender_for_balance);
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
    <?php include __DIR__ . '/inc/head_brand_meta.php'; ?>
    <title><?php echo $app['app_name']; ?></title>
    <link type="text/css" href="css/ui-lightness/jquery-ui-1.8.13.custom.css?v=20260511" rel="Stylesheet" />
    <script type="text/javascript" src="js/jquery-1.5.1.min.js?v=20260511"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.13.custom.min.js?v=20260511"></script>

    <link rel="stylesheet" href="css/all.css?v=20260511">
    <link rel="stylesheet" href="css/style.css?v=20260511">
    <script src="js/script.js?v=20260511"></script>
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
            <?php if (isset($_GET['r'])) {
                if ($_GET['r'] == "Sent") {
            ?>
                    <div class="message-sent">Message Sent</div>
                <?php
                }
                if ($_GET['r'] != "Sent") {
                ?>
                    <div class="message-failed"><?php echo htmlspecialchars((string) $_GET['r'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php
                }
            } else { ?>

                <div class="page-title">Compose Message</div>
                <div class="page-content" id="page-content">
                    <form id="composer" name="composer" action="send_sms.php" method="post" autocomplete="off">
                        <div class="page-form">
                            <div class="form-field">
                                <div class="field-label">Recipients:</div>
                                <div class="custom-input-wrapper">
                                    <input type="text" name="recipient" id="recipient" placeholder="Type MSISDN (255… / 254… / 256…) then press Enter ">
                                    <div class="supported-prefixes-banner" role="note">
                                        <span class="pfx pfx-tz"><i class="fas fa-sim-card" aria-hidden="true"></i><strong>255</strong> Tanzania</span>
                                        <span class="pfx pfx-ke"><i class="fas fa-sim-card" aria-hidden="true"></i><strong>254</strong> Kenya</span>
                                        <span class="pfx pfx-ug"><i class="fas fa-sim-card" aria-hidden="true"></i><strong>256</strong> Uganda</span>
                                    </div>
                                    <small class="supported-prefixes-note">Numbers are stored and sent as <strong>255</strong>, <strong>254</strong>, or <strong>256</strong> country codes (no + prefix).</small>
                                    <div class="custom-input">
                                        <div name="recipient_list" id="recipient_list"></div>

                                    </div>
                                    <div>
                                        <input type="hidden" name="contacts" id="contacts">
                                        <br><br>
                                        <input type="hidden" name="groups" id="groups">
                                    </div>
                                    <div class="custom-counter"><span id="total_recipients">0</span> Recipients</div>
                                </div>
                                <ul class="field-list">
                                    <li>
                                        <label for="insert_contacts"><i class="fas fa-user fa-s"></i>Insert Contacts</label>
                                        <input type="checkbox" id="insert_contacts" name="insert_contacts">
                                        <div id="contacts_modal">
                                            <div class="modal-wrapper">
                                                <div class="modal-header">
                                                    <div class="modal-title">
                                                        Contacts
                                                    </div>
                                                    <div class="modal-close">
                                                        <label for="insert_contacts"><i class="fas fa-times fa-2x"></i></label>
                                                    </div>
                                                </div>
                                                <div class="modal-content" id="contacts_modal_content">

                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <label for="insert_groups"><i class="fas fa-users fa-s"></i>Insert Groups</label>
                                        <input type="checkbox" id="insert_groups" name="insert_groups">
                                        <div id="groups_modal">
                                            <div class="modal-wrapper">
                                                <div class="modal-header">
                                                    <div class="modal-title">
                                                        Groups
                                                    </div>
                                                    <div class="modal-close">
                                                        <label for="insert_groups"><i class="fas fa-times fa-2x"></i></label>
                                                    </div>
                                                </div>
                                                <div class="modal-content" id="groups_modal_content">

                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="form-field">
                                <div class="field-label">From:</div>
                                <div class="custom-input-wrapper">
                                    <select name="sender_id" id="sender_id">
                                        <option value="">Select Sender ID</option>
                                        <?php
                                        $uid_sel = mysqli_real_escape_string($conn, (string) $_SESSION['user_id']);
                                        $q = mysqli_query($conn, "SELECT * FROM senders WHERE id_status='Active' AND (user_id='" . $uid_sel . "' OR id_type='Public' OR id_type='Global') ORDER BY sender_id ASC");
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
                                    <div class="custom-counter"><span id="message_length">0</span> Characters <span id="sms_count">0</span> SMS Credits </div>
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
                                    <div class="send-button" onclick="send_sms()">Send <i class="fas fa-caret-right fa-l"></i></div>
                                </div>
                                <ul class="field-list">
                                    <li></li>
                                </ul>
                            </div>
                            <div class="form-field">
                                <div class="field-label"></div>
                                <div class="form-errors" id="compose_form_errors"></div>
                            </div>

                        </div>
                    </form>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php include "footer.php"; ?>
    <script>
        // Restricts input for the given textbox to the given inputFilter.
        function setInputFilter(textbox, inputFilter) {
            ["input", "keydown", "keyup", "mousedown", "mouseup", "select", "contextmenu", "drop"].forEach(function(event) {
                textbox.addEventListener(event, function() {
                    if (inputFilter(this.value)) {
                        this.oldValue = this.value;
                        this.oldSelectionStart = this.selectionStart;
                        this.oldSelectionEnd = this.selectionEnd;
                    } else if (this.hasOwnProperty("oldValue")) {
                        this.value = this.oldValue;
                        this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
                    } else {
                        this.value = "";
                    }
                });
            });
        }

        setInputFilter(document.getElementById("recipient"), function(value) {
            return /^-?\d*$/.test(value);
        });


        var recipient_input = document.getElementById('recipient');
        recipient_input.addEventListener('keypress', function(e) {
            if (e.keyCode == 13) {
                parse_recipient();
                this.value = "";
            }
        }, true);

        var message_input = document.getElementById('message');
        ["input", "keydown", "keyup", "mousedown", "mouseup", "select", "contextmenu", "drop"].forEach(function(event) {
            message_input.addEventListener(event, function() {
                count_message();
            });
        });


        get_contacts_modal(1, 20);
        get_groups_modal(1, 20);
        get_templates_modal(1, 20);
    </script>
</body>

</html>
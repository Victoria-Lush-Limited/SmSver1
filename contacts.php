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
    <script src="js/script.js?v=20260501"></script>
</head>

<body>
    <?php include "header.php"; ?>
    <div class="container">
        <div class="menu">
            <?php include "menu.php"; ?>
        </div>
        <div class="content-wrapper">
            <?php if (isset($_GET['r'])) { ?>
                <div class="message-sent"><?php echo htmlspecialchars($_GET['r']); ?></div>
            <?php } ?>
            <div class="page-title">Contacts</div>
            <div class="page-header">
                <ul class="page-menu">
                    <li>
                        <label for="create_contact">
                            <i class="fas fa-plus fa-s"></i>Add Contact
                        </label>
                    </li>

                    <li onclick="download_contacts()">
                        <label for=""><i class="fas fa-upload fa-s"></i>Import Contacts</label>
                    </li>
                    <li>
                        <label for="create_group">
                            <i class="fas fa-plus fa-s"></i>New Group
                        </label>
                    </li>
                
                    <li onclick="bulk_delete_contacts()">
                        <label for=""><i class="fas fa-trash fa-s"></i>Delete</label>
                    </li>
                </ul>
                <div class="page-options">
                    <ul>
                        <li>
                            <select type="text" id="group_id" name="group_id" onchange="get_contacts(1,document.getElementById('per_page').value)">
                                <option value="">Select Group [All]</option>
                                <?php
                                $q = mysqli_query($conn, "SELECT * FROM groups WHERE user_id='" . $_SESSION['user_id'] . "'");
                                while ($group = mysqli_fetch_assoc($q)) {
                                    echo "<option value=\"" . $group['group_id'] . "\">" . $group['group_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </li>
                        <li><input type="text" id="keyword" name="keyword" placeholder="Search Keyword" onchange="get_contacts(1,document.getElementById('per_page').value)"><i class="fas fa-search fa-s" onclick="get_contacts(1,document.getElementById('per_page').value)"></i></li>
                    </ul>
                </div>
            </div>
            <input type="checkbox" id="create_contact" name="create_contact">
            <div id="create_contact_modal">
                <div class="modal-wrapper">
                    <div class="modal-header">
                        <div class="modal-title">
                            New Contact
                        </div>
                        <div class="modal-close">
                            <label for="create_contact"><i class="fas fa-times fa-2x"></i></label>
                        </div>
                    </div>
                    <div class="modal-content" id="create_contact_modal_content">
                        <div class="form-field">
                            <label for="contact_phone_region">Country / number type</label>
                            <select name="contact_phone_region" id="contact_phone_region">
                                <option value="TZ">Tanzania (+255)</option>
                                <option value="KE">Kenya (+254)</option>
                                <option value="UG">Uganda (+256)</option>
                                <option value="OTHER">Other (full international digits)</option>
                            </select>
                            <small>SMS sending supports <strong>Tanzania (+255)</strong>, <strong>Kenya (+254)</strong>, and <strong>Uganda (+256)</strong>.</small>
                        </div>
                        <div class="form-field">
                            <label for="phone_number">Phone number</label>
                            <input type="text" name="phone_number" id="phone_number" placeholder="0742200333 or 255742200333">
                            <small>National 0… or 9 digits without code; or full number with country code (Other = international only).</small>
                        </div>
                        <div class="form-field">
                            <label for="">Contact Name</label>
                            <input type="text" name="contact_name" id="contact_name">
                        </div>
                        <div class="form-field">
                            <label for="">Email</label>
                            <input type="text" name="email" id="email">
                        </div>

                        <div class="form-field">
                            <div class="send-button" onclick="save_contact(document.getElementById('start_row').value,document.getElementById('per_page').value)"><i class="fas fa-save"></i>Save Contact</div>
                        </div>
                        <div class="form-field">
                            <div class="form-errors" id="contact_form_errors"></div>
                        </div>

                    </div>
                </div>
            </div>
            <input type="checkbox" id="create_group" name="create_group">
            <div id="create_group_modal">
                <div class="modal-wrapper">
                    <div class="modal-header">
                        <div class="modal-title">
                            New Group
                        </div>
                        <div class="modal-close">
                            <label for="create_group"><i class="fas fa-times fa-2x"></i></label>
                        </div>
                    </div>
                    <div class="modal-content" id="create_group_modal_content">
                        <div class="form-field">
                            <label for="">Group Name</label>
                            <input type="text" name="group_name" id="group_name">
                        </div>
                        <div class="form-field">
                            <div class="send-button" onclick="save_group()"><i class="fas fa-users"></i>Save Group</div>
                        </div>
                        <div class="form-field">
                            <div class="form-errors" id="group_form_errors"></div>
                        </div>

                    </div>
                </div>
            </div>
            <input type="checkbox" id="edit_contact" name="edit_contact">
            <div id="edit_contact_modal">

                <div class="modal-wrapper">
                    <div class="modal-header">
                        <div class="modal-title">
                            Edit Contact
                        </div>
                        <div class="modal-close">
                            <label for="edit_contact"><i class="fas fa-times fa-2x"></i></label>
                        </div>
                    </div>
                    <div class="modal-content" id="edit_contact_modal_content">
                        
                    </div>
                </div>
            </div>
            <input type="checkbox" id="import_contacts" name="import_contacts">
            <div id="import_contacts_modal">
                <div class="modal-wrapper">
                    <div class="modal-header">
                        <div class="modal-title">
                            Import Contacts
                        </div>
                        <div class="modal-close">
                            <label for="import_contacts"><i class="fas fa-times fa-2x"></i></label>
                        </div>
                    </div>
                    <div class="modal-content">
                        <form id="import_contacts_form" action="import_contacts.php" method="post" enctype="multipart/form-data">
                            <div class="form-field">
                                <label for="import_region">Country for numbers in file</label>
                                <select name="import_region" id="import_region">
                                    <option value="TZ">Tanzania (+255)</option>
                                    <option value="KE">Kenya (+254)</option>
                                    <option value="UG">Uganda (+256)</option>
                                    <option value="OTHER">Other (full international per row)</option>
                                </select>
                                <small>SMS sending supports +255 (TZ), +254 (KE), and +256 (UG).</small>
                            </div>
                            <div class="form-field">
                                <label for="contacts_file">Upload CSV/XLSX file</label>
                                <input type="file" name="contacts_file" id="contacts_file" accept=".csv,.xlsx,.xls">
                            </div>
                            <div class="form-field">
                                <small>Expected columns: Phone Number, Contact Name, Email</small>
                            </div>
                            <div class="form-field">
                                <div class="send-button" onclick="import_contacts_file()"><i class="fas fa-file-import"></i>Import</div>
                            </div>
                            <div class="form-field">
                                <div class="form-errors" id="import_form_errors"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="page-content" id="page-content">

            </div>
        </div>
    </div>
    <?php include "footer.php";?>
    <script>
        get_contacts(1, 10);

        setInputFilter(document.getElementById("phone_number"), function(value) {
            return /^-?\d*$/.test(value);
        });
    </script>
</body>

</html>
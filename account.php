<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
} else {
    $q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "'");
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
    <?php include __DIR__ . '/inc/head_brand_meta.php'; ?>
    <title><?php echo $app['app_name']; ?></title>
    <link rel="stylesheet" href="css/all.css?v=20260511">
    <link rel="stylesheet" href="css/style.css?v=20260511">
    <script src="js/script.js?v=20260511"></script>
</head>

<body>
    <?php include "header.php"; ?>
    <div class="container">
        <div class="menu">
            <?php include "menu.php"; ?>
        </div>
        <div class="content-wrapper">
            <div class="page-title">My Account</div>
            <div class="page-header">
                <div class="page-options">
                    <span class="account-name"><i class="fas fa-id-card fa-lg"></i><?php echo $user['client_name']; ?></span>
                </div>

                <ul class="page-menu">
                    <li><a href="signout.php"><i class="fas fa-lock fa-s"></i>Sign Out</a></li>
                </ul>
            </div>
            <input type="checkbox" name="change_password" id="change_password">
            <div id="change_password_modal">
                <div class="modal-wrapper">
                    <div class="modal-header">
                        <div class="modal-title">
                            Change Password
                        </div>
                        <div class="modal-close">
                            <label for="change_password"><i class="fas fa-times fa-2x"></i></label>
                        </div>
                    </div>
                    <div class="modal-content" id="change_password_modal_content">
                        <form id="password_form" action="change_password.php" method="post">
                            <div class="form-field">
                                <label for="">Current Password</label>
                                <input type="password" name="current_password" id="current_password" placeholder="">
                            </div>
                            <div class="form-field">
                                <label for="">New Password</label>
                                <input type="password" name="new_password" id="new_password">
                            </div>
                            <div class="form-field">
                                <label for="">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password">
                            </div>
                            <div class="form-field">
                                <div class="send-button" onclick="change_password()"><i class="fas fa-key"></i>Change Password</div>
                            </div>
                            <div class="form-field">
                                <div class="form-errors" id="form_errors"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="page-content" id="page-content">
                <?php
                if (isset($_GET["r"])) {
                    $flash = (string) $_GET["r"];
                    if ($flash === "key_ok") {
                        echo '<div class="account-flash account-flash-success">API key generated. Copy it below and store it somewhere safe.</div>';
                    } elseif ($flash === "key_err") {
                        echo '<div class="account-flash account-flash-error">Could not save API key. Ask your administrator to run <code>SmSver1/db/add_users_api_key.sql</code> on the database if the column is missing.</div>';
                    } elseif ($flash === "key_inactive") {
                        echo '<div class="account-flash account-flash-error">API keys are only available for active accounts.</div>';
                    }
                }
                ?>
                <div class="profile-info">
                    <div><label>Phone Number:</label></div>
                    <div><?php echo htmlspecialchars((string) $user['user_id'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="profile-info">
                    <div><label>Email:</label></div>
                    <div><?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="profile-info">
                    <div><label>Account Name:</label></div>
                    <div><?php echo htmlspecialchars((string) $user['client_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="profile-info">
                    <div><label>Status:</label></div>
                    <div><?php echo $user['status']; ?></div>
                </div>

                <div class="profile-info api-key-row">
                    <div><label>API Key:</label></div>
                    <div class="api-key-wrap">
                        <?php
                        if (!empty($user["api_key"])) {
                            $k = htmlspecialchars($user["api_key"], ENT_QUOTES, "UTF-8");
                            ?>
                            <code id="api_key_value" class="api-key-value"><?php echo $k; ?></code>
                            <button type="button" class="api-key-copy-btn" onclick="vllCopyApiKey()"><i class="fas fa-copy"></i> Copy</button>
                            <a class="api-key-regen" href="generate_api_key.php" onclick="return confirm('Regenerate API key? The current key will stop working immediately.');"><i class="fas fa-sync-alt"></i> Regenerate</a>
                            <?php
                        } else {
                            ?>
                            <span class="api-key-missing">No key yet.</span>
                            <a class="api-key-generate" href="generate_api_key.php" onclick="return confirm('Generate a new API key for integrations and external apps?');"><i class="fas fa-key"></i> Generate key</a>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <script>
                function vllCopyApiKey() {
                    var el = document.getElementById("api_key_value");
                    if (!el) { return; }
                    var text = (el.textContent || "").trim();
                    if (!text) { return; }
                    function done() { alert("API key copied to clipboard."); }
                    function fail() { window.prompt("Copy this API key:", text); }
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(done).catch(function() {
                            try {
                                var ta = document.createElement("textarea");
                                ta.value = text;
                                ta.style.position = "fixed";
                                ta.style.left = "-9999px";
                                document.body.appendChild(ta);
                                ta.select();
                                document.execCommand("copy");
                                document.body.removeChild(ta);
                                done();
                            } catch (e) { fail(); }
                        });
                    } else {
                        try {
                            var ta = document.createElement("textarea");
                            ta.value = text;
                            ta.style.position = "fixed";
                            ta.style.left = "-9999px";
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand("copy");
                            document.body.removeChild(ta);
                            done();
                        } catch (e) { fail(); }
                    }
                }
                </script>

                <div class="profile-info">
                    <div><label>Password:</label></div>
                    <div>
                        <label for="change_password">
                            <span class="change-password">
                                <i class="fas fa-edit fa-small"></i>Change Password
                            </span>
                        </label>
                        <?php
                        if (isset($_GET['r']) && $_GET['r'] == "Incorrect password") {
                        ?>
                            <span class="password-error">Incorrect Password</span>
                        <?php } ?>


                        <?php
                        if (isset($_GET['r']) && $_GET['r'] == "Invalid password") {
                        ?>
                            <span class="password-error">Invalid Password</span>
                        <?php } ?>

                        <?php
                        if (isset($_GET['r']) && $_GET['r'] == "Password changed") {
                        ?>
                            <span class="password-changed">Password changed</span>
                        <?php } ?>
                    </div>
                </div>

                <div class="form-field" style="margin:40px 0px;">
                    <label for="" style="font-weight:bold; font-size:1rem; margin:15px 0px;">Purchase History</label>
                    <table>
                        <tr class="table-header">
                            <td>Order No.</td>
                            <td>Date</td>
                            <td>Quantity</td>
                            <td style="text-align:right;">Price</td>
                            <td style="text-align:right;">Amount (TSH)</td>
                            <td style="text-align:center;">Status</td>
                            <td>Receipt No.</td>
                            <td>Payment Method</td>
                            <td></td>
                        </tr>
                        <?php
                        $q = mysqli_query($conn, "SELECT * FROM sms_orders WHERE user_id='" . $_SESSION['user_id'] . "' ORDER BY order_date DESC");

                        while ($order = mysqli_fetch_assoc($q)) {
                        ?>
                            <tr>
                                <td><?php echo $order['reference']; ?></td>
                                <td><?php echo date("d-m-Y", $order['order_date']); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td align="right"><?php echo round($order['price']); ?></td>
                                <td align="right"><?php echo number_format($order['amount']); ?></td>
                                <td align="center"><?php echo $order['order_status']; ?></td>
                                <td><?php echo $order['receipt']; ?></td>
                                <td><?php echo $order['payment_method']; ?></td>
                                <td>
                                    <?php
                                    if ($order['order_status'] == "Pending") {
                                        echo "<a href='pay.php?order_id=" . $order['order_id'] . "'><span class='pay-now'>Pay Now</span></a>";
                                    ?>
                                        <i class="fas fa-trash fa-lg" style="margin-left:15px; cursor:pointer;" onclick="delete_sms_order(<?php echo $order['order_id'];?>)"></i>
                                    <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include "footer.php"; ?>
</body>

</html>
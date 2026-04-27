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

$order_id = mysqli_real_escape_string($conn, $_GET['order_id']);
$q = mysqli_query($conn, "SELECT * FROM sms_orders WHERE order_id='" . $order_id . "'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:index.php");
}
$order = mysqli_fetch_assoc($q);


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
            <div class="page-title">How to Pay</div>
            <div class="page-header">
                <div class="page-options">
                    <div class="form-field">
                        <div class="order-summary">
                            <div>
                                <label>Order #:
                                    <span><?php echo $order['order_id']; ?></span>
                                </label>
                            </div>
                            <div>
                                <label>Amount:
                                    <span>TSH <?php echo number_format($order['amount']); ?></span>
                                </label>
                            </div>
                            <div>
                                <label>SMS Quantity:
                                    <span><?php echo number_format($order['quantity']); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-content" id="page-content">
                <div class="form-field">
                    <div class="payment-box">
                        <div class="payment-instructions">
                            <div class="payment-method">M-Pesa</div>
                            <div class="instructions">
                                <div>Dial <strong>*150*00#</strong></div>
                                <div>Select option <strong>4</strong> (Pay by M-Pesa)</div>
                                <div>Select option <strong>4</strong> (Enter Business Number)</div>
                                <div>Enter Business number <strong>977900</strong></div>
                                <div>Enter <strong><?php echo $order['order_id']; ?></strong> as the reference number</div>
                                <div>Enter <strong><?php echo round($order['amount']); ?></strong> as the amount </div>
                                <div>Enter your <strong>PIN</strong></div>
                            </div>
                        </div>
                        <div class="payment-instructions">
                            <div class="payment-method">TigoPesa</div>
                            <div class="instructions">
                                <div>Dial <strong>*150*01#</strong></div>
                                <div>Select option <strong>4</strong> (Pay Bill)</div>
                                <div>Select option <strong>3</strong> (Enter Business Number)</div>
                                <div>Enter Business number <strong>333200</strong></div>
                                <div>Enter <strong><?php echo $order['order_id']; ?></strong> as the reference number</div>
                                <div>Enter <strong><?php echo round($order['amount']); ?></strong> as the amount </div>
                                <div>Enter your <strong>PIN</strong></div>
                            </div>
                        </div>
                        <div class="payment-instructions">
                            <div class="payment-method">Airtel Money</div>
                            <div class="instructions">
                                <div>Dial <strong>*150*60#</strong></div>
                                <div>Select option <strong>5</strong> (Make Payments)</div>
                                <div>Select option <strong>4</strong> (Enter Business Number)</div>
                                <div>Enter Business number <strong>333200</strong></div>
                                <div>Enter <strong><?php echo round($order['amount']); ?></strong> as the amount </div>
                                <div>Enter <strong><?php echo $order['order_id']; ?></strong> as the reference number</div>
                                <div>Enter your <strong>PIN</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-field">
                    <input class="send-button" type="button" value="Confirm Payment" onclick="document.location.href='confirm_payment.php?order_id=<?php echo $order['order_id']; ?>'">
                </div>

            </div>
        </div>
    </div>
    <?php include "footer.php";?>
</body>

</html>
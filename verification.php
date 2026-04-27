<?php include "db/dblink.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app['app_name']; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js"></script>
</head>

<body>
    <form action="verify.php" method="post">
        <div class="login-form">
            <div class="form-title">Verify your Mobile Number</div>

            <div class="form-field">
                <label>
                    <div class="error-message"><?php echo $_GET['r']; ?></div>
                </label>
            </div>
            <div class="form-field">
                <label for="">We have sent your verification code by SMS to <b><?php echo $_SESSION['user_id']; ?></b></label>
            </div>
            <div class="form-field">
                <label for="user_id">Enter Verification Code:</label>
                <input type="text" id="vcode" name="vcode">
            </div>
            <div class="form-field">
                <input class="submit-button" type="submit" value="Submit">
            </div>
            <div class="form-field">
                <label for="">
                    Did not get verification code? <a href="resend_vcode.php">Resend Code</a>
                </label>
            </div>
        </div>
    </form>
</body>

</html>
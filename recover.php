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
    <form action="reset_password.php" method="post">
        <div class="login-form">
            <div class="form-title">Recover your account</div>

            <div class="form-field">
                <label>
                    <div class="error-message"><?php echo $_GET['r']; ?></div>
                </label>
            </div>
            <div class="form-field">
                <label for="">We have sent your reset code by SMS to <b><?php echo $_SESSION['temp_user_id']; ?></b></label>
            </div>
            <div class="form-field">
                <label for="user_id">Enter Reset Code:</label>
                <input type="text" id="rcode" name="rcode">
            </div>
            <div class="form-field">
                <input class="submit-button" type="submit" value="Submit">
            </div>
            <div class="form-field">
                <label for="">
                    Did not get your reset code? <a href="resend_rcode.php">Resend Code</a>
                </label>
            </div>
        </div>
    </form>
</body>

</html>
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
    <form id="password_form" action="save_password.php" method="post">
        <div class="login-form">
            <div class="form-title">Set your new password</div>
            <div class="form-field">
                <label for="password">New Password</label>
                <input type="password" id="new_password" name="new_password">
            </div>

            <div class="form-field">
                <label for="password">Repeat Password</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <div class="form-field">
                <input class="submit-button" type="button" value="Save Password" onclick="save_new_password()">
            </div>
            <div class="form-field">
                <div id="form_errors" class="form-errors"></div>
            </div>
        </div>
    </form>
</body>

</html>
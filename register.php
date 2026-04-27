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
    <form id="client_form" action="save_user.php?rid=Administrator" method="post">
        <div class="login-form">
            <div class="form-title">Create your new account</div>

            <div class="form-field">
                <label>
                    <div class="error-message"><?php echo $_GET['r']; ?></div>
                </label>
            </div>

            <div class="form-field">
                <label for="client_name">Your Name</label>
                <input type="text" id="client_name" name="client_name">
            </div>

            <div class="form-field">
                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number">
            </div>

            <div class="form-field">
                <label for="email">Email Address</label>
                <input type="text" id="email" name="email">
            </div>

            <div class="form-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username">
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input type="password" id="new_password" name="new_password">
            </div>

            <div class="form-field">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <div class="form-field">
                <input class="submit-button" type="button" value="Register" onclick="save_user()">
            </div>
            <div class="form-field">
                <label for="">
                    Already have an account? <a href="login.php">Sign In</a>
                </label>
            </div>
            <div class="form-errors" id="form_errors"></div>
        </div>
    </form>
</body>

</html>
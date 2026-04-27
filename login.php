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
    <form action="auth.php" method="post">
        <div class="login-form">
            <div class="form-title">Sign In to your account</div>

            <div class="form-field">
                <label>
                    <div class="error-message"><?php echo $_GET['r']; ?></div>
                </label>
            </div>
            <div class="form-field">
                <label for="user_id">Username</label>
                <input type="text" id="user_id" name="user_id">
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password">
            </div>

            <div class="form-field">
                <label for="password"></label>
                <label for="">
                    <a href="forgot.php">Forgot Password</a>
                </label>
            </div>

            <div class="form-field">
                <input class="submit-button" type="submit" value="Login">
            </div>
            <div class="form-field">
                <label for="">
                    Dont have an account? <a href="register.php">Sign Up</a>
                </label>
            </div>
        </div>
    </form>
</body>

</html>
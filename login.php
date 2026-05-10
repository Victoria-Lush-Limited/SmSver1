<?php
include "db/dblink.php";
$rmsg = isset($_GET['r']) ? (string) $_GET['r'] : '';
$login_help = '';
if ($conn && isset($app['login_help'])) {
    $login_help = trim((string) $app['login_help']);
}
$vll_page_description = 'Victoria Lush SMS portal — sign in to send bulk SMS, manage contacts, and campaigns.';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/inc/head_brand_meta.php'; ?>
    <title><?php echo $app['app_name']; ?></title>
    <link rel="stylesheet" href="css/style.css?v=20260511">
    <script src="js/script.js?v=20260511"></script>
</head>

<body>
    <form action="auth.php" method="post">
        <div class="login-form">
            <div class="form-title">Sign In to your account</div>

            <?php if ($login_help !== '') { ?>
            <div class="form-field">
                <div class="login-help-box"><?php echo nl2br(htmlspecialchars($login_help, ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
            <?php } ?>

            <div class="form-field">
                <label>
                    <div class="error-message"><?php echo htmlspecialchars($rmsg, ENT_QUOTES, 'UTF-8'); ?></div>
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
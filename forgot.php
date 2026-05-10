<?php
include "db/dblink.php";
$vll_page_description = 'Reset your Victoria Lush SMS account password.';
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
    <form action="send_rcode.php" method="post">
        <div class="login-form">
            <div class="form-title">Forgot your password?</div>
            <div class="form-field">
                <label for="">We will send you a reset code by SMS.</label>
            </div>
            <div class="form-field">
                <label for="user_id">Enter your user name:</label>
                <input type="text" id="username" name="username">
            </div>
            <div class="form-field">
                <input class="submit-button" type="submit" value="Submit">
            </div>
            <div class="form-errors">
                <?php if (isset($_GET['r']) && !empty($_GET['r'])) {
                    echo $_GET['r'];
                }
                ?>
            </div>
        </div>
    </form>
</body>

</html>
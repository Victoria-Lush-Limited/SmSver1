<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
    exit;
}
$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND status='Active'");
$found = mysqli_num_rows($q);
if (!$found) {
    header("location:signout.php");
    exit;
}
$user = mysqli_fetch_assoc($q);

$uid_esc = mysqli_real_escape_string($conn, $_SESSION["user_id"]);
$sms_balance = 0;
$cnt_pending = 0;
$cnt_scheduled = 0;
$cnt_sent_7d = 0;
$cnt_contacts = 0;
$cnt_templates = 0;
$now_ts = time();
$week_ago = $now_ts - (7 * 86400);

if ($conn) {
    $b = mysqli_query($conn, "SELECT (SUM(allocated)-SUM(consumed)) AS b FROM transactions WHERE user_id='" . $uid_esc . "'");
    if ($b) {
        $br = mysqli_fetch_assoc($b);
        $sms_balance = (float) ($br["b"] ?? 0);
    }
    $p = mysqli_query($conn, "SELECT COUNT(*) AS c FROM outgoing WHERE user_id='" . $uid_esc . "' AND sms_status='Pending' AND date_created<='" . (int) $now_ts . "'");
    if ($p) {
        $cnt_pending = (int) (mysqli_fetch_assoc($p)["c"] ?? 0);
    }
    $s = mysqli_query($conn, "SELECT COUNT(*) AS c FROM outgoing WHERE user_id='" . $uid_esc . "' AND sms_status='Pending' AND date_created>'" . (int) $now_ts . "'");
    if ($s) {
        $cnt_scheduled = (int) (mysqli_fetch_assoc($s)["c"] ?? 0);
    }
    $d = mysqli_query($conn, "SELECT COUNT(*) AS c FROM outgoing WHERE user_id='" . $uid_esc . "' AND date_created>='" . (int) $week_ago . "' AND sms_status IN ('Sent','Delivered')");
    if ($d) {
        $cnt_sent_7d = (int) (mysqli_fetch_assoc($d)["c"] ?? 0);
    }
    $c = mysqli_query($conn, "SELECT COUNT(*) AS c FROM contacts WHERE user_id='" . $uid_esc . "'");
    if ($c) {
        $cnt_contacts = (int) (mysqli_fetch_assoc($c)["c"] ?? 0);
    }
    $t = mysqli_query($conn, "SELECT COUNT(*) AS c FROM templates WHERE user_id='" . $uid_esc . "'");
    if ($t) {
        $cnt_templates = (int) (mysqli_fetch_assoc($t)["c"] ?? 0);
    }
}
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
            <div class="page-title">Dashboard</div>
            <div class="page-content dashboard-page">
                <p class="dashboard-welcome">Hello, <strong><?php echo htmlspecialchars($user["client_name"], ENT_QUOTES, "UTF-8"); ?></strong>. Here is a quick snapshot of your account.</p>
                <div class="dashboard-grid">
                    <a class="dash-card" href="compose.php">
                        <div class="dash-icon"><i class="fas fa-wallet"></i></div>
                        <div class="dash-label">SMS balance</div>
                        <div class="dash-value"><?php echo number_format($sms_balance); ?></div>
                        <div class="dash-hint">Credits available to send</div>
                    </a>
                    <a class="dash-card" href="history.php">
                        <div class="dash-icon"><i class="fas fa-paper-plane"></i></div>
                        <div class="dash-label">Sent (7 days)</div>
                        <div class="dash-value"><?php echo number_format($cnt_sent_7d); ?></div>
                        <div class="dash-hint">Delivered / sent segments</div>
                    </a>
                    <a class="dash-card" href="history.php">
                        <div class="dash-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="dash-label">Queued to send</div>
                        <div class="dash-value"><?php echo number_format($cnt_pending); ?></div>
                        <div class="dash-hint">Pending &amp; due now or past</div>
                    </a>
                    <a class="dash-card" href="scheduled.php">
                        <div class="dash-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="dash-label">Scheduled ahead</div>
                        <div class="dash-value"><?php echo number_format($cnt_scheduled); ?></div>
                        <div class="dash-hint">Future-dated pending</div>
                    </a>
                    <a class="dash-card" href="contacts.php">
                        <div class="dash-icon"><i class="fas fa-address-book"></i></div>
                        <div class="dash-label">Contacts</div>
                        <div class="dash-value"><?php echo number_format($cnt_contacts); ?></div>
                        <div class="dash-hint">Manage lists &amp; groups</div>
                    </a>
                    <a class="dash-card" href="templates.php">
                        <div class="dash-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="dash-label">Templates</div>
                        <div class="dash-value"><?php echo number_format($cnt_templates); ?></div>
                        <div class="dash-hint">Reusable messages</div>
                    </a>
                </div>
                <div class="dashboard-actions">
                    <a class="dash-action-btn" href="compose.php"><i class="fas fa-envelope"></i> Compose SMS</a>
                    <a class="dash-action-btn" href="file_sms.php"><i class="fas fa-mail-bulk"></i> File SMS</a>
                    <a class="dash-action-btn" href="account.php"><i class="fas fa-user-cog"></i> My account</a>
                </div>
            </div>
        </div>
    </div>
    <?php include "footer.php";?>
</body>

</html>
<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
} else {
    $q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $_SESSION['user_id'] . "' AND status='Active'");
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

$dash_uid = mysqli_real_escape_string($conn, (string) $_SESSION['user_id']);
$now_ts = time();
$month_start = strtotime(date('Y-m-01 00:00:00'));

include_once __DIR__ . '/inc/ledger_balance.php';
$sms_balance = vll_ledger_balance_for_user($conn, isset($user) ? $user : null);

$dash_counts = array(
    'contacts' => 0,
    'groups' => 0,
    'templates' => 0,
    'senders' => 0,
    'month_sms' => 0,
    'scheduled' => 0,
    'custom_queue' => 0,
);
$qc = mysqli_query($conn, "SELECT COUNT(*) AS c FROM contacts WHERE user_id='" . $dash_uid . "'");
if ($qc && ($r = mysqli_fetch_assoc($qc))) {
    $dash_counts['contacts'] = (int) $r['c'];
}
$qc = mysqli_query($conn, "SELECT COUNT(*) AS c FROM groups WHERE user_id='" . $dash_uid . "'");
if ($qc && ($r = mysqli_fetch_assoc($qc))) {
    $dash_counts['groups'] = (int) $r['c'];
}
$qc = mysqli_query($conn, "SELECT COUNT(*) AS c FROM templates WHERE user_id='" . $dash_uid . "'");
if ($qc && ($r = mysqli_fetch_assoc($qc))) {
    $dash_counts['templates'] = (int) $r['c'];
}
$qc = mysqli_query($conn, "SELECT COUNT(*) AS c FROM senders WHERE user_id='" . $dash_uid . "'");
if ($qc && ($r = mysqli_fetch_assoc($qc))) {
    $dash_counts['senders'] = (int) $r['c'];
}
$qc = mysqli_query($conn, "SELECT COUNT(*) AS c FROM outgoing WHERE user_id='" . $dash_uid . "' AND date_created>='" . (int) $month_start . "' AND date_created<='" . (int) $now_ts . "'");
if ($qc && ($r = mysqli_fetch_assoc($qc))) {
    $dash_counts['month_sms'] = (int) $r['c'];
}
$qc = mysqli_query($conn, "SELECT COUNT(*) AS c FROM outgoing WHERE user_id='" . $dash_uid . "' AND date_created>'" . (int) $now_ts . "'");
if ($qc && ($r = mysqli_fetch_assoc($qc))) {
    $dash_counts['scheduled'] = (int) $r['c'];
}
$qc = mysqli_query($conn, "SELECT COUNT(*) AS c FROM custom_sms WHERE user_id='" . $dash_uid . "'");
if ($qc && ($r = mysqli_fetch_assoc($qc))) {
    $dash_counts['custom_queue'] = (int) $r['c'];
}
$vll_page_description = 'Victoria Lush SMS dashboard — credits, compose, contacts, and message history.';
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
        <div class="content-wrapper dashboard-page">
            <div class="page-title">Dashboard</div>
            <p class="dashboard-welcome">Welcome back, <?php echo htmlspecialchars($user['client_name'], ENT_QUOTES, 'UTF-8'); ?>. Use the overview below to move through compose, contacts, history, and account tasks in one place.</p>

            <div class="dashboard-grid">
                <a href="account.php" class="dash-card dash-card-link">
                    <div class="dash-icon"><i class="fas fa-coins"></i></div>
                    <div class="dash-label">SMS credits</div>
                    <div class="dash-value"><?php echo number_format($sms_balance); ?></div>
                    <div class="dash-hint">Buy or review usage in My Account.</div>
                </a>
                <a href="contacts.php" class="dash-card dash-card-link">
                    <div class="dash-icon"><i class="fas fa-address-book"></i></div>
                    <div class="dash-label">Contacts</div>
                    <div class="dash-value"><?php echo number_format($dash_counts['contacts']); ?></div>
                    <div class="dash-hint">Manage numbers and groups for sending.</div>
                </a>
                <a href="contacts.php" class="dash-card dash-card-link">
                    <div class="dash-icon"><i class="fas fa-users"></i></div>
                    <div class="dash-label">Groups</div>
                    <div class="dash-value"><?php echo number_format($dash_counts['groups']); ?></div>
                    <div class="dash-hint">Organise lists for bulk messaging.</div>
                </a>
                <a href="templates.php" class="dash-card dash-card-link">
                    <div class="dash-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="dash-label">Templates</div>
                    <div class="dash-value"><?php echo number_format($dash_counts['templates']); ?></div>
                    <div class="dash-hint">Save and reuse common messages.</div>
                </a>
                <a href="senders.php" class="dash-card dash-card-link">
                    <div class="dash-icon"><i class="fas fa-id-card"></i></div>
                    <div class="dash-label">Sender IDs</div>
                    <div class="dash-value"><?php echo number_format($dash_counts['senders']); ?></div>
                    <div class="dash-hint">Your registered sender names.</div>
                </a>
                <a href="history.php" class="dash-card dash-card-link">
                    <div class="dash-icon"><i class="fas fa-paper-plane"></i></div>
                    <div class="dash-label">Messages this month</div>
                    <div class="dash-value"><?php echo number_format($dash_counts['month_sms']); ?></div>
                    <div class="dash-hint">Outgoing rows logged in the current month.</div>
                </a>
                <a href="scheduled.php" class="dash-card dash-card-link">
                    <div class="dash-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="dash-label">Scheduled</div>
                    <div class="dash-value"><?php echo number_format($dash_counts['scheduled']); ?></div>
                    <div class="dash-hint">Future-dated messages in the queue.</div>
                </a>
                <a href="file_sms.php" class="dash-card dash-card-link">
                    <div class="dash-icon"><i class="fas fa-mail-bulk"></i></div>
                    <div class="dash-label">File SMS queue</div>
                    <div class="dash-value"><?php echo number_format($dash_counts['custom_queue']); ?></div>
                    <div class="dash-hint">Custom or file-based sends waiting in the list.</div>
                </a>
            </div>

            <div class="dashboard-section">
                <div class="dashboard-section-title">Suggested flow</div>
                <div class="dashboard-actions">
                    <a class="dash-action-btn" href="compose.php"><i class="fas fa-envelope"></i> Compose SMS</a>
                    <a class="dash-action-btn dash-action-btn--neutral" href="contacts.php"><i class="fas fa-user-plus"></i> Manage contacts</a>
                    <a class="dash-action-btn dash-action-btn--neutral" href="history.php"><i class="fas fa-history"></i> View history</a>
                    <a class="dash-action-btn dash-action-btn--neutral" href="social_checks.php"><i class="fas fa-hashtag"></i> Social checks</a>
                    <a class="dash-action-btn dash-action-btn--neutral" href="account.php"><i class="fas fa-user-cog"></i> My account</a>
                </div>
            </div>
        </div>
    </div>
    <?php include "footer.php";?>
</body>

</html>

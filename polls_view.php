<?php
include "db/dblink.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("location:signout.php");
    exit;
}

$uid = mysqli_real_escape_string($conn, (string) $_SESSION['user_id']);
$q = mysqli_query($conn, "SELECT * FROM users WHERE user_id='" . $uid . "' AND status='Active'");
if (!$q || mysqli_num_rows($q) < 1) {
    header("location:signout.php");
    exit;
}

$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'audience_polls'");
if (!$chk || mysqli_num_rows($chk) < 1) {
    header("location:polls.php?r=" . urlencode("Polls table is missing."));
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
    header("location:polls.php?r=" . urlencode("Invalid poll."));
    exit;
}

$rs = mysqli_query(
    $conn,
    "SELECT id, title, opt1, opt2, opt3, opt4, started_at_ms, ended_at_ms, active, tallies_json, created_at, updated_at " .
    "FROM audience_polls WHERE id='" . $id . "' AND user_id='" . $uid . "' LIMIT 1"
);
if (!$rs || mysqli_num_rows($rs) < 1) {
    header("location:polls.php?r=" . urlencode("Poll not found."));
    exit;
}
$row = mysqli_fetch_assoc($rs);

function polls_view_ms($ms)
{
    if ($ms === null || $ms === '') {
        return '';
    }
    $n = (int) $ms;
    if ($n < 1) {
        return '';
    }
    return date('Y-m-d H:i:s', (int) floor($n / 1000));
}

$talliesPretty = '';
$raw = $row['tallies_json'] ?? '';
if ($raw !== null && $raw !== '') {
    $decoded = json_decode((string) $raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $talliesPretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        $talliesPretty = (string) $raw;
    }
}

$opts = array();
foreach (array('opt1', 'opt2', 'opt3', 'opt4') as $k) {
    $v = trim((string) ($row[$k] ?? ''));
    if ($v !== '') {
        $opts[] = $v;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (file_exists(__DIR__ . '/inc/head_brand_meta.php')) { include __DIR__ . '/inc/head_brand_meta.php'; } ?>
    <title><?php echo $app['app_name']; ?></title>
    <link rel="stylesheet" href="css/all.css?v=20260511">
    <link rel="stylesheet" href="css/style.css?v=20260511">
</head>
<body>
<?php include "header.php"; ?>
<div class="container">
    <div class="menu"><?php include "menu.php"; ?></div>
    <div class="content-wrapper">
        <div class="page-title">Poll #<?php echo (int) $row['id']; ?></div>
        <p style="margin:0 0 14px 0;"><a href="polls.php">&larr; Back to audience polls</a></p>

        <div class="incoming-card" style="margin-bottom:12px;">
            <h3 class="incoming-card-title"><?php echo htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p style="margin:0 0 8px 0;">
                <strong>Status:</strong>
                <?php echo ((int) ($row['active'] ?? 0) === 1) ? 'Live' : 'Ended'; ?>
            </p>
            <p style="margin:0 0 8px 0;"><strong>Started (local):</strong> <?php echo htmlspecialchars(polls_view_ms($row['started_at_ms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin:0 0 8px 0;"><strong>Ended (local):</strong> <?php echo htmlspecialchars(polls_view_ms($row['ended_at_ms'] ?? null), ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin:0;"><strong>Row timestamps:</strong> <?php echo htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) ($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="incoming-card" style="margin-bottom:12px;">
            <h3 class="incoming-card-title">Options</h3>
            <ol style="margin:0; padding-left:1.2em;">
                <?php foreach ($opts as $o) { ?>
                    <li><?php echo htmlspecialchars($o, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php } ?>
            </ol>
        </div>

        <div class="incoming-card">
            <h3 class="incoming-card-title">Tallies (JSON)</h3>
            <?php if ($talliesPretty === '') { ?>
                <p style="margin:0; color:#666;">No tallies stored yet.</p>
            <?php } else { ?>
                <pre style="margin:0; white-space:pre-wrap; word-break:break-word; font-size:13px; background:#f7f7f7; padding:12px; border-radius:6px;"><?php echo htmlspecialchars($talliesPretty, ENT_QUOTES, 'UTF-8'); ?></pre>
            <?php } ?>
        </div>
    </div>
</div>
<?php include "footer.php"; ?>
</body>
</html>

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
$user = mysqli_fetch_assoc($q);

function polls_has_table($conn)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = false;
    $t = @mysqli_query($conn, "SHOW TABLES LIKE 'audience_polls'");
    if ($t && mysqli_num_rows($t) > 0) {
        $cache = true;
    }
    return $cache;
}

function polls_ms_to_local($ms)
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

function polls_format_tallies($jsonRaw)
{
    if ($jsonRaw === null || $jsonRaw === '') {
        return '';
    }
    $j = json_decode((string) $jsonRaw, true);
    if (!is_array($j) || count($j) < 1) {
        return (string) $jsonRaw;
    }
    $parts = array();
    foreach ($j as $k => $v) {
        $parts[] = (string) $k . ':' . (string) $v;
    }
    return implode(', ', $parts);
}

$hasTable = polls_has_table($conn);

$state = isset($_GET['state']) ? trim((string) $_GET['state']) : 'all';
if ($state !== 'active' && $state !== 'ended' && $state !== 'all') {
    $state = 'all';
}

$where = " WHERE user_id='" . $uid . "'";
if ($state === 'active') {
    $where .= " AND active='1'";
} elseif ($state === 'ended') {
    $where .= " AND active='0'";
}

$rows = array();
if ($hasTable) {
    $rs = mysqli_query(
        $conn,
        "SELECT id, title, opt1, opt2, opt3, opt4, started_at_ms, ended_at_ms, active, tallies_json, created_at " .
        "FROM audience_polls " . $where . " ORDER BY id DESC LIMIT 500"
    );
    if ($rs) {
        while ($r = mysqli_fetch_assoc($rs)) {
            $rows[] = $r;
        }
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
        <div class="page-title">Audience polls (reports)</div>
        <p class="page-intro" style="margin:0 0 12px 0; color:#444; max-width:720px;">
            Live polls are created in the <strong>VLL SMS</strong> app (<code>vll_sms</code>); ended polls sync here with optional vote tallies. Use this screen for reporting, CSV export, and housekeeping deletes.
        </p>
        <?php if (isset($_GET['r']) && $_GET['r'] !== '') { ?>
            <?php $pollsFlashOk = isset($_GET['t']) && $_GET['t'] === 'ok'; ?>
            <div class="<?php echo $pollsFlashOk ? 'message-sent' : 'message-failed'; ?>"><?php echo htmlspecialchars((string) $_GET['r'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if (!$hasTable) { ?>
            <div class="message-failed">Polls storage is missing. Run <code>php artisan migrate</code> on vll_backend (same database as this portal), or execute <code>SmSver1/db/ensure_app_bridge_tables.sql</code> (or <code>SmSver1/db/create_audience_polls_table.sql</code>) on this database.</div>
        <?php } ?>

        <?php if ($hasTable) { ?>
        <form method="get" action="polls.php" class="page-form" style="margin-bottom:12px;">
            <div class="form-field">
                <div class="field-label">Status:</div>
                <div class="custom-input-wrapper">
                    <select name="state">
                        <option value="all" <?php echo $state === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="active" <?php echo $state === 'active' ? 'selected' : ''; ?>>Live only</option>
                        <option value="ended" <?php echo $state === 'ended' ? 'selected' : ''; ?>>Ended only</option>
                    </select>
                </div>
            </div>
            <div class="form-field"><div class="field-label"></div><div class="custom-input-wrapper"><button class="send-button" type="submit">Apply</button></div></div>
        </form>

        <div class="incoming-actions-bar" style="margin-bottom:12px;">
            <a href="polls_export.php?state=<?php echo urlencode($state); ?>" class="send-button incoming-action-btn">Download UTF-8 CSV (Excel)</a>
        </div>

        <form method="post" action="polls_actions.php">
            <input type="hidden" name="state" value="<?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="incoming-card" style="margin-bottom:10px;">
                <h3 class="incoming-card-title">Stored polls</h3>
                <p style="margin:0;">Select rows to bulk delete, or use the menu on each row. Deleting here removes the row from the shared database.</p>
            </div>
            <div class="incoming-table-wrap">
                <table class="incoming-table">
                    <tr class="incoming-table-header">
                        <td><input type="checkbox" onclick="for(const c of document.querySelectorAll('.poll-sel')){c.checked=this.checked;}"></td>
                        <td>ID</td>
                        <td>Title</td>
                        <td>Options</td>
                        <td>Started</td>
                        <td>Ended</td>
                        <td>Status</td>
                        <td>Tallies</td>
                        <td>Actions</td>
                    </tr>
                    <?php if (count($rows) === 0) { ?>
                        <tr><td colspan="9" class="incoming-empty">No polls found.</td></tr>
                    <?php } ?>
                    <?php foreach ($rows as $r) { ?>
                        <tr class="incoming-row">
                            <td><input class="poll-sel" type="checkbox" name="selected_ids[]" value="<?php echo (int) $r['id']; ?>"></td>
                            <td><a href="polls_view.php?id=<?php echo (int) $r['id']; ?>"><?php echo (int) $r['id']; ?></a></td>
                            <td><?php echo htmlspecialchars((string) $r['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="max-width:220px;font-size:12px;">
                                <?php
                                $opts = array_filter(array(
                                    (string) $r['opt1'],
                                    (string) $r['opt2'],
                                    trim((string) ($r['opt3'] ?? '')),
                                    trim((string) ($r['opt4'] ?? '')),
                                ), function ($x) {
                                    return $x !== '';
                                });
                                $i = 0;
                                foreach ($opts as $o) {
                                    $i++;
                                    echo '<div><b>' . (int) $i . '.</b> ' . htmlspecialchars($o, ENT_QUOTES, 'UTF-8') . '</div>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars(polls_ms_to_local($r['started_at_ms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(polls_ms_to_local($r['ended_at_ms'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo ((int) ($r['active'] ?? 0) === 1) ? 'Live' : 'Ended'; ?></td>
                            <td style="max-width:180px;font-size:12px;"><?php echo htmlspecialchars(polls_format_tallies($r['tallies_json'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <button class="incoming-delete-btn" type="submit" name="action" value="delete_one" formaction="polls_actions.php" onclick="document.getElementById('poll_delete_id').value='<?php echo (int) $r['id']; ?>';">Delete</button>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <input type="hidden" id="poll_delete_id" name="id" value="">
            <div style="margin-top:10px;">
                <button class="send-button incoming-action-btn" type="submit" name="action" value="delete_selected">Delete selected</button>
            </div>
        </form>
        <?php } ?>
    </div>
</div>
<?php include "footer.php"; ?>
</body>
</html>

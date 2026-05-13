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

function social_return_filters_from_post()
{
    $q = array();
    if (isset($_POST['ret_platform'])) {
        $p = strtolower(trim((string) $_POST['ret_platform']));
        if (in_array($p, array('facebook', 'instagram', 'whatsapp', 'telegram', 'x'), true)) {
            $q['platform'] = $p;
        }
    }
    if (isset($_POST['ret_phone'])) {
        $ph = preg_replace('/\D+/', '', (string) $_POST['ret_phone']);
        if ($ph !== '') {
            $q['phone'] = $ph;
        }
    }
    return $q;
}

function social_redirect($msg, $ok = false, $preserved = array())
{
    $q = $preserved;
    if ($msg !== '') {
        $q['r'] = $msg;
        if ($ok) {
            $q['t'] = 'ok';
        }
    }
    $tail = count($q) ? ('?' . http_build_query($q)) : '';
    header('location:social_checks.php' . $tail);
    exit;
}

function social_has_table($conn)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = false;
    $q = @mysqli_query($conn, "SHOW TABLES LIKE 'social_check_results'");
    if ($q && mysqli_num_rows($q) > 0) {
        $cache = true;
    }
    return $cache;
}

$hasTable = social_has_table($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ret = social_return_filters_from_post();
    if (!$hasTable) {
        social_redirect("Social checks table is missing. Run backend migrations first.", false, $ret);
    }

    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

    if ($action === 'delete_one') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            social_redirect("Invalid record id.", false, $ret);
        }
        mysqli_query($conn, "DELETE FROM social_check_results WHERE id='" . $id . "' AND user_id='" . $uid . "' LIMIT 1");
        social_redirect("Social check record deleted.", true, $ret);
    }

    if ($action === 'delete_selected') {
        if (empty($_POST['selected_ids']) || !is_array($_POST['selected_ids'])) {
            social_redirect("Select records to delete.", false, $ret);
        }
        $ids = array_map('intval', $_POST['selected_ids']);
        $ids = array_filter($ids, function ($v) { return $v > 0; });
        if (count($ids) < 1) {
            social_redirect("Select valid records.", false, $ret);
        }
        $csv = implode(',', $ids);
        mysqli_query($conn, "DELETE FROM social_check_results WHERE user_id='" . $uid . "' AND id IN (" . $csv . ")");
        $deleted = (int) mysqli_affected_rows($conn);
        social_redirect("Deleted " . $deleted . " social check record(s).", true, $ret);
    }

    social_redirect("Unsupported action.", false, $ret);
}

$platformFilter = isset($_GET['platform']) ? trim((string) $_GET['platform']) : '';
$phoneFilter = preg_replace('/\D+/', '', (string) ($_GET['phone'] ?? ''));
$where = " WHERE user_id='" . $uid . "'";
if ($platformFilter !== '') {
    $where .= " AND platform='" . mysqli_real_escape_string($conn, strtolower($platformFilter)) . "'";
}
if ($phoneFilter !== '') {
    $where .= " AND phone_number LIKE '%" . mysqli_real_escape_string($conn, $phoneFilter) . "%'";
}

$rows = array();
if ($hasTable) {
    $rs = mysqli_query($conn, "SELECT id, phone_number, platform, status, is_found, profile_name, profile_url, checked_at FROM social_check_results " . $where . " ORDER BY checked_at DESC, id DESC LIMIT 500");
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
    <title><?php echo $app['app_name']; ?></title>
    <link rel="stylesheet" href="css/all.css?v=20260511">
    <link rel="stylesheet" href="css/style.css?v=20260511">
</head>
<body>
<?php include "header.php"; ?>
<div class="container">
    <div class="menu"><?php include "menu.php"; ?></div>
    <div class="content-wrapper">
        <div class="page-title">Social Checks</div>
        <p class="page-intro" style="margin:0 0 12px 0; color:#444; max-width:720px;">
            New lookups and edits run in the <strong>VLL SMS</strong> app (<code>vll_sms</code>). This page is the archive: filter, review outcomes, export from your reporting tools if needed, and delete rows for housekeeping.
        </p>
        <?php if (isset($_GET['r']) && $_GET['r'] !== '') { ?>
            <?php $socialFlashOk = isset($_GET['t']) && $_GET['t'] === 'ok'; ?>
            <div class="<?php echo $socialFlashOk ? 'message-sent' : 'message-failed'; ?>"><?php echo htmlspecialchars((string) $_GET['r'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if (!$hasTable) { ?>
            <div class="message-failed">Social checks storage is missing. Run backend migration for <code>social_check_results</code>.</div>
        <?php } ?>
        <div class="incoming-card" style="margin-bottom:12px;">
            <h3 class="incoming-card-title">Data source</h3>
            <p style="margin:0;">Rows are written when checks complete in the app or through automated services connected to your workspace.</p>
        </div>

        <form method="get" action="social_checks.php" class="page-form" style="margin-bottom:12px;">
            <div class="form-field">
                <div class="field-label">Platform:</div>
                <div class="custom-input-wrapper">
                    <select name="platform">
                        <option value="">All</option>
                        <?php foreach (array('facebook','instagram','whatsapp','telegram','x') as $p) { ?>
                            <option value="<?php echo $p; ?>" <?php echo $platformFilter === $p ? 'selected' : ''; ?>><?php echo ucfirst($p); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-field">
                <div class="field-label">Phone contains:</div>
                <div class="custom-input-wrapper"><input type="text" name="phone" value="<?php echo htmlspecialchars($phoneFilter, ENT_QUOTES, 'UTF-8'); ?>"></div>
            </div>
            <div class="form-field"><div class="field-label"></div><div class="custom-input-wrapper"><button class="send-button" type="submit">Apply</button></div></div>
        </form>

        <form method="post" action="social_checks.php">
            <input type="hidden" name="ret_platform" value="<?php echo htmlspecialchars($platformFilter, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ret_phone" value="<?php echo htmlspecialchars($phoneFilter, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="incoming-card" style="margin-top:14px; margin-bottom:10px;">
                <h3 class="incoming-card-title">Stored results</h3>
                <p style="margin:0;">Use checkboxes for bulk delete, or delete a single row. Clearing here does not remove history already exported to your reports.</p>
            </div>
            <div class="incoming-table-wrap" style="margin-top:14px;">
                <table class="incoming-table">
                    <tr class="incoming-table-header">
                        <td><input type="checkbox" onclick="for(const c of document.querySelectorAll('.sc-sel')){c.checked=this.checked;}"></td>
                        <td>ID</td>
                        <td>Phone</td>
                        <td>Platform</td>
                        <td>Status</td>
                        <td>Found</td>
                        <td>Name</td>
                        <td>URL</td>
                        <td>Checked At</td>
                        <td>Actions</td>
                    </tr>
                    <?php if (count($rows) === 0) { ?>
                        <tr><td colspan="10" class="incoming-empty">No social check records found.</td></tr>
                    <?php } ?>
                    <?php foreach ($rows as $r) { ?>
                        <tr class="incoming-row">
                            <td><input class="sc-sel" type="checkbox" name="selected_ids[]" value="<?php echo (int) $r['id']; ?>"></td>
                            <td><?php echo (int) $r['id']; ?></td>
                            <td><?php echo htmlspecialchars((string) $r['phone_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $r['platform'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $r['is_found'] === null ? 'Unknown' : ((int) $r['is_found'] === 1 ? 'Yes' : 'No'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($r['profile_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($r['profile_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($r['checked_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <button class="incoming-delete-btn" type="submit" name="action" value="delete_one" onclick="document.getElementById('delete_id').value='<?php echo (int) $r['id']; ?>';">Delete</button>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <input type="hidden" id="delete_id" name="id" value="">
            <div style="margin-top:10px;">
                <button class="send-button incoming-action-btn" type="submit" name="action" value="delete_selected">Delete Selected</button>
            </div>
        </form>
    </div>
</div>
<?php include "footer.php"; ?>
</body>
</html>

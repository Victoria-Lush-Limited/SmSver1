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

function social_redirect($msg)
{
    header("location:social_checks.php?r=" . urlencode($msg));
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

$socialPlatforms = array('facebook', 'instagram', 'whatsapp', 'telegram', 'x');

function social_probe_platform($platform, $phone)
{
    $profileUrl = null;
    if ($platform === 'whatsapp') {
        $profileUrl = 'https://wa.me/' . $phone;
    } elseif ($platform === 'telegram') {
        $profileUrl = 'https://t.me/+' . $phone;
    } elseif ($platform === 'facebook') {
        $profileUrl = 'https://www.facebook.com/search/top/?q=' . urlencode($phone);
    } elseif ($platform === 'instagram') {
        $profileUrl = 'https://www.instagram.com/';
    } elseif ($platform === 'x') {
        $profileUrl = 'https://x.com/search?q=' . urlencode($phone);
    }

    return array(
        'status' => 'not_configured',
        'is_found' => null,
        'profile_name' => null,
        'profile_url' => $profileUrl,
    );
}

function social_insert_probe($conn, $uid, $phone, $platform, $probe)
{
    $uidEsc = mysqli_real_escape_string($conn, $uid);
    $phoneEsc = mysqli_real_escape_string($conn, $phone);
    $platformEsc = mysqli_real_escape_string($conn, $platform);
    $statusEsc = mysqli_real_escape_string($conn, (string) ($probe['status'] ?? 'checked'));
    $foundSql = "NULL";
    if (isset($probe['is_found']) && ($probe['is_found'] === 0 || $probe['is_found'] === 1 || $probe['is_found'] === true || $probe['is_found'] === false)) {
        $foundSql = ((int) ((bool) $probe['is_found'])) === 1 ? "'1'" : "'0'";
    }
    $nameSql = empty($probe['profile_name']) ? "NULL" : ("'" . mysqli_real_escape_string($conn, (string) $probe['profile_name']) . "'");
    $urlSql = empty($probe['profile_url']) ? "NULL" : ("'" . mysqli_real_escape_string($conn, (string) $probe['profile_url']) . "'");

    mysqli_query(
        $conn,
        "INSERT INTO social_check_results(user_id,phone_number,platform,status,is_found,profile_name,profile_url,metadata,checked_at,created_at,updated_at) VALUES('" .
        $uidEsc . "','" . $phoneEsc . "','" . $platformEsc . "','" . $statusEsc . "'," . $foundSql . "," . $nameSql . "," . $urlSql . ",NULL,NOW(),NOW(),NOW())"
    );
}

function social_backend_base_url()
{
    $v = getenv('VLL_BACKEND_API_BASE');
    if ($v === false || $v === null || trim((string) $v) === '') {
        return 'http://127.0.0.1:8010/api/v1';
    }
    return rtrim(trim((string) $v), '/');
}

function social_backend_token()
{
    $v = getenv('VLL_BACKEND_SOCIAL_TOKEN');
    if ($v === false || $v === null) {
        return '';
    }
    return trim((string) $v);
}

function social_backend_call($endpoint, $payload)
{
    $token = social_backend_token();
    if ($token === '') {
        return array('ok' => false, 'code' => 0, 'data' => null, 'error' => 'backend_token_missing');
    }

    $url = social_backend_base_url() . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $raw === null || $raw === '') {
        return array('ok' => false, 'code' => $code, 'data' => null, 'error' => $err !== '' ? $err : 'empty_response');
    }
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return array('ok' => false, 'code' => $code, 'data' => null, 'error' => 'invalid_json');
    }
    $ok = ($code >= 200 && $code < 300 && isset($json['success']) && $json['success'] === true);
    return array('ok' => $ok, 'code' => $code, 'data' => $json, 'error' => $ok ? '' : 'backend_failed');
}

$hasTable = social_has_table($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$hasTable) {
        social_redirect("Social checks table is missing. Run backend migrations first.");
    }

    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

    if ($action === 'create') {
        $phone = preg_replace('/\D+/', '', (string) ($_POST['phone_number'] ?? ''));
        $platform = strtolower(trim((string) ($_POST['platform'] ?? '')));
        $status = trim((string) ($_POST['status'] ?? 'checked'));
        $isFound = isset($_POST['is_found']) && $_POST['is_found'] !== '' ? (int) $_POST['is_found'] : null;
        $profileName = trim((string) ($_POST['profile_name'] ?? ''));
        $profileUrl = trim((string) ($_POST['profile_url'] ?? ''));
        if ($phone === '' || strlen($phone) < 10 || strlen($phone) > 15 || $platform === '') {
            social_redirect("Phone number and platform are required.");
        }
        $platformEsc = mysqli_real_escape_string($conn, $platform);
        $statusEsc = mysqli_real_escape_string($conn, $status !== '' ? $status : 'checked');
        $phoneEsc = mysqli_real_escape_string($conn, $phone);
        $nameSql = $profileName !== '' ? ("'" . mysqli_real_escape_string($conn, $profileName) . "'") : "NULL";
        $urlSql = $profileUrl !== '' ? ("'" . mysqli_real_escape_string($conn, $profileUrl) . "'") : "NULL";
        $foundSql = ($isFound === 0 || $isFound === 1) ? ("'" . $isFound . "'") : "NULL";

        mysqli_query(
            $conn,
            "INSERT INTO social_check_results(user_id,phone_number,platform,status,is_found,profile_name,profile_url,metadata,checked_at,created_at,updated_at) VALUES('" .
            $uid . "','" . $phoneEsc . "','" . $platformEsc . "','" . $statusEsc . "'," . $foundSql . "," . $nameSql . "," . $urlSql . ",NULL,NOW(),NOW(),NOW())"
        );
        social_redirect("Social check record created.");
    }

    if ($action === 'run_check') {
        $phone = preg_replace('/\D+/', '', (string) ($_POST['run_phone_number'] ?? ''));
        $platform = strtolower(trim((string) ($_POST['run_platform'] ?? '')));
        if ($phone === '' || strlen($phone) < 10 || strlen($phone) > 15 || !in_array($platform, $socialPlatforms, true)) {
            social_redirect("Valid phone number and platform are required.");
        }
        $backend = social_backend_call('/social-checks/check', array(
            'phone_number' => $phone,
            'platforms' => array($platform),
        ));
        if ($backend['ok']) {
            social_redirect("Social check completed via backend API.");
        }
        $probe = social_probe_platform($platform, $phone);
        social_insert_probe($conn, $uid, $phone, $platform, $probe);
        social_redirect("Social check saved in local fallback mode.");
    }

    if ($action === 'run_batch') {
        $rawPhones = (string) ($_POST['run_batch_numbers'] ?? '');
        $platforms = isset($_POST['run_batch_platforms']) && is_array($_POST['run_batch_platforms']) ? $_POST['run_batch_platforms'] : array();
        $picked = array();
        foreach ($platforms as $p) {
            $k = strtolower(trim((string) $p));
            if (in_array($k, $socialPlatforms, true)) {
                $picked[$k] = true;
            }
        }
        $picked = array_keys($picked);
        if (count($picked) < 1) {
            social_redirect("Select at least one platform.");
        }
        $parts = preg_split('/[\s,;]+/', $rawPhones);
        $phones = array();
        foreach ($parts as $item) {
            $n = preg_replace('/\D+/', '', (string) $item);
            if ($n !== '' && strlen($n) >= 10 && strlen($n) <= 15) {
                $phones[$n] = true;
            }
        }
        $phones = array_keys($phones);
        if (count($phones) < 1) {
            social_redirect("Provide at least one valid phone number.");
        }
        $saved = 0;
        foreach ($phones as $phone) {
            foreach ($picked as $platform) {
                $probe = social_probe_platform($platform, $phone);
                social_insert_probe($conn, $uid, $phone, $platform, $probe);
                $saved++;
            }
        }
        $backend = social_backend_call('/social-checks/batch', array(
            'phone_numbers' => $phones,
            'platforms' => $picked,
        ));
        if ($backend['ok']) {
            social_redirect("Batch social check completed via backend API.");
        }
        social_redirect("Batch social check saved in local fallback mode. Saved " . $saved . " record(s).");
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            social_redirect("Invalid record id.");
        }
        $status = trim((string) ($_POST['status'] ?? ''));
        $isFound = isset($_POST['is_found']) && $_POST['is_found'] !== '' ? (int) $_POST['is_found'] : null;
        $profileName = trim((string) ($_POST['profile_name'] ?? ''));
        $profileUrl = trim((string) ($_POST['profile_url'] ?? ''));
        if ($status === '') {
            social_redirect("Status is required.");
        }

        $statusEsc = mysqli_real_escape_string($conn, $status);
        $nameSql = $profileName !== '' ? ("'" . mysqli_real_escape_string($conn, $profileName) . "'") : "NULL";
        $urlSql = $profileUrl !== '' ? ("'" . mysqli_real_escape_string($conn, $profileUrl) . "'") : "NULL";
        $foundSql = ($isFound === 0 || $isFound === 1) ? ("'" . $isFound . "'") : "NULL";

        mysqli_query(
            $conn,
            "UPDATE social_check_results SET status='" . $statusEsc . "', is_found=" . $foundSql . ", profile_name=" . $nameSql . ", profile_url=" . $urlSql . ", updated_at=NOW() WHERE id='" . $id . "' AND user_id='" . $uid . "' LIMIT 1"
        );
        social_redirect("Social check record updated.");
    }

    if ($action === 'delete_one') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            social_redirect("Invalid record id.");
        }
        mysqli_query($conn, "DELETE FROM social_check_results WHERE id='" . $id . "' AND user_id='" . $uid . "' LIMIT 1");
        social_redirect("Social check record deleted.");
    }

    if ($action === 'delete_selected') {
        if (empty($_POST['selected_ids']) || !is_array($_POST['selected_ids'])) {
            social_redirect("Select records to delete.");
        }
        $ids = array_map('intval', $_POST['selected_ids']);
        $ids = array_filter($ids, function ($v) { return $v > 0; });
        if (count($ids) < 1) {
            social_redirect("Select valid records.");
        }
        $csv = implode(',', $ids);
        mysqli_query($conn, "DELETE FROM social_check_results WHERE user_id='" . $uid . "' AND id IN (" . $csv . ")");
        $deleted = (int) mysqli_affected_rows($conn);
        social_redirect("Deleted " . $deleted . " social check record(s).");
    }

    social_redirect("Unsupported action.");
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
    $rs = mysqli_query($conn, "SELECT * FROM social_check_results " . $where . " ORDER BY checked_at DESC, id DESC LIMIT 500");
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
        <?php if (isset($_GET['r']) && $_GET['r'] !== '') { ?>
            <div class="message-failed"><?php echo htmlspecialchars((string) $_GET['r'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if (!$hasTable) { ?>
            <div class="message-failed">Social checks storage is missing. Run backend migration for <code>social_check_results</code>.</div>
        <?php } ?>
        <div class="incoming-card" style="margin-bottom:12px;">
            <h3 class="incoming-card-title">Integration</h3>
            <?php if (social_backend_token() !== '') { ?>
                Backend integration is active (server token configured).
            <?php } else { ?>
                Backend integration token is not configured on server. Checks will be stored in local fallback mode.
            <?php } ?>
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

        <div class="incoming-card">
            <h3 class="incoming-card-title">Run Checks</h3>
            <p style="margin-bottom:10px;">Run single or batch checks below. Records are saved automatically for audit and follow-up.</p>

        <form method="post" action="social_checks.php" style="margin-bottom:10px;">
            <h4 style="margin-bottom:8px;">Single Check</h4>
            <div class="form-field"><div class="field-label">Phone:</div><div class="custom-input-wrapper"><input type="text" name="run_phone_number" placeholder="2557xxxxxxx"></div></div>
            <div class="form-field"><div class="field-label">Platform:</div><div class="custom-input-wrapper">
                <select name="run_platform">
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="telegram">Telegram</option>
                    <option value="x">X</option>
                </select>
            </div></div>
            <button class="send-button" type="submit" name="action" value="run_check">Run Check</button>
        </form>

        <form method="post" action="social_checks.php">
            <h4 style="margin-bottom:8px;">Batch Check</h4>
            <div class="form-field"><div class="field-label">Phone Numbers:</div><div class="custom-input-wrapper"><textarea name="run_batch_numbers" rows="4" placeholder="2557xxxxxxx,2557yyyyyyy"></textarea></div></div>
            <div class="form-field">
                <div class="field-label">Platforms:</div>
                <div class="custom-input-wrapper">
                    <label><input type="checkbox" name="run_batch_platforms[]" value="facebook"> Facebook</label>
                    <label><input type="checkbox" name="run_batch_platforms[]" value="instagram"> Instagram</label>
                    <label><input type="checkbox" name="run_batch_platforms[]" value="whatsapp"> WhatsApp</label>
                    <label><input type="checkbox" name="run_batch_platforms[]" value="telegram"> Telegram</label>
                    <label><input type="checkbox" name="run_batch_platforms[]" value="x"> X</label>
                </div>
            </div>
            <button class="send-button" type="submit" name="action" value="run_batch">Run Batch</button>
        </form>
        </div>

        <form method="post" action="social_checks.php">
            <div class="incoming-card" style="margin-top:14px; margin-bottom:10px;">
                <h3 class="incoming-card-title">Results</h3>
                <p>Use checkboxes for bulk delete, or edit a specific row.</p>
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
                                <button class="incoming-delete-btn" type="button" onclick="fillSocialEdit(<?php echo (int) $r['id']; ?>,'<?php echo htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8'); ?>','<?php echo htmlspecialchars((string) ($r['is_found'] === null ? '' : (string) (int) $r['is_found']), ENT_QUOTES, 'UTF-8'); ?>','<?php echo htmlspecialchars((string) ($r['profile_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>','<?php echo htmlspecialchars((string) ($r['profile_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>')">Edit</button>
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

        <form method="post" action="social_checks.php" class="incoming-card">
            <h3 class="incoming-card-title">Update Selected Record</h3>
            <div class="form-field"><div class="field-label">Record ID:</div><div class="custom-input-wrapper"><input type="number" min="1" name="id" id="edit_id"></div></div>
            <div class="form-field"><div class="field-label">Status:</div><div class="custom-input-wrapper"><input type="text" name="status" id="edit_status"></div></div>
            <div class="form-field"><div class="field-label">Found:</div><div class="custom-input-wrapper"><select name="is_found" id="edit_found"><option value="">Unknown</option><option value="1">Found</option><option value="0">Not found</option></select></div></div>
            <div class="form-field"><div class="field-label">Profile Name:</div><div class="custom-input-wrapper"><input type="text" name="profile_name" id="edit_name"></div></div>
            <div class="form-field"><div class="field-label">Profile URL:</div><div class="custom-input-wrapper"><input type="text" name="profile_url" id="edit_url"></div></div>
            <button class="send-button" type="submit" name="action" value="update">Update Record</button>
        </form>
    </div>
</div>
<script>
function fillSocialEdit(id, status, found, name, url) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_found').value = found;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_url').value = url;
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}
</script>
<?php include "footer.php"; ?>
</body>
</html>

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

$cfgPath = __DIR__ . '/db/social_bridge.local.php';
$cfg = array(
    'base_url' => 'http://127.0.0.1:8010/api/v1',
    'token' => '',
);
if (is_file($cfgPath)) {
    $loaded = include $cfgPath;
    if (is_array($loaded)) {
        $cfg = array_merge($cfg, $loaded);
    }
}

function bridge_redirect($msg)
{
    header("location:social_bridge_settings.php?r=" . urlencode($msg));
    exit;
}

function bridge_mask_token($token)
{
    $t = (string) $token;
    $len = strlen($t);
    if ($len <= 8) {
        return str_repeat('*', $len);
    }
    return substr($t, 0, 4) . str_repeat('*', $len - 8) . substr($t, -4);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'save') {
        $base = rtrim(trim((string) ($_POST['base_url'] ?? '')), '/');
        $tokenInput = trim((string) ($_POST['token'] ?? ''));
        $token = $tokenInput !== '' ? $tokenInput : (string) ($cfg['token'] ?? '');
        if ($base === '') {
            bridge_redirect("Base URL is required.");
        }
        $content = "<?php\nreturn " . var_export(array(
            'base_url' => $base,
            'token' => $token,
        ), true) . ";\n";
        $ok = @file_put_contents($cfgPath, $content);
        if ($ok === false) {
            bridge_redirect("Failed to save settings. Check folder write permissions.");
        }
        bridge_redirect("Bridge settings saved.");
    }

    if ($action === 'test') {
        $base = rtrim(trim((string) ($_POST['base_url'] ?? '')), '/');
        $tokenInput = trim((string) ($_POST['token'] ?? ''));
        $token = $tokenInput !== '' ? $tokenInput : (string) ($cfg['token'] ?? '');
        if ($base === '' || $token === '') {
            bridge_redirect("Provide base URL and token to test.");
        }
        $url = $base . '/social-checks/recent?limit=1';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code >= 200 && $code < 300) {
            bridge_redirect("Connection test passed (HTTP " . $code . ").");
        }
        $msg = $err !== '' ? $err : ("Connection failed (HTTP " . $code . ").");
        bridge_redirect($msg);
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
        <div class="page-title">Social Bridge Settings</div>
        <?php if (isset($_GET['r']) && $_GET['r'] !== '') { ?>
            <div class="message-failed"><?php echo htmlspecialchars((string) $_GET['r'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <form method="post" action="social_bridge_settings.php" class="incoming-card">
            <h3 class="incoming-card-title">Backend API Bridge Configuration</h3>
            <div class="form-field">
                <div class="field-label">API Base URL:</div>
                <div class="custom-input-wrapper"><input type="text" name="base_url" value="<?php echo htmlspecialchars((string) $cfg['base_url'], ENT_QUOTES, 'UTF-8'); ?>"></div>
            </div>
            <div class="form-field">
                <div class="field-label">Bearer Token:</div>
                <div class="custom-input-wrapper">
                    <input type="password" name="token" value="" placeholder="Enter new token to update (leave blank to keep current)">
                    <div style="margin-top:6px; font-size:0.85rem;">
                        Current token:
                        <?php if (!empty($cfg['token'])) { ?>
                            <code><?php echo htmlspecialchars(bridge_mask_token((string) $cfg['token']), ENT_QUOTES, 'UTF-8'); ?></code>
                        <?php } else { ?>
                            <code>not set</code>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="form-field">
                <div class="field-label"></div>
                <div class="custom-input-wrapper">
                    <button class="send-button" type="submit" name="action" value="save">Save Settings</button>
                    <button class="send-button incoming-action-btn" type="submit" name="action" value="test">Test Connection</button>
                    <a class="send-button incoming-action-btn" href="social_checks.php">Back to Social Checks</a>
                </div>
            </div>
            <div class="form-field">
                <div class="field-label"></div>
                <div class="custom-input-wrapper">
                    Settings file: <code>SmSver1/db/social_bridge.local.php</code>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include "footer.php"; ?>
</body>
</html>

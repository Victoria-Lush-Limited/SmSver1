<?php
/**
 * Serves the latest VLL SMS APK from downloads/vll_sms-latest.apk
 * URL: https://sms.victorialush.co.tz/serve-apk.php
 */
declare(strict_types=1);

$path = __DIR__ . '/downloads/vll_sms-latest.apk';
if (!is_readable($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'APK not uploaded yet. Place vll_sms-latest.apk in the downloads/ folder.';
    exit;
}

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="vll_sms-latest.apk"');
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: no-cache');
readfile($path);
exit;

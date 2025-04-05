<?php

// ================= CẤU HÌNH =================
$limit_seconds = 2; // mỗi IP truy cập tối đa 1 lần mỗi 2 giây
$log_dir = __DIR__ . '/rate_limit_logs'; // nơi lưu log IP

// Tạo thư mục log nếu chưa có
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Xoá log sau 24h
$logExpireSeconds = 86400; // 24 tiếng
if (file_exists($logFile) && (time() - filemtime($logFile)) > $logExpireSeconds) {
    unlink($logFile);
}

// ================= KIỂM TRA BOT TỐT =================
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$trustedBots = [
    // Google
    'googlebot',
    'googlebot-image',
    'googlebot-news',
    'googlebot-video',
    'adsbot-google',
    'adsbot-google',
    
    'google-inspectiontool',

    // Google PageSpeed Insights (rất quan trọng!)
    'lighthouse',
    'chrome-lighthouse',
    'pagespeed',
    'psi', // đôi khi có thể là 'psi/1.0'

    // Bing
    'bingbot',
    'msnbot',
    'adidxbot',
    'bingpreview',

    // Yahoo
    'slurp',

    // DuckDuckGo
    'duckduckbot',

    // Yandex (Nga)
    'yandex',
    'yandexbot',
    'yandeximages',

    // Baidu (TQ)
    'baiduspider',
    'baiduspider-image',

    // Facebook
    'facebookexternalhit',
    'facebookbot',

    // Twitter
    'twitterbot',

    // LinkedIn
    'linkedinbot',

    // Pinterest
    'pinterest',
    'pinterestbot',

    // Apple
    'applebot',

    // Sogou (TQ)
    'sogou spider',
    'sogou web spider',

    // Exalead
    'exabot',

    // Alexa
    'ia_archiver',

    // Archive.org
    'archive.org_bot',
    'wayback machine',

    // Ahrefs
    'ahrefsbot',

    // Semrush
    'semrushbot',

    // Majestic
    'mj12bot',

    // Seznam.cz
    'seznambot',

    // Qwant
    'qwantbot',

    // Cốc Cốc
    'coccocbot',

    // Uptime & Monitor bots (tuỳ chọn cho phép)
    'uptimerobot',
    'site24x7',
    'pingdom',
];
$isTrustedBot = false;

foreach ($trustedBots as $bot) {
    if (stripos($userAgent, $bot) !== false) {
        $isTrustedBot = true;
        break;
    }
}

// ================= CHỐNG DOS =================
if (!$isTrustedBot) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time();
    $logFile = "$log_dir/$ip.txt";

    if (file_exists($logFile)) {
        $lastAccess = (int) file_get_contents($logFile);
        if (($now - $lastAccess) < $limit_seconds) {
            http_response_code(429); // Too Many Requests
            header('Retry-After: ' . $limit_seconds);
            echo '<meta http-equiv="refresh" content="2;url=/" />';
            echo '⚠️ Bạn thao tác quá nhanh! Đang quay về trang chủ trong 2 giây...';
            exit;
        }
    }
    file_put_contents($logFile, $now);
}

// ================= CHẶN REFERER LẠ =================
// if (!$isTrustedBot) {
//     if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'reviewhaiphong.io.vn') === false) {
//         // Nếu là request từ nội dung nhúng (ảnh, script...), có thể bỏ qua hoặc không
//         if (!str_starts_with($_SERVER['REQUEST_URI'], '/images')) {
//             http_response_code(403);
//             exit('⛔ Truy cập không hợp lệ');
//         }
//     }
// }

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

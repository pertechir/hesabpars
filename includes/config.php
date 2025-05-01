<?php
// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'hesabpars');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/hesabpars');
// تنظیمات سایت
define('SITE_NAME', 'حساب پارسه');
define('SITE_URL', 'http://localhost/hesabpars');

// تنظیمات امنیتی
define('HASH_COST', 10);
define('SESSION_LIFETIME', 3600);

// تنظیمات نمایشی
define('ITEMS_PER_PAGE', 20);
define('DATE_FORMAT', 'Y/m/d');
define('TIME_FORMAT', 'H:i:s');

// مسیرها
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('LOG_PATH', __DIR__ . '/../logs');

// تنظیمات منطقه‌ای
date_default_timezone_set('Asia/Tehran');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// اتصال به دیتابیس
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// توابع کمکی عمومی
function redirect($url) {
    header("Location: $url");
    exit;
}

function asset($path) {
    return SITE_URL . '/assets/' . ltrim($path, '/');
}

function url($path) {
    return SITE_URL . '/' . ltrim($path, '/');
}
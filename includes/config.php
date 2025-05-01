<?php
// تنظیمات پایه
if (!defined('BASE_URL')) {
    define('BASE_URL', '/hesabpars');
}

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'hesabpars');
define('DB_USER', 'root');
define('DB_PASS', '');

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
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(__DIR__ . '/..'));
}
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('LOG_PATH', BASE_PATH . '/logs');

// تنظیمات منطقه‌ای
date_default_timezone_set('Asia/Tehran');

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
    error_log("Database Error: " . $e->getMessage());
    die("خطا در اتصال به دیتابیس");
}

// Remove function declarations from here since they're now in init.php
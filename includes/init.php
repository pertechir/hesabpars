<?php
// نمایش خطاها
ini_set('display_errors', 1);
error_reporting(E_ALL);

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'hesabpars');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/hesabpars');
define('SITE_NAME', 'حساب پارس');

// تنظیم session اگر قبلاً شروع نشده باشد
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        )
    );
} catch (PDOException $e) {
    die("خطای اتصال به دیتابیس: " . $e->getMessage());
}

/**
 * نمایش پیغام خطا
 */
function showError($message) {
    echo '<div class="alert alert-danger" role="alert">';
    echo $message;
    echo '</div>';
}

/**
 * نمایش پیغام موفقیت
 */
function showSuccess($message) {
    echo '<div class="alert alert-success" role="alert">';
    echo $message;
    echo '</div>';
}

/**
 * بررسی لاگین بودن کاربر
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * دریافت اطلاعات کاربر جاری
 */
function getCurrentUser() {
    global $db;
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

/**
 * فرمت کردن مبلغ به صورت پول
 */
function formatMoney($amount) {
    return number_format($amount, 0, '.', ',');
}

/**
 * تبدیل تاریخ میلادی به شمسی
 */
function toJalali($date) {
    if (!$date) return '';
    $datetime = new DateTime($date);
    $timezone = new DateTimeZone('Asia/Tehran');
    $datetime->setTimezone($timezone);
    
    require_once __DIR__ . '/../lib/jdf.php';
    return jdate("Y/m/d H:i", $datetime->getTimestamp());
}

// بارگذاری فایل auth.php
require_once __DIR__ . '/auth.php';
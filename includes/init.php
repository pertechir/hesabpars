<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error handling setup
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
// Create logs directory if it doesn't exist
$logPath = __DIR__ . '/../logs';
if (!is_dir($logPath)) {
    mkdir($logPath, 0777, true);
}

ini_set('error_log', $logPath . '/error.log');

// Basic error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    return false;
});

// Basic configurations
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));
define('BASE_URL', '/hesabpars');

// Exception handler
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage());
    http_response_code(500);
    if (ini_get('display_errors')) {
        echo "خطای سیستمی: " . $e->getMessage();
    } else {
        echo "خطای سیستمی رخ داده است. لطفا با پشتیبانی تماس بگیرید.";
    }
    exit;
});


// Timezone
date_default_timezone_set('Asia/Tehran');

// Include required files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// URL Helper functions
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
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
    
    // تست اتصال
    echo "اتصال به دیتابیس برقرار شد.<br>";
    
} catch (PDOException $e) {
    die("خطای اتصال به دیتابیس: " . $e->getMessage());
}

// تنظیم session
session_start();

// تابع برای نمایش خطاها
function showError($message) {
    echo "<div style='color: red; padding: 10px; margin: 10px; border: 1px solid red;'>";
    echo "خطا: " . $message;
    echo "</div>";
}
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hesabpars');

// تنظیمات عمومی
define('SITE_NAME', 'حساب پارسه');
define('SITE_URL', 'http://localhost/hesabpars');
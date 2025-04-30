<?php
function clean($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

function redirect($location) {
    header("Location: " . SITE_URL . "/" . $location);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('pages/login.php');
    }
}

function generateHash($password) {
    return password_hash($password . SALT, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password . SALT, $hash);
}

function formatMoney($amount) {
    return number_format($amount, 0, '.', ',') . ' ریال';
}

function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

function getJalaliDate($date) {
    // تبدیل تاریخ میلادی به شمسی
    $datetime = new DateTime($date);
    $datetime->setTimezone(new DateTimeZone('Asia/Tehran'));
    return $datetime->format('Y-m-d H:i:s');
}


?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// چک کردن لاگین بودن کاربر
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// چک کردن دسترسی ادمین
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// خروج کاربر
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
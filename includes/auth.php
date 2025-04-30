<?php
session_start();

// تابع بررسی لاگین بودن کاربر
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// تابع خروج کاربر
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// تابع بررسی دسترسی ادمین
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// تابع ریدایرکت کاربران غیر مجاز
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// تابع ریدایرکت کاربران غیر ادمین
function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// بررسی دسترسی کاربر
function checkPermission($permission) {
    return true; // فعلاً همه دسترسی‌ها رو مجاز می‌کنیم
}

// بررسی وجود دسترسی
function hasPermission($permission) {
    return true; // فعلاً همه دسترسی‌ها رو مجاز می‌کنیم
}


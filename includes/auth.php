<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// بررسی لاگین بودن کاربر
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * بررسی لاگین بودن کاربر
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * بررسی لاگین بودن کاربر و ریدایرکت در صورت لاگین نبودن
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'title' => 'نیاز به ورود',
            'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید'
        ];
        
        // اگر درخواست Ajax باشه
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید'
            ]);
            exit;
        }

        // در غیر اینصورت ریدایرکت به صفحه لاگین
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// تابع خروج کاربر
function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// تابع بررسی دسترسی ادمین
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// تابع بررسی دسترسی
function hasPermission($permission) {
    // اگر کاربر سوپر ادمین است
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
        return true;
    }

    // بررسی دسترسی از دیتابیس
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT 1 
            FROM user_permissions up 
            INNER JOIN permissions p ON p.id = up.permission_id 
            WHERE up.user_id = ? AND p.name = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}
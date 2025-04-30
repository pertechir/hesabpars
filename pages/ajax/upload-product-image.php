<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// تنظیم هدر برای JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // بررسی دسترسی
    if (!hasPermission('add_products')) {
        throw new Exception('شما دسترسی لازم برای این عملیات را ندارید');
    }

    // بررسی وجود فایل
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('خطا در آپلود فایل');
    }

    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    // بررسی نوع فایل
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('نوع فایل مجاز نیست');
    }

    // بررسی حجم فایل
    if ($file['size'] > $maxSize) {
        throw new Exception('حجم فایل بیشتر از حد مجاز است');
    }

    // ایجاد نام یکتا برای فایل
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    
    // مسیر ذخیره فایل
    $uploadDir = '../../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadPath = $uploadDir . $filename;

    // انتقال فایل
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('خطا در ذخیره فایل');
    }

    // بازگرداندن پاسخ موفق
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'path' => 'uploads/products/' . $filename
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
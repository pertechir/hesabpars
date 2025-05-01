<?php
require_once '../includes/init.php';

try {
    // خواندن محتوای فایل SQL
    $sql = file_get_contents(__DIR__ . '/add_product_tables.sql');

    // اجرای اسکریپت SQL
    $db->exec($sql);

    echo "جداول و فیلدهای جدید با موفقیت اضافه شدند.";
} catch (PDOException $e) {
    die("خطا در اجرای اسکریپت: " . $e->getMessage());
}
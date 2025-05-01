<?php
require_once '../includes/init.php';

try {
    // بررسی جداول موجود
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>جداول موجود در دیتابیس:</h3>";
    echo "<pre>";
    print_r($tables);
    echo "</pre>";

    // بررسی ساختار جدول products
    echo "<h3>ساختار جدول products:</h3>";
    $columns = $db->query("SHOW COLUMNS FROM products")->fetchAll();
    echo "<pre>";
    print_r($columns);
    echo "</pre>";

    // بررسی تنظیمات کاراکترست
    echo "<h3>تنظیمات کاراکترست دیتابیس:</h3>";
    $charset = $db->query("SHOW VARIABLES LIKE 'character_set%'")->fetchAll();
    echo "<pre>";
    print_r($charset);
    echo "</pre>";

} catch (PDOException $e) {
    die("خطا در بررسی دیتابیس: " . $e->getMessage());
}
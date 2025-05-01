<?php
require_once '../includes/init.php';

try {
    // تنظیم charset دیتابیس
    $db->exec("SET NAMES utf8mb4");
    $db->exec("SET CHARACTER SET utf8mb4");
    $db->exec("SET character_set_connection = utf8mb4");

    echo "<div style='direction: rtl; font-family: Tahoma; padding: 20px;'>";
    
    // بررسی وجود جداول قبل از ایجاد
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('products', $tables)) {
        // ایجاد جدول products اگر وجود نداشت
        $db->exec("CREATE TABLE IF NOT EXISTS `products` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(200) NOT NULL,
            `code` varchar(50) DEFAULT NULL,
            `price` decimal(12,2) DEFAULT 0.00,
            `stock` int(11) DEFAULT 0,
            `status` enum('active','inactive') DEFAULT 'active',
            `category_id` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci");
        echo "جدول products ایجاد شد.<br>";
    }

    // اضافه کردن فیلدهای جدید به جدول products
    $alterQueries = [
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `accounting_code` varchar(50) DEFAULT NULL AFTER `code`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `barcodes` text DEFAULT NULL AFTER `accounting_code`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `main_unit` varchar(50) DEFAULT NULL AFTER `category_id`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `sub_unit` varchar(50) DEFAULT NULL AFTER `main_unit`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `unit_description` text DEFAULT NULL AFTER `sub_unit`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `stock_control` tinyint(1) DEFAULT 0 AFTER `stock`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `min_order` int(11) DEFAULT 1 AFTER `stock_control`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `lead_time` int(11) DEFAULT 0 AFTER `min_order`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `sales_description` text DEFAULT NULL AFTER `lead_time`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `purchase_description` text DEFAULT NULL AFTER `sales_description`",
        "ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `purchase_price` decimal(12,2) DEFAULT 0.00 AFTER `price`"
    ];

    foreach ($alterQueries as $query) {
        try {
            $db->exec($query);
            echo "فیلد جدید اضافه شد.<br>";
        } catch (PDOException $e) {
            echo "خطا در اضافه کردن فیلد: " . $e->getMessage() . "<br>";
        }
    }

    // ایجاد سایر جداول مورد نیاز
    $createTableQueries = [
        "product_prices" => "CREATE TABLE IF NOT EXISTS `product_prices` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `price_type` varchar(50) NOT NULL,
            `currency_code` varchar(3) NOT NULL DEFAULT 'IRR',
            `price` decimal(12,2) NOT NULL DEFAULT 0.00,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci",

        "product_taxes" => "CREATE TABLE IF NOT EXISTS `product_taxes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `tax_type` enum('sales','purchase') NOT NULL,
            `rate` decimal(5,2) NOT NULL DEFAULT 9.00,
            `enabled` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci",

        "product_images" => "CREATE TABLE IF NOT EXISTS `product_images` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `file_name` varchar(255) NOT NULL,
            `file_path` varchar(255) NOT NULL,
            `is_primary` tinyint(1) DEFAULT 0,
            `sort_order` int(11) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci"
    ];

    foreach ($createTableQueries as $tableName => $query) {
        try {
            $db->exec($query);
            echo "جدول {$tableName} ایجاد شد.<br>";
        } catch (PDOException $e) {
            echo "خطا در ایجاد جدول {$tableName}: " . $e->getMessage() . "<br>";
        }
    }

    echo "<br>عملیات نصب با موفقیت به پایان رسید.</div>";

} catch (PDOException $e) {
    die("خطای کلی در اجرای اسکریپت: " . $e->getMessage());
}
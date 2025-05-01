<?php
require_once '../includes/init.php';

try {
    // درج داده‌های پیش‌فرض برای انبارها اگر خالی است
    $warehouseCount = $db->query("SELECT COUNT(*) FROM warehouses")->fetchColumn();
    if ($warehouseCount == 0) {
        $db->exec("INSERT INTO warehouses (name, code, status, created_by) VALUES 
            ('انبار مرکزی', 'WH001', 'active', 1),
            ('انبار شماره 2', 'WH002', 'active', 1)");
        echo "داده‌های پیش‌فرض انبارها اضافه شدند.<br>";
    }

    // درج داده‌های پیش‌فرض برای مالیات‌ها اگر خالی است
    $taxCount = $db->query("SELECT COUNT(*) FROM taxes")->fetchColumn();
    if ($taxCount == 0) {
        $db->exec("INSERT INTO taxes (name, rate, status, created_by) VALUES 
            ('مالیات بر ارزش افزوده', 9.00, 'active', 1),
            ('مالیات تکلیفی', 3.00, 'active', 1)");
        echo "داده‌های پیش‌فرض مالیات‌ها اضافه شدند.<br>";
    }

    // درج داده‌های پیش‌فرض برای واحدها اگر خالی است
    $unitCount = $db->query("SELECT COUNT(*) FROM units")->fetchColumn();
    if ($unitCount == 0) {
        $db->exec("INSERT INTO units (name, code, status, created_by) VALUES 
            ('عدد', 'PCS', 'active', 1),
            ('کیلوگرم', 'KG', 'active', 1),
            ('متر', 'M', 'active', 1),
            ('لیتر', 'L', 'active', 1),
            ('بسته', 'PKG', 'active', 1)");
        echo "داده‌های پیش‌فرض واحدها اضافه شدند.<br>";
    }

    // درج تنظیمات پیش‌فرض اگر خالی است
    $settingsCount = $db->query("SELECT COUNT(*) FROM settings WHERE module = 'products'")->fetchColumn();
    if ($settingsCount == 0) {
        $db->exec("INSERT INTO settings (module, `key`, value) VALUES 
            ('products', 'default_min_stock', '0'),
            ('products', 'default_max_stock', '999999'),
            ('products', 'default_tax_rate', '9'),
            ('products', 'default_tax_method', 'exclusive')");
        echo "تنظیمات پیش‌فرض محصولات اضافه شدند.<br>";
    }

    echo "<br>عملیات با موفقیت انجام شد.";

} catch (PDOException $e) {
    die("خطا در درج داده‌های پیش‌فرض: " . $e->getMessage());
}
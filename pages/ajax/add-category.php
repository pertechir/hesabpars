<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
// تنظیمات خطایابی
error_reporting(E_ALL);
ini_set('display_errors', 0);

// تنظیم هدر برای JSON
header('Content-Type: application/json; charset=utf-8');






error_log("Received POST data: " . print_r($_POST, true));
if (!empty($_FILES)) {
    error_log("Received FILES data: " . print_r($_FILES, true));
}


// بررسی درخواست Ajax
if (!isAjaxRequest()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'درخواست نامعتبر'
    ]);
    exit;
}
// ثبت داده‌های ورودی برای دیباگ
error_log("Received POST data in add-category.php: " . print_r($_POST, true));


// بررسی دسترسی
if (!hasPermission('add_categories')) {
    echo json_encode([
        'success' => false,
        'message' => 'شما دسترسی لازم برای این عملیات را ندارید'
    ]);
    exit;
}

try {
    // دریافت و تمیز کردن داده‌ها
    $name = clean($_POST['name'] ?? '');
    if (empty($name)) {
        throw new Exception('نام دسته‌بندی الزامی است');
    }

    $slug = !empty($_POST['slug']) ? clean($_POST['slug']) : createSlug($name);
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $description = clean($_POST['description'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $icon = clean($_POST['icon'] ?? '');
    $color = clean($_POST['color'] ?? '#e3f2fd');

    // بررسی یکتا بودن slug
    $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('این نامک قبلاً استفاده شده است');
    }

    // بررسی والد
    if ($parentId) {
        $stmt = $db->prepare("SELECT id, status FROM categories WHERE id = ?");
        $stmt->execute([$parentId]);
        $parent = $stmt->fetch();
        if (!$parent) {
            throw new Exception('دسته‌بندی والد نامعتبر است');
        }
        if ($parent['status'] !== 'active') {
            throw new Exception('دسته‌بندی والد غیرفعال است');
        }
    }

    // تعیین موقعیت جدید
    $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories WHERE parent_id " . ($parentId ? "= ?" : "IS NULL"));
    if ($parentId) {
        $stmt->execute([$parentId]);
    } else {
        $stmt->execute();
    }
    $sortOrder = $stmt->fetchColumn();

    $db->beginTransaction();

    // درج دسته‌بندی
    $stmt = $db->prepare("
        INSERT INTO categories (
            name, slug, description, parent_id, status,
            icon, color, sort_order, created_by, created_at
        ) VALUES (
            :name, :slug, :description, :parent_id, :status,
            :icon, :color, :sort_order, :user_id, NOW()
        )
    ");

    $stmt->execute([
        ':name' => $name,
        ':slug' => $slug,
        ':description' => $description,
        ':parent_id' => $parentId,
        ':status' => $status,
        ':icon' => $icon,
        ':color' => $color,
        ':sort_order' => $sortOrder,
        ':user_id' => $_SESSION['user_id']
    ]);

    $categoryId = $db->lastInsertId();

    // ثبت فعالیت
    logActivity('categories', $categoryId, 'create', 'ایجاد دسته‌بندی جدید: ' . $name);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'دسته‌بندی با موفقیت ایجاد شد'
    ]);

} catch (Exception $e) {
    error_log("Error in add-category.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'post' => $_POST,
            'files' => $_FILES ?? []
        ]
    ]);
}
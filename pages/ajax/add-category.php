<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// بررسی درخواست Ajax
if (!isAjaxRequest()) {
    http_response_code(400);
    exit('درخواست نامعتبر');
}

// بررسی دسترسی
if (!hasPermission('add_categories')) {
    echo json_encode([
        'success' => false,
        'message' => 'شما دسترسی لازم برای این عملیات را ندارید'
    ]);
    exit;
}

try {
    // دریافت و اعتبارسنجی داده‌ها
    $name = clean($_POST['name'] ?? '');
    if (empty($name)) {
        throw new Exception('نام دسته‌بندی الزامی است');
    }

    $slug = !empty($_POST['slug']) ? clean($_POST['slug']) : createSlug($name);
    $description = clean($_POST['description'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $status = clean($_POST['status'] ?? 'active');
    $icon = clean($_POST['icon'] ?? '');
    $color = clean($_POST['color'] ?? '#e3f2fd');

    // بررسی یکتا بودن slug
    $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('این نامک قبلاً استفاده شده است');
    }

    // بررسی وجود دسته‌بندی والد
    if ($parentId) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$parentId]);
        if (!$stmt->fetch()) {
            throw new Exception('دسته‌بندی والد نامعتبر است');
        }
    }

    // آپلود تصویر
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbnail = uploadImage($_FILES['thumbnail'], 'categories');
    }

    // تعیین موقعیت جدید
    $stmt = $db->prepare("SELECT COALESCE(MAX(position), 0) + 1 FROM categories WHERE parent_id " . ($parentId ? "= ?" : "IS NULL"));
    if ($parentId) {
        $stmt->execute([$parentId]);
    } else {
        $stmt->execute();
    }
    $position = $stmt->fetchColumn();

    $db->beginTransaction();

    // درج دسته‌بندی
    $stmt = $db->prepare("
        INSERT INTO categories (
            name, slug, description, parent_id, status, icon, color, 
            thumbnail, position, created_by, created_at
        ) VALUES (
            :name, :slug, :description, :parent_id, :status, :icon, :color,
            :thumbnail, :position, :user_id, NOW()
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
        ':thumbnail' => $thumbnail,
        ':position' => $position,
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
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error in add-category.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
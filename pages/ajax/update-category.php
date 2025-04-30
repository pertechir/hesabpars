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
if (!hasPermission('edit_categories')) {
    echo json_encode([
        'success' => false,
        'message' => 'شما دسترسی لازم برای این عملیات را ندارید'
    ]);
    exit;
}

try {
    // دریافت و اعتبارسنجی داده‌ها
    $categoryId = (int)$_POST['category_id'];
    $name = clean($_POST['name'] ?? '');
    
    if (empty($categoryId) || empty($name)) {
        throw new Exception('اطلاعات ناقص است');
    }

    // بررسی وجود دسته‌بندی
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception('دسته‌بندی مورد نظر یافت نشد');
    }

    $slug = !empty($_POST['slug']) ? clean($_POST['slug']) : createSlug($name);
    $description = clean($_POST['description'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $status = clean($_POST['status'] ?? 'active');
    $icon = clean($_POST['icon'] ?? '');
    $color = clean($_POST['color'] ?? '#e3f2fd');

    // بررسی یکتا بودن slug
    $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $categoryId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('این نامک قبلاً استفاده شده است');
    }

    // بررسی حلقه در ساختار درختی
    if ($parentId) {
        if ($parentId == $categoryId) {
            throw new Exception('دسته‌بندی نمی‌تواند زیرمجموعه خودش باشد');
        }

        // بررسی والدهای بالاتر
        $currentParent = $parentId;
        while ($currentParent) {
            $stmt = $db->prepare("SELECT parent_id FROM categories WHERE id = ?");
            $stmt->execute([$currentParent]);
            $currentParent = $stmt->fetchColumn();
            
            if ($currentParent == $categoryId) {
                throw new Exception('ساختار درختی نامعتبر است');
            }
        }
    }

    $db->beginTransaction();

    // آپلود تصویر جدید
    $thumbnail = $category['thumbnail'];
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        // حذف تصویر قبلی
        if ($thumbnail) {
            deleteImage($thumbnail, 'categories');
        }
        $thumbnail = uploadImage($_FILES['thumbnail'], 'categories');
    }

    // بروزرسانی دسته‌بندی
    $stmt = $db->prepare("
        UPDATE categories SET 
            name = :name,
            slug = :slug,
            description = :description,
            parent_id = :parent_id,
            status = :status,
            icon = :icon,
            color = :color,
            thumbnail = :thumbnail,
            updated_by = :user_id,
            updated_at = NOW()
        WHERE id = :category_id
    ");

    $stmt->execute([
        ':category_id' => $categoryId,
        ':name' => $name,
        ':slug' => $slug,
        ':description' => $description,
        ':parent_id' => $parentId,
        ':status' => $status,
        ':icon' => $icon,
        ':color' => $color,
        ':thumbnail' => $thumbnail,
        ':user_id' => $_SESSION['user_id']
    ]);

    // ثبت فعالیت
    logActivity('categories', $categoryId, 'update', 'ویرایش دسته‌بندی: ' . $name);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'دسته‌بندی با موفقیت بروزرسانی شد'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error in update-category.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
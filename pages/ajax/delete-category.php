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
if (!hasPermission('delete_categories')) {
    echo json_encode([
        'success' => false,
        'message' => 'شما دسترسی لازم برای این عملیات را ندارید'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $categoryId = (int)($data['category_id'] ?? 0);
    
    if (!$categoryId) {
        throw new Exception('شناسه دسته‌بندی نامعتبر است');
    }

    // بررسی وجود دسته‌بندی
    $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        throw new Exception('دسته‌بندی مورد نظر یافت نشد');
    }

    // بررسی وجود زیردسته‌ها
    $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $stmt->execute([$categoryId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ابتدا باید زیردسته‌های این دسته‌بندی را حذف کنید');
    }

    // بررسی وجود محصولات مرتبط
    $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('این دسته‌بندی دارای محصولات مرتبط است و قابل حذف نیست');
    }

    $db->beginTransaction();

    // حذف تگ‌های دسته‌بندی
    $stmt = $db->prepare("DELETE FROM category_tags WHERE category_id = ?");
    $stmt->execute([$categoryId]);

    // حذف دسته‌بندی
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);

    // ثبت فعالیت
    logActivity('categories', $categoryId, 'delete', 'حذف دسته‌بندی: ' . $category['name']);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'دسته‌بندی با موفقیت حذف شد'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error in delete-category.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
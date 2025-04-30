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
    $data = json_decode(file_get_contents('php://input'), true);
    
    $categoryId = (int)($data['category_id'] ?? 0);
    $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
    $position = (int)($data['position'] ?? 0);
    
    if (!$categoryId || $position < 0) {
        throw new Exception('اطلاعات نامعتبر است');
    }

    // بررسی وجود دسته‌بندی
    $stmt = $db->prepare("SELECT name, parent_id FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        throw new Exception('دسته‌بندی مورد نظر یافت نشد');
    }

    $db->beginTransaction();

    // بروزرسانی موقعیت
    if ($category['parent_id'] !== $parentId) {
        // تغییر والد
        $stmt = $db->prepare("
            UPDATE categories 
            SET parent_id = ?, 
                sort_order = ?, 
                last_updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$parentId, $position, $_SESSION['user_id'], $categoryId]);

        // مرتب‌سازی مجدد دسته‌بندی‌های قدیمی
        $stmt = $db->prepare("
            SET @rank = 0;
            UPDATE categories 
            SET sort_order = @rank:=@rank+1 
            WHERE parent_id " . ($category['parent_id'] ? "= " . $category['parent_id'] : "IS NULL") . "
            ORDER BY sort_order;
        ");
        $stmt->execute();

        // مرتب‌سازی مجدد دسته‌بندی‌های جدید
        $stmt = $db->prepare("
            SET @rank = 0;
            UPDATE categories 
            SET sort_order = @rank:=@rank+1 
            WHERE parent_id " . ($parentId ? "= " . $parentId : "IS NULL") . "
            ORDER BY sort_order;
        ");
        $stmt->execute();
    } else {
        // فقط تغییر موقعیت
        $stmt = $db->prepare("
            UPDATE categories 
            SET sort_order = ?, 
                last_updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$position, $_SESSION['user_id'], $categoryId]);
    }

    // ثبت فعالیت
    logActivity('categories', $categoryId, 'move', 'تغییر موقعیت دسته‌بندی: ' . $category['name']);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'موقعیت دسته‌بندی با موفقیت بروزرسانی شد'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error in update-position.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
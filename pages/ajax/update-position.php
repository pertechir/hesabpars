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
    $position = (int)($data['position'] ?? 0);
    
    if (!$categoryId || $position < 0) {
        throw new Exception('اطلاعات نامعتبر است');
    }

    // بروزرسانی موقعیت
    $stmt = $db->prepare("
        UPDATE categories 
        SET position = :position,
            updated_by = :user_id,
            updated_at = NOW()
        WHERE id = :category_id
    ");

    $stmt->execute([
        ':category_id' => $categoryId,
        ':position' => $position,
        ':user_id' => $_SESSION['user_id']
    ]);

    // ثبت فعالیت
    logActivity('categories', $categoryId, 'move', 'تغییر موقعیت دسته‌بندی به: ' . $position);

    echo json_encode([
        'success' => true,
        'message' => 'موقعیت دسته‌بندی با موفقیت بروزرسانی شد'
    ]);

} catch (Exception $e) {
    error_log("Error in update-position.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
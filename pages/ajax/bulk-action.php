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
if (!hasPermission('bulk_edit_categories')) {
    echo json_encode([
        'success' => false,
        'message' => 'شما دسترسی لازم برای این عملیات را ندارید'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $items = array_map('intval', $data['items'] ?? []);
    
    if (empty($action) || empty($items)) {
        throw new Exception('پارامترهای عملیات گروهی نامعتبر است');
    }

    $db->beginTransaction();

    switch ($action) {
        case 'activate':
        case 'deactivate':
            $status = $action === 'activate' ? 'active' : 'inactive';
            
            $placeholders = str_repeat('?,', count($items) - 1) . '?';
            $stmt = $db->prepare("
                UPDATE categories 
                SET status = ?, 
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id IN ($placeholders)
            ");
            
            $params = array_merge([$status, $_SESSION['user_id']], $items);
            $stmt->execute($params);

            // ثبت فعالیت
            logActivity('categories', 0, 'bulk_' . $action, 'تغییر گروهی وضعیت دسته‌بندی‌ها به: ' . $status);
            break;

        case 'delete':
            // بررسی وجود زیردسته‌ها
            $placeholders = str_repeat('?,', count($items) - 1) . '?';
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM categories 
                WHERE parent_id IN ($placeholders)
            ");
            $stmt->execute($items);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('برخی از دسته‌بندی‌های انتخاب شده دارای زیردسته هستند');
            }

            // بررسی وجود محصولات مرتبط
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM products 
                WHERE category_id IN ($placeholders)
            ");
            $stmt->execute($items);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('برخی از دسته‌بندی‌های انتخاب شده دارای محصولات مرتبط هستند');
            }

            // حذف تصاویر
            $stmt = $db->prepare("SELECT thumbnail FROM categories WHERE id IN ($placeholders)");
            $stmt->execute($items);
            while ($category = $stmt->fetch()) {
                if ($category['thumbnail']) {
                    deleteImage($category['thumbnail'], 'categories');
                }
            }

            // حذف دسته‌بندی‌ها
            $stmt = $db->prepare("DELETE FROM categories WHERE id IN ($placeholders)");
            $stmt->execute($items);

            // ثبت فعالیت
            logActivity('categories', 0, 'bulk_delete', 'حذف گروهی دسته‌بندی‌ها');
            break;

        default:
            throw new Exception('عملیات نامعتبر است');
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'عملیات گروهی با موفقیت انجام شد'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error in bulk-action.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
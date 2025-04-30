<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/jdf.php';

// برای نمایش خطاها در حالت دیباگ
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

error_log("Received POST data: " . print_r($_POST, true));
if (!empty($_FILES)) {
    error_log("Received FILES data: " . print_r($_FILES, true));
}


ini_set('display_errors', 0);

// بررسی درخواست Ajax
if (!isAjaxRequest()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'درخواست نامعتبر'
    ]);
    exit;
}

try {
    // تابع بازگشتی برای ساخت درخت دسته‌بندی‌ها
    function buildCategoryTree($db, $parentId = null, $level = 0) {
        $stmt = $db->prepare("
            SELECT c.*, 
                   COALESCE(p.product_count, 0) as product_count,
                   u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count
            FROM categories c
            LEFT JOIN (
                SELECT category_id, COUNT(*) as product_count 
                FROM products 
                GROUP BY category_id
            ) p ON p.category_id = c.id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.parent_id " . ($parentId === null ? "IS NULL" : "= ?") . "
            ORDER BY c.sort_order ASC, c.name ASC
        ");

        if ($parentId === null) {
            $stmt->execute();
        } else {
            $stmt->execute([$parentId]);
        }

        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $html = '';

        foreach ($categories as $category) {
            $statusClass = $category['status'] === 'active' ? 'status-active' : 'status-inactive';
            $statusText = $category['status'] === 'active' ? 'فعال' : 'غیرفعال';
            $hasChildren = $category['children_count'] > 0;

            $html .= '<div class="tree-item" data-id="' . $category['id'] . '" data-status="' . $category['status'] . '">';
            $html .= str_repeat('<div class="tree-indent"></div>', $level);

            $html .= '<div class="tree-item-content">';
            if ($hasChildren) {
                $html .= '<div class="tree-toggle"><i class="fas fa-caret-down"></i></div>';
            }
            $html .= '<div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>';

            // آیکون با رنگ سفارشی
            $iconBackground = $category['color'] ?: '#e3f2fd';
            $html .= '<div class="category-icon" style="background: ' . $iconBackground . '">';
            $html .= '<i class="' . ($category['icon'] ?: 'fas fa-folder') . '"></i>';
            $html .= '</div>';

            $html .= '<div class="category-info">';
            $html .= '<div class="category-name">' . htmlspecialchars($category['name']) . '</div>';
            $html .= '<div class="category-meta">';
            $html .= '<span class="meta-item"><i class="fas fa-box"></i> ' . $category['product_count'] . ' محصول</span>';
            $html .= '<span class="meta-item"><span class="status-badge ' . $statusClass . '">' . $statusText . '</span></span>';
            $html .= '<span class="meta-item"><i class="fas fa-clock"></i> ' . jdate('Y/m/d', strtotime($category['created_at'])) . '</span>';
            $html .= '</div></div>';

            $html .= '<div class="category-actions">';
            if (hasPermission('edit_categories')) {
                $html .= '<button type="button" class="btn btn-sm btn-outline-secondary edit-category" ';
                $html .= 'data-id="' . $category['id'] . '" ';
                $html .= 'data-name="' . htmlspecialchars($category['name']) . '" ';
                $html .= 'data-slug="' . htmlspecialchars($category['slug']) . '" ';
                $html .= 'data-description="' . htmlspecialchars($category['description']) . '" ';
                $html .= 'data-parent="' . ($category['parent_id'] ?: '') . '" ';
                $html .= 'data-status="' . $category['status'] . '" ';
                $html .= 'data-icon="' . ($category['icon'] ?: '') . '" ';
                $html .= 'data-color="' . ($category['color'] ?: '') . '">';
                $html .= '<i class="fas fa-edit"></i></button>';
            }
            if (hasPermission('delete_categories')) {
                $html .= '<button type="button" class="btn btn-sm btn-outline-danger delete-category" ';
                $html .= 'data-id="' . $category['id'] . '" ';
                $html .= 'data-name="' . htmlspecialchars($category['name']) . '">';
                $html .= '<i class="fas fa-trash-alt"></i></button>';
            }
            $html .= '</div></div>';

            // بازگشت فراخوانی برای زیردسته‌ها
            if ($hasChildren) {
                $html .= '<div class="tree-children">';
                $html .= buildCategoryTree($db, $category['id'], $level + 1);
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    // ساخت درخت دسته‌بندی‌ها
    $categoryTree = buildCategoryTree($db);

    // ارسال پاسخ
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $categoryTree
    ]);

} catch (Exception $e) {
    error_log("Error in get-categories.php: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'خطا در دریافت لیست دسته‌بندی‌ها: ' . $e->getMessage()
    ]);
}
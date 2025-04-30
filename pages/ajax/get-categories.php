<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// بررسی درخواست Ajax
if (!isAjaxRequest()) {
    http_response_code(400);
    exit('درخواست نامعتبر');
}

try {
    // تابع بازگشتی برای ساخت درخت دسته‌بندی‌ها
    function buildCategoryTree($db, $parentId = null, $level = 0) {
        $stmt = $db->prepare("
            SELECT c.*,
                   COUNT(p.id) as product_count,
                   u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.parent_id " . ($parentId === null ? "IS NULL" : "= ?") . "
            GROUP BY c.id
            ORDER BY c.position ASC, c.name ASC
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
                $html .= '<button type="button" class="btn btn-sm btn-outline-secondary edit-category" data-id="' . $category['id'] . '">';
                $html .= '<i class="fas fa-edit"></i></button>';
            }
            if (hasPermission('delete_categories')) {
                $html .= '<button type="button" class="btn btn-sm btn-outline-danger delete-category" data-id="' . $category['id'] . '" ';
                $html .= 'data-name="' . htmlspecialchars($category['name']) . '"><i class="fas fa-trash-alt"></i></button>';
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

    $categoryTree = buildCategoryTree($db);

    echo json_encode([
        'success' => true,
        'html' => $categoryTree
    ]);

} catch (Exception $e) {
    error_log("Error in get-categories.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'خطا در دریافت لیست دسته‌بندی‌ها'
    ]);
}
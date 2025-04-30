<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// دریافت متد درخواست
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => 'درخواست نامعتبر'];

try {
    switch ($method) {
        case 'GET':
            // دریافت اطلاعات دسته‌بندی
            if (preg_match('/^\/(\d+)$/', $_SERVER['PATH_INFO'] ?? '', $matches)) {
                $categoryId = (int)$matches[1];
                $stmt = $db->prepare("
                    SELECT c.*, GROUP_CONCAT(t.id) as tag_ids
                    FROM categories c
                    LEFT JOIN category_tags ct ON c.id = ct.category_id
                    LEFT JOIN tags t ON ct.tag_id = t.id
                    WHERE c.id = ?
                    GROUP BY c.id
                ");
                $stmt->execute([$categoryId]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($category) {
                    $category['tag_ids'] = $category['tag_ids'] ? explode(',', $category['tag_ids']) : [];
                    $response = ['success' => true, 'data' => $category];
                } else {
                    $response = ['success' => false, 'message' => 'دسته‌بندی یافت نشد'];
                }
            }
            // دریافت ساختار درختی
            elseif ($_SERVER['PATH_INFO'] === '/tree') {
                $stmt = $db->query("
                    WITH RECURSIVE category_tree AS (
                        SELECT 
                            id, name, parent_id, status, icon,
                            CAST(name AS CHAR(1000)) AS path
                        FROM categories
                        WHERE parent_id IS NULL
                        
                        UNION ALL
                        
                        SELECT 
                            c.id, c.name, c.parent_id, c.status, c.icon,
                            CONCAT(ct.path, ' > ', c.name)
                        FROM categories c
                        JOIN category_tree ct ON c.parent_id = ct.id
                    )
                    SELECT * FROM category_tree
                    ORDER BY path
                ");
                
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $tree = [];
                
                foreach ($categories as $category) {
                    $node = [
                        'id' => $category['id'],
                        'text' => $category['name'],
                        'icon' => $category['icon'] ?: 'fas fa-folder',
                        'type' => $category['status'],
                        'state' => ['opened' => true]
                    ];
                    
                    if (!$category['parent_id']) {
                        $tree[] = $node;
                    } else {
                        // پیدا کردن والد و اضافه کردن به children
                        $parent = findParentNode($tree, $category['parent_id']);
                        if ($parent) {
                            if (!isset($parent['children'])) {
                                $parent['children'] = [];
                            }
                            $parent['children'][] = $node;
                        }
                    }
                }
                
                $response = ['success' => true, 'data' => $tree];
            }
            break;

        case 'POST':
            if ($_SERVER['PATH_INFO'] === '/move') {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = handleMoveCategory($db, $data);
                $response = $result;
            } else {
                $result = handleAddCategory($db);
                $response = $result;
            }
            break;

        case 'PUT':
            if (preg_match('/^\/(\d+)$/', $_SERVER['PATH_INFO'] ?? '', $matches)) {
                $_POST['category_id'] = (int)$matches[1];
                $result = handleEditCategory($db);
                $response = $result;
            }
            break;

        case 'DELETE':
            if (preg_match('/^\/(\d+)$/', $_SERVER['PATH_INFO'] ?? '', $matches)) {
                $_POST['category_id'] = (int)$matches[1];
                $result = handleDeleteCategory($db);
                $response = $result;
            }
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// ارسال پاسخ
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);

// تابع کمکی برای پیدا کردن والد در درخت
function findParentNode(&$nodes, $parentId) {
    foreach ($nodes as &$node) {
        if ($node['id'] === $parentId) {
            return $node;
        }
        if (isset($node['children'])) {
            $result = findParentNode($node['children'], $parentId);
            if ($result) {
                return $result;
            }
        }
    }
    return null;
}
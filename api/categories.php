<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// بررسی دسترسی کاربر
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

// دریافت متد درخواست و مسیر
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

try {
    switch ($method) {
        case 'GET':
            // دریافت اطلاعات یک دسته‌بندی
            if (is_numeric($endpoint)) {
                $categoryId = (int)$endpoint;
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
                    echo json_encode(['success' => true, 'data' => $category]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'دسته‌بندی یافت نشد']);
                }
            }
            // دریافت ساختار درختی
            elseif ($endpoint === 'tree') {
                $categories = getCategoryTree($db);
                echo json_encode(['success' => true, 'data' => $categories]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // جابجایی دسته‌بندی
            if ($endpoint === 'move' && !empty($input)) {
                $result = handleMoveCategory($db);
                echo json_encode($result);
            }
            // ایجاد دسته‌بندی جدید
            else {
                $result = handleAddCategory($db);
                echo json_encode($result);
            }
            break;

        case 'PUT':
            if (is_numeric($endpoint)) {
                $_POST = json_decode(file_get_contents('php://input'), true);
                $_POST['category_id'] = (int)$endpoint;
                $result = handleEditCategory($db);
                echo json_encode($result);
            }
            break;

        case 'DELETE':
            if (is_numeric($endpoint)) {
                $result = handleDeleteCategory($db, (int)$endpoint);
                echo json_encode($result);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است']);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطای سرور']);
}

// دریافت ساختار درختی دسته‌بندی‌ها
function getCategoryTree($db) {
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
    
    return $tree;
}

// پیدا کردن گره والد در درخت
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
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

// دریافت متد درخواست
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($method) {
        case 'POST':
            if (empty($_POST)) {
                $_POST = json_decode(file_get_contents('php://input'), true) ?: [];
            }
            $result = handleAddCategory($db);
            echo json_encode($result);
            break;

        case 'PUT':
            if (empty($_POST)) {
                $_POST = json_decode(file_get_contents('php://input'), true) ?: [];
            }
            if ($id) {
                $_POST['category_id'] = $id;
                $result = handleEditCategory($db);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'شناسه دسته‌بندی مشخص نشده است']);
            }
            break;

        case 'DELETE':
            if ($id) {
                $_POST['category_id'] = $id;
                $result = handleDeleteCategory($db);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'شناسه دسته‌بندی مشخص نشده است']);
            }
            break;

        case 'GET':
            if ($id) {
                $stmt = $db->prepare("
                    SELECT c.*, GROUP_CONCAT(t.id) as tag_ids
                    FROM categories c
                    LEFT JOIN category_tags ct ON c.id = ct.category_id
                    LEFT JOIN tags t ON ct.tag_id = t.id
                    WHERE c.id = ?
                    GROUP BY c.id
                ");
                $stmt->execute([$id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($category) {
                    $category['tag_ids'] = $category['tag_ids'] ? explode(',', $category['tag_ids']) : [];
                    echo json_encode(['success' => true, 'data' => $category]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'دسته‌بندی یافت نشد']);
                }
            } else {
                // دریافت لیست همه دسته‌بندی‌ها
                $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order");
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $categories]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// بررسی دسترسی
redirectIfNotLoggedIn();

header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // دریافت لیست دسته‌بندی‌ها
            $search = $_GET['search'] ?? '';
            $whereClause = $search ? "WHERE name LIKE :search OR description LIKE :search" : "";
            
            $stmt = $db->prepare("
                SELECT 
                    c.*,
                    COUNT(DISTINCT p.id) as products_count,
                    COUNT(DISTINCT s.id) as subcategories_count
                FROM categories c 
                LEFT JOIN products p ON p.category_id = c.id
                LEFT JOIN categories s ON s.parent_id = c.id
                $whereClause
                GROUP BY c.id
                ORDER BY c.sort_order
            ");

            if ($search) {
                $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
            }

            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'categories' => $categories
            ]);
            break;

        case 'POST':
            // افزودن دسته‌بندی جدید
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("
                INSERT INTO categories (name, icon, description, parent_id, status, sort_order)
                VALUES (:name, :icon, :description, :parent_id, :status, 
                    (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories c2))
            ");

            $stmt->execute([
                'name' => $data['name'],
                'icon' => $data['icon'],
                'description' => $data['description'],
                'parent_id' => $data['parent_id'] ?: null,
                'status' => $data['status'] ?? 'active'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'دسته‌بندی با موفقیت ایجاد شد',
                'id' => $db->lastInsertId()
            ]);
            break;

        case 'PUT':
            // ویرایش دسته‌بندی
            $data = json_decode(file_get_contents('php://input'), true);

            if (isset($data['action']) && $data['action'] === 'reorder') {
                // بروزرسانی ترتیب دسته‌بندی‌ها
                $stmt = $db->prepare("
                    UPDATE categories 
                    SET sort_order = CASE
                        WHEN sort_order = :old THEN :new
                        WHEN sort_order = :new THEN :old
                        ELSE sort_order
                    END
                    WHERE sort_order IN (:old, :new)
                ");

                $stmt->execute([
                    'old' => $data['oldIndex'],
                    'new' => $data['newIndex']
                ]);
            } else {
                // بروزرسانی اطلاعات دسته‌بندی
                $stmt = $db->prepare("
                    UPDATE categories 
                    SET name = :name,
                        icon = :icon,
                        description = :description,
                        parent_id = :parent_id,
                        status = :status
                    WHERE id = :id
                ");

                $stmt->execute([
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'icon' => $data['icon'],
                    'description' => $data['description'],
                    'parent_id' => $data['parent_id'] ?: null,
                    'status' => $data['status']
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'بروزرسانی با موفقیت انجام شد'
            ]);
            break;

        case 'DELETE':
            // حذف دسته‌بندی
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('شناسه دسته‌بندی الزامی است');
            }

            // بررسی وجود محصول در دسته‌بندی
            $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('این دسته‌بندی دارای محصول است و قابل حذف نیست');
            }

            // بررسی وجود زیردسته
            $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('این دسته‌بندی دارای زیردسته است و قابل حذف نیست');
            }

            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'success' => true,
                'message' => 'دسته‌بندی با موفقیت حذف شد'
            ]);
            break;

        default:
            throw new Exception('متد درخواست نامعتبر است');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
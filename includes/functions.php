<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * بررسی درخواست Ajax
 * @return bool
 */
function isAjaxRequest() {
    // بررسی هدر X-Requested-With
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    
    // بررسی Accept header
    if (!empty($_SERVER['HTTP_ACCEPT']) && 
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    
    // بررسی Content-Type header
    if (!empty($_SERVER['CONTENT_TYPE']) && 
        strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        return true;
    }
    
    return false;
}

function hasPermission($permission) {
    // اگر کاربر سوپر ادمین است
    if ($_SESSION['user_role'] === 'super_admin') {
        return true;
    }

    // بررسی دسترسی از دیتابیس
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT 1 
            FROM user_permissions up 
            INNER JOIN permissions p ON p.id = up.permission_id 
            WHERE up.user_id = ? AND p.name = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking permission: " . $e->getMessage());
        return false;
    }
}

// تابع فرمت تاریخ
function formatDate($date, $includeTime = false) {
    if (!$date) return '';
    $timestamp = strtotime($date);
    return $includeTime 
        ? jdate('Y/m/d H:i', $timestamp)
        : jdate('Y/m/d', $timestamp);
}

// تابع محاسبه زمان سپری شده
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'چند لحظه پیش';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' دقیقه پیش';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' ساعت پیش';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' روز پیش';
    } else {
        return formatDate($datetime);
    }
}

// تابع ایجاد دسته‌بندی جدید
function handleAddCategory($db) {
    try {
        if (empty($_POST['name'])) {
            throw new Exception('نام دسته‌بندی الزامی است');
        }

        $name = clean($_POST['name']);
        $slug = !empty($_POST['slug']) ? clean($_POST['slug']) : createSlug($name);
        $description = clean($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = clean($_POST['status'] ?? 'active');
        $icon = clean($_POST['icon'] ?? '');
        $color = clean($_POST['color'] ?? '');
        $metaTitle = clean($_POST['meta_title'] ?? '');
        $metaKeywords = clean($_POST['meta_keywords'] ?? '');
        $metaDescription = clean($_POST['meta_description'] ?? '');
        
        $db->beginTransaction();

        // درج دسته‌بندی
        $stmt = $db->prepare("
            INSERT INTO categories (
                name, slug, description, parent_id, status, icon, color,
                meta_title, meta_keywords, meta_description, created_by
            ) VALUES (
                :name, :slug, :description, :parent_id, :status, :icon, :color,
                :meta_title, :meta_keywords, :meta_description, :user_id
            )
        ");

        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description,
            ':parent_id' => $parentId,
            ':status' => $status,
            ':icon' => $icon,
            ':color' => $color,
            ':meta_title' => $metaTitle,
            ':meta_keywords' => $metaKeywords,
            ':meta_description' => $metaDescription,
            ':user_id' => $_SESSION['user_id']
        ]);

        $categoryId = $db->lastInsertId();

        // ذخیره برچسب‌ها
        if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
            $tagStmt = $db->prepare("INSERT INTO category_tags (category_id, tag_id) VALUES (:category_id, :tag_id)");
            foreach ($_POST['tags'] as $tagId) {
                $tagStmt->execute([':category_id' => $categoryId, ':tag_id' => (int)$tagId]);
            }
        }

        // ثبت فعالیت
        logCategoryActivity($db, $categoryId, 'create');

        $db->commit();
        return ['success' => true, 'message' => 'دسته‌بندی با موفقیت ایجاد شد'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// تابع ویرایش دسته‌بندی
function handleEditCategory($db) {
    try {
        if (empty($_POST['category_id']) || empty($_POST['name'])) {
            throw new Exception('اطلاعات ناقص است');
        }

        $categoryId = (int)$_POST['category_id'];
        $name = clean($_POST['name']);
        $slug = !empty($_POST['slug']) ? clean($_POST['slug']) : createSlug($name);
        $description = clean($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = clean($_POST['status'] ?? 'active');
        $icon = clean($_POST['icon'] ?? '');
        $color = clean($_POST['color'] ?? '');
        $metaTitle = clean($_POST['meta_title'] ?? '');
        $metaKeywords = clean($_POST['meta_keywords'] ?? '');
        $metaDescription = clean($_POST['meta_description'] ?? '');

        $db->beginTransaction();

        // بروزرسانی دسته‌بندی
        $stmt = $db->prepare("
            UPDATE categories SET 
                name = :name,
                slug = :slug,
                description = :description,
                parent_id = :parent_id,
                status = :status,
                icon = :icon,
                color = :color,
                meta_title = :meta_title,
                meta_keywords = :meta_keywords,
                meta_description = :meta_description,
                last_updated_by = :user_id,
                updated_at = NOW()
            WHERE id = :category_id
        ");

        $stmt->execute([
            ':category_id' => $categoryId,
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description,
            ':parent_id' => $parentId,
            ':status' => $status,
            ':icon' => $icon,
            ':color' => $color,
            ':meta_title' => $metaTitle,
            ':meta_keywords' => $metaKeywords,
            ':meta_description' => $metaDescription,
            ':user_id' => $_SESSION['user_id']
        ]);

        // بروزرسانی برچسب‌ها
        $db->prepare("DELETE FROM category_tags WHERE category_id = ?")->execute([$categoryId]);
        
        if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
            $tagStmt = $db->prepare("INSERT INTO category_tags (category_id, tag_id) VALUES (:category_id, :tag_id)");
            foreach ($_POST['tags'] as $tagId) {
                $tagStmt->execute([':category_id' => $categoryId, ':tag_id' => (int)$tagId]);
            }
        }

        // ثبت فعالیت
        logCategoryActivity($db, $categoryId, 'update');

        $db->commit();
        return ['success' => true, 'message' => 'دسته‌بندی با موفقیت بروزرسانی شد'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// تابع حذف دسته‌بندی
function handleDeleteCategory($db) {
    try {
        if (empty($_POST['category_id'])) {
            throw new Exception('شناسه دسته‌بندی نامعتبر است');
        }

        $categoryId = (int)$_POST['category_id'];

        // بررسی وجود زیردسته‌ها
        $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$categoryId]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('ابتدا باید زیردسته‌های این دسته‌بندی را حذف کنید');
        }

        $db->beginTransaction();

        // حذف برچسب‌ها
        $db->prepare("DELETE FROM category_tags WHERE category_id = ?")->execute([$categoryId]);
        
        // ثبت فعالیت
        logCategoryActivity($db, $categoryId, 'delete');

        // حذف دسته‌بندی
        $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$categoryId]);

        $db->commit();
        return ['success' => true, 'message' => 'دسته‌بندی با موفقیت حذف شد'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// تابع جابجایی دسته‌بندی
function handleMoveCategory($db) {
    try {
        if (empty($_POST['id']) || !isset($_POST['parent'])) {
            throw new Exception('اطلاعات جابجایی ناقص است');
        }

        $categoryId = (int)$_POST['id'];
        $newParentId = $_POST['parent'] ? (int)$_POST['parent'] : null;
        $position = (int)($_POST['position'] ?? 0);

        // بررسی حلقه در ساختار درختی
        if ($newParentId === $categoryId) {
            throw new Exception('دسته‌بندی نمی‌تواند زیرمجموعه خودش باشد');
        }

        $db->beginTransaction();

        // بروزرسانی parent_id
        $stmt = $db->prepare("UPDATE categories SET parent_id = ?, position = ? WHERE id = ?");
        $stmt->execute([$newParentId, $position, $categoryId]);

        // ثبت فعالیت
        logCategoryActivity($db, $categoryId, 'move');

        $db->commit();
        return ['success' => true, 'message' => 'دسته‌بندی با موفقیت جابجا شد'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// تابع عملیات گروهی
function handleBulkAction($db) {
    try {
        if (empty($_POST['action']) || empty($_POST['items']) || !is_array($_POST['items'])) {
            throw new Exception('پارامترهای عملیات گروهی نامعتبر است');
        }

        $action = clean($_POST['action']);
        $items = array_map('intval', $_POST['items']);

        $db->beginTransaction();

        switch ($action) {
            case 'activate':
            case 'deactivate':
                $status = $action === 'activate' ? 'active' : 'inactive';
                $stmt = $db->prepare("UPDATE categories SET status = ? WHERE id IN (" . str_repeat('?,', count($items)-1) . "?)");
                $stmt->execute(array_merge([$status], $items));
                break;

            case 'delete':
                // بررسی وجود زیردسته‌ها
                $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id IN (" . str_repeat('?,', count($items)-1) . "?)");
                $stmt->execute($items);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('برخی از دسته‌بندی‌های انتخاب شده دارای زیردسته هستند');
                }

                // حذف برچسب‌ها
                $db->prepare("DELETE FROM category_tags WHERE category_id IN (" . str_repeat('?,', count($items)-1) . "?)")->execute($items);
                
                // حذف دسته‌بندی‌ها
                $db->prepare("DELETE FROM categories WHERE id IN (" . str_repeat('?,', count($items)-1) . "?)")->execute($items);
                break;

            default:
                throw new Exception('عملیات نامعتبر است');
        }

        $db->commit();
        return ['success' => true, 'message' => 'عملیات گروهی با موفقیت انجام شد'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
function clean($input) {
    if (is_array($input)) {
        return array_map('clean', $input);
    }
    $input = trim($input);
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}


/**
 * تولید slug از متن
 * @param string $text متن ورودی
 * @return string نامک تولید شده
 */
function createSlug($text) {
    // آرایه حروف فارسی و معادل انگلیسی
    $persian = ['ا', 'ب', 'پ', 'ت', 'ث', 'ج', 'چ', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'ژ', 
                'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ک', 'گ', 'ل', 'م', 
                'ن', 'و', 'ه', 'ی'];
    $english = ['a', 'b', 'p', 't', 's', 'j', 'ch', 'h', 'kh', 'd', 'z', 'r', 'z', 'zh',
                's', 'sh', 's', 'z', 't', 'z', 'a', 'gh', 'f', 'gh', 'k', 'g', 'l', 'm',
                'n', 'v', 'h', 'y'];

    // تبدیل به حروف کوچک
    $text = mb_strtolower($text, 'UTF-8');
    
    // جایگزینی حروف فارسی با انگلیسی
    $text = str_replace($persian, $english, $text);
    
    // حذف کاراکترهای غیرمجاز و تبدیل فاصله به خط تیره
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

/**
 * ثبت فعالیت‌های سیستم
 * @param string $module نام ماژول
 * @param int $recordId شناسه رکورد
 * @param string $action نوع عملیات
 * @param string $details توضیحات
 * @return bool نتیجه ثبت
 */
function logActivity($module, $recordId, $action, $details = '') {
    global $db;
    try {
        $stmt = $db->prepare("
            INSERT INTO activities (
                module, record_id, action, details, 
                user_id, ip_address, created_at
            ) VALUES (
                :module, :record_id, :action, :details,
                :user_id, :ip_address, NOW()
            )
        ");

        return $stmt->execute([
            ':module' => $module,
            ':record_id' => $recordId,
            ':action' => $action,
            ':details' => $details,
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        error_log("Error in logActivity: " . $e->getMessage());
        return false;
    }
}

// تابع ثبت فعالیت دسته‌بندی
function logCategoryActivity($db, $categoryId, $action, $details = '') {
    $stmt = $db->prepare("
        INSERT INTO category_activities (
            category_id, user_id, action, details, created_at
        ) VALUES (
            :category_id, :user_id, :action, :details, NOW()
        )
    ");

    return $stmt->execute([
        ':category_id' => $categoryId,
        ':user_id' => $_SESSION['user_id'],
        ':action' => $action,
        ':details' => $details
    ]);
}
/**
 * ایجاد پیام هشدار
 * @param string $type نوع پیام (success, error, warning, info)
 * @param string $message متن پیام
 * @param string $title عنوان پیام (اختیاری)
 */
function createAlert($type, $message, $title = '') {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message,
        'title' => $title ?: ucfirst($type)
    ];
}




// تابع نمایش پیام‌ها
function showAlerts() {
    if (!empty($_SESSION['alerts'])) {
        foreach ($_SESSION['alerts'] as $alert) {
            echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">
                    ' . $alert['message'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
        unset($_SESSION['alerts']);
    }
}
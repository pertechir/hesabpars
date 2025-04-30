<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// بررسی دسترسی کاربر
redirectIfNotLoggedIn();

// تنظیمات صفحه‌بندی
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// تنظیمات فیلتر و مرتب‌سازی
$sortBy = isset($_GET['sort']) ? clean($_GET['sort']) : 'created_at';
$sortOrder = isset($_GET['order']) ? clean($_GET['order']) : 'DESC';
$filterStatus = isset($_GET['status']) ? clean($_GET['status']) : '';
$searchQuery = isset($_GET['search']) ? clean($_GET['search']) : '';
$parentId = isset($_GET['parent']) ? (int)$_GET['parent'] : null;

// آرایه نگهداری خطاها و پیام‌ها
$errors = [];
$messages = [];

// مقداردهی اولیه متغیرها
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'parent' => 0,
    'child' => 0,
    'products' => 0,
    'depth' => 0
];

$categories = [];
$recentActivities = [];
$breadcrumbs = [];

// پردازش عملیات‌های POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            handleAddCategory($db);
            break;
        case 'edit':
            handleEditCategory($db);
            break;
        case 'delete':
            handleDeleteCategory($db);
            break;
        case 'move':
            handleMoveCategory($db);
            break;
        case 'bulk':
            handleBulkAction($db);
            break;
    }
}

try {
    // دریافت آمار کلی
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
            (SELECT COUNT(*) FROM categories WHERE parent_id IS NULL) as parent,
            (SELECT COUNT(*) FROM categories WHERE parent_id IS NOT NULL) as child,
            SUM(products_count) as products,
            MAX((
                WITH RECURSIVE category_depth AS (
                    SELECT id, parent_id, 0 as depth
                    FROM categories
                    WHERE parent_id IS NULL
                    UNION ALL
                    SELECT c.id, c.parent_id, cd.depth + 1
                    FROM categories c
                    INNER JOIN category_depth cd ON c.parent_id = cd.id
                )
                SELECT MAX(depth) FROM category_depth
            )) as depth
        FROM categories
    ";
    $stats = $db->query($statsQuery)->fetch(PDO::FETCH_ASSOC);

    // ساخت شرط‌های SQL برای فیلتر
    $whereConditions = [];
    $params = [];

    if ($filterStatus) {
        $whereConditions[] = "c.status = :status";
        $params[':status'] = $filterStatus;
    }

    if ($searchQuery) {
        $whereConditions[] = "(
            c.name LIKE :search OR 
            c.slug LIKE :search OR 
            c.description LIKE :search OR
            c.meta_title LIKE :search OR
            c.meta_description LIKE :search
        )";
        $params[':search'] = "%{$searchQuery}%";
    }

    if ($parentId !== null) {
        $whereConditions[] = "c.parent_id = :parent_id";
        $params[':parent_id'] = $parentId;
    }

    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // دریافت تعداد کل برای صفحه‌بندی
    $countQuery = "SELECT COUNT(*) FROM categories c $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);

    // دریافت دسته‌بندی‌ها با اطلاعات کامل
    $categoriesQuery = "
        WITH RECURSIVE category_tree AS (
            SELECT 
                c.*,
                CAST(c.name AS CHAR(1000)) AS path,
                0 as level,
                c.id as root_id
            FROM categories c
            WHERE parent_id IS NULL
            
            UNION ALL
            
            SELECT 
                child.*,
                CONCAT(ct.path, ' > ', child.name),
                ct.level + 1,
                ct.root_id
            FROM categories child
            JOIN category_tree ct ON child.parent_id = ct.id
        )
        SELECT 
            ct.*,
            COALESCE(p.product_count, 0) as products_count,
            COALESCE(s.sub_count, 0) as subcategories_count,
            COALESCE(v.view_count, 0) as views,
            u.username as created_by,
            lu.username as last_updated_by,
            parent.name as parent_name,
            GROUP_CONCAT(DISTINCT t.name) as tags
        FROM category_tree ct
        LEFT JOIN (
            SELECT category_id, COUNT(*) as product_count 
            FROM products 
            GROUP BY category_id
        ) p ON p.category_id = ct.id
        LEFT JOIN (
            SELECT parent_id, COUNT(*) as sub_count 
            FROM categories 
            GROUP BY parent_id
        ) s ON s.parent_id = ct.id
        LEFT JOIN (
            SELECT category_id, COUNT(*) as view_count 
            FROM category_views 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY category_id
        ) v ON v.category_id = ct.id
        LEFT JOIN categories parent ON ct.parent_id = parent.id
        LEFT JOIN users u ON u.id = ct.created_by
        LEFT JOIN users lu ON lu.id = ct.last_updated_by
        LEFT JOIN category_tags ct_tag ON ct.id = ct_tag.category_id
        LEFT JOIN tags t ON ct_tag.tag_id = t.id
        $whereClause
        GROUP BY ct.id
        ORDER BY ct.path, ct.$sortBy $sortOrder
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($categoriesQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // دریافت breadcrumbs اگر parent_id تنظیم شده باشد
    if ($parentId) {
        $breadcrumbQuery = "
            WITH RECURSIVE category_path AS (
                SELECT id, parent_id, name, 0 as level
                FROM categories
                WHERE id = :category_id
                
                UNION ALL
                
                SELECT c.id, c.parent_id, c.name, cp.level + 1
                FROM categories c
                INNER JOIN category_path cp ON c.id = cp.parent_id
            )
            SELECT id, name
            FROM category_path
            ORDER BY level DESC
        ";
        $stmt = $db->prepare($breadcrumbQuery);
        $stmt->bindValue(':category_id', $parentId, PDO::PARAM_INT);
        $stmt->execute();
        $breadcrumbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // دریافت فعالیت‌های اخیر
    $recentQuery = "
        SELECT 
            'category' as type,
            c.id,
            c.name,
            c.status,
            u.username as user,
            ca.action,
            ca.details,
            ca.created_at
        FROM category_activities ca
        JOIN categories c ON c.id = ca.category_id
        JOIN users u ON u.id = ca.user_id
        ORDER BY ca.created_at DESC
        LIMIT 10
    ";
    $recentActivities = $db->query($recentQuery)->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $errors[] = "خطا در دریافت اطلاعات دسته‌بندی‌ها";
}

// تنظیمات نمایش
$viewMode = isset($_COOKIE['category_view_mode']) ? $_COOKIE['category_view_mode'] : 'grid';
$themeName = isset($_COOKIE['category_theme']) ? $_COOKIE['category_theme'] : 'light';

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="<?php echo $themeName; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت دسته‌بندی‌ها - <?php echo SITE_NAME; ?></title>
    
    <!-- Styles -->
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/categories.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jstree@3.3.15/dist/themes/default/style.min.css">
    
    <!-- Custom Fonts -->
    <link rel="stylesheet" href="../assets/fonts/anjoman/stylesheet.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="categories-container">
        <!-- نوار هدر -->
        <div class="categories-header">
            <div class="header-info">
                <h1 class="page-title">مدیریت دسته‌بندی‌ها</h1>
                <div class="categories-stats">
                    <span class="stat-item" title="کل دسته‌بندی‌ها">
                        <i class="fas fa-folder"></i>
                        <?php echo number_format($stats['total']); ?>
                    </span>
                    <span class="stat-item" title="دسته‌بندی‌های فعال">
                        <i class="fas fa-check-circle"></i>
                        <?php echo number_format($stats['active']); ?>
                    </span>
                    <span class="stat-item" title="دسته‌بندی‌های غیرفعال">
                        <i class="fas fa-times-circle"></i>
                        <?php echo number_format($stats['inactive']); ?>
                    </span>
                    <span class="stat-item" title="دسته‌بندی‌های والد">
                        <i class="fas fa-folder-plus"></i>
                        <?php echo number_format($stats['parent']); ?>
                    </span>
                    <span class="stat-item" title="دسته‌بندی‌های فرزند">
                        <i class="fas fa-level-down-alt"></i>
                        <?php echo number_format($stats['child']); ?>
                    </span>
                </div>
            </div>
            
            <div class="header-actions">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i>
                    افزودن دسته‌بندی جدید
                </button>
                
                <div class="view-options">
                    <button class="btn btn-icon <?php echo $viewMode === 'grid' ? 'active' : ''; ?>" 
                            data-view="grid" title="نمایش شبکه‌ای">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="btn btn-icon <?php echo $viewMode === 'list' ? 'active' : ''; ?>" 
                            data-view="list" title="نمایش لیستی">
                        <i class="fas fa-list"></i>
                    </button>
                    <button class="btn btn-icon <?php echo $viewMode === 'tree' ? 'active' : ''; ?>" 
                            data-view="tree" title="نمایش درختی">
                        <i class="fas fa-sitemap"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- نوار فیلتر و جستجو -->
        <div class="filter-bar">
            <div class="search-box">
                <input type="text" id="categorySearch" placeholder="جستجو در دسته‌بندی‌ها..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                <i class="fas fa-search"></i>
            </div>
            
            <div class="filters">
                <select id="statusFilter" class="form-select">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>فعال</option>
                    <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>غیرفعال</option>
                </select>
                
                <select id="sortFilter" class="form-select">
                    <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>تاریخ ایجاد</option>
                    <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>نام</option>
                    <option value="products_count" <?php echo $sortBy === 'products_count' ? 'selected' : ''; ?>>تعداد محصولات</option>
                    <option value="views" <?php echo $sortBy === 'views' ? 'selected' : ''; ?>>بازدید</option>
                </select>
                
                <select id="orderFilter" class="form-select">
                    <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>نزولی</option>
                    <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>صعودی</option>
                </select>
            </div>
        </div>

        <!-- Breadcrumb -->
        <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb" class="categories-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="?">دسته‌بندی‌ها</a>
                </li>
                <?php foreach ($breadcrumbs as $crumb): ?>
                <li class="breadcrumb-item <?php echo $crumb['id'] == $parentId ? 'active' : ''; ?>">
                    <?php if ($crumb['id'] == $parentId): ?>
                        <?php echo htmlspecialchars($crumb['name']); ?>
                    <?php else: ?>
                        <a href="?parent=<?php echo $crumb['id']; ?>">
                            <?php echo htmlspecialchars($crumb['name']); ?>
                        </a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <!-- محتوای اصلی -->
        <div class="categories-content">
            <!-- نمایش خطاها -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- نمایش پیام‌ها -->
            <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <ul class="mb-0">
                    <?php foreach ($messages as $message): ?>
                        <li><?php echo $message; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- نمایش دسته‌بندی‌ها -->
            <div class="categories-grid <?php echo $viewMode; ?>">
                <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-header">
                        <div class="category-icon">
                            <i class="<?php echo $category['icon'] ?? 'fas fa-folder'; ?>"></i>
                        </div>
                        <div class="category-info">
                            <h3 class="category-name">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <?php if ($category['status'] === 'inactive'): ?>
                                    <span class="badge bg-warning">غیرفعال</span>
                                <?php endif; ?>
                            </h3>
                            <?php if ($category['parent_name']): ?>
                            <span class="parent-category">
                                <i class="fas fa-level-up-alt fa-rotate-90"></i>
                                <?php echo htmlspecialchars($category['parent_name']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="category-stats">
                        <span title="تعداد محصولات">
                            <i class="fas fa-box"></i>
                            <?php echo number_format($category['products_count']); ?>
                        </span>
                        <span title="تعداد زیردسته‌ها">
                            <i class="fas fa-folder-tree"></i>
                            <?php echo number_format($category['subcategories_count']); ?>
                        </span>
                        <span title="بازدید 30 روز گذشته">
                            <i class="fas fa-eye"></i>
                            <?php echo number_format($category['views']); ?>
                        </span>
                    </div>
                    
                    <div class="category-actions">
                        <button class="btn btn-icon" title="ویرایش" 
                                onclick="editCategory(<?php echo $category['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-icon" title="افزودن زیردسته" 
                                onclick="addSubcategory(<?php echo $category['id']; ?>)">
                            <i class="fas fa-folder-plus"></i>
                        </button>
                        <button class="btn btn-icon" title="مشاهده محصولات" 
                                onclick="viewProducts(<?php echo $category['id']; ?>)">
                            <i class="fas fa-box-open"></i>
                        </button>
                        <button class="btn btn-icon" title="حذف" 
                                onclick="deleteCategory(<?php echo $category['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                    <?php if (!empty($category['tags'])): ?>
                    <div class="category-tags">
                        <?php foreach (explode(',', $category['tags']) as $tag): ?>
                        <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="category-meta">
                        <small>
                            ایجاد: <?php echo formatDate($category['created_at']); ?> 
                            توسط <?php echo htmlspecialchars($category['created_by']); ?>
                        </small>
                        <?php if ($category['last_updated_by']): ?>
                        <small>
                            آخرین ویرایش: <?php echo formatDate($category['updated_at']); ?>
                            توسط <?php echo htmlspecialchars($category['last_updated_by']); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- صفحه‌بندی -->
            <?php if ($totalPages > 1): ?>
            <nav class="categories-pagination">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchQuery); ?><?php echo $parentId ? '&parent=' . $parentId : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchQuery); ?><?php echo $parentId ? '&parent=' . $parentId : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchQuery); ?><?php echo $parentId ? '&parent=' . $parentId : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>

        <!-- نمایش فعالیت‌های اخیر -->
        <div class="recent-activities">
            <h3>فعالیت‌های اخیر</h3>
            <div class="activities-list">
                <?php foreach ($recentActivities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php
                        $icon = 'fa-info-circle';
                        switch ($activity['action']) {
                            case 'create': $icon = 'fa-plus-circle'; break;
                            case 'update': $icon = 'fa-edit'; break;
                            case 'delete': $icon = 'fa-trash'; break;
                            case 'move': $icon = 'fa-arrows-alt'; break;
                        }
                        ?>
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="activity-details">
                        <span class="activity-user"><?php echo htmlspecialchars($activity['user']); ?></span>
                        <?php
                        switch ($activity['action']) {
                            case 'create':
                                echo 'دسته‌بندی جدید ایجاد کرد';
                                break;
                            case 'update':
                                echo 'دسته‌بندی را ویرایش کرد';
                                break;
                            case 'delete':
                                echo 'دسته‌بندی را حذف کرد';
                                break;
                            case 'move':
                                echo 'دسته‌بندی را منتقل کرد';
                                break;
                        }
                        ?>
                        <span class="activity-target">
                            <?php echo htmlspecialchars($activity['name']); ?>
                        </span>
                    </div>
                    <span class="activity-time" title="<?php echo formatDate($activity['created_at'], true); ?>">
                        <?php echo timeAgo($activity['created_at']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal افزودن دسته‌بندی -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">افزودن دسته‌بندی جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm" method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">نام دسته‌بندی</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نامک (Slug)</label>
                                <input type="text" name="slug" class="form-control" dir="ltr">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">دسته‌بندی والد</label>
                                <select name="parent_id" class="form-control select2">
                                    <option value="">بدون والد</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo str_repeat('- ', $cat['level']) . htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">وضعیت</label>
                                <select name="status" class="form-control">
                                    <option value="active">فعال</option>
                                    <option value="inactive">غیرفعال</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">توضیحات</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">آیکون</label>
                                <div class="input-group">
                                    <input type="text" name="icon" class="form-control icon-picker" dir="ltr">
                                    <button type="button" class="btn btn-outline-secondary" id="iconPickerBtn">
                                        <i class="fas fa-icons"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رنگ</label>
                                <input type="text" name="color" class="form-control color-picker" dir="ltr">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">برچسب‌ها</label>
                                <select name="tags[]" class="form-control select2" multiple>
                                    <?php
                                    // دریافت لیست برچسب‌ها از دیتابیس
                                    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($tags as $tag):
                                    ?>
                                    <option value="<?php echo $tag['id']; ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                                        
                                                <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">عنوان سئو</label>
                                <input type="text" name="meta_title" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">کلمات کلیدی سئو</label>
                                <input type="text" name="meta_keywords" class="form-control" dir="ltr">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">توضیحات سئو</label>
                            <textarea name="meta_description" class="form-control" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" form="addCategoryForm" class="btn btn-primary">افزودن دسته‌بندی</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ویرایش دسته‌بندی -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ویرایش دسته‌بندی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">نام دسته‌بندی</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نامک (Slug)</label>
                                <input type="text" name="slug" id="edit_slug" class="form-control" dir="ltr">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">دسته‌بندی والد</label>
                                <select name="parent_id" id="edit_parent_id" class="form-control select2">
                                    <option value="">بدون والد</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo str_repeat('- ', $cat['level']) . htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">وضعیت</label>
                                <select name="status" id="edit_status" class="form-control">
                                    <option value="active">فعال</option>
                                    <option value="inactive">غیرفعال</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">توضیحات</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">آیکون</label>
                                <div class="input-group">
                                    <input type="text" name="icon" id="edit_icon" class="form-control icon-picker" dir="ltr">
                                    <button type="button" class="btn btn-outline-secondary" id="editIconPickerBtn">
                                        <i class="fas fa-icons"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رنگ</label>
                                <input type="text" name="color" id="edit_color" class="form-control color-picker" dir="ltr">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">برچسب‌ها</label>
                                <select name="tags[]" id="edit_tags" class="form-control select2" multiple>
                                    <?php foreach ($tags as $tag): ?>
                                    <option value="<?php echo $tag['id']; ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">عنوان سئو</label>
                                <input type="text" name="meta_title" id="edit_meta_title" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">کلمات کلیدی سئو</label>
                                <input type="text" name="meta_keywords" id="edit_meta_keywords" class="form-control" dir="ltr">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">توضیحات سئو</label>
                            <textarea name="meta_description" id="edit_meta_description" class="form-control" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" form="editCategoryForm" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jstree@3.3.15/dist/jstree.min.js"></script>
    <script src="../assets/js/categories.js"></script>
</body>
</html>
<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/jdf.php';

// بررسی دسترسی کاربر
checkPermission('view_categories');

// تنظیم عنوان صفحه
$pageTitle = 'مدیریت دسته‌بندی‌ها';

try {
    // دریافت آمار دسته‌بندی‌ها
    $statsQuery = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as parents,
            SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as children
        FROM categories
    ");
    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

    // دریافت لیست دسته‌بندی‌ها
    $categoriesQuery = $db->query("
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
        WHERE c.parent_id IS NULL
        ORDER BY c.sort_order ASC, c.name ASC
    ");
    $categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error in categories.php: " . $e->getMessage());
    createAlert('error', 'خطا در دریافت اطلاعات');
    $stats = [
        'total' => 0,
        'active' => 0,
        'parents' => 0,
        'children' => 0
    ];
    $categories = [];
}

// دریافت دسته‌بندی‌های والد برای select
function getCategoryOptions($db, $excludeId = null) {
    try {
        $sql = "SELECT id, name, parent_id FROM categories WHERE status = 'active'";
        if ($excludeId) {
            $sql .= " AND id != ? AND parent_id != ?";
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $db->prepare($sql);
        if ($excludeId) {
            $stmt->execute([$excludeId, $excludeId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error in getCategoryOptions: " . $e->getMessage());
        return [];
    }
}

$parentCategories = getCategoryOptions($db);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css">
    <link rel="stylesheet" href="../assets/css/categories.css">
</head>
<body>
    <?php include_once '../includes/sidebar.php'; ?>

    <div class="category-container">
        <!-- Header Section -->
        <div class="category-header">
            <div class="header-title">
                <h1><?php echo $pageTitle; ?></h1>
                <div class="header-breadcrumb">
                    <i class="fas fa-home"></i>
                    <span>داشبورد</span>
                    <i class="fas fa-angle-left"></i>
                    <span><?php echo $pageTitle; ?></span>
                </div>
            </div>
            <div class="header-actions">
                <?php if (hasPermission('add_categories')): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i>
                    افزودن دسته‌بندی جدید
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>کل دسته‌بندی‌ها</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['active']); ?></h3>
                    <p>دسته‌بندی‌های فعال</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon parent">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['parents']); ?></h3>
                    <p>دسته‌بندی‌های اصلی</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon child">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['children']); ?></h3>
                    <p>زیردسته‌ها</p>
                </div>
            </div>
        </div>

        <!-- Tree Section -->
        <div class="category-tree-section">
            <div class="tree-header">
                <div class="tree-title">
                    <i class="fas fa-sitemap"></i>
                    ساختار دسته‌بندی‌ها
                </div>
                <div class="tree-actions">
                    <div class="tree-search">
                        <input type="text" id="categorySearch" class="form-control" placeholder="جستجو در دسته‌بندی‌ها...">
                        <i class="fas fa-search"></i>
                    </div>
                    <?php if (hasPermission('bulk_edit_categories')): ?>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bulkActionModal">
                        <i class="fas fa-tasks"></i>
                        عملیات گروهی
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tree-filters">
                <div class="filter-item">
                    <select id="statusFilter" class="form-select">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                    </select>
                </div>
            </div>

            <div class="tree-view">
                <?php
                function renderCategoryTree($categories, $level = 0) {
                    $html = '';
                    foreach ($categories as $category) {
                        $statusClass = $category['status'] === 'active' ? 'status-active' : 'status-inactive';
                        $statusText = $category['status'] === 'active' ? 'فعال' : 'غیرفعال';
                        
                        $html .= '<div class="tree-item" data-id="' . $category['id'] . '" data-status="' . $category['status'] . '">';
                        $html .= str_repeat('<div class="tree-indent"></div>', $level);
                        
                        $html .= '<div class="tree-item-content">';
                        if ($category['children_count'] > 0) {
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
                        if ($category['children_count'] > 0) {
                            global $db;
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
                                WHERE c.parent_id = ?
                                ORDER BY c.sort_order ASC, c.name ASC
                            ");
                            $stmt->execute([$category['id']]);
                            $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            $html .= '<div class="tree-children">';
                            $html .= renderCategoryTree($children, $level + 1);
                            $html .= '</div>';
                        }
                        
                        $html .= '</div>';
                    }
                    return $html;
                }
                
                echo renderCategoryTree($categories);
                ?>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">افزودن دسته‌بندی جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCategoryForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">نام دسته‌بندی</label>
                                <input type="text" class="form-control category-name-input" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نامک (Slug)</label>
                                <input type="text" class="form-control category-slug-input" name="slug">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">دسته‌بندی والد</label>
                                <select class="form-select select2" name="parent_id">
                                    <option value="">دسته‌بندی اصلی</option>
                                    <?php foreach ($parentCategories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">وضعیت</label>
                                <select class="form-select" name="status">
                                    <option value="active">فعال</option>
                                    <option value="inactive">غیرفعال</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">توضیحات</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">آیکون</label>
                                <input type="text" class="form-control" name="icon" placeholder="مثال: fas fa-folder">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رنگ</label>
                                <input type="color" class="form-control form-control-color w-100" name="color" value="#e3f2fd">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            ایجاد دسته‌بندی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ویرایش دسته‌بندی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCategoryForm" enctype="multipart/form-data">
                    <input type="hidden" name="category_id" id="editCategoryId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">نام دسته‌بندی</label>
                                <input type="text" class="form-control category-name-input" name="name" id="editCategoryName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نامک (Slug)</label>
                                <input type="text" class="form-control category-slug-input" name="slug" id="editCategorySlug">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">دسته‌بندی والد</label>
                                <select class="form-select select2" name="parent_id" id="editCategoryParent">
                                    <option value="">دسته‌بندی اصلی</option>
                                    <?php foreach ($parentCategories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">وضعیت</label>
                                <select class="form-select" name="status" id="editCategoryStatus">
                                    <option value="active">فعال</option>
                                    <option value="inactive">غیرفعال</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">توضیحات</label>
                                <textarea class="form-control" name="description" id="editCategoryDescription" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">آیکون</label>
                                <input type="text" class="form-control" name="icon" id="editCategoryIcon" placeholder="مثال: fas fa-folder">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رنگ</label>
                                <input type="color" class="form-control form-control-color w-100" name="color" id="editCategoryColor">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Action Modal -->
    <div class="modal fade" id="bulkActionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">عملیات گروهی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulkActionForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">انتخاب عملیات</label>
                            <select class="form-select" id="bulkAction" name="action" required>
                                <option value="">انتخاب کنید...</option>
                                <option value="activate">فعال کردن</option>
                                <option value="deactivate">غیرفعال کردن</option>
                                <option value="delete">حذف</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">اجرای عملیات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="../assets/js/categories.js"></script>

    <?php if (isset($_SESSION['alert'])): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $_SESSION['alert']['type']; ?>',
            title: '<?php echo $_SESSION['alert']['title']; ?>',
            text: '<?php echo $_SESSION['alert']['message']; ?>',
            confirmButtonText: 'باشه'
        });
    </script>
    <?php unset($_SESSION['alert']); endif; ?>
</body>
</html>
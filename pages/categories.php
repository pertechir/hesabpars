<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/jdf.php';

// بررسی دسترسی
checkPermission('view_categories');

// دریافت آمار دسته‌بندی‌ها
try {
    global $db;  // استفاده از متغیر دیتابیس تعریف شده در config.php
    
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as parents,
        SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as children
    FROM categories");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // دریافت لیست دسته‌بندی‌ها به صورت درختی
    function getCategoryTree($db, $parentId = null) {
        $stmt = $db->prepare("
            SELECT c.*, 
                   COUNT(p.id) as product_count,
                   u.full_name as created_by_name
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
        
        foreach ($categories as &$category) {
            $category['children'] = getCategoryTree($db, $category['id']);
        }
        
        return $categories;
    }

    $categories = getCategoryTree($db);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    createAlert('error', 'خطا در دریافت اطلاعات از دیتابیس');
    // مقداردهی پیش‌فرض برای جلوگیری از خطا
    $stats = [
        'total' => 0,
        'active' => 0,
        'parents' => 0,
        'children' => 0
    ];
    $categories = [];
}

$pageTitle = "مدیریت دسته‌بندی‌ها";
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - حساب پارسه</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/categories.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="category-container">
        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-folder"></i>
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
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['parents']); ?></h3>
                    <p>دسته‌بندی‌های اصلی</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon child">
                    <i class="fas fa-code-branch"></i>
                </div>
                <div class="stat

-info">
                    <h3><?php echo number_format($stats['children']); ?></h3>
                    <p>زیر دسته‌ها</p>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form id="filterForm" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="categorySearch" class="form-control" placeholder="جستجو در دسته‌بندی‌ها...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-select">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="parentFilter" class="form-select">
                        <option value="">همه دسته‌بندی‌ها</option>
                        <option value="parent">دسته‌های اصلی</option>
                        <option value="child">زیردسته‌ها</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Category Tree -->
        <div class="category-tree">
            <div class="tree-header">
                <h2 class="tree-title">ساختار دسته‌بندی‌ها</h2>
                <div class="category-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="expandAll">
                        <i class="fas fa-plus-square"></i> باز کردن همه
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="collapseAll">
                        <i class="fas fa-minus-square"></i> بستن همه
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> افزودن دسته‌بندی
                    </button>
                </div>
            </div>

            <div class="tree-view">
                <?php
                function renderCategoryTree($categories, $level = 0) {
                    foreach ($categories as $category) {
                        $hasChildren = !empty($category['children']);
                        ?>
                        <div class="tree-item" data-id="<?php echo $category['id']; ?>">
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <?php if ($hasChildren): ?>
                                <button class="btn btn-link btn-sm toggle-children">
                                    <i class="fas fa-caret-down"></i>
                                </button>
                            <?php else: ?>
                                <span style="width: 28px;"></span>
                            <?php endif; ?>
                            
                            <div class="category-name">
                                <?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level); ?>
                                <?php echo htmlspecialchars($category['name']); ?>
                                <small class="text-muted">(<?php echo $category['product_count']; ?> محصول)</small>
                            </div>
                            
                            <div class="category-actions">
                                <button type="button" class="btn btn-outline-info btn-sm" 
                                        onclick="window.location.href='category-details.php?id=<?php echo $category['id']; ?>'">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm edit-category" 
                                        data-id="<?php echo $category['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm toggle-status" 
                                        data-id="<?php echo $category['id']; ?>"
                                        data-status="<?php echo $category['status']; ?>">
                                    <i class="fas fa-power-off"></i>
                                </button>
                                <?php if ($category['product_count'] == 0 && empty($category['children'])): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm delete-category" 
                                            data-id="<?php echo $category['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($hasChildren): ?>
                            <div class="tree-branch">
                                <?php renderCategoryTree($category['children'], $level + 1); ?>
                            </div>
                        <?php endif;
                    }
                }

                renderCategoryTree($categories);
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
                <form id="addCategoryForm">
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
                                    <?php
                                    function renderCategoryOptions($categories, $level = 0) {
                                        foreach ($categories as $category) {
                                            echo '<option value="' . $category['id'] . '">' . 
                                                 str_repeat('— ', $level) . htmlspecialchars($category['name']) . 
                                                 '</option>';
                                            if (!empty($category['children'])) {
                                                renderCategoryOptions($category['children'], $level + 1);
                                            }
                                        }
                                    }
                                    renderCategoryOptions($categories);
                                    ?>
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
                                <input type="color" class="form-control form-control-color w-100" name="color" value="#563d7c">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">تصویر شاخص</label>
                                <input type="file" class="form-control" name="thumbnail" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> ذخیره
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
                <form id="editCategoryForm">
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
                                    <?php renderCategoryOptions($categories); ?>
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
                            <div class="col-md-12">
                                <label class="form-label">تصویر شاخص جدید</label>
                                <input type="file" class="form-control" name="thumbnail" accept="image/*">
                            </div>
                            <div class="col-md-12" id="currentThumbnail">
                                <!-- نمایش تصویر فعلی -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> بروزرسانی
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
</body>
</html>
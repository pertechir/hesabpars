<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// بررسی دسترسی کاربر
redirectIfNotLoggedIn();

// مقداردهی اولیه متغیرها
$stats = [
    'total' => 0,
    'active' => 0,
    'products' => 0
];
$mainCategories = [];
$error = null;

// دریافت آمار دسته‌بندی‌ها
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // آمار کلی
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(products_count) as products
        FROM categories
    ");
    $stats = $stmt->fetch();

    // دریافت دسته‌بندی‌های اصلی
    $stmt = $db->query("
        SELECT 
            c.*,
            (SELECT COUNT(*) FROM categories s WHERE s.parent_id = c.id) as subcategories_count
        FROM categories c
        WHERE c.parent_id IS NULL
        ORDER BY c.sort_order
    ");
    $mainCategories = $stmt->fetchAll();

    // دریافت زیردسته‌ها برای هر دسته‌بندی اصلی
    foreach ($mainCategories as &$category) {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as products_count
            FROM categories c
            WHERE c.parent_id = ?
            ORDER BY c.sort_order
        ");
        $stmt->execute([$category['id']]);
        $category['subcategories'] = $stmt->fetchAll();
    }
    unset($category); // پاک کردن رفرنس

} catch(PDOException $e) {
    error_log("Database Error in categories.php: " . $e->getMessage());
    $error = "خطا در دریافت اطلاعات دسته‌بندی‌ها: " . $e->getMessage();
    $mainCategories = [];
}

// اضافه کردن چند دسته‌بندی نمونه اگر جدول خالی است
if (empty($mainCategories)) {
    try {
        $sampleCategories = [
            ['name' => 'دسته‌بندی نمونه 1', 'icon' => 'fas fa-box', 'description' => 'توضیحات نمونه برای دسته‌بندی 1'],
            ['name' => 'دسته‌بندی نمونه 2', 'icon' => 'fas fa-shopping-bag', 'description' => 'توضیحات نمونه برای دسته‌بندی 2']
        ];

        foreach ($sampleCategories as $category) {
            $stmt = $db->prepare("
                INSERT INTO categories (name, icon, description, status, sort_order)
                VALUES (:name, :icon, :description, 'active', 
                    (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories c2))
            ");
            $stmt->execute($category);
        }

        // بازخوانی دسته‌بندی‌ها
        $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order");
        $mainCategories = $stmt->fetchAll();

    } catch(PDOException $e) {
        error_log("Error creating sample categories: " . $e->getMessage());
        $error = "خطا در ایجاد دسته‌بندی‌های نمونه";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت دسته‌بندی‌ها - <?php echo SITE_NAME; ?></title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/categories.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="categories-container">
        <div class="categories-header">
            <div class="header-info">
                <h1 class="page-title">مدیریت دسته‌بندی‌ها</h1>
                <div class="categories-stats">
                    <span class="stat-item">
                        <i class="fas fa-folder"></i>
                        <?php echo number_format($stats['total']); ?> دسته‌بندی
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-check-circle"></i>
                        <?php echo number_format($stats['active']); ?> فعال
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-box"></i>
                        <?php echo number_format($stats['products']); ?> محصول
                    </span>
                </div>
            </div>
            <div class="categories-actions">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="جستجو در دسته‌بندی‌ها...">
                </div>
                <button class="add-category-btn">
                    <i class="fas fa-plus"></i>
                    دسته‌بندی جدید
                </button>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="categories-grid">
            <?php foreach ($mainCategories as $category): ?>
                <div class="category-card" data-id="<?php echo $category['id']; ?>">
                    <div class="category-header">
                        <div class="category-title">
                            <div class="category-icon">
                                <i class="<?php echo $category['icon'] ?: 'fas fa-folder'; ?>"></i>
                            </div>
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                        </div>
                        <div class="category-options">
                            <button class="options-btn">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="options-menu">
                                <a href="#" class="option-item" onclick="categoryManager.editCategory(<?php echo $category['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                    <span>ویرایش</span>
                                </a>
                                <a href="#" class="option-item" onclick="categoryManager.addSubcategory(<?php echo $category['id']; ?>)">
                                    <i class="fas fa-plus"></i>
                                    <span>افزودن زیردسته</span>
                                </a>
                                <?php if ($category['status'] === 'active'): ?>
                                    <a href="#" class="option-item" onclick="categoryManager.deactivateCategory(<?php echo $category['id']; ?>)">
                                        <i class="fas fa-ban"></i>
                                        <span>غیرفعال‌سازی</span>
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="option-item" onclick="categoryManager.activateCategory(<?php echo $category['id']; ?>)">
                                        <i class="fas fa-check-circle"></i>
                                        <span>فعال‌سازی</span>
                                    </a>
                                <?php endif; ?>
                                <a href="#" class="option-item text-danger" onclick="categoryManager.deleteCategory(<?php echo $category['id']; ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                    <span>حذف</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="category-content">
                        <div class="category-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($category['products_count']); ?></div>
                                <div class="stat-label">محصول</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($category['subcategories_count']); ?></div>
                                <div class="stat-label">زیردسته</div>
                            </div>
                        </div>
                        <?php if (!empty($category['description'])): ?>
                            <div class="category-description">
                                <?php echo nl2br(htmlspecialchars($category['description'])); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($category['subcategories'])): ?>
                            <div class="subcategories-list">
                                <?php foreach ($category['subcategories'] as $sub): ?>
                                    <div class="subcategory-item">
                                        <div class="subcategory-name">
                                            <i class="fas fa-folder-open"></i>
                                            <span><?php echo htmlspecialchars($sub['name']); ?></span>
                                        </div>
                                        <span class="subcategory-count">
                                            <?php echo number_format($sub['products_count']); ?> محصول
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Category Modal Template -->
    <template id="categoryModalTemplate">
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">دسته‌بندی جدید</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm">
                        <div class="form-group">
                            <label class="form-label">نام دسته‌بندی</label>
                            <input type="text" class="form-input" name="name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">آیکون</label>
                            <div class="icon-picker">
                                <!-- آیکون‌های فونت‌آوسام اینجا لود می‌شوند -->
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">توضیحات</label>
                            <textarea class="form-input" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">دسته‌بندی والد</label>
                            <select class="form-input" name="parent_id">
                                <option value="">بدون والد</option>
                                <?php foreach ($mainCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">وضعیت</label>
                            <select class="form-input" name="status">
                                <option value="active">فعال</option>
                                <option value="inactive">غیرفعال</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                    <button class="btn btn-primary" id="saveCategory">ذخیره</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Scripts -->
    <script src="../assets/js/categories.js"></script>
</body>
</html>
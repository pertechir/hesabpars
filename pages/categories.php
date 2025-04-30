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

// آرایه نگهداری خطاها و پیام‌ها
$errors = [];
$messages = [];

// مقداردهی اولیه متغیرها
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'products' => 0,
    'subcategories' => 0
];

$mainCategories = [];
$recentActivities = [];

try {
    // دریافت آمار کلی
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
            SUM(products_count) as products,
            (SELECT COUNT(*) FROM categories WHERE parent_id IS NOT NULL) as subcategories
        FROM categories
    ";
    $stats = $db->query($statsQuery)->fetch(PDO::FETCH_ASSOC);

    // ساخت شرط‌های SQL
    $whereConditions = [];
    $params = [];

    if ($filterStatus) {
        $whereConditions[] = "c.status = :status";
        $params[':status'] = $filterStatus;
    }

    if ($searchQuery) {
        $whereConditions[] = "(c.name LIKE :search OR c.description LIKE :search)";
        $params[':search'] = "%{$searchQuery}%";
    }

    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // دریافت تعداد کل برای صفحه‌بندی
    $countQuery = "SELECT COUNT(*) FROM categories c $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);

    // دریافت دسته‌بندی‌های اصلی با اطلاعات کامل
    $mainQuery = "
        SELECT 
            c.*,
            COALESCE(p.product_count, 0) as products_count,
            COALESCE(s.sub_count, 0) as subcategories_count,
            COALESCE(v.view_count, 0) as views,
            u.username as created_by,
            lu.username as last_updated_by
        FROM categories c
        LEFT JOIN (
            SELECT category_id, COUNT(*) as product_count 
            FROM products 
            GROUP BY category_id
        ) p ON p.category_id = c.id
        LEFT JOIN (
            SELECT parent_id, COUNT(*) as sub_count 
            FROM categories 
            GROUP BY parent_id
        ) s ON s.parent_id = c.id
        LEFT JOIN (
            SELECT category_id, COUNT(*) as view_count 
            FROM category_views 
            GROUP BY category_id
        ) v ON v.category_id = c.id
        LEFT JOIN users u ON u.id = c.created_by
        LEFT JOIN users lu ON lu.id = c.last_updated_by
        $whereClause
        ORDER BY c.$sortBy $sortOrder
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($mainQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $mainCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // دریافت فعالیت‌های اخیر
    $recentQuery = "
        SELECT 
            'category' as type,
            c.id,
            c.name,
            c.status,
            u.username as user,
            ca.action,
            ca.created_at
        FROM category_activities ca
        JOIN categories c ON c.id = ca.category_id
        JOIN users u ON u.id = ca.user_id
        ORDER BY ca.created_at DESC
        LIMIT 5
    ";
    $recentActivities = $db->query($recentQuery)->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $errors[] = "خطا در دریافت اطلاعات دسته‌بندی‌ها";
}

// آیکون‌های پیش‌فرض برای دسته‌بندی‌ها
$defaultIcons = [
    'electronics' => 'fas fa-laptop',
    'clothing' => 'fas fa-tshirt',
    'food' => 'fas fa-utensils',
    'books' => 'fas fa-book',
    'sports' => 'fas fa-football-ball',
    'health' => 'fas fa-heartbeat',
    'home' => 'fas fa-home',
    'beauty' => 'fas fa-spa',
    'toys' => 'fas fa-gamepad',
    'auto' => 'fas fa-car',
    'garden' => 'fas fa-leaf',
    'tools' => 'fas fa-tools',
    'jewelry' => 'fas fa-gem',
    'art' => 'fas fa-paint-brush',
    'music' => 'fas fa-music',
    'office' => 'fas fa-briefcase',
    'pets' => 'fas fa-paw',
    'travel' => 'fas fa-plane',
    'default' => 'fas fa-folder'
];

// رنگ‌های پیش‌فرض برای دسته‌بندی‌ها
$defaultColors = [
    '#2196F3', // آبی
    '#4CAF50', // سبز
    '#FFC107', // زرد
    '#9C27B0', // بنفش
    '#F44336', // قرمز
    '#FF9800', // نارنجی
    '#795548', // قهوه‌ای
    '#607D8B', // خاکستری آبی
    '#E91E63', // صورتی
    '#00BCD4', // فیروزه‌ای
    '#8BC34A', // سبز روشن
    '#FFEB3B', // زرد روشن
    '#673AB7', // بنفش تیره
    '#FF5722', // نارنجی تیره
    '#009688', // سبز دریایی
    '#03A9F4', // آبی روشن
    '#3F51B5', // نیلی
    '#CDDC39'  // لیمویی
];

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css">
    
    <!-- Custom Fonts -->
    <link rel="stylesheet" href="../assets/fonts/anjoman/font-face.css">
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
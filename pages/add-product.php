<?php
require_once '../includes/init.php';

// نمایش خطاها در حالت توسعه
ini_set('display_errors', 1);
error_reporting(E_ALL);

// بررسی احراز هویت
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// مقادیر پیش‌فرض
$defaultValues = [
    'status' => 'active',
    'tax_method' => 'exclusive',
    'min_stock' => 0,
    'max_stock' => 999999,
    'weight' => 0,
    'length' => 0,
    'width' => 0,
    'height' => 0
];

try {
    // دریافت لیست دسته‌بندی‌ها
    $categories = [];
    $stmt = $db->query("
        WITH RECURSIVE category_tree AS (
            SELECT 
                id, name, parent_id, 0 as level,
                CAST(name AS CHAR(1000)) as path
            FROM categories
            WHERE parent_id IS NULL
            UNION ALL
            SELECT 
                c.id, c.name, c.parent_id, ct.level + 1,
                CONCAT(ct.path, ' > ', c.name)
            FROM categories c
            INNER JOIN category_tree ct ON c.parent_id = ct.id
        )
        SELECT id, name, parent_id, level, path
        FROM category_tree
        ORDER BY path;
    ");
    $categories = $stmt->fetchAll();

    // دریافت لیست انبارها
    $warehouses = [];
    $stmt = $db->query("SELECT * FROM warehouses WHERE status = 'active' ORDER BY name");
    $warehouses = $stmt->fetchAll();

    // دریافت لیست تامین‌کنندگان
    $suppliers = [];
    $stmt = $db->query("SELECT * FROM suppliers WHERE status = 'active' ORDER BY company_name");
    $suppliers = $stmt->fetchAll();

    // دریافت لیست مالیات‌ها
    $taxes = [];
    $stmt = $db->query("SELECT * FROM taxes WHERE status = 'active' ORDER BY name");
    $taxes = $stmt->fetchAll();

    // دریافت لیست برندها
    $brands = [];
    $stmt = $db->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name");
    $brands = $stmt->fetchAll();

    // دریافت تنظیمات محصولات
    $settings = [];
    $stmt = $db->query("SELECT * FROM settings WHERE module = 'products'");
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }

    // دریافت لیست واحدها
    $units = [];
    $stmt = $db->query("SELECT * FROM units WHERE status = 'active' ORDER BY name");
    $units = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    die('خطا در دریافت اطلاعات. لطفا با پشتیبانی تماس بگیرید.');
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>افزودن محصول جدید - <?php echo SITE_NAME; ?></title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css">
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/products.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">افزودن محصول جدید</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-left">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/dashboard.php">داشبورد</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/products.php">محصولات</a></li>
                            <li class="breadcrumb-item active">افزودن محصول</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <form id="productForm" enctype="multipart/form-data">
                    <!-- اطلاعات اصلی محصول -->
                    <div class="form-section">
                        <div class="section-header">
                            <h3><i class="fas fa-info-circle"></i> اطلاعات اصلی محصول</h3>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="productName" class="form-label required">نام محصول</label>
                                    <input type="text" class="form-control" id="productName" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="productCode" class="form-label required">کد محصول</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="productCode" name="code" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="generateProductCode()">
                                            <i class="fas fa-random"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="barcode" class="form-label">بارکد</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="barcode" name="barcode">
                                        <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()">
                                            <i class="fas fa-barcode"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category" class="form-label required">دسته‌بندی</label>
                                    <select class="form-select select2" id="category" name="category_id" required>
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo str_repeat('- ', $category['level']) . $category['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="brand" class="form-label">برند</label>
                                    <select class="form-select select2" id="brand" name="brand_id">
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo $brand['id']; ?>">
                                                <?php echo $brand['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit" class="form-label required">واحد</label>
                                    <select class="form-select select2" id="unit" name="unit_id" required>
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($units as $unit): ?>
                                            <option value="<?php echo $unit['id']; ?>">
                                                <?php echo $unit['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="productDescription" class="form-label">توضیحات</label>
                            <textarea id="productDescription" name="description"></textarea>
                        </div>
                    </div>

                    <!-- قیمت‌گذاری و موجودی -->
                    <div class="form-section">
                        <div class="section-header">
                            <h3><i class="fas fa-dollar-sign"></i> قیمت‌گذاری و موجودی</h3>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="costPrice" class="form-label required">قیمت خرید</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="costPrice" name="cost_price" required>
                                        <span class="input-group-text">تومان</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sellingPrice" class="form-label required">قیمت فروش</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="sellingPrice" name="selling_price" required>
                                        <span class="input-group-text">تومان</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="taxMethod" class="form-label">روش محاسبه مالیات</label>
                                    <select class="form-select" id="taxMethod" name="tax_method">
                                        <option value="exclusive" <?php echo $defaultValues['tax_method'] === 'exclusive' ? 'selected' : ''; ?>>
                                            مالیات مجزا
                                        </option>
                                        <option value="inclusive" <?php echo $defaultValues['tax_method'] === 'inclusive' ? 'selected' : ''; ?>>
                                            مالیات شامل
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="minStock" class="form-label">حداقل موجودی</label>
                                    <input type="number" class="form-control" id="minStock" name="min_stock" 
                                           value="<?php echo $defaultValues['min_stock']; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="maxStock" class="form-label">حداکثر موجودی</label>
                                    <input type="number" class="form-control" id="maxStock" name="max_stock"
                                           value="<?php echo $defaultValues['max_stock']; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="productStatus" class="form-label">وضعیت</label>
                                    <select class="form-select" id="productStatus" name="status">
                                        <option value="active" <?php echo $defaultValues['status'] === 'active' ? 'selected' : ''; ?>>فعال</option>
                                        <option value="inactive" <?php echo $defaultValues['status'] === 'inactive' ? 'selected' : ''; ?>>غیرفعال</option>
                                        <option value="discontinued" <?php echo $defaultValues['status'] === 'discontinued' ? 'selected' : ''; ?>>توقف تولید</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تنوع محصول -->
                    <div class="form-section">
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <h3><i class="fas fa-tasks"></i> تنوع محصول</h3>
                            <button type="button" class="btn btn-primary" id="addVariation">
                                <i class="fas fa-plus"></i> افزودن تنوع
                            </button>
                        </div>
                        <div id="variationsContainer">
                            <!-- تنوع‌ها اینجا اضافه می‌شوند -->
                        </div>
                    </div>

                    <!-- تصاویر محصول -->
                    <div class="form-section">
                        <div class="section-header">
                            <h3><i class="fas fa-images"></i> تصاویر محصول</h3>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="productImage" class="form-label">تصویر اصلی</label>
                                    <input type="file" class="form-control" id="productImage" name="image" accept="image/*">
                                    <div id="imagePreviewContainer" class="image-preview" style="display: none;">
                                        <img id="imagePreview" src="#" alt="پیش‌نمایش تصویر">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">گالری تصاویر</label>
                                    <div id="galleryUpload" class="dropzone">
                                        <div class="dz-message">
                                            تصاویر را اینجا رها کنید یا کلیک کنید
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- مشخصات فیزیکی -->
                    <div class="form-section">
                        <div class="section-header">
                            <h3><i class="fas fa-ruler-combined"></i> مشخصات فیزیکی</h3>

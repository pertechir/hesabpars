<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/jdf.php';

// بررسی دسترسی
checkPermission('add_products');

// دریافت لیست دسته‌بندی‌ها
$categories = [];
try {
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
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// دریافت لیست واحدها
$units = [];
try {
    $stmt = $db->query("SELECT * FROM units WHERE status = 'active' ORDER BY name");
    $units = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching units: " . $e->getMessage());
}

// دریافت لیست انبارها
$warehouses = [];
try {
    $stmt = $db->query("SELECT * FROM warehouses WHERE status = 'active' ORDER BY name");
    $warehouses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching warehouses: " . $e->getMessage());
}

// دریافت لیست تامین‌کنندگان
$suppliers = [];
try {
    $stmt = $db->query("SELECT * FROM suppliers WHERE status = 'active' ORDER BY company_name");
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching suppliers: " . $e->getMessage());
}

// دریافت لیست مالیات‌ها
$taxes = [];
try {
    $stmt = $db->query("SELECT * FROM taxes WHERE status = 'active' ORDER BY name");
    $taxes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching taxes: " . $e->getMessage());
}

// دریافت لیست برندها
$brands = [];
try {
    $stmt = $db->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name");
    $brands = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching brands: " . $e->getMessage());
}

// دریافت تنظیمات محصولات
$settings = [];
try {
    $stmt = $db->query("SELECT * FROM settings WHERE module = 'products'");
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }
} catch (PDOException $e) {
    error_log("Error fetching product settings: " . $e->getMessage());
}

// تنظیم متغیرهای پیش‌فرض
$defaultValues = [
    'min_stock' => $settings['default_min_stock'] ?? 0,
    'max_stock' => $settings['default_max_stock'] ?? 999999,
    'tax_method' => $settings['default_tax_method'] ?? 'exclusive',
    'cost_price' => 0,
    'selling_price' => 0,
    'status' => 'active'
];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>افزودن محصول جدید | حسابپارس</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../../assets/images/favicon.ico">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../../assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../../assets/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/select2.min.css">
    <link rel="stylesheet" href="../../assets/css/select2-bootstrap-5-theme.rtl.min.css">
    <link rel="stylesheet" href="../../assets/css/sweetalert2.min.css">
    <link rel="stylesheet" href="../../assets/css/dropzone.min.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/products.css">
</head>
<body>

    <!-- Sidebar -->
    <?php include_once '../../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <?php include_once '../../includes/navbar.php'; ?>

        <!-- Page Content -->
        <div class="page-content product-add">
            <div class="container-fluid">
                
                <!-- Breadcrumb -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="page-title">افزودن محصول جدید</h1>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../dashboard.php">داشبورد</a></li>
                                <li class="breadcrumb-item"><a href="index.php">محصولات</a></li>
                                <li class="breadcrumb-item active">افزودن محصول</li>
                            </ul>
                        </div>
                        <div class="col-auto">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i>
                                <span>بازگشت</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Product Form -->
                <form id="addProductForm" class="product-form" novalidate>
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">اطلاعات اصلی</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productName">نام محصول <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="productName" name="name" required>
                                                <div class="invalid-feedback">لطفاً نام محصول را وارد کنید</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productCode">کد محصول <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="productCode" name="code" required>
                                                    <button type="button" class="btn btn-secondary" id="generateCode">
                                                        <i class="fas fa-random"></i>
                                                    </button>
                                                </div>
                                                <div class="invalid-feedback">لطفاً کد محصول را وارد کنید</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productBarcode">بارکد</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="productBarcode" name="barcode">
                                                    <button type="button" class="btn btn-secondary" id="generateBarcode">
                                                        <i class="fas fa-barcode"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productBrand">برند</label>
                                                <select class="form-select select2" id="productBrand" name="brand_id">
                                                    <option value="">انتخاب برند</option>
                                                    <?php foreach ($brands as $brand): ?>
                                                    <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productCategory">دسته‌بندی <span class="text-danger">*</span></label>
                                                <select class="form-select select2" id="productCategory" name="category_id" required>
                                                    <option value="">انتخاب دسته‌بندی</option>
                                                    <?php foreach ($categories as $category): ?>
                                                    <option value="<?= $category['id'] ?>"><?= str_repeat('—', $category['level']) . ' ' . htmlspecialchars($category['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">لطفاً دسته‌بندی محصول را انتخاب کنید</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productUnit">واحد <span class="text-danger">*</span></label>
                                                <select class="form-select select2" id="productUnit" name="unit_id" required>
                                                    <option value="">انتخاب واحد</option>
                                                    <?php foreach ($units as $unit): ?>
                                                    <option value="<?= $unit['id'] ?>"><?= htmlspecialchars($unit['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">لطفاً واحد محصول را انتخاب کنید</div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="form-label" for="productDescription">توضیحات</label>
                                                <textarea class="form-control" id="productDescription" name="description" rows="4"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">اطلاعات قیمت‌گذاری</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productCostPrice">قیمت خرید <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="productCostPrice" name="cost_price" required min="0" step="1">
                                                    <span class="input-group-text">ریال</span>
                                                </div>
                                                <div class="invalid-feedback">لطفاً قیمت خرید را وارد کنید</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productSellingPrice">قیمت فروش <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="productSellingPrice" name="selling_price" required min="0" step="1">
                                                    <span class="input-group-text">ریال</span>
                                                </div>
                                                <div class="invalid-feedback">لطفاً قیمت فروش را وارد کنید</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">مالیات</label>
                                                <div class="tax-container">
                                                    <?php foreach ($taxes as $tax): ?>
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="tax<?= $tax['id'] ?>" name="taxes[]" value="<?= $tax['id'] ?>">
                                                        <label class="form-check-label" for="tax<?= $tax['id'] ?>"><?= htmlspecialchars($tax['name']) ?> (<?= $tax['rate'] ?>%)</label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="taxMethod">روش محاسبه مالیات</label>
                                                <select class="form-select" id="taxMethod" name="tax_method">
                                                    <option value="exclusive" <?= ($defaultValues['tax_method'] == 'exclusive') ? 'selected' : '' ?>>مالیات جدا از قیمت</option>
                                                    <option value="inclusive" <?= ($defaultValues['tax_method'] == 'inclusive') ? 'selected' : '' ?>>مالیات داخل قیمت</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">اطلاعات موجودی</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productMinStock">حداقل موجودی</label>
                                                <input type="number" class="form-control" id="productMinStock" name="min_stock" value="<?= $defaultValues['min_stock'] ?>" min="0" step="1">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="productMaxStock">حداکثر موجودی</label>
                                                <input type="number" class="form-control" id="productMaxStock" name="max_stock" value="<?= $defaultValues['max_stock'] ?>" min="0" step="1">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label class="form-label">موجودی اولیه در انبارها</label>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>انبار</th>
                                                                <th>موجودی</th>
                                                                <th>محل قفسه</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($warehouses as $warehouse): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($warehouse['name']) ?></td>
                                                                <td>
                                                                    <input type="number" class="form-control form-control-sm" 
                                                                           name="stock[<?= $warehouse['id'] ?>][quantity]" value="0" min="0" step="1">
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" 
                                                                           name="stock[<?= $warehouse['id'] ?>][location]" placeholder="مثال: A-12-3">
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Status -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">وضعیت</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <select class="form-select" id="productStatus" name="status">
                                            <option value="active" <?= ($defaultValues['status'] == 'active') ? 'selected' : '' ?>>فعال</option>
                                            <option value="inactive" <?= ($defaultValues['status'] == 'inactive') ? 'selected' : '' ?>>غیرفعال</option>
                                            <option value="discontinued" <?= ($defaultValues['status'] == 'discontinued') ? 'selected' : '' ?>>توقف تولید</option>
                                        </select>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary w

-100">
                                            <i class="fas fa-save"></i>
                                            <span>ذخیره محصول</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Image -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">تصویر محصول</h5>
                                </div>
                                <div class="card-body">
                                    <div class="product-image-upload">
                                        <div id="imageDropzone" class="dropzone">
                                            <div class="dz-message">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <span>فایل را اینجا رها کنید یا کلیک کنید</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">اطلاعات تکمیلی</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="form-label" for="productWeight">وزن (گرم)</label>
                                        <input type="number" class="form-control" id="productWeight" name="weight" min="0" step="0.01">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">ابعاد (سانتی‌متر)</label>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" class="form-control" name="length" placeholder="طول" min="0" step="0.1">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control" name="width" placeholder="عرض" min="0" step="0.1">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control" name="height" placeholder="ارتفاع" min="0" step="0.1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="productManufacturer">سازنده</label>
                                        <input type="text" class="form-control" id="productManufacturer" name="manufacturer">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="productModel">مدل</label>
                                        <input type="text" class="form-control" id="productModel" name="model">
                                    </div>
                                </div>
                            </div>

                            <!-- Default Supplier -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">تامین‌کننده پیش‌فرض</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <select class="form-select select2" id="productSupplier" name="default_supplier_id">
                                            <option value="">انتخاب تامین‌کننده</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['company_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS Files -->
    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/select2.min.js"></script>
    <script src="../../assets/js/select2-fa.js"></script>
    <script src="../../assets/js/sweetalert2.min.js"></script>
    <script src="../../assets/js/dropzone.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/products.js"></script>

</body>
</html>
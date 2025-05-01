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

// دریافت تنظیمات مالیات پیش‌فرض
$defaultTaxRate = 9; // نرخ پیش‌فرض مالیات (باید از تنظیمات خوانده شود)

try {
    // دریافت لیست دسته‌بندی‌ها (5 تای اول)
    $stmt = $db->query("SELECT id, name, parent_id FROM categories ORDER BY name LIMIT 5");
    $initialCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // دریافت لیست ارزها
    $stmt = $db->query("SELECT code, name FROM currencies WHERE active = 1 ORDER BY name");
    $currencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // دریافت انواع مالیات
    $stmt = $db->query("SELECT id, name FROM tax_types WHERE active = 1 ORDER BY name");
    $taxTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // دریافت واحدهای مالیاتی
    $stmt = $db->query("SELECT id, name FROM tax_units WHERE active = 1 ORDER BY name");
    $taxUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/add-product.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <form id="productForm" class="needs-validation" novalidate>
                <!-- مشخصات کالا -->
                <div class="product-section">
                    <div class="section-header">
                        <h5 class="section-title">مشخصات کالا</h5>
                    </div>
                    
                    <!-- تصویر و گالری -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label">تصاویر محصول</label>
                            <div class="image-upload-area" id="dropZone">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                <p class="mb-1">تصاویر را اینجا رها کنید یا کلیک کنید</p>
                                <input type="file" id="productImages" multiple accept="image/*" style="display: none;">
                            </div>
                            <div class="image-preview-container"></div>
                        </div>
                    </div>

                    <!-- کد حسابداری -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">کد حسابداری</label>
                            <div class="input-group">
                                <div class="switch-container">
                                    <label class="switch me-2">
                                        <input type="checkbox" id="autoAccountingCode" checked>
                                        <span class="slider"></span>
                                    </label>
                                    <span>تولید خودکار</span>
                                </div>
                                <input type="text" class="form-control" id="accountingCode" disabled>
                                <button class="btn btn-outline-secondary" type="button" id="generateAccountingCode">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- نام و کد کالا -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="productName">نام کالا <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="productName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="productCode">کد کالا <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="productCode" name="code" required>
                            </div>
                        </div>
                    </div>

                    <!-- بارکد -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="barcodes">بارکد</label>
                                <input type="text" class="form-control" id="barcodes" name="barcodes" 
                                       placeholder="بارکدهای مختلف را با ; از هم جدا کنید">
                            </div>
                        </div>
                    </div>

                    <!-- دسته‌بندی -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="categorySelect">دسته‌بندی <span class="text-danger">*</span></label>
                                <select class="form-select" id="categorySelect" name="category_id" required>
                                    <option value="">انتخاب دسته‌بندی</option>
                                    <?php foreach ($initialCategories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo $category['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- قسمت فروش -->
                <div class="product-section">
                    <div class="section-header">
                        <h5 class="section-title">اطلاعات فروش</h5>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">قیمت فروش <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="sellingPrice" name="selling_price" required>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#priceModal">
                                        <i class="fas fa-list"></i>
                                        قیمت‌های دیگر
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="salesDescription">توضیحات فروش</label>
                                <textarea class="form-control" id="salesDescription" name="sales_description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="purchasePrice">قیمت خرید</label>
                                <input type="number" class="form-control" id="purchasePrice" name="purchase_price">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="purchaseDescription">توضیحات خرید</label>
                                <textarea class="form-control" id="purchaseDescription" name="purchase_description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- قسمت موجودی کالا -->
                <div class="product-section">
                    <div class="section-header">
                        <h5 class="section-title">واحد و موجودی</h5>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="mainUnit">واحد اصلی <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mainUnit" name="main_unit" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="subUnit">واحد فرعی</label>
                                <input type="text" class="form-control" id="subUnit" name="sub_unit">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="unitDescription">توضیحات</label>
                                <textarea class="form-control" id="unitDescription" name="unit_description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- کنترل موجودی -->
                <div class="product-section">
                    <div class="section-header">
                        <h5 class="section-title">کنترل موجودی</h5>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="stockControl" name="stock_control">
                                <label class="form-check-label" for="stockControl">کنترل موجودی</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="currentStock">تعداد موجود در انبار</label>
                                <input type="number" class="form-control" id="currentStock" name="current_stock" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="minOrder">حداقل سفارش</label>
                                <input type="number" class="form-control" id="minOrder" name="min_order" min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="leadTime">زمان انتظار (روز)</label>
                                <input type="number" class="form-control" id="leadTime" name="lead_time" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- مالیات -->
                <div class="product-section">
                    <div class="section-header">
                        <h5 class="section-title">مالیات</h5>
                    </div>

                    <!-- مالیات فروش -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="tax-section">
                                <div class="tax-switch">
                                    <label class="switch">
                                        <input type="checkbox" id="salesTaxEnabled" name="sales_tax_enabled">
                                        <span class="slider"></span>
                                    </label>
                                    <span>مالیات فروش</span>
                                </div>
                                <div class="tax-details" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">نرخ مالیات (%)</label>
                                                <input type="number" class="form-control tax-rate-input" 
                                                       name="sales_tax_rate" value="<?php echo $defaultTaxRate; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- مالیات خرید -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="tax-section">
                                <div class="tax-switch">
                                    <label class="switch">
                                        <input type="checkbox" id="purchaseTaxEnabled" name="purchase_tax_enabled">
                                        <span class="slider"></span>
                                    </label>
                                    <span>مالیات خرید</span>
                                </div>
                                <div class="tax-details" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">نرخ مالیات (%)</label>
                                                <input type="number" class="form-control tax-rate-input" 
                                                       name="purchase_tax_rate" value="<?php echo $defaultTaxRate; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نوع مالیات و کد مالیاتی -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">نوع مالیات</label>
                                <div class="input-group">
                                    <select class="form-select" id="taxType" name="tax_type">
                                        <?php foreach ($taxTypes as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-secondary tax-type-btn" type="button" id="addTaxType">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">واحد مالیاتی</label>
                                <div class="input-group">
                                    <select class="form-select" id="taxUnit" name="tax_unit">
                                        <?php foreach ($taxUnits as $unit): ?>
                                            <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-secondary tax-unit-btn" type="button" id="addTaxUnit">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">کد مالیاتی</label>
                                <input type="text" class="form-control" id="taxCode" name="tax_code">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- دکمه‌های عملیات -->
                <div class="form-actions text-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        ذخیره محصول
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/products.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-times"></i>
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal قیمت‌های مختلف -->
    <div class="modal fade" id="priceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">قیمت‌های مختلف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <button type="button" class="btn btn-primary mb-3" id="addPriceType">
                        <i class="fas fa-plus"></i>
                        افزودن قیمت جدید
                    </button>
                    <div id="priceTypesContainer">
                        <!-- محتوای پویا اضافه می‌شود -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                    <button type="button" class="btn btn-primary" id="savePrices">ذخیره قیمت‌ها</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/fa.js"></script>
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/add-product.js"></script>
</body>
</html>
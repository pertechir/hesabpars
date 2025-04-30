<?php
// بررسی دسترسی
checkPermission('view_categories');

// تنظیمات نمایش و فیلترها
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $perPage;

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$parentId = isset($_GET['parent']) ? (int)$_GET['parent'] : null;
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'id';
$sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

// لیست فیلدهای مجاز برای مرتب‌سازی
$allowedSortFields = ['id', 'name', 'status', 'products_count', 'created_at', 'sort_order'];
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'id';
}

$errors = [];
$stats = [];
$categories = [];

try {
    // دریافت آمار کلی
    $statsQuery = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
            COUNT(CASE WHEN parent_id IS NULL THEN 1 END) as parent,
            COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as child
        FROM categories
    ");
    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

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
            c.description LIKE :search
        )";
        $params[':search'] = "%{$searchQuery}%";
    }

    if ($parentId !== null) {
        $whereConditions[] = "c.parent_id " . ($parentId === 0 ? "IS NULL" : "= :parent_id");
        if ($parentId !== 0) {
            $params[':parent_id'] = $parentId;
        }
    }

    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // دریافت تعداد کل برای صفحه‌بندی
    $countQuery = "SELECT COUNT(*) FROM categories c $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);

    // اصلاح شماره صفحه اگر نامعتبر باشد
    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
        $offset = ($currentPage - 1) * $perPage;
    }

    // دریافت دسته‌بندی‌ها
    $query = "
        SELECT 
            c.*,
            p.name as parent_name,
            u1.username as created_by_username,
            u2.username as updated_by_username,
            (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count,
            (SELECT COUNT(*) FROM products WHERE category_id = c.id) as products_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        LEFT JOIN users u1 ON c.created_by = u1.id
        LEFT JOIN users u2 ON c.last_updated_by = u2.id
        $whereClause
        ORDER BY c.$sortBy $sortOrder
        LIMIT :offset, :limit
    ";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // دریافت لیست والدین برای درختواره
    $parentsQuery = $db->query("
        SELECT id, name, icon 
        FROM categories 
        WHERE parent_id IS NULL 
        ORDER BY name
    ");
    $parents = $parentsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error in categories.php: " . $e->getMessage());
    $errors[] = "خطا در دریافت اطلاعات دسته‌بندی‌ها";
}

// نمایش صفحه
$pageTitle = 'مدیریت دسته‌بندی‌ها';
$pageDescription = 'مدیریت دسته‌بندی‌های محصولات';
$pageClass = 'categories-page';

include '../templates/header.php';
?>

<!-- محتوای اصلی صفحه -->
<div class="page-content">
    <div class="container-fluid">
        <!-- نوار بالای صفحه -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="page-title">
                    <i class="fas fa-folder-tree"></i>
                    <?php echo $pageTitle; ?>
                    <small class="text-muted font-size-sm"><?php echo number_format($totalItems); ?> دسته‌بندی</small>
                </h1>
            </div>
            <div class="col-md-6 text-end">
                <?php if (hasPermission('add_category')): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i>
                    افزودن دسته‌بندی
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- کارت‌های آمار -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="card-title mb-1"><?php echo number_format($stats['total']); ?></h3>
                                <p class="card-text mb-0">کل دسته‌بندی‌ها</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="card-title mb-1"><?php echo number_format($stats['active']); ?></h3>
                                <p class="card-text mb-0">دسته‌بندی‌های فعال</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box">
                                <i class="fas fa-folder-tree"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="card-title mb-1"><?php echo number_format($stats['parent']); ?></h3>
                                <p class="card-text mb-0">دسته‌بندی‌های اصلی</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box">
                                <i class="fas fa-code-branch"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="card-title mb-1"><?php echo number_format($stats['child']); ?></h3>
                                <p class="card-text mb-0">زیر دسته‌ها</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- فیلترها و جستجو -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="جستجو...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>فعال</option>
                            <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>غیرفعال</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="parent" class="form-select">
                            <option value="">همه دسته‌بندی‌ها</option>
                            <option value="0" <?php echo $parentId === 0 ? 'selected' : ''; ?>>فقط دسته‌های اصلی</option>
                            <?php foreach ($parents as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>" <?php echo $parentId === $parent['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                            اعمال فیلتر
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- جدول دسته‌بندی‌ها -->
        <div class="card">
            <div class="card-body">
                <?php if ($errors): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($categories): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 50px">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="checkAll">
                                        </div>
                                    </th>
                                    <th style="width: 80px">
                                        <a href="?sort=id&order=<?php echo $sortBy === 'id' && $sortOrder === 'ASC' ? 'desc' : 'asc'; ?>" class="text-decoration-none text-dark">
                                            شناسه
                                            <?php if ($sortBy === 'id'): ?>
                                                <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?sort=name&order=<?php echo $sortBy === 'name' && $sortOrder === 'ASC' ? 'desc' : 'asc'; ?>" class="text-decoration-none text-dark">
                                            نام
                                            <?php if ($sortBy === 'name'): ?>
                                                <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>والد</th>
                                    <th style="width: 120px">
                                        <a href="?sort=status&order=<?php echo $sortBy === 'status' && $sortOrder === 'ASC' ? 'desc' : 'asc'; ?>" class="text-decoration-none text-dark">
                                            وضعیت
                                            <?php if ($sortBy === 'status'): ?>
                                                <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th style="width: 100px">زیردسته‌ها</th>
                                    <th style="width: 100px">محصولات</th>
                                    <th style="width: 180px">آخرین بروزرسانی</th>
                                    <th style="width: 150px">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input category-checkbox" value="<?php echo $category['id']; ?>">
                                        </div>
                                    </td>
                                    <td><?php echo $category['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="<?php echo htmlspecialchars($category['icon']); ?> me-2"></i>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($category['parent_name']): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-folder-tree"></i>
                                                <?php echo htmlspecialchars($category['parent_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">دسته اصلی</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($category['status'] === 'active'): ?>
                                            <span class="badge bg-success">فعال</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">غیرفعال</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo number_format($category['children_count']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo number_format($category['products_count']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($category['updated_at']): ?>
                                            <small class="text-muted">
                                                <?php echo jdate('Y/m/d H:i', strtotime($category['updated_at'])); ?>
                                                <?php if ($category['updated_by_username']): ?>
                                                    <br>توسط: <?php echo htmlspecialchars($category['updated_by_username']); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if (hasPermission('edit_category')): ?>
                                            <button type="button" class="btn btn-sm btn-info edit-category" data-id="<?php echo $category['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('delete_category')): ?>
                                            <button type="button" class="btn btn-sm btn-danger delete-category" data-id="<?php echo $category['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- صفحه‌بندی -->
                    <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&status=<?php echo urlencode($filterStatus); ?>&parent=<?php echo $parentId; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        هیچ دسته‌بندی یافت نشد.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- مودال افزودن دسته‌بندی -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title">افزودن دسته‌بندی جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نامک</label>
                            <input type="text" class="form-control" name="slug" dir="ltr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">آیکون</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="icon" value="fas fa-folder">
                                <button type="button" class="btn btn-outline-secondary" id="iconPickerBtn">
                                    <i class="fas fa-icons"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رنگ</label>
                            <input type="text" class="form-control color-picker" name="color" value="#2196F3">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">والد</label>
                            <select class="form-select select2" name="parent_id">
                                <option value="">بدون والد</option>
                                <?php foreach ($parents as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">توضیحات</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">وضعیت</label>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="status" value="active" checked>
                                <label class="form-check-label">فعال</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="status" value="inactive">
                                <label class="form-check-label">غیرفعال</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        ذخیره
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال ویرایش دسته‌بندی -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCategoryForm">
                <input type="hidden" name="category_id">
                <div class="modal-header">
                    <h5 class="modal-title">ویرایش دسته‌بندی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نامک</label>
                            <input type="text" class="form-control" name="slug" dir="ltr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">آیکون</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="icon">
                                <button type="button" class="btn btn-outline-secondary" id="editIconPickerBtn">
                                    <i class="fas fa-icons"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رنگ</label>
                            <input type="text" class="form-control color-picker" name="color">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">والد</label>
                            <select class="form-select select2" name="parent_id">
                                <option value="">بدون والد</option>
                                <?php foreach ($parents as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">توضیحات</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">وضعیت</label>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="status" value="active">
                                <label class="form-check-label">فعال</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="status" value="inactive">
                                <label class="form-check-label">غیرفعال</label>
                            </div>
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

<?php include '../templates/footer.php'; ?>
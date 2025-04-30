<form id="addCategoryForm" class="needs-validation" novalidate>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">نام <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" required>
            <div class="invalid-feedback">
                نام دسته‌بندی الزامی است
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">نامک</label>
            <input type="text" class="form-control" name="slug" dir="ltr">
            <div class="form-text">
                اگر خالی بماند، به صورت خودکار از روی نام ساخته می‌شود
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">آیکون</label>
            <div class="input-group">
                <input type="text" class="form-control" name="icon" value="fas fa-folder">
                <button type="button" class="btn btn-outline-secondary" id="iconPickerBtn">
                    <i class="fas fa-icons"></i>
                    انتخاب آیکون
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
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-12">
            <label class="form-label">توضیحات</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">عنوان متا</label>
            <input type="text" class="form-control" name="meta_title">
        </div>
        <div class="col-md-6">
            <label class="form-label">کلمات کلیدی متا</label>
            <input type="text" class="form-control" name="meta_keywords">
            <div class="form-text">
                کلمات را با کاما از هم جدا کنید
            </div>
        </div>
        <div class="col-md-12">
            <label class="form-label">توضیحات متا</label>
            <textarea class="form-control" name="meta_description" rows="2"></textarea>
        </div>
        <div class="col-md-12">
            <label class="form-label d-block">وضعیت</label>
            <div class="form-check form-check-inline">
                <input type="radio" class="form-check-input" name="status" value="active" checked>
                <label class="form-check-label">فعال</label>
            </div>
            <div class="form-check form-check-
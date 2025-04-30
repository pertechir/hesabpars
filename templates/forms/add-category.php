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
            <div class="form-check form-check-inline">
                <input type="radio" class="form-check-input" name="status" value="inactive">
                <label class="form-check-label">غیرفعال</label>
            </div>
        </div>
        <div class="col-md-12">
            <label class="form-label">ترتیب نمایش</label>
            <input type="number" class="form-control" name="sort_order" value="0">
            <div class="form-text">
                عدد بزرگتر در اولویت نمایش بالاتر قرار می‌گیرد
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">برچسب‌ها</label>
            <select class="form-select select2-tags" name="tags[]" multiple>
                <?php foreach ($tags as $tag): ?>
                <option value="<?php echo $tag['id']; ?>">
                    <?php echo htmlspecialchars($tag['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">
                برای افزودن برچسب جدید، متن را تایپ کرده و Enter بزنید
            </div>
        </div>
    </div>
    <div class="mt-4 text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            ذخیره دسته‌بندی
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تنظیمات Select2
    $('.select2').select2({
        dir: 'rtl',
        language: 'fa',
        placeholder: 'انتخاب کنید...',
        allowClear: true
    });

    // تنظیمات Select2 برای برچسب‌ها
    $('.select2-tags').select2({
        dir: 'rtl',
        language: 'fa',
        placeholder: 'برچسب‌ها را انتخاب یا وارد کنید...',
        tags: true,
        tokenSeparators: [',', ' '],
        createTag: function(params) {
            return {
                id: params.term,
                text: params.term,
                newTag: true
            };
        }
    });

    // تنظیمات Pickr برای انتخاب رنگ
    const pickr = Pickr.create({
        el: '.color-picker',
        theme: 'classic',
        default: '#2196F3',
        swatches: [
            '#2196F3', '#4CAF50', '#FFC107', '#9C27B0',
            '#F44336', '#FF9800', '#795548', '#607D8B'
        ],
        components: {
            preview: true,
            opacity: true,
            hue: true,
            interaction: {
                hex: true,
                rgba: true,
                input: true,
                clear: true,
                save: true
            }
        }
    });

    // ذخیره رنگ انتخاب شده
    pickr.on('save', (color) => {
        document.querySelector('.color-picker').value = color.toHEXA().toString();
        pickr.hide();
    });

    // اعتبارسنجی فرم
    const form = document.getElementById('addCategoryForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // تنظیم خودکار Slug
    const nameInput = form.querySelector('[name="name"]');
    const slugInput = form.querySelector('[name="slug"]');
    
    nameInput.addEventListener('input', function() {
        if (!slugInput.value) {
            slugInput.value = createSlug(this.value);
        }
    });

    function createSlug(str) {
        return str
            .toString()
            .toLowerCase()
            .trim()
            .replace(/[\u0600-\u06FF]/g, '') // حذف حروف فارسی
            .replace(/\s+/g, '-') // تبدیل فاصله به خط تیره
            .replace(/[^\w\-]+/g, '') // حذف کاراکترهای غیرمجاز
            .replace(/\-\-+/g, '-') // حذف خط تیره‌های تکراری
            .replace(/^-+/, '') // حذف خط تیره از ابتدا
            .replace(/-+$/, ''); // حذف خط تیره از انتها
    }
});
</script>
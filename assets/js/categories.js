document.addEventListener('DOMContentLoaded', function() {
    // تنظیمات Select2
    $('.select2').select2({
        dir: 'rtl',
        language: 'fa',
        placeholder: 'انتخاب کنید...',
        allowClear: true
    });

    // تنظیمات Pickr برای انتخاب رنگ
    const pickrOptions = {
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
    };

    // ایجاد Pickr برای هر المان
    document.querySelectorAll('.color-picker').forEach(el => {
        const pickr = Pickr.create({
            ...pickrOptions,
            el: el
        });

        pickr.on('save', (color) => {
            el.value = color.toHEXA().toString();
            pickr.hide();
        });
    });

    // آیکون پیکر
    const iconPicker = {
        icons: [
            'fas fa-folder', 'fas fa-folder-open', 'fas fa-folder-plus',
            'fas fa-box', 'fas fa-boxes', 'fas fa-shopping-bag',
            'fas fa-store', 'fas fa-tags', 'fas fa-bookmark',
            'fas fa-star', 'fas fa-heart', 'fas fa-gift'
        ],
        
        show(input) {
            const modal = document.createElement('div');
            modal.className = 'icon-picker-modal';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
            modal.style.zIndex = '9999';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            
            const content = document.createElement('div');
            content.className = 'icon-picker-content';
            content.style.backgroundColor = '#fff';
            content.style.padding = '20px';
            content.style.borderRadius = '8px';
            content.style.maxWidth = '400px';
            content.style.width = '90%';
            content.style.maxHeight = '80vh';
            content.style.overflowY = 'auto';
            
            content.innerHTML = `
                <div class="icon-picker-search mb-3">
                    <input type="text" class="form-control" placeholder="جستجوی آیکون...">
                </div>
                <div class="icon-picker-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap: 10px;">
                    ${this.icons.map(icon => `
                        <button type="button" class="btn btn-outline-secondary icon-item" data-icon="${icon}">
                            <i class="${icon}"></i>
                        </button>
                    `).join('')}
                </div>
            `;

            modal.appendChild(content);
            document.body.appendChild(modal);

            // رویداد انتخاب آیکون
            content.querySelectorAll('.icon-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    input.value = btn.dataset.icon;
                    // بروزرسانی نمایش آیکون
                    const iconPreview = input.closest('.input-group').querySelector('i');
                    if (iconPreview) {
                        iconPreview.className = btn.dataset.icon;
                    }
                    modal.remove();
                });
            });

            // رویداد جستجو
            const searchInput = content.querySelector('input');
            searchInput.addEventListener('input', (e) => {
                const value = e.target.value.toLowerCase();
                content.querySelectorAll('.icon-item').forEach(btn => {
                    const icon = btn.dataset.icon.toLowerCase();
                    btn.style.display = icon.includes(value) ? '' : 'none';
                });
            });

            // بستن با کلیک بیرون
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.remove();
            });
        }
    };

    // رویداد دکمه‌های آیکون پیکر
    document.querySelectorAll('#iconPickerBtn, #editIconPickerBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('input');
            iconPicker.show(input);
        });
    });

    // تنظیم خودکار Slug
    document.querySelectorAll('[name="name"]').forEach(input => {
        input.addEventListener('input', function() {
            const slugInput = this.closest('form').querySelector('[name="slug"]');
            if (slugInput && !slugInput.value) {
                slugInput.value = createSlug(this.value);
            }
        });
    });

    // فرم افزودن دسته‌بندی
    document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('../api/categories.php', { // مسیر رو اصلاح کردیم
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.message);
                location.reload();
            } else {
                showError(data.message || 'خطا در ثبت دسته‌بندی');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('خطا در ارسال اطلاعات');
        });
    });

    // فرم ویرایش دسته‌بندی
document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const categoryId = formData.get('category_id');
    
    fetch(`../api/categories.php?id=${categoryId}`, { // مسیر رو اصلاح کردیم
        method: 'PUT',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            location.reload();
        } else {
            showError(data.message || 'خطا در ویرایش دسته‌بندی');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('خطا در ارسال اطلاعات');
    });
});

    // توابع کمکی
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

    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'موفقیت',
            text: message,
            timer: 3000,
            timerProgressBar: true
        });
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: message
        });
    }
});
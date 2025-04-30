// مدیریت دسته‌بندی‌ها
class CategoryManager {
    constructor() {
        this.initializeEventListeners();
        this.loadCategories();
        this.setupSearch();
        this.setupSortable();
    }

    initializeEventListeners() {
        // دکمه افزودن دسته‌بندی جدید
        document.querySelector('.add-category-btn').addEventListener('click', () => {
            this.showCategoryModal();
        });

        // مدیریت کلیک‌های خارج از منوی آپشن‌ها
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.category-options')) {
                document.querySelectorAll('.options-menu').forEach(menu => {
                    menu.classList.remove('active');
                });
            }
        });

        // مدیریت کلیک‌های منوی آپشن‌ها
        document.addEventListener('click', (e) => {
            const optionsBtn = e.target.closest('.options-btn');
            if (optionsBtn) {
                const menu = optionsBtn.nextElementSibling;
                this.toggleOptionsMenu(menu);
            }
        });
    }

    toggleOptionsMenu(menu) {
        // بستن همه منوهای باز
        document.querySelectorAll('.options-menu').forEach(m => {
            if (m !== menu) m.classList.remove('active');
        });
        // تغییر وضعیت منوی فعلی
        menu.classList.toggle('active');
    }

    async loadCategories() {
        try {
            const response = await fetch('api/categories.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderCategories(data.categories);
            } else {
                this.showError('خطا در بارگذاری دسته‌بندی‌ها');
            }
        } catch (error) {
            this.showError('خطا در ارتباط با سرور');
        }
    }

    renderCategories(categories) {
        const grid = document.querySelector('.categories-grid');
        grid.innerHTML = '';

        categories.forEach(category => {
            const card = this.createCategoryCard(category);
            grid.appendChild(card);
        });
    }

    createCategoryCard(category) {
        const card = document.createElement('div');
        card.className = 'category-card';
        card.innerHTML = `
            <div class="category-header">
                <div class="category-title">
                    <div class="category-icon">
                        <i class="${category.icon || 'fas fa-folder'}"></i>
                    </div>
                    <span>${category.name}</span>
                </div>
                <div class="category-options">
                    <button class="options-btn">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="options-menu">
                        <a href="#" class="option-item" onclick="categoryManager.editCategory(${category.id})">
                            <i class="fas fa-edit"></i>
                            <span>ویرایش</span>
                        </a>
                        <a href="#" class="option-item" onclick="categoryManager.addSubcategory(${category.id})">
                            <i class="fas fa-plus"></i>
                            <span>افزودن زیردسته</span>
                        </a>
                        <a href="#" class="option-item" onclick="categoryManager.moveCategory(${category.id})">
                            <i class="fas fa-arrows-alt"></i>
                            <span>انتقال</span>
                        </a>
                        <a href="#" class="option-item text-danger" onclick="categoryManager.deleteCategory(${category.id})">
                            <i class="fas fa-trash-alt"></i>
                            <span>حذف</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="category-content">
                <div class="category-stats">
                    <div class="stat-item">
                        <div class="stat-value">${category.products_count}</div>
                        <div class="stat-label">محصول</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${category.subcategories_count}</div>
                        <div class="stat-label">زیردسته</div>
                    </div>
                </div>
                <div class="category-description">
                    ${category.description || 'بدون توضیحات'}
                </div>
                <div class="subcategories-list">
                    ${this.renderSubcategories(category.subcategories)}
                </div>
            </div>
        `;
        return card;
    }

    renderSubcategories(subcategories) {
        if (!subcategories || subcategories.length === 0) {
            return '<div class="empty-state">بدون زیردسته</div>';
        }

        return subcategories.map(sub => `
            <div class="subcategory-item">
                <div class="subcategory-name">
                    <i class="fas fa-folder-open"></i>
                    <span>${sub.name}</span>
                </div>
                <span class="subcategory-count">${sub.products_count} محصول</span>
            </div>
        `).join('');
    }

    setupSearch() {
        const searchInput = document.querySelector('.search-box input');
        let timeout;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.searchCategories(e.target.value);
            }, 300);
        });
    }

    async searchCategories(query) {
        try {
            const response = await fetch(`api/categories.php?search=${query}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderCategories(data.categories);
            }
        } catch (error) {
            console.error('خطا در جستجو:', error);
        }
    }

    setupSortable() {
        new Sortable(document.querySelector('.categories-grid'), {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: (evt) => {
                this.updateCategoryOrder(evt.oldIndex, evt.newIndex);
            }
        });
    }

    async updateCategoryOrder(oldIndex, newIndex) {
        try {
            const response = await fetch('api/categories.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'reorder',
                    oldIndex,
                    newIndex
                })
            });

            const data = await response.json();
            if (!data.success) {
                this.showError('خطا در بروزرسانی ترتیب');
                this.loadCategories(); // بارگذاری مجدد برای حفظ ترتیب قبلی
            }
        } catch (error) {
            this.showError('خطا در ارتباط با سرور');
            this.loadCategories();
        }
    }

    showCategoryModal(categoryId = null) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">${categoryId ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید'}</h3>
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
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                    <button class="btn btn-primary" id="saveCategory">ذخیره</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('active'), 10);

        if (categoryId) {
            this.loadCategoryData(categoryId);
        }

        this.setupModalEvents(modal);
    }

    async saveCategory(formData) {
        try {
            const response = await fetch('api/categories.php', {
                method: formData.id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            if (data.success) {
                this.showSuccess(formData.id ? 'دسته‌بندی با موفقیت ویرایش شد' : 'دسته‌بندی جدید با موفقیت ایجاد شد');
                this.loadCategories();
            } else {
                this.showError(data.message || 'خطا در ذخیره دسته‌بندی');
            }
        } catch (error) {
            this.showError('خطا در ارتباط با سرور');
        }
    }

    async deleteCategory(categoryId) {
        const result = await Swal.fire({
            title: 'آیا مطمئن هستید؟',
            text: 'این عمل قابل بازگشت نیست!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله، حذف شود',
            cancelButtonText: 'انصراف',
            reverseButtons: true
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`api/categories.php?id=${categoryId}`, {
                    method: 'DELETE'
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess('دسته‌بندی با موفقیت حذف شد');
                    this.loadCategories();
                } else {
                    this.showError(data.message || 'خطا در حذف دسته‌بندی');
                }
            } catch (error) {
                this.showError('خطا در ارتباط با سرور');
            }
        }
    }

    // توابع کمکی برای نمایش پیام‌ها
    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'موفق',
            text: message,
            timer: 2000,
            timerProgressBar: true
        });
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: message
        });
    }
}

// راه‌اندازی مدیریت دسته‌بندی‌ها
const categoryManager = new CategoryManager();
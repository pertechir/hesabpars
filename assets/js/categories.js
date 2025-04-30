// تابع اصلی برای مدیریت دسته‌بندی‌ها
document.addEventListener('DOMContentLoaded', function() {
    // لودینگ اولیه
    showLoading();

    // اینیشیال کردن Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl',
        language: {
            noResults: function() {
                return "نتیجه‌ای یافت نشد";
            }
        },
        width: '100%'
    });

    // اینیشیال کردن Sortable
    const treeView = document.querySelector('.tree-view');
    if (treeView) {
        new Sortable(treeView, {
            group: 'nested',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            handle: '.drag-handle',
            dragClass: 'sortable-drag',
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                updateCategoryPosition(evt.item);
            }
        });

        // امکان Drag & Drop برای زیردسته‌ها
        document.querySelectorAll('.tree-children').forEach(el => {
            new Sortable(el, {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.drag-handle',
                dragClass: 'sortable-drag',
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    updateCategoryPosition(evt.item);
                }
            });
        });
    }

    // Event Listeners برای فرم‌ها
    setupFormListeners();
    
    // Event Listeners برای جستجو و فیلترها
    setupSearchAndFilters();
    
    // Event Listeners برای دکمه‌های عملیات
    setupActionButtons();

    // حذف لودینگ
    hideLoading();
});

// تنظیم Event Listeners برای فرم‌ها
function setupFormListeners() {
    // فرم افزودن دسته‌بندی
    const addForm = document.getElementById('addCategoryForm');
    if (addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await handleFormSubmit(this, 'add-category.php', 'افزودن دسته‌بندی');
        });
    }

    // فرم ویرایش دسته‌بندی
    const editForm = document.getElementById('editCategoryForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await handleFormSubmit(this, 'update-category.php', 'ویرایش دسته‌بندی');
        });
    }

    // فرم عملیات گروهی
    const bulkForm = document.getElementById('bulkActionForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await handleBulkAction(this);
        });
    }

    // تولید خودکار Slug
    document.querySelectorAll('.category-name-input').forEach(input => {
        input.addEventListener('input', function() {
            const slugInput = this.closest('form').querySelector('.category-slug-input');
            if (slugInput && !slugInput.dataset.manual) {
                slugInput.value = generateSlug(this.value);
            }
        });
    });

    // مدیریت دستی Slug
    document.querySelectorAll('.category-slug-input').forEach(input => {
        input.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    });
}

// تنظیم Event Listeners برای جستجو و فیلترها
function setupSearchAndFilters() {
    // جستجو در دسته‌بندی‌ها
    const searchInput = document.getElementById('categorySearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            filterCategories(searchTerm);
        }, 300));
    }

    // فیلتر وضعیت
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterCategories(document.getElementById('categorySearch').value.toLowerCase());
        });
    }
}

// تنظیم Event Listeners برای دکمه‌های عملیات
function setupActionButtons() {
    // دکمه‌های ویرایش
    document.querySelectorAll('.edit-category').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.dataset;
            populateEditForm(data);
            $('#editCategoryModal').modal('show');
        });
    });

    // دکمه‌های حذف
    document.querySelectorAll('.delete-category').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.dataset;
            confirmDelete(data.id, data.name);
        });
    });

    // دکمه‌های Toggle درخت
    document.querySelectorAll('.tree-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.tree-item');
            const children = item.querySelector('.tree-children');
            if (children) {
                children.classList.toggle('collapsed');
                this.querySelector('i').classList.toggle('fa-caret-down');
                this.querySelector('i').classList.toggle('fa-caret-left');
            }
        });
    });
}

// مدیریت ارسال فرم‌ها
async function handleFormSubmit(form, endpoint, action) {
    try {
        showLoading();

        const formData = new FormData(form);
        
        const response = await fetch(`ajax/${endpoint}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });

        // بررسی نوع محتوای پاسخ
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // اگر پاسخ JSON نبود، متن خطا رو نمایش بدیم
            const text = await response.text();
            throw new Error(`خطای سرور: ${text}`);
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || `خطا در ${action}`);
        }

        // نمایش پیام موفقیت
        await Swal.fire({
            icon: 'success',
            title: 'موفق',
            text: result.message,
            confirmButtonText: 'باشه'
        });

        // بستن مودال و رفرش صفحه
        $(form).closest('.modal').modal('hide');
        form.reset();
        
        // رفرش لیست دسته‌بندی‌ها
        await refreshCategoryList();

    } catch (error) {
        console.error('Error:', error);
        await Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: error.message,
            confirmButtonText: 'باشه'
        });
    } finally {
        hideLoading();
    }
}

// مدیریت عملیات گروهی
async function handleBulkAction(form) {
    const action = form.querySelector('#bulkAction').value;
    const selectedItems = Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb.value);

    if (selectedItems.length === 0) {
        await Swal.fire({
            icon: 'warning',
            title: 'خطا',
            text: 'لطفاً حداقل یک دسته‌بندی را انتخاب کنید',
            confirmButtonText: 'باشه'
        });
        return;
    }

    try {
        showLoading();

        const response = await fetch('ajax/bulk-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                items: selectedItems
            })
        });

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }

        // نمایش پیام موفقیت
        await Swal.fire({
            icon: 'success',
            title: 'موفق',
            text: result.message,
            confirmButtonText: 'باشه'
        });

        // بستن مودال و رفرش صفحه
        $('#bulkActionModal').modal('hide');
        form.reset();
        
        // رفرش لیست دسته‌بندی‌ها
        await refreshCategoryList();

    } catch (error) {
        console.error('Error:', error);
        await Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: error.message,
            confirmButtonText: 'باشه'
        });
    } finally {
        hideLoading();
    }
}

// تأیید حذف دسته‌بندی
async function confirmDelete(categoryId, categoryName) {
    const result = await Swal.fire({
        title: 'آیا مطمئن هستید؟',
        text: `دسته‌بندی "${categoryName}" حذف خواهد شد.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بله، حذف شود',
        cancelButtonText: 'خیر',
        reverseButtons: true
    });

    if (result.isConfirmed) {
        await deleteCategory(categoryId);
    }
}

// حذف دسته‌بندی
async function deleteCategory(categoryId) {
    try {
        showLoading();

        const response = await fetch('ajax/delete-category.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ category_id: categoryId })
        });

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }

        // نمایش پیام موفقیت
        await Swal.fire({
            icon: 'success',
            title: 'موفق',
            text: result.message,
            confirmButtonText: 'باشه'
        });

        // رفرش لیست دسته‌بندی‌ها
        await refreshCategoryList();

    } catch (error) {
        console.error('Error:', error);
        await Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: error.message,
            confirmButtonText: 'باشه'
        });
    } finally {
        hideLoading();
    }
}

// بروزرسانی موقعیت دسته‌بندی
async function updateCategoryPosition(item) {
    try {
        const categoryId = item.dataset.id;
        const parent = item.parentElement;
        const parentId = parent.closest('.tree-item')?.dataset.id || null;
        const siblings = Array.from(parent.children);
        const position = siblings.indexOf(item);

        const response = await fetch('ajax/update-position.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                category_id: categoryId,
                parent_id: parentId,
                position: position
            })
        });

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }

        // رفرش لیست بدون نمایش لودینگ
        await refreshCategoryList(false);

    } catch (error) {
        console.error('Error:', error);
        // در صورت خطا، رفرش کامل صفحه
        await refreshCategoryList();
    }
}

// رفرش لیست دسته‌بندی‌ها
async function refreshCategoryList(showLoadingIndicator = true) {
    try {
        if (showLoadingIndicator) {
            showLoading();
        }

        const response = await fetch('ajax/get-categories.php');
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }

        // بروزرسانی HTML درخت
        const treeView = document.querySelector('.tree-view');
        treeView.innerHTML = result.html;

        // اینیشیال مجدد کامپوننت‌ها
        setupActionButtons();

    } catch (error) {
        console.error('Error:', error);
        await Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: 'خطا در بروزرسانی لیست دسته‌بندی‌ها',
            confirmButtonText: 'باشه'
        });
    } finally {
        if (showLoadingIndicator) {
            hideLoading();
        }
    }
}

// فیلتر کردن دسته‌بندی‌ها
function filterCategories(searchTerm) {
    const statusFilter = document.getElementById('statusFilter').value;
    const items = document.querySelectorAll('.tree-item');
    
    items.forEach(item => {
        const name = item.querySelector('.category-name').textContent.toLowerCase();
        const status = item.dataset.status;
        
        const matchesSearch = name.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        const shouldShow = matchesSearch && matchesStatus;
        
        item.style.display = shouldShow ? '' : 'none';
        
        // نمایش والدین آیتم‌های منطبق
        if (shouldShow) {
            let parent = item.parentElement.closest('.tree-item');
            while (parent) {
                parent.style.display = '';
                parent = parent.parentElement.closest('.tree-item');
            }
        }
    });
}

// پر کردن فرم ویرایش
function populateEditForm(data) {
    document.getElementById('editCategoryId').value = data.id;
    document.getElementById('editCategoryName').value = data.name;
    document.getElementById('editCategorySlug').value = data.slug || '';
    document.getElementById('editCategoryParent').value = data.parent || '';
    document.getElementById('editCategoryStatus').value = data.status;
    document.getElementById('editCategoryDescription').value = data.description || '';
    document.getElementById('editCategoryIcon').value = data.icon || '';
    document.getElementById('editCategoryColor').value = data.color || '#e3f2fd';
    
    // بروزرسانی Select2
    $('#editCategoryParent').trigger('change');
}

// تولید Slug
function generateSlug(text) {
    return text.toLowerCase()
        .replace(/\s+/g, '-')           // Replace spaces with -
        .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
        .replace(/\-\-+/g, '-')         // Replace multiple - with single -
        .replace(/^-+/, '')             // Trim - from start of text
        .replace(/-+$/, '');            // Trim - from end of text
}

// تابع Debounce برای جستجو
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// نمایش لودینگ
function showLoading() {
    let loader = document.querySelector('.loading-overlay');
    if (!loader) {
        loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(loader);
    }
    loader.style.display = 'flex';
}

// مخفی کردن لودینگ
function hideLoading() {
    const loader = document.querySelector('.loading-overlay');
    if (loader) {
        loader.style.display = 'none';
    }
}
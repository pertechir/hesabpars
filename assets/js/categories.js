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
            modal.innerHTML = `
                <div class="icon-picker-content">
                    <div class="icon-picker-search">
                        <input type="text" placeholder="جستجوی آیکون...">
                    </div>
                    <div class="icon-picker-grid">
                        ${this.icons.map(icon => `
                            <button type="button" class="icon-item" data-icon="${icon}">
                                <i class="${icon}"></i>
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // رویداد انتخاب آیکون
            modal.querySelectorAll('.icon-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    input.value = btn.dataset.icon;
                    modal.remove();
                });
            });

            // رویداد جستجو
            const searchInput = modal.querySelector('input');
            searchInput.addEventListener('input', (e) => {
                const value = e.target.value.toLowerCase();
                modal.querySelectorAll('.icon-item').forEach(btn => {
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

    // فیلترها و جستجو
    const filterForm = document.createElement('form');
    filterForm.id = 'filterForm';
    
    document.querySelectorAll('#statusFilter, #sortFilter, #orderFilter').forEach(select => {
        select.addEventListener('change', () => filterForm.submit());
    });

    const searchInput = document.getElementById('categorySearch');
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => filterForm.submit(), 500);
    });

    // مدیریت نمای دسته‌بندی‌ها
    document.querySelectorAll('.view-options button').forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            document.cookie = `category_view_mode=${view};path=/;max-age=31536000`;
            document.querySelector('.categories-grid').className = `categories-grid ${view}`;
            
            document.querySelectorAll('.view-options button').forEach(b => 
                b.classList.toggle('active', b === this)
            );
        });
    });

    // عملیات دسته‌بندی
    window.editCategory = function(id) {
        fetch(`api/categories/${id}`)
            .then(response => response.json())
            .then(category => {
                Object.keys(category).forEach(key => {
                    const input = document.getElementById(`edit_${key}`);
                    if (input) {
                        if (input.tagName === 'SELECT' && input.multiple) {
                            $(input).val(category[key]).trigger('change');
                        } else {
                            input.value = category[key];
                        }
                    }
                });
                
                const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                modal.show();
            })
            .catch(error => showError('خطا در دریافت اطلاعات دسته‌بندی'));
    };

    window.deleteCategory = function(id) {
        Swal.fire({
            title: 'حذف دسته‌بندی',
            text: 'آیا از حذف این دسته‌بندی اطمینان دارید؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله، حذف شود',
            cancelButtonText: 'انصراف',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`api/categories/${id}`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('دسته‌بندی با موفقیت حذف شد');
                            location.reload();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => showError('خطا در حذف دسته‌بندی'));
            }
        });
    };

    window.addSubcategory = function(parentId) {
        document.querySelector('[name="parent_id"]').value = parentId;
        const modal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
        modal.show();
    };

    window.viewProducts = function(categoryId) {
        window.location.href = `products.php?category=${categoryId}`;
    };

    // توابع کمکی
    function createSlug(str) {
        return str
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
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

    // نمایش درختی با jsTree
    if (document.querySelector('.categories-grid.tree')) {
        $('#categoryTree').jstree({
            'core': {
                'themes': {
                    'name': 'default',
                    'responsive': true
                },
                'data': {
                    'url': 'api/categories/tree',
                    'data': function(node) {
                        return { 'id': node.id };
                    }
                }
            },
            'plugins': ['dnd', 'search', 'state', 'types', 'wholerow'],
            'types': {
                'default': {
                    'icon': 'fas fa-folder'
                },
                'active': {
                    'icon': 'fas fa-folder text-success'
                },
                'inactive': {
                    'icon': 'fas fa-folder text-muted'
                }
            }
        }).on('move_node.jstree', function(e, data) {
            // به‌روزرسانی موقعیت دسته‌بندی
            fetch('api/categories/move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: data.node.id,
                    parent: data.parent,
                    position: data.position
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showError(data.message);
                    $('#categoryTree').jstree('refresh');
                }
            })
            .catch(error => {
                showError('خطا در جابجایی دسته‌بندی');
                $('#categoryTree').jstree('refresh');
            });
        });

        // جستجو در درخت
        let treeSearchTimeout;
        document.getElementById('categorySearch').addEventListener('input', function() {
            clearTimeout(treeSearchTimeout);
            treeSearchTimeout = setTimeout(() => {
                $('#categoryTree').jstree('search', this.value);
            }, 250);
        });
    }
});
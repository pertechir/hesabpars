// Categories Management Scripts
document.addEventListener('DOMContentLoaded', function() {
    // Loading Animation
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loadingOverlay);

    // Initialize all components
    Promise.all([
        initializeTreeView(),
        initializeSelect2(),
        initializeSortable(),
        fetchCategories()
    ]).then(() => {
        loadingOverlay.remove();
    }).catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'خطا در بارگذاری',
            text: 'لطفاً صفحه را مجدداً بارگذاری کنید.',
            confirmButtonText: 'تلاش مجدد'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    });

    // Event Listeners
    setupEventListeners();
});

// Initialize Select2
function initializeSelect2() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl',
        language: 'fa',
        placeholder: 'انتخاب کنید...',
        allowClear: true,
        width: '100%'
    });
}

// Initialize Sortable
function initializeSortable() {
    const treeList = document.querySelector('.tree-view');
    if (treeList) {
        new Sortable(treeList, {
            group: 'nested',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            handle: '.drag-handle',
            dragClass: 'sortable-drag',
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                updateCategoryPosition(evt.item.dataset.id, evt.newIndex);
            }
        });
    }
}

// Setup Event Listeners
function setupEventListeners() {
    // Category Search
    const searchInput = document.querySelector('#categorySearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterCategories(searchTerm);
        }, 300));
    }

    // Category Filters
    const statusFilter = document.querySelector('#statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            applyFilters();
        });
    }

    // Add Category Form
    const addCategoryForm = document.querySelector('#addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', handleAddCategory);
    }

    // Edit Category Form
    const editCategoryForm = document.querySelector('#editCategoryForm');
    if (editCategoryForm) {
        editCategoryForm.addEventListener('submit', handleEditCategory);
    }

    // Category Name Input (for slug generation)
    document.querySelectorAll('.category-name-input').forEach(input => {
        input.addEventListener('input', function() {
            const slugInput = this.closest('form').querySelector('.category-slug-input');
            if (slugInput && !slugInput.dataset.manual) {
                slugInput.value = generateSlug(this.value);
            }
        });
    });

    // Manual Slug Editing
    document.querySelectorAll('.category-slug-input').forEach(input => {
        input.addEventListener('input', function() {
            this.dataset.manual = true;
        });
    });

    // Delete Category Buttons
    document.querySelectorAll('.delete-category').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.dataset.id;
            const categoryName = this.dataset.name;
            confirmDeleteCategory(categoryId, categoryName);
        });
    });

    // Toggle Category Status
    document.querySelectorAll('.toggle-status').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.dataset.id;
            const currentStatus = this.dataset.status;
            toggleCategoryStatus(categoryId, currentStatus);
        });
    });

    // Bulk Actions
    const bulkActionForm = document.querySelector('#bulkActionForm');
    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', handleBulkAction);
    }

    // Tree Toggle Buttons
    document.querySelectorAll('.tree-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.tree-item');
            const children = item.nextElementSibling;
            if (children && children.classList.contains('tree-children')) {
                children.classList.toggle('collapsed');
                this.querySelector('i').classList.toggle('fa-caret-down');
                this.querySelector('i').classList.toggle('fa-caret-left');
            }
        });
    });
}

// Category CRUD Operations
async function handleAddCategory(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        Swal.fire({
            title: 'در حال پردازش...',
            text: 'لطفاً صبر کنید',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch('ajax/add-category.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: 'دسته‌بندی با موفقیت ایجاد شد',
                confirmButtonText: 'باشه'
            }).then(() => {
                $('#addCategoryModal').modal('hide');
                form.reset();
                refreshCategoryTree();
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: error.message || 'خطا در ایجاد دسته‌بندی',
            confirmButtonText: 'باشه'
        });
    }
}

async function handleEditCategory(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        Swal.fire({
            title: 'در حال پردازش...',
            text: 'لطفاً صبر کنید',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch('ajax/update-category.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: 'دسته‌بندی با موفقیت بروزرسانی شد',
                confirmButtonText: 'باشه'
            }).then(() => {
                $('#editCategoryModal').modal('hide');
                refreshCategoryTree();
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: error.message || 'خطا در بروزرسانی دسته‌بندی',
            confirmButtonText: 'باشه'
        });
    }
}

function confirmDeleteCategory(categoryId, categoryName) {
    Swal.fire({
        title: 'آیا مطمئن هستید؟',
        text: `دسته‌بندی "${categoryName}" حذف خواهد شد.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بله، حذف شود',
        cancelButtonText: 'خیر',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            deleteCategory(categoryId);
        }
    });
}

async function deleteCategory(categoryId) {
    try {
        Swal.fire({
            title: 'در حال پردازش...',
            text: 'لطفاً صبر کنید',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch('ajax/delete-category.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ category_id: categoryId })
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: 'دسته‌بندی با موفقیت حذف شد',
                confirmButtonText: 'باشه'
            }).then(() => {
                refreshCategoryTree();
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: error.message || 'خطا در حذف دسته‌بندی',
            confirmButtonText: 'باشه'
        });
    }
}

async function toggleCategoryStatus(categoryId, currentStatus) {
    try {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        Swal.fire({
            title: 'در حال پردازش...',
            text: 'لطفاً صبر کنید',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch('ajax/update-category-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                category_id: categoryId,
                status: newStatus
            })
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: 'وضعیت دسته‌بندی با موفقیت تغییر کرد',
                confirmButtonText: 'باشه'
            }).then(() => {
                refreshCategoryTree();
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: error.message || 'خطا در تغییر وضعیت دسته‌بندی',
            confirmButtonText: 'باشه'
        });
    }
}

async function handleBulkAction(e) {
    e.preventDefault();
    const form = e.target;
    const action = form.querySelector('#bulkAction').value;
    const selectedItems = Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb.value);

    if (selectedItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'خطا',
            text: 'لطفاً حداقل یک دسته‌بندی را انتخاب کنید',
            confirmButtonText: 'باشه'
        });
        return;
    }

    try {
        Swal.fire({
            title: 'در حال پردازش...',
            text: 'لطفاً صبر کنید',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

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

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: 'عملیات با موفقیت انجام شد',
                confirmButtonText: 'باشه'
            }).then(() => {
                $('#bulkActionModal').modal('hide');
                form.reset();
                refreshCategoryTree();
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: error.message || 'خطا در انجام عملیات گروهی',
            confirmButtonText: 'باشه'
        });
    }
}

// Utility Functions
function generateSlug(text) {
    const persian = ['ا','ب','پ','ت','ث','ج','چ','ح','خ','د','ذ','ر','ز','ژ','س','ش','ص','ض','ط','ظ','ع','غ','ف','ق','ک','گ','ل','م','ن','و','ه','ی'];
    const english = ['a','b','p','t','th','j','ch','h','kh','d','th','r','z','zh','s','sh','s','z','t','z','a','gh','f','q','k','g','l','m','n','v','h','y'];
    
    let slug = text.toLowerCase();
    
    // Replace Persian characters with English equivalents
    for (let i = 0; i < persian.length; i++) {
        slug = slug.replace(new RegExp(persian[i], 'g'), english[i]);
    }
    
    // Replace spaces and special characters with dashes
    slug = slug.replace(/[^a-z0-9-]/g, '-')
               .replace(/-+/g, '-')
               .replace(/^-|-$/g, '');
               
    return slug;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function filterCategories(searchTerm) {
    const items = document.querySelectorAll('.tree-item');
    items.forEach(item => {
        const name = item.querySelector('.category-name').textContent.toLowerCase();
        const visible = name.includes(searchTerm);
        item.style.display = visible ? '' : 'none';
        
        // Show parent categories if child matches
        if (visible) {
            let parent = item.parentElement;
            while (parent && parent.classList.contains('tree-children')) {
                parent.style.display = '';
                parent = parent.parentElement;
            }
        }
    });
}

function applyFilters() {
    const statusFilter = document.querySelector('#statusFilter').value;
    const items = document.querySelectorAll('.tree-item');
    
    items.forEach(item => {
        const status = item.dataset.status;
        const visible = !statusFilter || status === statusFilter;
        item.style.display = visible ? '' : 'none';
    });
}

async function refreshCategoryTree() {
    try {
        const response = await fetch('ajax/get-categories.php');
        const result = await response.json();
        
        if (result.success) {
            const treeView = document.querySelector('.tree-view');
            treeView.innerHTML = result.html;
            
            // Reinitialize components
            initializeSortable();
            setupEventListeners();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error refreshing category tree:', error);
    }
}

async function updateCategoryPosition(categoryId, newIndex) {
    try {
        const response = await fetch('ajax/update-position.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                category_id: categoryId,
                position: newIndex
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error updating category position:', error);
        // Optionally refresh the tree to ensure correct order
        refreshCategoryTree();
    }
}
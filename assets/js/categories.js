// Category Tree Management
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        dir: 'rtl',
        language: 'fa'
    });

    // Initialize SortableJS for drag-and-drop
    const treeList = document.querySelector('.tree-view');
    if (treeList) {
        new Sortable(treeList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const itemId = evt.item.dataset.id;
                const newIndex = evt.newIndex;
                updateCategoryPosition(itemId, newIndex);
            }
        });
    }

    // Toggle category status
    $('.toggle-status').on('click', function() {
        const categoryId = $(this).data('id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        Swal.fire({
            title: 'تغییر وضعیت',
            text: 'آیا از تغییر وضعیت این دسته‌بندی اطمینان دارید؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله',
            cancelButtonText: 'خیر',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('ajax/update-category-status.php', {
                    category_id: categoryId,
                    status: newStatus
                }, function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'موفق',
                            text: 'وضعیت دسته‌بندی با موفقیت تغییر کرد',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('خطا', response.message, 'error');
                    }
                });
            }
        });
    });

    // Delete category
    $('.delete-category').on('click', function() {
        const categoryId = $(this).data('id');

        Swal.fire({
            title: 'حذف دسته‌بندی',
            text: 'آیا از حذف این دسته‌بندی اطمینان دارید؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله، حذف کن',
            cancelButtonText: 'خیر',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('ajax/delete-category.php', {
                    category_id: categoryId
                }, function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'موفق',
                            text: 'دسته‌بندی با موفقیت حذف شد',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('خطا', response.message, 'error');
                    }
                });
            }
        });
    });

    // Add new category
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: 'ajax/add-category.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'موفق',
                        text: 'دسته‌بندی جدید با موفقیت اضافه شد',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('خطا', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('خطا', 'خطا در ارتباط با سرور', 'error');
            }
        });
    });

    // Edit category
    $('.edit-category').on('click', function() {
        const categoryId = $(this).data('id');
        
        $.get('ajax/get-category.php', { category_id: categoryId }, function(response) {
            if (response.success) {
                const category = response.data;
                $('#editCategoryId').val(category.id);
                $('#editCategoryName').val(category.name);
                $('#editCategorySlug').val(category.slug);
                $('#editCategoryParent').val(category.parent_id).trigger('change');
                $('#editCategoryDescription').val(category.description);
                $('#editCategoryStatus').val(category.status);
                $('#editCategoryModal').modal('show');
            } else {
                Swal.fire('خطا', response.message, 'error');
            }
        });
    });

    // Update category
    $('#editCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: 'ajax/update-category.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'موفق',
                        text: 'دسته‌بندی با موفقیت بروزرسانی شد',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('خطا', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('خطا', 'خطا در ارتباط با سرور', 'error');
            }
        });
    });

    // Bulk actions
    $('#bulkActionForm').on('submit', function(e) {
        e.preventDefault();
        const selectedItems = $('.category-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedItems.length === 0) {
            Swal.fire('خطا', 'لطفاً حداقل یک دسته‌بندی را انتخاب کنید', 'warning');
            return;
        }

        const action = $('#bulkAction').val();
        let confirmMessage = '';
        
        switch(action) {
            case 'delete':
                confirmMessage = 'آیا از حذف دسته‌بندی‌های انتخاب شده اطمینان دارید؟';
                break;
            case 'activate':
                confirmMessage = 'آیا از فعال کردن دسته‌بندی‌های انتخاب شده اطمینان دارید؟';
                break;
            case 'deactivate':
                confirmMessage = 'آیا از غیرفعال کردن دسته‌بندی‌های انتخاب شده اطمینان دارید؟';
                break;
            default:
                Swal.fire('خطا', 'لطفاً یک عملیات را انتخاب کنید', 'warning');
                return;
        }

        Swal.fire({
            title: 'تأیید عملیات',
            text: confirmMessage,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله',
            cancelButtonText: 'خیر',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('ajax/bulk-action.php', {
                    items: selectedItems,
                    action: action
                }, function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'موفق',
                            text: 'عملیات با موفقیت انجام شد',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('خطا', response.message, 'error');
                    }
                });
            }
        });
    });
});

// Utility Functions
function updateCategoryPosition(categoryId, newIndex) {
    $.post('ajax/update-position.php', {
        category_id: categoryId,
        position: newIndex
    }, function(response) {
        if (!response.success) {
            Swal.fire('خطا', response.message, 'error');
        }
    });
}

function generateSlug(title) {
    return title
        .toLowerCase()
        .replace(/[^a-z0-9-]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

// Auto-generate slug
$('.category-name-input').on('input', function() {
    const title = $(this).val();
    const slugInput = $(this).closest('form').find('.category-slug-input');
    if (!slugInput.data('manual')) {
        slugInput.val(generateSlug(title));
    }
});

// Manual slug editing
$('.category-slug-input').on('input', function() {
    $(this).data('manual', true);
});

// Live search
$('#categorySearch').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    $('.tree-item').each(function() {
        const categoryName = $(this).find('.category-name').text().toLowerCase();
        $(this).toggle(categoryName.includes(searchTerm));
    });
});

// Collapse/Expand all
$('#collapseAll').on('click', function() {
    $('.tree-branch').addClass('collapsed');
});

$('#expandAll').on('click', function() {
    $('.tree-branch').removeClass('collapsed');
});
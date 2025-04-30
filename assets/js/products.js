document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl',
        language: 'fa'
    });

    // Initialize Dropzone
    let imageDropzone = new Dropzone("#imageDropzone", {
        url: "ajax/upload-product-image.php",
        paramName: "image",
        maxFilesize: 5, // MB
        maxFiles: 1,
        acceptedFiles: "image/*",
        addRemoveLinks: true,
        dictDefaultMessage: "تصویر محصول را اینجا رها کنید",
        dictRemoveFile: "حذف تصویر",
        dictFileTooBig: "حجم فایل بیشتر از حد مجاز است ({{filesize}}MB). حداکثر حجم مجاز: {{maxFilesize}}MB.",
        dictInvalidFileType: "این نوع فایل مجاز نیست",
    });

    // Generate random code
    document.getElementById('generateCode').addEventListener('click', function() {
        const randomCode = Math.random().toString(36).substr(2, 8).toUpperCase();
        document.getElementById('productCode').value = randomCode;
    });

    // Generate barcode
    document.getElementById('generateBarcode').addEventListener('click', function() {
        const timestamp = Date.now().toString();
        const randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
        document.getElementById('productBarcode').value = timestamp.substr(-8) + randomNum;
    });

    // Calculate selling price based on cost price
    document.getElementById('productCostPrice').addEventListener('input', function() {
        const costPrice = parseFloat(this.value) || 0;
        // افزودن 20% به قیمت خرید به عنوان پیش‌فرض
        const sellingPrice = Math.ceil(costPrice * 1.2);
        document.getElementById('productSellingPrice').value = sellingPrice;
    });

    // Form validation and submission
    const form = document.getElementById('addProductForm');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        try {
            const formData = new FormData(form);
            
            // نمایش لودینگ
            showLoading();

            const response = await fetch('ajax/add-product.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('خطا در ارتباط با سرور');
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'خطا در ثبت محصول');
            }

            // نمایش پیام موفقیت
            await Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: 'محصول با موفقیت ثبت شد',
                confirmButtonText: 'باشه'
            });

            // ریدایرکت به صفحه محصولات
            window.location.href = 'products.php';

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
    });

    // Prevent form submission on enter key
    form.addEventListener('keypress', function(e) {
        if (e.keyCode === 13 || e.which === 13) {
            e.preventDefault();
            return false;
        }
    });
});

// Format numbers with comma
function formatNumber(input) {
    let value = input.value.replace(/[^\d]/g, '');
    input.value = Number(value).toLocaleString('fa-IR');
}

// Show loading overlay
function showLoading() {
    const loader = document.querySelector('.loading-overlay');
    if (loader) {
        loader.style.display = 'flex';
    }
}

// Hide loading overlay
function hideLoading() {
    const loader = document.querySelector('.loading-overlay');
    if (loader) {
        loader.style.display = 'none';
    }
}
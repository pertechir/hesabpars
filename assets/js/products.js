$(document).ready(function() {
    // Initialize Select2 for all select elements
    $('.select2').select2({
        theme: 'bootstrap-5',
        language: 'fa',
        dir: 'rtl'
    });

    // Initialize TinyMCE for product description
    tinymce.init({
        selector: '#productDescription',
        direction: 'rtl',
        language: 'fa',
        plugins: 'directionality paste lists link image table code',
        toolbar: 'undo redo | formatselect | bold italic | alignright aligncenter alignleft alignjustify | numlist bullist | link image | table | ltr rtl | code',
        height: 300,
        menubar: false
    });

    // Image preview
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
                $('#imagePreviewContainer').show();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#productImage").change(function() {
        readURL(this);
    });

    // Dropzone for gallery images
    Dropzone.autoDiscover = false;
    new Dropzone("#galleryUpload", {
        url: BASE_URL + "/api/upload-gallery.php",
        acceptedFiles: "image/*",
        maxFilesize: 5,
        maxFiles: 10,
        dictDefaultMessage: "تصاویر را اینجا رها کنید یا کلیک کنید",
        success: function(file, response) {
            var hiddenInput = $('<input type="hidden" name="gallery[]" />').val(response.path);
            $('#productForm').append(hiddenInput);
        }
    });

    // Handle variations
    let variationCounter = 0;
    
    $('#addVariation').click(function() {
        variationCounter++;
        let template = `
            <div class="variation-row" id="variation-${variationCounter}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">ویژگی</label>
                            <input type="text" class="form-control" name="variations[${variationCounter}][attribute]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">مقدار</label>
                            <input type="text" class="form-control" name="variations[${variationCounter}][value]" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">قیمت اضافی</label>
                            <input type="number" class="form-control" name="variations[${variationCounter}][price]" value="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">موجودی</label>
                            <input type="number" class="form-control" name="variations[${variationCounter}][stock]" value="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-remove-variation" 
                                onclick="removeVariation(${variationCounter})">
                            حذف
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#variationsContainer').append(template);
    });

    // Form validation and submission
    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get TinyMCE content
        var description = tinymce.get('productDescription').getContent();
        
        // Prepare form data
        var formData = new FormData(this);
        formData.append('description', description);
        
        // Show loading spinner
        showSpinner();
        
        // Submit form via AJAX
        $.ajax({
            url: BASE_URL + '/api/save-product.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideSpinner();
                if (response.success) {
                    showToast('success', 'موفقیت', 'محصول با موفقیت ذخیره شد');
                    setTimeout(function() {
                        window.location.href = BASE_URL + '/pages/products.php';
                    }, 2000);
                } else {
                    showToast('error', 'خطا', response.message);
                }
            },
            error: function() {
                hideSpinner();
                showToast('error', 'خطا', 'خطا در ارتباط با سرور');
            }
        });
    });
});

// Helper functions
function removeVariation(id) {
    $(`#variation-${id}`).remove();
}

function showSpinner() {
    $('body').append(`
        <div class="spinner-overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">در حال بارگذاری...</span>
            </div>
        </div>
    `);
}

function hideSpinner() {
    $('.spinner-overlay').remove();
}

function showToast(type, title, message) {
    const toast = `
        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="toast-header">
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    $('.toast-container').append(toast);
    $('.toast').toast('show');
}

// Price calculator
function calculateFinalPrice() {
    const basePrice = parseFloat($('#basePrice').val()) || 0;
    const taxRate = parseFloat($('#taxRate').val()) || 0;
    const discount = parseFloat($('#discount').val()) || 0;
    
    let finalPrice = basePrice;
    
    // Add tax
    if ($('#taxMethod').val() === 'inclusive') {
        finalPrice = basePrice * (1 + (taxRate / 100));
    }
    
    // Subtract discount
    finalPrice -= discount;
    
    $('#finalPrice').val(finalPrice.toFixed(2));
}

// Barcode generator
function generateBarcode() {
    const prefix = '200'; // یا هر پیشوند دلخواه
    const random = Math.floor(Math.random() * 1000000000).toString().padStart(9, '0');
    const barcode = prefix + random;
    
    // محاسبه check digit
    let sum = 0;
    for (let i = 0; i < barcode.length; i++) {
        sum += parseInt(barcode[i]) * (i % 2 === 0 ? 3 : 1);
    }
    const checkDigit = (10 - (sum % 10)) % 10;
    
    $('#barcode').val(barcode + checkDigit);
}
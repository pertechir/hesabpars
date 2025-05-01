document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
    setupEventListeners();
});

// Initialize all components
function initializeComponents() {
    initializeSelect2();
    initializeImageUpload();
    initializeSwitches();
    initializePriceModal();
}

// Initialize Select2 with AJAX
function initializeSelect2() {
    $('#categorySelect').select2({
        theme: 'bootstrap-5',
        dir: 'rtl',
        language: 'fa',
        ajax: {
            url: BASE_URL + '/api/categories/search',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items,
                    pagination: {
                        more: data.hasMore
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 0,
        placeholder: 'انتخاب دسته‌بندی',
    });
}

// Initialize image upload functionality
function initializeImageUpload() {
    const dropZone = document.querySelector('.image-upload-area');
    const fileInput = document.querySelector('#productImages');
    const previewContainer = document.querySelector('.image-preview-container');

    // Prevent defaults
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    // Handle drop
    dropZone.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    // Handle file input change
    fileInput.addEventListener('change', function(e) {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        [...files].forEach(file => {
            if (file.type.startsWith('image/')) {
                uploadFile(file);
            }
        });
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('image', file);

        // Show temporary preview
        const reader = new FileReader();
        reader.onload = function(e) {
            addImagePreview(e.target.result, 'در حال آپلود...');
        }
        reader.readAsDataURL(file);

        // Upload to server
        fetch(BASE_URL + '/api/products/upload-image', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateImagePreview(data.imageUrl, data.imageId);
            } else {
                showNotification('error', 'خطا در آپلود تصویر');
                removeLastPreview();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'خطا در آپلود تصویر');
            removeLastPreview();
        });
    }

    function addImagePreview(src, status) {
        const html = `
            <div class="image-preview-item" data-status="${status}">
                <img src="${src}" alt="Preview">
                <button type="button" class="remove-image">×</button>
                <div class="upload-status">${status}</div>
            </div>
        `;
        previewContainer.insertAdjacentHTML('beforeend', html);
    }

    function updateImagePreview(url, id) {
        const lastPreview = previewContainer.lastElementChild;
        if (lastPreview) {
            lastPreview.querySelector('img').src = url;
            lastPreview.dataset.imageId = id;
            lastPreview.querySelector('.upload-status').remove();
        }
    }

    function removeLastPreview() {
        const lastPreview = previewContainer.lastElementChild;
        if (lastPreview) {
            lastPreview.remove();
        }
    }
}

// Initialize switches
function initializeSwitches() {
    // Accounting code switch
    const accountingCodeSwitch = document.querySelector('#autoAccountingCode');
    const accountingCodeInput = document.querySelector('#accountingCode');

    accountingCodeSwitch.addEventListener('change', function() {
        accountingCodeInput.disabled = this.checked;
        if (this.checked) {
            generateAccountingCode();
        }
    });

    // Tax switches
    const salesTaxSwitch = document.querySelector('#salesTaxEnabled');
    const purchaseTaxSwitch = document.querySelector('#purchaseTaxEnabled');
    
    [salesTaxSwitch, purchaseTaxSwitch].forEach(switchEl => {
        switchEl.addEventListener('change', function() {
            const targetSection = this.closest('.tax-section')
                                    .querySelector('.tax-details');
            targetSection.style.display = this.checked ? 'block' : 'none';
        });
    });
}

// Initialize price modal
function initializePriceModal() {
    const modal = new bootstrap.Modal(document.querySelector('#priceModal'));
    const addPriceTypeBtn = document.querySelector('#addPriceType');
    const priceTypesContainer = document.querySelector('#priceTypesContainer');

    addPriceTypeBtn.addEventListener('click', function() {
        const html = `
            <div class="price-type-row">
                <input type="text" class="form-control" placeholder="نوع قیمت">
                <select class="form-select currency-select">
                    <option value="IRR">ریال</option>
                    <option value="USD">دلار</option>
                    <option value="EUR">یورو</option>
                </select>
                <input type="number" class="form-control" placeholder="قیمت">
                <button type="button" class="btn btn-danger btn-sm remove-price-type">×</button>
            </div>
        `;
        priceTypesContainer.insertAdjacentHTML('beforeend', html);
    });

    // Remove price type
    priceTypesContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-price-type')) {
            e.target.closest('.price-type-row').remove();
        }
    });
}

// Setup event listeners
function setupEventListeners() {
    // Generate accounting code
    document.querySelector('#generateAccountingCode').addEventListener('click', generateAccountingCode);

    // Add tax type
    document.querySelector('#addTaxType').addEventListener('click', function() {
        const taxType = prompt('نوع مالیات جدید را وارد کنید:');
        if (taxType) {
            addTaxType(taxType);
        }
    });

    // Add tax unit
    document.querySelector('#addTaxUnit').addEventListener('click', function() {
        const taxUnit = prompt('واحد مالیاتی جدید را وارد کنید:');
        if (taxUnit) {
            addTaxUnit(taxUnit);
        }
    });

    // Form submission
    document.querySelector('#productForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveProduct();
    });
}

// Generate accounting code
function generateAccountingCode() {
    fetch(BASE_URL + '/api/products/generate-code')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('#accountingCode').value = data.code;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Add tax type
function addTaxType(type) {
    const taxTypeSelect = document.querySelector('#taxType');
    const option = new Option(type, type);
    taxTypeSelect.add(option);
    taxTypeSelect.value = type;
}

// Add tax unit
function addTaxUnit(unit) {
    const taxUnitSelect = document.querySelector('#taxUnit');
    const option = new Option(unit, unit);
    taxUnitSelect.add(option);
    taxUnitSelect.value = unit;
}

// Save product
function saveProduct() {
    const form = document.querySelector('#productForm');
    const formData = new FormData(form);

    // Add price types
    const priceTypes = [];
    document.querySelectorAll('.price-type-row').forEach(row => {
        priceTypes.push({
            type: row.querySelector('input[type="text"]').value,
            currency: row.querySelector('.currency-select').value,
            price: row.querySelector('input[type="number"]').value
        });
    });
    formData.append('priceTypes', JSON.stringify(priceTypes));

    // Show loading
    showLoading();

    // Send to server
    fetch(BASE_URL + '/api/products/save', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('success', 'محصول با موفقیت ذخیره شد');
            setTimeout(() => {
                window.location.href = BASE_URL + '/pages/products.php';
            }, 2000);
        } else {
            showNotification('error', data.message || 'خطا در ذخیره محصول');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('error', 'خطا در ذخیره محصول');
    });
}

// Utility functions
function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function showLoading() {
    const loader = document.createElement('div');
    loader.className = 'loading-overlay';
    loader.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.querySelector('.loading-overlay');
    if (loader) {
        loader.remove();
    }
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
} 
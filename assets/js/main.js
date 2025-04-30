document.addEventListener('DOMContentLoaded', function() {
    // تابع نمایش پیام‌های سیستم
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert alert-${type}`;
        messageDiv.textContent = message;
        
        const container = document.querySelector('.main-content');
        container.insertBefore(messageDiv, container.firstChild);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    // اعتبارسنجی فرم‌ها
    function validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                showMessage(`فیلد ${field.getAttribute('placeholder')} الزامی است`, 'error');
            } else {
                field.classList.remove('error');
            }
        });

        return isValid;
    }

    // اضافه کردن اعتبارسنجی به تمام فرم‌ها
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // تابع فرمت‌کننده اعداد به فرمت پول
    function formatMoney(amount) {
        return new Intl.NumberFormat('fa-IR').format(amount);
    }

    // تبدیل تمام اعداد با کلاس money-format به فرمت پول
    document.querySelectorAll('.money-format').forEach(element => {
        const amount = parseInt(element.textContent);
        if (!isNaN(amount)) {
            element.textContent = formatMoney(amount);
        }
    });

    // مدیریت منوی کناری در نمایشگرهای کوچک
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
    }

    // اضافه کردن لودر به دکمه‌های submit
    document.querySelectorAll('button[type="submit"]').forEach(button => {
        button.addEventListener('click', function() {
            if (validateForm(this.closest('form'))) {
                this.classList.add('loading');
                this.disabled = true;
            }
        });
    });
});

// تابع برای ارسال درخواست‌های AJAX
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`خطا در درخواست: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('خطا:', error);
        showMessage(error.message, 'error');
        return null;
    }
}

// تابع برای به‌روزرسانی خودکار داده‌ها
function autoUpdate(url, containerId, interval = 60000) {
    setInterval(async () => {
        const data = await fetchData(url);
        if (data) {
            const container = document.getElementById(containerId);
            if (container) {
                // به‌روزرسانی محتوا
                container.innerHTML = data.html;
            }
        }
    }, interval);
}
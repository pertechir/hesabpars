document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const steps = document.querySelectorAll('.step-pane');
    const stepIndicators = document.querySelectorAll('.step');
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    let currentStep = 1;

    // تابع اعتبارسنجی فیلدها
    const validateStep = (step) => {
        const currentPane = document.querySelector(`.step-pane[data-step="${step}"]`);
        const requiredFields = currentPane.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                showError(field, 'این فیلد الزامی است');
            } else {
                clearError(field);
                
                // اعتبارسنجی خاص برای هر فیلد
                switch(field.id) {
                    case 'username':
                        if (field.value.length < 4) {
                            isValid = false;
                            showError(field, 'نام کاربری باید حداقل 4 کاراکتر باشد');
                        } else if (!/^[a-zA-Z0-9_]+$/.test(field.value)) {
                            isValid = false;
                            showError(field, 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، اعداد و _ باشد');
                        }
                        break;

                    case 'email':
                        if (!isValidEmail(field.value)) {
                            isValid = false;
                            showError(field, 'لطفاً یک ایمیل معتبر وارد کنید');
                        }
                        break;

                    case 'password':
                        if (field.value.length < 8) {
                            isValid = false;
                            showError(field, 'رمز عبور باید حداقل 8 کاراکتر باشد');
                        } else if (!/[A-Z]/.test(field.value)) {
                            isValid = false;
                            showError(field, 'رمز عبور باید حداقل یک حرف بزرگ داشته باشد');
                        } else if (!/[a-z]/.test(field.value)) {
                            isValid = false;
                            showError(field, 'رمز عبور باید حداقل یک حرف کوچک داشته باشد');
                        } else if (!/[0-9]/.test(field.value)) {
                            isValid = false;
                            showError(field, 'رمز عبور باید حداقل یک عدد داشته باشد');
                        }
                        break;

                    case 'password_confirm':
                        const password = document.getElementById('password');
                        if (field.value !== password.value) {
                            isValid = false;
                            showError(field, 'تکرار رمز عبور مطابقت ندارد');
                        }
                        break;

                    case 'phone':
                        if (field.value && !/^[0-9]{11}$/.test(field.value)) {
                            isValid = false;
                            showError(field, 'شماره تماس باید 11 رقم باشد');
                        }
                        break;
                }
            }
        });

        return isValid;
    };

    // نمایش خطا
    const showError = (field, message) => {
        clearError(field);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.fontSize = '0.85em';
        errorDiv.style.marginTop = '5px';
        field.parentNode.parentNode.appendChild(errorDiv);
        field.parentNode.classList.add('error');
    };

    // پاک کردن خطا
    const clearError = (field) => {
        const parent = field.parentNode.parentNode;
        const existingError = parent.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        field.parentNode.classList.remove('error');
    };

    // بررسی ایمیل معتبر
    const isValidEmail = (email) => {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    // مدیریت نمایش/مخفی کردن رمز عبور
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // رفتن به مرحله بعد
    const goToStep = (step) => {
        steps.forEach(s => s.classList.remove('active'));
        stepIndicators.forEach(s => s.classList.remove('active'));
        
        document.querySelector(`.step-pane[data-step="${step}"]`).classList.add('active');
        document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
        
        currentStep = step;

        // انیمیشن برای مرحله جدید
        const activeStep = document.querySelector(`.step-pane[data-step="${step}"]`);
        activeStep.style.animation = 'fadeIn 0.5s forwards';
    };

    // دکمه‌های بعدی
    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (validateStep(currentStep)) {
                goToStep(currentStep + 1);
            }
        });
    });

    // دکمه‌های قبلی
    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            goToStep(currentStep - 1);
        });
    });

    // ارسال فرم
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!validateStep(currentStep)) {
            return;
        }

        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <span class="spinner"></span>
            در حال ارسال...
        `;

        try {
            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showMessage('ثبت نام با موفقیت انجام شد. در حال انتقال...', 'success');
                setTimeout(() => {
                    window.location.href = result.redirect || 'registration_success.php';
                }, 2000);
            } else {
                showMessage(result.message || 'خطا در ثبت نام. لطفاً دوباره تلاش کنید.', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = 'تکمیل ثبت نام <i class="fas fa-check"></i>';
            }
        } catch (error) {
            showMessage('خطا در برقراری ارتباط با سرور', 'error');
            submitButton.disabled = false;
            submitButton.innerHTML = 'تکمیل ثبت نام <i class="fas fa-check"></i>';
        }
    });

    // نمایش پیام
    const showMessage = (message, type = 'info') => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.innerHTML = `
            <div class="message-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(messageDiv);

        // انیمیشن ورود پیام
        messageDiv.style.animation = 'slideIn 0.5s forwards';

        // حذف پیام بعد از 5 ثانیه
        setTimeout(() => {
            messageDiv.style.animation = 'slideOut 0.5s forwards';
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    };

    // انیمیشن برای پلن‌های اشتراک
    const plans = document.querySelectorAll('.plan');
    plans.forEach(plan => {
        plan.addEventListener('mouseover', function() {
            plans.forEach(p => p.style.transform = 'scale(0.95)');
            this.style.transform = 'scale(1.05)';
        });

        plan.addEventListener('mouseout', function() {
            plans.forEach(p => p.style.transform = 'scale(1)');
        });
    });
});

// استایل‌های CSS برای انیمیشن‌ها
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(-100%);
            opacity: 0;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .message {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .message-success {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
    }

    .message-error {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
    }

    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
`;

document.head.appendChild(style);
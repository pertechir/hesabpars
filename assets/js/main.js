document.addEventListener('DOMContentLoaded', function() {
    // انتخاب المان‌های مورد نیاز
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const header = document.querySelector('.main-header');
    const newsletterForm = document.querySelector('.newsletter-form');
    const contactForm = document.querySelector('.contact-form');

    // تابع نمایش پیام
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.textContent = message;

        document.body.appendChild(messageDiv);

        // حذف پیام بعد از 5 ثانیه
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    // مدیریت منوی موبایل
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            mobileMenuToggle.classList.toggle('active');
        });
    }

    // مدیریت هدر چسبان
    let lastScroll = 0;
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll <= 0) {
            header.classList.remove('scroll-up');
            return;
        }

        if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
            // اسکرول به پایین
            header.classList.remove('scroll-up');
            header.classList.add('scroll-down');
        } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
            // اسکرول به بالا
            header.classList.remove('scroll-down');
            header.classList.add('scroll-up');
        }
        lastScroll = currentScroll;
    });

    // اعتبارسنجی فرم خبرنامه
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();

            if (!email) {
                showMessage('لطفا ایمیل خود را وارد کنید', 'error');
                return;
            }

            if (!isValidEmail(email)) {
                showMessage('لطفا یک ایمیل معتبر وارد کنید', 'error');
                return;
            }

            // ارسال فرم
            submitNewsletterForm(email);
        });
    }

    // اعتبارسنجی فرم تماس
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = this.querySelector('#name').value.trim();
            const email = this.querySelector('#email').value.trim();
            const subject = this.querySelector('#subject').value.trim();
            const message = this.querySelector('#message').value.trim();

            if (!name || !email || !subject || !message) {
                showMessage('لطفا تمام فیلدها را پر کنید', 'error');
                return;
            }

            if (!isValidEmail(email)) {
                showMessage('لطفا یک ایمیل معتبر وارد کنید', 'error');
                return;
            }

            // ارسال فرم
            submitContactForm({name, email, subject, message});
        });
    }

    // انیمیشن اسکرول
    const elements = document.querySelectorAll('.animate-on-scroll');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    });

    elements.forEach(element => observer.observe(element));

    // توابع کمکی
    function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }

    async function submitNewsletterForm(email) {
        try {
            const response = await fetch('/process/newsletter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (data.success) {
                showMessage('با تشکر از عضویت شما در خبرنامه', 'success');
                newsletterForm.reset();
            } else {
                showMessage(data.message || 'خطا در ثبت ایمیل', 'error');
            }
        } catch (error) {
            showMessage('خطا در برقراری ارتباط با سرور', 'error');
        }
    }

    async function submitContactForm(formData) {
        try {
            const response = await fetch('/process/contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                showMessage('پیام شما با موفقیت ارسال شد', 'success');
                contactForm.reset();
            } else {
                showMessage(data.message || 'خطا در ارسال پیام', 'error');
            }
        } catch (error) {
            showMessage('خطا در برقراری ارتباط با سرور', 'error');
        }
    }
});
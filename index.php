<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// تنظیم زبان و منطقه زمانی
setlocale(LC_ALL, 'fa_IR.utf8');
date_default_timezone_set('Asia/Tehran');

// کلاس اصلی برای مدیریت صفحه
class LandingPage {
    private $db;
    private $testimonials;
    private $features;
    private $pricingPlans;
    private $blogPosts;
    private $statistics;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeData();
    }

    private function initializeData() {
        // داده‌های نمونه برای بخش‌های مختلف
        $this->testimonials = [
            [
                'id' => 1,
                'name' => 'علی محمدی',
                'position' => 'مدیر شرکت پارس تک',
                'content' => 'استفاده از حساب پارسه باعث صرفه‌جویی در وقت و هزینه‌های ما شده است.',
                'rating' => 5,
                'image' => 'assets/images/testimonials/user1.jpg'
            ],
            [
                'id' => 2,
                'name' => 'مریم احمدی',
                'position' => 'حسابدار ارشد',
                'content' => 'رابط کاربری ساده و امکانات کامل، حساب پارسه را به بهترین انتخاب تبدیل کرده است.',
                'rating' => 5,
                'image' => 'assets/images/testimonials/user2.jpg'
            ],
            [
                'id' => 3,
                'name' => 'رضا کریمی',
                'position' => 'مدیر مالی',
                'content' => 'گزارش‌گیری پیشرفته و دقیق، نقطه قوت اصلی این سیستم است.',
                'rating' => 4,
                'image' => 'assets/images/testimonials/user3.jpg'
            ]
        ];

        $this->features = [
            [
                'id' => 1,
                'title' => 'مدیریت درآمد و هزینه',
                'description' => 'ثبت و پیگیری تمام تراکنش‌های مالی با جزئیات کامل',
                'icon' => 'fas fa-money-bill-wave',
                'benefits' => [
                    'ثبت خودکار تراکنش‌ها',
                    'دسته‌بندی هوشمند هزینه‌ها',
                    'گزارش‌گیری لحظه‌ای',
                    'پشتیبانی از انواع ارز'
                ]
            ],
            [
                'id' => 2,
                'title' => 'گزارش‌های پیشرفته',
                'description' => 'تحلیل و گزارش‌گیری حرفه‌ای از وضعیت مالی',
                'icon' => 'fas fa-chart-line',
                'benefits' => [
                    'نمودارهای تحلیلی',
                    'گزارش‌های دوره‌ای',
                    'مقایسه دوره‌های مالی',
                    'خروجی Excel و PDF'
                ]
            ],
            [
                'id' => 3,
                'title' => 'مدیریت فاکتورها',
                'description' => 'ایجاد و مدیریت فاکتورهای خرید و فروش',
                'icon' => 'fas fa-file-invoice',
                'benefits' => [
                    'قالب‌های حرفه‌ای فاکتور',
                    'ارسال خودکار به مشتری',
                    'پیگیری وضعیت پرداخت',
                    'یادآوری سررسیدها'
                ]
            ],
            [
                'id' => 4,
                'title' => 'داشبورد هوشمند',
                'description' => 'نمایش خلاصه وضعیت مالی در یک نگاه',
                'icon' => 'fas fa-tachometer-alt',
                'benefits' => [
                    'نمایش آمار کلیدی',
                    'هشدارهای هوشمند',
                    'گزارش روزانه',
                    'قابلیت شخصی‌سازی'
                ]
            ]
        ];

        $this->pricingPlans = [
            [
                'id' => 1,
                'name' => 'پایه',
                'price' => '۰',
                'duration' => 'ماهانه',
                'features' => [
                    'ثبت ۱۰۰ تراکنش در ماه',
                    '۲ کاربر همزمان',
                    'گزارش‌های پایه',
                    'پشتیبانی ایمیلی'
                ],
                'recommended' => false,
                'button_text' => 'شروع رایگان'
            ],
            [
                'id' => 2,
                'name' => 'حرفه‌ای',
                'price' => '۲۹۹,۰۰۰',
                'duration' => 'ماهانه',
                'features' => [
                    'تراکنش نامحدود',
                    '۱۰ کاربر همزمان',
                    'گزارش‌های پیشرفته',
                    'پشتیبانی تلفنی',
                    'آموزش اختصاصی'
                ],
                'recommended' => true,
                'button_text' => 'انتخاب پلن حرفه‌ای'
            ],
            [
                'id' => 3,
                'name' => 'سازمانی',
                'price' => '۹۹۹,۰۰۰',
                'duration' => 'ماهانه',
                'features' => [
                    'تراکنش نامحدود',
                    'کاربر نامحدود',
                    'تمام امکانات',
                    'پشتیبانی ۲۴/۷',
                    'آموزش حضوری',
                    'API اختصاصی'
                ],
                'recommended' => false,
                'button_text' => 'تماس با ما'
            ]
        ];

        $this->blogPosts = [
            [
                'id' => 1,
                'title' => 'اصول حسابداری مدرن',
                'excerpt' => 'در این مقاله به بررسی اصول حسابداری در عصر دیجیتال می‌پردازیم...',
                'author' => 'دکتر محمد رضایی',
                'date' => '۱۴۰۴/۰۲/۱۰',
                'image' => 'assets/images/blog/post1.jpg',
                'category' => 'آموزش'
            ],
            [
                'id' => 2,
                'title' => 'مزایای حسابداری ابری',
                'excerpt' => 'حسابداری ابری چه مزایایی برای کسب و کار شما دارد؟',
                'author' => 'مهندس زهرا کریمی',
                'date' => '۱۴۰۴/۰۲/۰۸',
                'image' => 'assets/images/blog/post2.jpg',
                'category' => 'تکنولوژی'
            ],
            [
                'id' => 3,
                'title' => 'گزارش‌گیری هوشمند',
                'excerpt' => 'چگونه از داده‌های مالی خود بهترین گزارش‌ها را تهیه کنید...',
                'author' => 'علی محمدی',
                'date' => '۱۴۰۴/۰۲/۰۵',
                'image' => 'assets/images/blog/post3.jpg',
                'category' => 'راهنما'
            ]
        ];

        $this->statistics = [
            [
                'label' => 'کاربران فعال',
                'value' => '۱۲,۰۰۰+',
                'icon' => 'fas fa-users',
                'color' => 'primary'
            ],
            [
                'label' => 'تراکنش‌های روزانه',
                'value' => '۵۰,۰۰۰+',
                'icon' => 'fas fa-exchange-alt',
                'color' => 'success'
            ],
            [
                'label' => 'رضایت مشتریان',
                'value' => '۹۸٪',
                'icon' => 'fas fa-smile',
                'color' => 'warning'
            ],
            [
                'label' => 'پشتیبانی ۲۴/۷',
                'value' => '۳۶۵ روز',
                'icon' => 'fas fa-headset',
                'color' => 'info'
            ]
        ];
    }

    public function renderHero() {
        ?>
        <section class="hero animate-fade-in">
            <div class="hero-content">
                <h1>مدیریت هوشمند حساب‌های مالی</h1>
                <p class="hero-subtitle">با حساب پارسه، مدیریت مالی کسب و کار خود را به سطح بعدی ببرید</p>
                <div class="hero-features">
                    <div class="hero-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>ثبت آسان تراکنش‌ها</span>
                    </div>
                    <div class="hero-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>گزارش‌گیری پیشرفته</span>
                    </div>
                    <div class="hero-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>امنیت بالا</span>
                    </div>
                </div>
                <div class="cta-buttons">
                    <a href="pages/register.php" class="btn btn-primary btn-large">
                        شروع رایگان
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="#demo" class="btn btn-outline btn-large">
                        مشاهده دمو
                        <i class="fas fa-play"></i>
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <img src="assets/images/hero.svg" alt="حسابداری آنلاین">
            </div>
        </section>
        <?php
    }

    public function renderFeatures() {
        ?>
        <section id="features" class="features">
            <div class="section-header">
                <h2>امکانات ویژه</h2>
                <p>با امکانات پیشرفته حساب پارسه، کسب و کار خود را متحول کنید</p>
            </div>
            <div class="features-grid">
                <?php foreach ($this->features as $feature): ?>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="<?php echo $feature['icon']; ?>"></i>
                        </div>
                        <h3><?php echo $feature['title']; ?></h3>
                        <p><?php echo $feature['description']; ?></p>
                        <ul class="feature-benefits">
                            <?php foreach ($feature['benefits'] as $benefit): ?>
                                <li>
                                    <i class="fas fa-check"></i>
                                    <?php echo $benefit; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    public function renderStatistics() {
        ?>
        <section class="statistics">
            <div class="statistics-grid">
                <?php foreach ($this->statistics as $stat): ?>
                    <div class="statistic-card bg-<?php echo $stat['color']; ?>">
                        <div class="statistic-icon">
                            <i class="<?php echo $stat['icon']; ?>"></i>
                        </div>
                        <div class="statistic-content">
                            <h3><?php echo $stat['value']; ?></h3>
                            <p><?php echo $stat['label']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    public function renderTestimonials() {
        ?>
        <section id="testimonials" class="testimonials">
            <div class="section-header">
                <h2>نظرات مشتریان</h2>
                <p>آنچه مشتریان ما درباره حساب پارسه می‌گویند</p>
            </div>
            <div class="testimonials-slider">
                <?php foreach ($this->testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <div class="testimonial-image">
                            <img src="<?php echo $testimonial['image']; ?>" alt="<?php echo $testimonial['name']; ?>">
                        </div>
                        <div class="testimonial-content">
                            <div class="testimonial-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo ($i <= $testimonial['rating']) ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p><?php echo $testimonial['content']; ?></p>
                            <div class="testimonial-author">
                                <h4><?php echo $testimonial['name']; ?></h4>
                                <span><?php echo $testimonial['position']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    public function renderPricing() {
        ?>
        <section id="pricing" class="pricing">
            <div class="section-header">
                <h2>تعرفه‌های اشتراک</h2>
                <p>پلن متناسب با نیاز خود را انتخاب کنید</p>
            </div>
            <div class="pricing-grid">
                <?php foreach ($this->pricingPlans as $plan): ?>
                    <div class="pricing-card <?php echo $plan['recommended'] ? 'recommended' : ''; ?>">
                        <?php if ($plan['recommended']): ?>
                            <div class="recommended-badge">پیشنهاد ویژه</div>
                        <?php endif; ?>
                        <div class="pricing-header">
                            <h3><?php echo $plan['name']; ?></h3>
                            <div class="pricing-price">
                                <span class="currency">تومان</span>
                                <span class="amount"><?php echo $plan['price']; ?></span>
                                <span class="duration">/ <?php echo $plan['duration']; ?></span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <ul>
                                <?php foreach ($plan['features'] as $feature): ?>
                                    <li>
                                        <i class="fas fa-check"></i>
                                        <?php echo $feature; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="pricing-footer">
                            <a href="pages/register.php?plan=<?php echo $plan['id']; ?>" 
                               class="btn <?php echo $plan['recommended'] ? 'btn-primary' : 'btn-outline'; ?>">
                                <?php echo $plan['button_text']; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    public function renderBlog() {
        ?>
        <section id="blog" class="blog">
            <div class="section-header">
                <h2>آخرین مقالات</h2>
                <p>مطالب آموزشی و کاربردی در حوزه حسابداری</p>
            </div>
            <div class="blog-grid">
                <?php foreach ($this->blogPosts as $post): ?>
                    <article class="blog-card">
                        <div class="blog-image">
                            <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>">
                            <span class="blog-category"><?php echo $post['category']; ?></span>
                        </div>
                        <div class="blog-content">
                            <h3><?php echo $post['title']; ?></h3>
                            <p><?php echo $post['excerpt']; ?></p>
                            <div class="blog-meta">
                                <span>
                                    <i class="fas fa-user"></i>
                                    <?php echo $post['author']; ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    <?php echo $post['date']; ?>
                                </span>
                            </div>
                            <a href="blog/post.php?id=<?php echo $post['id']; ?>" class="btn btn-link">
                                ادامه مطلب
                                <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    public function renderNewsletter() {
        ?>
        <section class="newsletter">
            <div class="newsletter-content">
                <h2>عضویت در خبرنامه</h2>
                <p>برای دریافت آخرین اخبار و آموزش‌های حسابداری، در خبرنامه ما عضو شوید</p>
                <form class="newsletter-form" action="process/newsletter.php" method="POST">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="ایمیل خود را وارد کنید" required>
                        <button type="submit" class="btn btn-primary">
                            عضویت
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </section>
        <?php
    }

    public function renderContactForm() {
        ?>
        <section id="contact" class="contact">
            <div class="section-header">
                <h2>تماس با ما</h2>
                <p>سوالی دارید؟ با ما در تماس باشید</p>
            </div>
            <div class="contact-container">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h3>آدرس</h3>
                            <p>تهران، خیابان ولیعصر، ساختمان پارسه</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h3>تلفن تماس</h3>
                            <p>۰۲۱-۱۲۳۴۵۶۷۸</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>ایمیل</h3>
                            <p>info@hesabparse.ir</p>
                        </div>
                    </div>
                </div>
                <form class="contact-form" action="process/contact.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">نام و نام خانوادگی</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">ایمیل</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subject">موضوع</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">پیام</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        ارسال پیام
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </section>
        <?php
    }
}

// ایجاد نمونه از کلاس و نمایش صفحه
$landingPage = new LandingPage();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - سیستم حسابداری آنلاین</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
</head>
<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="#features">امکانات</a></li>
                <li><a href="#pricing">تعرفه‌ها</a></li>
                <li><a href="#testimonials">نظرات</a></li>
                <li><a href="#blog">وبلاگ</a></li>
                <li><a href="#contact">تماس</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="pages/login.php" class="btn btn-outline">ورود</a>
                <a href="pages/register.php" class="btn btn-primary">ثبت نام</a>
            </div>
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </header>

    <main class="main-content">
        <?php
        $landingPage->renderHero();
        $landingPage->renderFeatures();
        $landingPage->renderStatistics();
        $landingPage->renderTestimonials();
        $landingPage->renderPricing();
        $landingPage->renderBlog();
        $landingPage->renderNewsletter();
        $landingPage->renderContactForm();
        ?>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>درباره ما</h3>
                    <p>حساب پارسه، راهکاری مدرن برای مدیریت مالی کسب و کارهای کوچک و متوسط</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-telegram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>دسترسی سریع</h3>
                    <ul>
                        <li><a href="#features">امکانات</a></li>
                        <li><a href="#pricing">تعرفه‌ها</a></li>
                        <li><a href="#testimonials">نظرات مشتریان</a></li>
                        <li><a href="#blog">وبلاگ</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>پشتیبانی</h3>
                    <ul>
                        <li><a href="#">سوالات متداول</a></li>
                        <li><a href="#">راهنمای استفاده</a></li>
                        <li><a href="#">تماس با ما</a></li>
                        <li><a href="#">درخواست پشتیبانی</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>اطلاعات تماس</h3>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            تهران، خیابان ولیعصر، ساختمان پارسه
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            ۰۲۱-۱۲۳۴۵۶۷۸
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            info@hesabparse.ir
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. تمامی حقوق محفوظ است.</p>
                <ul class="footer-links">
                    <li><a href="#">قوانین و مقررات</a></li>
                    <li><a href="#">حریم خصوصی</a></li>
                    <li><a href="#">شرایط استفاده</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
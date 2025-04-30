<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تعیین صفحه فعلی
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// دریافت اطلاعات کاربر
function getUserInfo($userId) {
    try {
        $conn = new PDO("mysql:host=localhost;dbname=hesabpars;charset=utf8mb4", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return null;
    }
}

$userInfo = isset($_SESSION['user_id']) ? getUserInfo($_SESSION['user_id']) : null;

// منوهای سایدبار
$menuItems = [
    [
        'title' => 'داشبورد',
        'icon' => 'fas fa-home',
        'url' => 'dashboard.php'
    ],
    [
        'title' => 'محصولات',
        'icon' => 'fas fa-box',
        'submenu' => [
            ['title' => 'لیست محصولات', 'url' => 'products.php', 'icon' => 'fas fa-list'],
            ['title' => 'افزودن محصول', 'url' => 'add-product.php', 'icon' => 'fas fa-plus'],
            ['title' => 'دسته‌بندی‌ها', 'url' => 'categories.php', 'icon' => 'fas fa-tags']
        ]
    ],
    [
        'title' => 'مشتریان',
        'icon' => 'fas fa-users',
        'submenu' => [
            ['title' => 'لیست مشتریان', 'url' => 'customers.php', 'icon' => 'fas fa-list'],
            ['title' => 'افزودن مشتری', 'url' => 'add-customer.php', 'icon' => 'fas fa-user-plus']
        ]
    ],
    [
        'title' => 'مالی',
        'icon' => 'fas fa-money-bill-wave',
        'submenu' => [
            ['title' => 'فاکتورها', 'url' => 'invoices.php', 'icon' => 'fas fa-file-invoice'],
            ['title' => 'تراکنش‌ها', 'url' => 'transactions.php', 'icon' => 'fas fa-exchange-alt'],
            ['title' => 'گزارشات', 'url' => 'reports.php', 'icon' => 'fas fa-chart-bar']
        ]
    ],
    [
        'title' => 'تنظیمات',
        'icon' => 'fas fa-cog',
        'url' => 'settings.php'
    ]
];
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>
    <div class="sidebar">
        <div class="user-section">
            <div class="user-avatar">
                <img src="<?php echo $userInfo['avatar'] ?? '../assets/images/default-avatar.png'; ?>" alt="Avatar">
            </div>
            <div class="user-name"><?php echo $userInfo['full_name'] ?? 'کاربر مهمان'; ?></div>
            <div class="user-role"><?php echo $userInfo['role'] === 'admin' ? 'مدیر' : 'کاربر'; ?></div>
        </div>

        <div class="sidebar-menu">
            <?php foreach ($menuItems as $item): ?>
                <?php
                $hasSubmenu = isset($item['submenu']);
                $isActive = $current_page === basename($item['url'] ?? '', '.php');
                if ($hasSubmenu) {
                    foreach ($item['submenu'] as $subItem) {
                        if ($current_page === basename($subItem['url'], '.php')) {
                            $isActive = true;
                            break;
                        }
                    }
                }
                ?>
                <a href="<?php echo $hasSubmenu ? '#' : $item['url']; ?>" 
                   class="menu-item <?php echo $isActive ? 'active' : ''; ?>"
                   <?php echo $hasSubmenu ? 'data-toggle="submenu"' : ''; ?>>
                    <i class="<?php echo $item['icon']; ?>"></i>
                    <span class="menu-title"><?php echo $item['title']; ?></span>
                    <?php if ($hasSubmenu): ?>
                        <i class="fas fa-chevron-left menu-arrow"></i>
                    <?php endif; ?>
                </a>

                <?php if ($hasSubmenu): ?>
                    <div class="submenu <?php echo $isActive ? 'active' : ''; ?>">
                        <?php foreach ($item['submenu'] as $subItem): ?>
                            <a href="<?php echo $subItem['url']; ?>" 
                               class="submenu-item <?php echo $current_page === basename($subItem['url'], '.php') ? 'active' : ''; ?>">
                                <i class="<?php echo $subItem['icon']; ?>"></i>
                                <span><?php echo $subItem['title']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <button class="toggle-button">
        <i class="fas fa-angle-left"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const toggleButton = document.querySelector('.toggle-button');
            const menuItems = document.querySelectorAll('[data-toggle="submenu"]');
            let lastOpenSubmenu = null;

            // بررسی وضعیت قبلی سایدبار
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }

            // دکمه باز/بسته کردن سایدبار
            toggleButton.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });

            // مدیریت زیرمنوها
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                    
                    // بستن منوی قبلی
                    if (lastOpenSubmenu && lastOpenSubmenu !== submenu) {
                        lastOpenSubmenu.classList.remove('active');
                        lastOpenSubmenu.previousElementSibling.classList.remove('expanded');
                    }

                    // باز/بسته کردن منوی جاری
                    submenu.classList.toggle('active');
                    this.classList.toggle('expanded');
                    
                    lastOpenSubmenu = submenu.classList.contains('active') ? submenu : null;
                });
            });
        });
    </script>
</body>
</html>
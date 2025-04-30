<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/jdf.php';

// بررسی لاگین بودن کاربر
redirectIfNotLoggedIn();

// مقادیر پیش‌فرض
$todaySales = 0;
$totalCustomers = 0;
$totalProducts = 0;
$monthlyIncome = 0;
$recentInvoices = [];
$recentNotifications = [];

// دریافت آمار کلی
try {
    $db = new PDO("mysql:host=localhost;dbname=hesabpars;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // آمار فروش امروز
    $stmt = $db->query("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM invoices 
        WHERE DATE(created_at) = CURDATE() 
        AND status = 'completed'
    ");
    $todaySales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // تعداد مشتریان فعال
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM customers 
        WHERE status = 'active'
    ");
    $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // تعداد محصولات موجود
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM products 
        WHERE status = 'active' 
        AND stock > 0
    ");
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // درآمد این ماه
    $stmt = $db->query("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM invoices 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        AND status = 'completed'
    ");
    $monthlyIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // آخرین فاکتورها
    $stmt = $db->query("
        SELECT i.*, c.full_name as customer_name 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        ORDER BY i.created_at DESC 
        LIMIT 5
    ");
    $recentInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // آخرین اعلان‌ها
    $stmt = $db->query("
        SELECT * FROM notifications 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    createAlert('error', 'خطا در دریافت اطلاعات از دیتابیس');
}

// اضافه کردن چند داده نمونه برای تست
if (empty($recentInvoices)) {
    $recentInvoices = [
        [
            'invoice_number' => '1001',
            'customer_name' => 'مشتری نمونه',
            'total_amount' => 1500000,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'completed'
        ]
    ];
}

if (empty($recentNotifications)) {
    $recentNotifications = [
        [
            'title' => 'خوش آمدید',
            'message' => 'به سیستم حسابداری پارسه خوش آمدید',
            'icon' => 'fas fa-bell',
            'color' => '#2196F3',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد - حساب پارسه</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>خوش آمدید، <?php echo $_SESSION['user_full_name'] ?? 'کاربر گرامی'; ?></h1>
                <div class="date-time">
                    <?php echo jdate('l j F Y'); ?> | <span id="live-clock"></span>
                </div>
            </div>
            <div class="header-actions">
                <button onclick="refreshDashboard()" class="refresh-btn">
                    <i class="fas fa-sync-alt"></i>
                    بروزرسانی
                </button>
            </div>
        </div>

        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-icon sales">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($todaySales); ?></h3>
                    <p>فروش امروز (تومان)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon customers">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($totalCustomers); ?></h3>
                    <p>مشتریان فعال</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon products">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($totalProducts); ?></h3>
                    <p>محصولات موجود</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon income">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($monthlyIncome); ?></h3>
                    <p>درآمد این ماه (تومان)</p>
                </div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">نمودار فروش</h3>
                    <div class="chart-actions">
                        <select class="chart-period-select" data-chart="sales">
                            <option value="week">هفتگی</option>
                            <option value="month" selected>ماهانه</option>
                            <option value="year">سالانه</option>
                        </select>
                    </div>
                </div>
                <div id="salesChart"></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">مشتریان جدید</h3>
                    <div class="chart-actions">
                        <select class="chart-period-select" data-chart="customers">
                            <option value="week">هفتگی</option>
                            <option value="month" selected>ماهانه</option>
                            <option value="year">سالانه</option>
                        </select>
                    </div>
                </div>
                <div id="customerChart"></div>
            </div>
        </div>

        <div class="recent-section">
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">آخرین فاکتورها</h3>
                    <a href="invoices.php" class="view-all">مشاهده همه</a>
                </div>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>شماره فاکتور</th>
                            <th>مشتری</th>
                            <th>مبلغ (تومان)</th>
                            <th>تاریخ</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentInvoices as $invoice): ?>
                            <tr>
                                <td>#<?php echo $invoice['invoice_number']; ?></td>
                                <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                <td><?php echo number_format($invoice['total_amount']); ?></td>
                                <td><?php echo jdate('Y/m/d', strtotime($invoice['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $invoice['status']; ?>">
                                        <?php
                                        $statusLabels = [
                                            'completed' => 'تکمیل شده',
                                            'pending' => 'در انتظار',
                                            'cancelled' => 'لغو شده'
                                        ];
                                        echo $statusLabels[$invoice['status']] ?? $invoice['status'];
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">اعلان‌های اخیر</h3>
                </div>
                <div class="notifications-list">
                    <?php foreach ($recentNotifications as $notification): ?>
                        <div class="notification-card">
                            <div class="notification-icon" style="background: <?php echo $notification['color']; ?>">
                                <i class="<?php echo $notification['icon']; ?>"></i>
                            </div>
                            <div class="notification-info">
                                <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <span class="notification-time">
                                    <?php echo jdate('Y/m/d H:i', strtotime($notification['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
     <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fa-IR');
            document.getElementById('live-clock').textContent = timeString;
        }
        
        setInterval(updateClock, 1000);
        updateClock();

        // Refresh Dashboard
        function refreshDashboard() {
            Swal.fire({
                title: 'بروزرسانی داشبورد',
                text: 'در حال بروزرسانی اطلاعات...',
                timer: 1000,
                timerProgressBar: true,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                window.location.reload();
            });
        }
    </script>
</body>
</html>
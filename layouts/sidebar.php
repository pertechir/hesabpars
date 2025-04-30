<?php
if (!isLoggedIn()) {
    redirect('pages/login.php');
}
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h3>حساب پارسه</h3>
    </div>
    <div class="sidebar-user">
        <img src="<?php echo SITE_URL; ?>/assets/images/user.png" alt="User">
        <p>خوش آمدید، <?php echo $_SESSION['user_name']; ?></p>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?php echo SITE_URL; ?>/pages/dashboard.php">
                    <i class="fas fa-home"></i>
                    داشبورد
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/pages/transactions.php">
                    <i class="fas fa-exchange-alt"></i>
                    تراکنش‌ها
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/pages/reports.php">
                    <i class="fas fa-chart-bar"></i>
                    گزارشات
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/pages/profile.php">
                    <i class="fas fa-user"></i>
                    پروفایل
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/pages/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    خروج
                </a>
            </li>
        </ul>
    </nav>
</div>
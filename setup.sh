#!/bin/bash  

# ساخت دایرکتوری‌های اصلی  
mkdir -p assets/css  
mkdir -p assets/js  
mkdir -p assets/images  
mkdir -p includes  
mkdir -p layouts  
mkdir -p pages  

# ایجاد فایل‌ها  

# assets/css  
touch assets/css/style.css  
touch assets/css/dashboard.css  

# assets/js  
touch assets/js/main.js  
touch assets/js/dashboard.js  

# includes  
touch includes/config.php  
touch includes/db.php  
touch includes/functions.php  

# layouts  
touch layouts/header.php  
touch layouts/footer.php  
touch layouts/sidebar.php  

# pages  
touch pages/dashboard.php  
touch pages/login.php  
touch pages/register.php  
touch pages/transactions.php  

# فایل‌ها در ریشه  
touch index.php  
touch README.md  

echo "ساختار پروژه با موفقیت ایجاد شد."  
<?php
// در ابتدای فایل
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        
        // دریافت و تمیز کردن داده‌های ورودی
        $username = clean($_POST['username'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = clean($_POST['full_name'] ?? '');

        // اعتبارسنجی داده‌ها
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            throw new Exception('لطفاً تمام فیلدها را پر کنید.');
        }

        // بررسی نام کاربری
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE username = ?", [$username]);
        if (!$stmt) {
            throw new Exception('خطا در بررسی نام کاربری');
        }
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            throw new Exception('این نام کاربری قبلاً ثبت شده است.');
        }

        // بررسی ایمیل
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE email = ?", [$email]);
        if (!$stmt) {
            throw new Exception('خطا در بررسی ایمیل');
        }
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            throw new Exception('این ایمیل قبلاً ثبت شده است.');
        }

        // رمزنگاری پسورد
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // درج کاربر جدید
        $sql = "INSERT INTO users (username, email, password, full_name, status, created_at) 
                VALUES (?, ?, ?, ?, 'active', NOW())";

        $result = $db->query($sql, [
            $username,
            $email,
            $hashed_password,
            $full_name
        ]);

        if (!$result) {
            throw new Exception('خطا در ثبت اطلاعات');
        }

        $_SESSION['success_message'] = 'ثبت نام با موفقیت انجام شد.';
        header('Location: login.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error in registration: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت نام - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #FF6B6B, #FFE66D);
            padding: 20px;
        }

        .register-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            animation: fadeIn 0.5s ease-out;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header img {
            height: 60px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
            background: #fff;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .input-group i {
            width: 40px;
            text-align: center;
            color: #666;
        }

        .input-group input {
            flex: 1;
            padding: 12px;
            border: none;
            background: none;
            font-size: 1em;
            color: #2c3e50;
        }

        .input-group input:focus {
            outline: none;
        }

        .toggle-password {
            background: none;
            border: none;
            padding: 0 10px;
            color: #666;
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #3498db;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <div class="register-header">
                <a href="../index.php">
                    <img src="../assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
                </a>
                <h1>ثبت نام در <?php echo SITE_NAME; ?></h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="full_name">نام و نام خانوادگی</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="full_name" name="full_name" required
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">نام کاربری</label>
                    <div class="input-group">
                        <i class="fas fa-user-circle"></i>
                        <input type="text" id="username" name="username" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">ایمیل</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">رمز عبور</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    ثبت نام
                    <i class="fas fa-user-plus"></i>
                </button>
            </form>

            <div class="login-link">
                قبلاً ثبت نام کرده‌اید؟ 
                <a href="login.php">ورود به حساب</a>
            </div>
        </div>
    </div>

    <script>
        // نمایش/مخفی کردن رمز عبور
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // اعتبارسنجی فرم
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            let isValid = true;

            // بررسی نام کاربری
            if (username.length < 4) {
                alert('نام کاربری باید حداقل 4 کاراکتر باشد');
                isValid = false;
            }

            // بررسی رمز عبور
            if (password.length < 8) {
                alert('رمز عبور باید حداقل 8 کاراکتر باشد');
                isValid = false;
            } else if (!/[A-Z]/.test(password)) {
                alert('رمز عبور باید حداقل یک حرف بزرگ داشته باشد');
                isValid = false;
            } else if (!/[a-z]/.test(password)) {
                alert('رمز عبور باید حداقل یک حرف کوچک داشته باشد');
                isValid = false;
            } else if (!/[0-9]/.test(password)) {
                alert('رمز عبور باید حداقل یک عدد داشته باشد');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
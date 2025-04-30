<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=hesabpars;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // آماده‌سازی داده‌ها
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');

        // بررسی خالی نبودن فیلدها
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            throw new Exception("لطفاً همه فیلدها را پر کنید.");
        }

        // بررسی ایمیل معتبر
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("لطفاً یک ایمیل معتبر وارد کنید.");
        }

        // رمزنگاری پسورد
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // درج کاربر جدید
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, full_name, status, created_at) 
            VALUES (:username, :email, :password, :full_name, 'active', NOW())
        ");

        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashed_password,
            ':full_name' => $full_name
        ]);

        // پیام موفقیت و ریدایرکت
        $_SESSION['success_message'] = "ثبت نام با موفقیت انجام شد!";
        header("Location: login.php");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // خطای Duplicate entry
            if (strpos($e->getMessage(), 'username')) {
                $error = "این نام کاربری قبلاً ثبت شده است.";
            } elseif (strpos($e->getMessage(), 'email')) {
                $error = "این ایمیل قبلاً ثبت شده است.";
            } else {
                $error = "خطا در ثبت اطلاعات.";
            }
        } else {
            $error = "خطا در ثبت اطلاعات: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت نام در سیستم</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Tahoma, Arial;
            background: linear-gradient(135deg, #4568dc, #b06ab3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #4568dc;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #4568dc, #b06ab3);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(69,104,220,0.3);
        }

        .error {
            background: #ffe6e6;
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .success {
            background: #e6ffe6;
            color: #27ae60;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #4568dc;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ثبت نام در سیستم</h1>

        <?php if (isset($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">نام کاربری:</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="email">ایمیل:</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="full_name">نام و نام خانوادگی:</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="password">رمز عبور:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">ثبت نام</button>
        </form>

        <div class="login-link">
            قبلاً ثبت نام کرده‌اید؟ <a href="login.php">وارد شوید</a>
        </div>
    </div>
</body>
</html>
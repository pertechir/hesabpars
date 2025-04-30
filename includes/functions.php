<?php
// تابع تمیز کردن ورودی‌ها
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// تابع فرمت کردن قیمت
function formatPrice($price) {
    return number_format($price) . ' تومان';
}

// تابع تولید کد تصادفی
function generateRandomCode($length = 8) {
    return substr(str_shuffle(str_repeat($x='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// تابع فرمت کردن تاریخ میلادی به شمسی
function formatDate($date) {
    return jdate('Y/m/d H:i', strtotime($date));
}

// تابع تبدیل وضعیت انگلیسی به فارسی
function getStatusLabel($status) {
    $statusLabels = [
        'active' => 'فعال',
        'inactive' => 'غیرفعال',
        'pending' => 'در انتظار',
        'completed' => 'تکمیل شده',
        'cancelled' => 'لغو شده'
    ];
    return $statusLabels[$status] ?? $status;
}

// تابع ایجاد پیغام اعلان
function createAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// تابع نمایش پیغام اعلان
function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return "<div class='alert alert-{$alert['type']}'>{$alert['message']}</div>";
    }
    return '';
}

// تابع بررسی وجود رکورد در دیتابیس
function recordExists($table, $column, $value, $excludeId = null) {
    global $db;
    $sql = "SELECT COUNT(*) FROM $table WHERE $column = ?";
    $params = [$value];
    
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

// تابع آپلود فایل
function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $fileInfo = pathinfo($file['name']);
    if (!in_array(strtolower($fileInfo['extension']), $allowedTypes)) {
        return false;
    }

    $fileName = uniqid() . '.' . $fileInfo['extension'];
    $filePath = $directory . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return $fileName;
    }

    return false;
}

// تابع تبدیل تاریخ شمسی به میلادی
function jalaliToGregorian($date) {
    $dateParts = explode('/', $date);
    if (count($dateParts) != 3) return false;
    
    $gregorian = jalali_to_gregorian($dateParts[0], $dateParts[1], $dateParts[2]);
    return implode('-', $gregorian);
}

// تابع محاسبه درصد
function calculatePercentage($value, $total) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, 2);
}

// تابع کوتاه کردن متن
function truncateText($text, $length = 100) {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}
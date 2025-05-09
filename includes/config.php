<?php
// Định nghĩa hằng số cho phép truy cập file maintenance.php
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Bắt đầu session nếu chưa bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cấu hình kết nối database sử dụng PDO
// $host = 'localhost';
// $db   = 'mifaguhz_copecutechatbot';
// $user = 'mifaguhz_admin'; // Thay đổi nếu bạn dùng user khác
// $pass = 'Lannhi@1609';
// $charset = 'utf8mb4';

// local
$host = 'localhost';
$db   = 'copecute';
$user = 'root'; // Thay đổi nếu bạn dùng user khác
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Kết nối database thất bại: ' . $e->getMessage());
}

// Thiết lập URL cơ sở cho ứng dụng
$base_url = '';
try {
    // Thử lấy base_url từ cài đặt hệ thống
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'base_url'");
    $base_url = $stmt->fetchColumn();
    if (empty($base_url)) {
        // Nếu không có trong cơ sở dữ liệu, tự động tạo base_url từ thông tin server
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_name = dirname($_SERVER['SCRIPT_NAME']);
        // Loại bỏ phần /includes nếu script được gọi từ thư mục includes
        $script_name = str_replace('/includes', '', $script_name);
        // Loại bỏ dấu / ở cuối nếu có
        $script_name = rtrim($script_name, '/');
        $base_url = $protocol . '://' . $host . $script_name;
        
        // Cập nhật vào cơ sở dữ liệu nếu setting_key 'base_url' đã tồn tại
        $check = $pdo->query("SELECT COUNT(*) FROM system_settings WHERE setting_key = 'base_url'");
        if ($check->fetchColumn() > 0) {
            $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'base_url'")->execute([$base_url]);
        }
    } else {
        // Loại bỏ dấu / ở cuối nếu có
        $base_url = rtrim($base_url, '/');
    }
} catch (PDOException $e) {
    // Nếu có lỗi, tạo base_url mặc định
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_name = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $script_name = str_replace('/includes', '', $script_name);
    $script_name = rtrim($script_name, '/');
    $base_url = $protocol . '://' . $host . $script_name;
}

// Kết nối đến file kiểm tra bảo trì
require_once __DIR__ . '/maintenance.php';

// Kiểm tra chế độ bảo trì
// Nếu hệ thống đang trong chế độ bảo trì, là trang không phải admin và người dùng không phải quản trị viên
// thì hiển thị trang bảo trì
if (isMaintenanceMode($pdo) && !isAdminPage() && !isAdminUser()) {
    showMaintenancePage();
}

// Kiểm tra trạng thái tài khoản nếu đã đăng nhập (thêm mới)
if (isset($_SESSION['user_id'])) {
    // Lấy thông tin user và kiểm tra trạng thái tài khoản
    try {
        $stmt = $pdo->prepare('SELECT is_acctive FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $is_acctive = $user['is_acctive'];
            $redirect = false;
            $message = '';
            
            // Tài khoản chưa kích hoạt
            if ($is_acctive == '0') {
                $redirect = true;
                $message = 'account_inactive';
            } 
            // Tài khoản bị khóa vĩnh viễn
            elseif ($is_acctive == '2') {
                $redirect = true;
                $message = 'account_banned';
            }
            // Tài khoản bị khóa có thời hạn (yyyy-mm-dd)
            elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $is_acctive)) {
                $lock_date = strtotime($is_acctive);
                $today = strtotime(date('Y-m-d'));
                
                if ($today < $lock_date) {
                    // Chưa đến ngày mở khóa
                    $redirect = true;
                    $lock_date_display = date('d/m/Y', $lock_date);
                    $message = 'account_locked&date=' . $lock_date_display;
                } else {
                    // Đã đến ngày mở khóa, cập nhật trạng thái
                    $update_stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                    $update_stmt->execute(['id' => $_SESSION['user_id']]);
                }
            }
            
            // Nếu cần chuyển hướng do tài khoản bị khóa hoặc chưa kích hoạt
            if ($redirect) {
                // Xóa session
                session_unset();
                session_destroy();
                
                // Xác định URL hiện tại
                $current_url = $_SERVER['REQUEST_URI'];
                $login_url = $base_url . '/login.php?error=' . $message;
                
                // Chuyển hướng về trang login
                header('Location: ' . $login_url);
                exit;
            }
        }
    } catch (PDOException $e) {
        // Ghi log lỗi nhưng không làm gián đoạn
        error_log('Lỗi kiểm tra trạng thái tài khoản: ' . $e->getMessage());
    }
}
?> 
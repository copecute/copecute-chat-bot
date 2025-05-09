<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Bắt đầu session nếu chưa bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Thêm các file cần thiết
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(__DIR__) . '/auth/google_config.php';

// Kiểm tra xem đang truy cập từ trang admin không
$is_admin_login = isset($_GET['admin']) && $_GET['admin'] == '1';
// Kiểm tra xem đang truy cập từ trang đăng ký không
$is_register = isset($_GET['register']) && $_GET['register'] == '1';

// Lưu thông tin vào session để sử dụng sau khi callback
$_SESSION['google_login_admin'] = $is_admin_login;
$_SESSION['google_login_register'] = $is_register;

try {
    // Tạo URL đăng nhập Google
    $auth_url = getGoogleAuthUrl();
    
    // Chuyển hướng người dùng đến trang đăng nhập Google
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
} catch (Exception $e) {
    // Xử lý lỗi
    if ($is_admin_login) {
        header('Location: ' . $base_url . '/admin/index.php?error=google_init_error&message=' . urlencode($e->getMessage()));
    } elseif ($is_register) {
        header('Location: ' . $base_url . '/login.php?error=google_init_error&message=' . urlencode($e->getMessage()));
    } else {
        header('Location: ' . $base_url . '/login.php?error=google_init_error&message=' . urlencode($e->getMessage()));
    }
    exit;
}
?> 
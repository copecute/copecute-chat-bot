<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

// Kiểm tra cấp độ người dùng
$user_level = isset($_SESSION['user_level']) ? (int)$_SESSION['user_level'] : 0;

// Nếu không phải quản lý hoặc quản trị viên, chuyển hướng về trang chat
if ($user_level < 1) {
    header('Location: ' . $base_url . '/chat?error=access_denied');
    exit;
}

// Định nghĩa biến kiểm tra quyền quản trị viên (cấp 2)
$is_admin = ($user_level == 2);

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Hiển thị tên vai trò người dùng
if (!function_exists('getUserRoleName')) {
    function getUserRoleName($level) {
        switch ($level) {
            case 2:
                return "Quản trị viên";
            case 1:
                return "Quản lý";
            case 0:
            default:
                return "Người dùng";
        }
    }
}
?> 
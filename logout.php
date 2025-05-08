<?php
session_start();
require_once 'includes/config.php';

// Hủy toàn bộ session
$_SESSION = array();

// Xóa cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Chuyển hướng về trang đăng nhập
header('Location: ' . $base_url . '/login.php');
exit;
?> 
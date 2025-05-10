<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}


// Kết nối đến config
require_once __DIR__ . '/../../includes/config.php';

// Định nghĩa các biến cần thiết
define('API_URL', $base_url . '/api');

// Lấy URL hiện tại để so sánh với menu
$current_url = $_SERVER['REQUEST_URI'];
$current_page = basename($current_url);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CopeCute API Documentation</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Highlight.js -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            position: sticky;
            top: 20px;
        }
        .nav-link {
            color: #495057;
        }
        .nav-link:hover {
            color: #0d6efd;
        }
        .nav-link.active {
            color: #0d6efd;
            font-weight: 600;
        }
        pre {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 1rem;
        }
        code {
            color: #e83e8c;
        }
        .endpoint {
            border-left: 4px solid #0d6efd;
            padding-left: 1rem;
            margin-bottom: 2rem;
        }
        .method {
            font-weight: 600;
        }
        .method.get { color: #0d6efd; }
        .method.post { color: #198754; }
        .method.put { color: #fd7e14; }
        .method.delete { color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_url; ?>/document">
                <i class="fas fa-robot me-2"></i>
                CopeCute API
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>" target="_blank">
                            <i class="fas fa-home me-1"></i> Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mailto:copesocute@gmail.com">
                            <i class="fas fa-envelope me-1"></i> Liên hệ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="sidebar">
                    <div class="list-group">
                        <a href="<?php echo $base_url; ?>/document/index.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'index.php' || $current_page == '') ? 'active' : ''; ?>">
                            <i class="fas fa-home me-2"></i> Tổng quan
                        </a>
                        <a href="<?php echo $base_url; ?>/document/authentication.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'authentication.php') ? 'active' : ''; ?>">
                            <i class="fas fa-key me-2"></i> Xác thực
                        </a>
                        <a href="<?php echo $base_url; ?>/document/chat.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'chat.php') ? 'active' : ''; ?>">
                            <i class="fas fa-comments me-2"></i> Chat API
                        </a>
                        <a href="<?php echo $base_url; ?>/document/profile.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                            <i class="fas fa-user me-2"></i> Profile API
                        </a>
                        <a href="<?php echo $base_url; ?>/document/history.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'history.php') ? 'active' : ''; ?>">
                            <i class="fas fa-history me-2"></i> Lịch sử Chat
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-9">

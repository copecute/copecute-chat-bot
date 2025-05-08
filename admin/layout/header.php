<?php
// Kiểm tra nếu session chưa được khởi động thì khởi động
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy đường dẫn hiện tại
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = dirname($_SERVER['PHP_SELF']);

// Kiểm tra đã đăng nhập chưa (code này chỉ dùng cho các trang không sử dụng check_admin.php)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Lấy đường dẫn gốc
    $root_path = dirname($_SERVER['PHP_SELF'], 2);
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

// Lấy thông tin người dùng đăng nhập từ session
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? '';
$user_level = $_SESSION['user_level'] ?? 0;
$is_admin = ($user_level == 2); // Level 2 là admin
$is_manager = ($user_level == 1); // Level 1 là quản lý

// Hàm lấy tên role người dùng
if (!function_exists('getUserRoleName')) {
    function getUserRoleName($level) {
        switch($level) {
            case 2: return 'Quản trị viên';
            case 1: return 'Quản lý';
            default: return 'Người dùng';
        }
    }
}

// Lấy thông tin cài đặt hệ thống
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE category = 'general'");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Lấy avatar của user
    $stmt = $pdo->prepare("SELECT avatar FROM user_infos WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_avatar = $stmt->fetchColumn();
    if (empty($user_avatar)) {
        $user_avatar = '/assets/img/avatar/default.png';
    }
} catch (PDOException $e) {
    $user_avatar = '/assets/img/avatar/default.png';
}

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    $root_path = dirname($_SERVER['PHP_SELF'], 2);
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo htmlspecialchars($settings['site_name'] ?? 'copecute'); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($settings['favicon_url'] ?? '/assets/img/favicon.ico'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #3a5fd1;
            --secondary-color: #1cc88a;
            --dark-color: #222d3e;
            --light-color: #f8f9fc;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;
            --sidebar-transition: all 0.3s ease;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Nunito', 'Segoe UI', Roboto, Arial, sans-serif;
            overflow-x: hidden;
            display: flex;
            min-height: 100vh;
            color: #5a5c69;
        }
        a {
            text-decoration: none;
        }
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--dark-color) 0%, #1e2a40 100%);
            transition: var(--sidebar-transition);
            z-index: 1000;
            overflow-x: hidden;
            overflow-y: auto;
            position: fixed;
            height: 100vh;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand img {
            max-height: 40px;
            margin-right: 0.75rem;
            transition: var(--sidebar-transition);
        }
        
        .sidebar-brand span {
            opacity: 1;
            transition: var(--sidebar-transition);
            white-space: nowrap;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .sidebar.collapsed .sidebar-brand span,
        .sidebar.collapsed .sidebar-heading,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .sidebar-footer span {
            opacity: 0;
            width: 0;
            display: none;
        }
        
        .sidebar.collapsed .sidebar-brand img {
            margin-right: 0;
            margin-left: 0.5rem;
        }
        
        .sidebar-heading {
            padding: 1rem 1.5rem 0.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05rem;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 700;
            transition: var(--sidebar-transition);
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 0.5rem 1rem;
        }
        
        .sidebar-nav {
            padding-left: 0;
            list-style: none;
        }
        
        .nav-item {
            transition: var(--sidebar-transition);
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            white-space: nowrap;
            transition: var(--sidebar-transition);
            position: relative;
        }
        
        .nav-link i {
            min-width: 1.5rem;
            margin-right: 0.75rem;
            font-size: 0.9rem;
            text-align: center;
            transition: var(--sidebar-transition);
        }
        
        .nav-text {
            transition: var(--sidebar-transition);
        }
        
        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .nav-link.active {
            color: white;
            background-color: rgba(78, 115, 223, 0.2);
            border-left: 3px solid var(--primary-color);
        }
        
        .nav-link.active i {
            color: var(--primary-color);
        }
        
        .nav-link.text-danger:hover {
            background-color: rgba(231, 74, 59, 0.1);
            color: #e57163;
        }
        
        .sidebar.collapsed .nav-link {
            padding: 0.8rem;
            justify-content: center;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.1rem;
        }
        
        /* Dropdown Menus */
        .nav-item.dropdown .dropdown-menu {
            position: absolute;
            left: 100%;
            top: 0;
            display: none;
            min-width: 14rem;
            background-color: var(--dark-color);
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.15);
            border-radius: 0.35rem;
            margin: 0;
        }
        
        .nav-item.dropdown .dropdown-item {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            padding: 0.5rem 1.5rem;
            white-space: nowrap;
        }
        
        .nav-item.dropdown .dropdown-item:hover,
        .nav-item.dropdown .dropdown-item:focus {
            color: white;
            background-color: rgba(78, 115, 223, 0.15);
        }
        
        .nav-item.dropdown .dropdown-item i {
            margin-right: 0.5rem;
            font-size: 0.8rem;
            width: 1rem;
            text-align: center;
        }
        
        .dropdown-toggle::after {
            margin-left: auto;
            transition: transform 0.15s ease;
        }
        
        .dropdown.show .dropdown-toggle::after {
            transform: rotate(90deg);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            transition: var(--sidebar-transition);
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        /* Topbar */
        .topbar {
            height: var(--header-height);
            background-color: white;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .toggle-sidebar {
            cursor: pointer;
            color: var(--dark-color);
            background: transparent;
            border: none;
            font-size: 1.2rem;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .toggle-sidebar:hover {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }
        
        .topbar-divider {
            width: 0;
            border-right: 1px solid #e3e6f0;
            height: calc(var(--header-height) - 2rem);
            margin: auto 1rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.35rem;
            transition: all 0.15s;
        }
        
        .user-profile:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.75rem;
            object-fit: cover;
            border: 3px solid #eaecf4;
        }
        
        .user-info span {
            display: block;
            line-height: 1.2;
        }
        
        .user-name {
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .user-role {
            font-size: 0.8rem;
            color: #858796;
        }
        
        /* Page Content */
        .page-content {
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            background-color: white;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header .card-title {
            margin-bottom: 0;
            font-weight: 700;
            font-size: 1rem;
            color: var(--dark-color);
        }
        
        .card-header-tabs {
            margin-right: -0.625rem;
            margin-bottom: -0.75rem;
            margin-left: -0.625rem;
            border-bottom: 0;
        }
        
        /* Tables */
        .table-responsive {
            border-radius: 0.35rem;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 700;
            color: var(--dark-color);
            white-space: nowrap;
        }
        
        /* Utilities */
        .bg-primary-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        }
        
        .bg-success-gradient {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #169e6c 100%);
        }
        
        .bg-info-gradient {
            background: linear-gradient(135deg, var(--info-color) 0%, #2a94a3 100%);
        }
        
        .bg-warning-gradient {
            background: linear-gradient(135deg, var(--warning-color) 0%, #dda20a 100%);
        }
        
        .bg-danger-gradient {
            background: linear-gradient(135deg, var(--danger-color) 0%, #be271a 100%);
        }
        
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            border-radius: 0.35rem;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background-color: white;
            position: relative;
            overflow: hidden;
            border-left: 0.25rem solid var(--primary-color);
            transition: transform 0.15s ease-in-out;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .dashboard-card h3 {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .dashboard-card .number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
            color: var(--dark-color);
        }
        
        .dashboard-card i.card-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2.5rem;
            opacity: 0.3;
            color: var(--primary-color);
        }
        
        .dashboard-card.border-primary {
            border-left-color: var(--primary-color);
        }
        
        .dashboard-card.border-success {
            border-left-color: var(--secondary-color);
        }
        
        .dashboard-card.border-info {
            border-left-color: var(--info-color);
        }
        
        .dashboard-card.border-warning {
            border-left-color: var(--warning-color);
        }
        
        /* Form Controls */
        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
            }
            
            .sidebar.mobile-shown {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .topbar {
                padding: 0 1rem;
            }
            
            .nav-item.dropdown .dropdown-menu {
                position: static;
                float: none;
                width: auto;
                margin-top: 0;
                background-color: transparent;
                border: 0;
                box-shadow: none;
                color: rgba(255, 255, 255, 0.6);
            }
            
            .nav-item.dropdown .dropdown-menu .dropdown-item {
                padding: 0.5rem 1.5rem 0.5rem 3rem;
            }
            
            .dashboard-card .number {
                font-size: 1.5rem;
            }
            
            .dashboard-card i.card-icon {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?php echo htmlspecialchars($settings['logo_url'] ?? '/assets/img/logo.png'); ?>" alt="Logo">
            <span><?php echo htmlspecialchars($settings['site_name'] ?? 'SimBot'); ?></span>
        </div>
        
        <div class="sidebar-heading">Core</div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="<?php echo $base_url ?>/admin/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span class="nav-text">Tổng quan</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-divider"></div>
        <div class="sidebar-heading">Quản lý</div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="<?php echo $base_url ?>/admin/users" class="nav-link <?php echo strpos($current_dir, '/users') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-fw fa-users"></i>
                    <span class="nav-text">Người dùng</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $base_url ?>/admin/keywords" class="nav-link <?php echo strpos($current_dir, '/keywords') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-fw fa-comment-dots"></i>
                    <span class="nav-text">Từ khóa & Phản hồi</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo $base_url ?>/admin/teachbot" class="nav-link <?php echo strpos($current_dir, '/teachbot') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-fw fa-comment-dots"></i>
                    <span class="nav-text">Dạy Bot</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $base_url ?>/admin/tokens.php" class="nav-link <?php echo $current_page === 'tokens.php' ? 'active' : ''; ?>">
                    <i class="fas fa-fw fa-key"></i>
                    <span class="nav-text">Quản lý Token</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-divider"></div>
        <div class="sidebar-heading">Hệ thống</div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="<?php echo $base_url ?>/admin/settings.php" class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-fw fa-cog"></i>
                    <span class="nav-text">Cài đặt hệ thống</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $base_url ?>/admin/maintenance.php" class="nav-link <?php echo $current_page === 'maintenance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-fw fa-tools"></i>
                    <span class="nav-text">Chế độ Bảo trì</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="?logout=1" class="nav-link text-danger">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span class="nav-text">Đăng xuất</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main content -->
    <div class="main-content" id="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <button id="toggleSidebar" class="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="d-flex align-items-center">
                <div class="user-profile dropdown">
                    <a href="#" class="d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="user-avatar">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                            <span class="user-role"><?php echo getUserRoleName($user_level); ?></span>
                        </div>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_url ?>/profile.php"><i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i> Hồ sơ</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url ?>/admin/settings.php"><i class="fas fa-cogs fa-sm fa-fw me-2 text-gray-400"></i> Cài đặt</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i> Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Page content -->
        <div class="page-content">
            <div class="container-fluid"> 
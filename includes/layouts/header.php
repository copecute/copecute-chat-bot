<?php
// Đảm bảo rằng config đã được tải
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
    require_once __DIR__ . '/../config.php';
}

// Kiểm tra trạng thái đăng nhập
$logged_in = isset($_SESSION['user_id']);
$user_level = isset($_SESSION['user_level']) ? $_SESSION['user_level'] : 0;
$username = $logged_in ? $_SESSION['username'] : '';

// Lấy avatar của người dùng nếu đã đăng nhập
$user_avatar = '/assets/img/avatar/default.png';
if ($logged_in) {
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM user_infos WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $avatar_result = $stmt->fetch();
        if ($avatar_result && !empty($avatar_result['avatar'])) {
            $user_avatar = $avatar_result['avatar'];
        }
    } catch (PDOException $e) {
        // Giữ avatar mặc định nếu có lỗi
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
} catch (PDOException $e) {
}

// Lấy quota cho người dùng đã đăng nhập
$quota = 0;
if ($logged_in) {
    try {
        $stmt = $pdo->prepare("SELECT quota FROM user_tokens WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $result = $stmt->fetch();
        $quota = $result ? $result['quota'] : 0;
        $quota = max(0, $quota); // Đảm bảo quota không âm
    } catch (PDOException $e) {
        $quota = 0;
    }
}

// Xác định trang hiện tại
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Hàm lấy tên vai trò người dùng
if (!function_exists('getUserRoleName')) {
    function getUserRoleName($level) {
        switch ($level) {
            case 2:
                return "Quản trị viên";
            case 1:
                return "Người kiểm duyệt";
            default:
                return "Người dùng";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?> - Chatbot Thông Minh</title>
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_description']); ?>">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($settings['favicon_url']); ?>" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #f6c23e;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            color: #444;
            padding-top: 76px; /* Thêm padding-top để tránh bị navbar đè */
        }
        
        /* Navbar styles */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-left: 10px;
        }
        
        .navbar-brand img {
            height: 36px;
        }
        
        /* Sidebar Styles */
        .sidebar-offcanvas {
            width: var(--sidebar-width);
            height: 100%;
            position: fixed;
            top: 0;
            left: -100%;
            background-color: #fff;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1050;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar-offcanvas.show {
            left: 0;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: var(--primary-color);
            color: white;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .sidebar-header .close-btn {
            color: white;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .sidebar-user {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .sidebar-user .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 3px solid var(--primary-color);
            display: block;
        }
        
        .user-info h5 {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-role {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 30px;
            background-color: #eef2ff;
            color: var(--primary-color);
            font-size: 0.8rem;
            margin-bottom: 10px;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav-item:hover, .sidebar-nav-item.active {
            background-color: #f8f9fc;
            color: var(--primary-color);
            border-left-color: var(--primary-color);
        }
        
        .sidebar-nav-item i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-divider {
            margin: 15px 0;
            border-top: 1px solid #eee;
        }
        
        .sidebar-section-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #888;
            margin: 15px 20px 10px;
            font-weight: 600;
        }
        
        .quota-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 30px;
            background-color: #e8f5e9;
            color: #2e7d32;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .quota-badge i {
            margin-right: 5px;
        }
        
        /* Overlay khi sidebar mở */
        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-backdrop.show {
            opacity: 1;
            visibility: visible;
        }
        
        /* Phần còn lại của CSS như cũ */
        @media (max-width: 991.98px) {
            .navbar .container {
                position: relative;
            }
            
            .navbar-toggler {
                position: relative;
                z-index: 2;
                margin-right: 10px;
            }
            
            .user-dropdown.d-lg-none {
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                z-index: 2;
            }
        }
        
        .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            margin: 0 10px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary-color);
            transition: width 0.3s;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .nav-link.active {
            color: var(--primary-color) !important;
        }
        
        .nav-link.active::after {
            width: 100%;
        }
        
        .nav-btn {
            padding: 8px 20px;
            border-radius: 30px;
            margin-left: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-btn-login {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .nav-btn-login:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .nav-btn-signup {
            background-color: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }
        
        .nav-btn-signup:hover {
            background-color: #3a5dd6;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <!-- <button class="navbar-toggler" type="button" id="sidebarToggler">
                <span class="navbar-toggler-icon"></span>
            </button> -->
            
            <a class="navbar-brand" href="<?php echo $base_url; ?>/">
                <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="<?php echo htmlspecialchars($settings['site_name']); ?>" onerror="this.src='<?php echo $base_url; ?>/assets/img/copecute/cope1.png'; this.style.height='30px';">
                <?php echo htmlspecialchars($settings['site_name']); ?>
            </a>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'features' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/features.php">Tính năng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'about' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/about.php">Giới thiệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'contact' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/contact.php">Liên hệ</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <?php if (!$logged_in): ?>
                    <!-- Nút đăng nhập/đăng ký khi chưa đăng nhập -->
                    <a href="<?php echo $base_url; ?>/login.php" class="btn nav-btn nav-btn-login">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                    <a href="<?php echo $base_url; ?>/login.php" class="btn nav-btn nav-btn-signup">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </a>
                    <?php else: ?>
                    <!-- Nút khi đã đăng nhập -->
                    <a href="<?php echo $base_url; ?>/chat/index.php" class="btn nav-btn nav-btn-signup">
                        <i class="fas fa-comments"></i> Chat ngay
                    </a>
                    
                    <?php if ($user_level >= 1): ?>
                    <!-- Nút quản trị cho admin -->
                    <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="btn nav-btn nav-btn-login ms-2">
                        <i class="fas fa-cog"></i> Quản trị
                    </a>
                    <?php endif; ?>
                    
                    <!-- Avatar trên desktop -->
                    <div class="d-none d-lg-block ms-3">
                        <div style="display: flex; align-items: center; background-color: #f8f9fa; padding: 5px 15px; border-radius: 50px; cursor: pointer;" id="desktopUserAvatar">
                            <img src="<?php echo $user_avatar; ?>" 
                                alt="<?php echo htmlspecialchars($username); ?>" style="border: 2px solid #4e73df; width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                            <div style="margin-left: 10px; display: flex; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($username); ?></div>
                                    <div style="font-size: 0.8rem; color: #666;"><?php echo getUserRoleName($user_level); ?></div>
                                </div>
                                <i class="fas fa-chevron-down ms-3" style="color: #666;"></i>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($logged_in): ?>
            <!-- Avatar trên mobile -->
            <div class="d-block d-lg-none ms-auto">
                <div style="display: flex; align-items: center; background-color: #f8f9fa; padding: 5px 10px; border-radius: 50px; cursor: pointer;" id="mobileUserAvatar">
                    <img src="<?php echo $user_avatar; ?>" 
                         alt="<?php echo htmlspecialchars($username); ?>" style="border: 2px solid #4e73df; width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <div style="margin-left: 8px; margin-right: 5px; font-weight: 600; color: #333; max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($username); ?></div>
                    <i class="fas fa-chevron-down" style="color: #666; font-size: 0.8rem;"></i>
                </div>
            </div>
            <?php else: ?>
            <!-- Nút toggle navbar cho thiết bị di động khi chưa đăng nhập -->
            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php endif; ?>
        </div>
    </nav>
    
    <?php if ($logged_in): ?>
    <!-- Sidebar Offcanvas -->
    <div class="sidebar-offcanvas" id="sidebar">
        <div class="sidebar-header">
            <h4><?php echo htmlspecialchars($settings['site_name']); ?></h4>
            <button class="close-btn" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="sidebar-user">
            <img src="<?php echo $user_avatar; ?>" class="user-avatar" alt="<?php echo htmlspecialchars($username); ?>">
            <div class="user-info">
                <h5><?php echo htmlspecialchars($username); ?></h5>
                <div class="user-role">
                    <i class="fas fa-user-tag"></i> <?php echo getUserRoleName($user_level); ?>
                </div>
                <div class="quota-badge">
                    <i class="fas fa-bolt"></i> Quota: <span id="sidebar_quota"><?php echo $quota; ?></span>
                </div>
            </div>
        </div>
        
        <div class="sidebar-nav">
            <a href="<?php echo $base_url; ?>/" class="sidebar-nav-item <?php echo $current_page == 'index' && dirname($_SERVER['PHP_SELF']) == $base_url ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Trang chủ
            </a>
            
            <a href="<?php echo $base_url; ?>/chat/" class="sidebar-nav-item <?php echo $current_page == 'index' && dirname($_SERVER['PHP_SELF']) == $base_url . '/chat' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> Chat với copecute
            </a>
            
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-title">Tài khoản</div>
            
            <a href="<?php echo $base_url; ?>/profile.php" class="sidebar-nav-item <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Hồ sơ cá nhân
            </a>
            
            <?php if ($user_level >= 1): ?>
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-title">Quản trị</div>
            
            <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="sidebar-nav-item">
                <i class="fas fa-tachometer-alt"></i> Bảng điều khiển
            </a>
            
            <a href="<?php echo $base_url; ?>/admin/settings.php" class="sidebar-nav-item">
                <i class="fas fa-cog"></i> Cài đặt hệ thống
            </a>
            
            <a href="<?php echo $base_url; ?>/admin/tokens.php" class="sidebar-nav-item">
                <i class="fas fa-key"></i> Quản lý token
            </a>
            
            <a href="<?php echo $base_url; ?>/admin/keywords/" class="sidebar-nav-item">
                <i class="fas fa-list"></i> Quản lý từ khóa
            </a>
            <?php endif; ?>
            
            <div class="sidebar-divider"></div>
            
            <a href="<?php echo $base_url; ?>/features.php" class="sidebar-nav-item <?php echo $current_page == 'features' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Tính năng
            </a>
            
            <a href="<?php echo $base_url; ?>/about.php" class="sidebar-nav-item <?php echo $current_page == 'about' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> Giới thiệu
            </a>
            
            <a href="<?php echo $base_url; ?>/contact.php" class="sidebar-nav-item <?php echo $current_page == 'contact' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Liên hệ
            </a>
            
            <div class="sidebar-divider"></div>
            
            <a href="<?php echo $base_url; ?>/logout.php" class="sidebar-nav-item text-danger">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </div>
    
    <!-- Backdrop overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <?php endif; ?>
    
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý mở sidebar
        const sidebarToggler = document.getElementById('sidebarToggler');
        const desktopUserAvatar = document.getElementById('desktopUserAvatar');
        const mobileUserAvatar = document.getElementById('mobileUserAvatar');
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const sidebarClose = document.getElementById('sidebarClose');
        
        function openSidebar() {
            sidebar.classList.add('show');
            sidebarBackdrop.classList.add('show');
            document.body.style.overflow = 'hidden'; // Khóa cuộn trang
        }
        
        function closeSidebar() {
            sidebar.classList.remove('show');
            sidebarBackdrop.classList.remove('show');
            document.body.style.overflow = ''; // Mở lại cuộn trang
        }
        
        if (sidebarToggler) {
            sidebarToggler.addEventListener('click', openSidebar);
        }
        
        if (desktopUserAvatar) {
            desktopUserAvatar.addEventListener('click', openSidebar);
        }
        
        if (mobileUserAvatar) {
            mobileUserAvatar.addEventListener('click', openSidebar);
        }
        
        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeSidebar);
        }
        
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', closeSidebar);
        }
        
        // Đóng sidebar khi nhấn Esc
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                closeSidebar();
            }
        });
    });
</script>
</body>
</html> 
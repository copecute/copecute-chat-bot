<?php
/**
 * Trang kiểm tra chế độ bảo trì
 * File này nên được include ở đầu các trang cần kiểm tra chế độ bảo trì
 */

// Nếu đây là trang admin, cho phép truy cập
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    return;
}

try {
    // Kiểm tra kết nối cơ sở dữ liệu
    if (!isset($pdo)) {
        require_once __DIR__ . '/config.php';
    }
    
    // Kiểm tra xem có trong chế độ bảo trì không
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    $maintenance_mode = $stmt->fetchColumn();
    
    if ($maintenance_mode == '1') {
        // Hiển thị trang bảo trì nếu không phải admin đang đăng nhập
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_level']) || $_SESSION['user_level'] < 1) {
            include __DIR__ . '/maintenance_page.php';
            exit;
        }
    }
} catch (PDOException $e) {
    // Nếu không thể kết nối hoặc kiểm tra, mặc định là không bảo trì
}

// File kiểm tra chế độ bảo trì
if (!defined('ALLOW_ACCESS')) {
    die('Truy cập trực tiếp không được phép!');
}

/**
 * Kiểm tra trạng thái bảo trì từ cơ sở dữ liệu
 * 
 * @param PDO $pdo Kết nối PDO
 * @return bool True nếu hệ thống đang trong chế độ bảo trì
 */
function isMaintenanceMode($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result && isset($result['setting_value']) && $result['setting_value'] == '1');
    } catch (PDOException $e) {
        // Nếu có lỗi, mặc định không bật chế độ bảo trì
        return false;
    }
}

/**
 * Kiểm tra xem URL hiện tại có phải là trang admin hay không
 * 
 * @return bool True nếu URL hiện tại là trang admin
 */
function isAdminPage() {
    $request_uri = $_SERVER['REQUEST_URI'];
    return (strpos($request_uri, '/admin/') !== false);
}

/**
 * Kiểm tra xem người dùng hiện tại có phải là quản trị viên không
 * 
 * @return bool True nếu người dùng hiện tại là quản trị viên
 */
function isAdminUser() {
    return (isset($_SESSION['user_level']) && (int)$_SESSION['user_level'] >= 1);
}

/**
 * Hiển thị trang bảo trì
 */
function showMaintenancePage() {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600'); // 1 giờ
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống đang bảo trì - copecute</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
            font-family: Arial, sans-serif;
        }
        .maintenance-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .logo-container {
            margin-bottom: 30px;
        }
        .logo-container img {
            max-width: 150px;
        }
        .icon-container {
            margin: 30px 0;
            font-size: 60px;
            color: #dc3545;
        }
        h1 {
            color: #343a40;
            margin-bottom: 20px;
        }
        p {
            color: #6c757d;
            font-size: 18px;
            line-height: 1.5;
        }
        .admin-link {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="maintenance-container">
            <div class="logo-container">
                <img src="/logo.png" alt="copecute Logo">
            </div>
            
            <div class="icon-container">
                <i class="fas fa-tools"></i>
            </div>
            
            <h1>Hệ thống đang bảo trì</h1>
            
            <p>
                Chúng tôi đang thực hiện một số bảo trì cần thiết.
                <br>Vui lòng quay lại sau!
            </p>
            
            <div class="admin-link">
                <a href="/admin/index.php" class="btn btn-primary">Đăng nhập quản trị</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    exit;
}
?> 
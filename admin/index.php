<?php
session_start();
require_once '../includes/config.php';

// Nếu người dùng đã đăng nhập, chuyển hướng đến dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . $base_url . '/admin/dashboard.php');
    exit;
}

$error = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
    } else {
        try {
            // Kiểm tra tài khoản quản trị và quản lý
            $stmt = $pdo->prepare('SELECT id, username, password, level, is_acctive FROM users WHERE username = :username AND level >= 1'); // Quản lý (level 1) hoặc Admin (level 2)
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Kiểm tra tài khoản có bị khóa không
                if ($admin['is_acctive'] === '0') {
                    $error = 'Tài khoản của bạn chưa được kích hoạt. Vui lòng liên hệ đội ngũ hỗ trợ!';
                } elseif ($admin['is_acctive'] === '2') {
                    $error = 'Tài khoản của bạn đã bị khóa vĩnh viễn. Vui lòng liên hệ đội ngũ hỗ trợ!';
                } elseif ($admin['is_acctive'] !== '1') {
                    // Kiểm tra xem có phải bị khóa đến ngày cụ thể không
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $admin['is_acctive'])) {
                        // Kiểm tra xem đã đến ngày mở khóa chưa
                        $lock_date = strtotime($admin['is_acctive']);
                        $today = strtotime(date('Y-m-d'));
                        
                        if ($today >= $lock_date) {
                            // Đã đến hoặc qua ngày mở khóa, cập nhật trạng thái tài khoản
                            try {
                                $update_stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                                $update_stmt->execute(['id' => $admin['id']]);
                                
                                // Cập nhật thời gian đăng nhập cuối
                                $update_login_time = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
                                $update_login_time->execute(['id' => $admin['id']]);
                                
                                // Đăng nhập thành công sau khi mở khóa
                                $_SESSION['user_id'] = $admin['id']; // Session chính để nhận diện đăng nhập
                                $_SESSION['admin_id'] = $admin['id']; // Giữ lại cho tương thích
                                $_SESSION['username'] = $admin['username'];
                                $_SESSION['admin_username'] = $admin['username']; // Giữ lại cho tương thích
                                $_SESSION['user_level'] = $admin['level']; // Lưu level tương ứng của user
                                
                                header('Location: ' . $base_url . '/admin/dashboard.php');
                                exit;
                            } catch (PDOException $e) {
                                $error = 'Lỗi hệ thống khi mở khóa tài khoản: ' . $e->getMessage();
                            }
                        } else {
                            // Chưa đến ngày mở khóa
                            $lock_date_display = date('d/m/Y', $lock_date);
                            $error = "Tài khoản của bạn đã bị khóa đến ngày {$lock_date_display}. Vui lòng liên hệ đội ngũ hỗ trợ!";
                        }
                    } else {
                        $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ đội ngũ hỗ trợ!';
                    }
                } else {
                    // Cập nhật thời gian đăng nhập cuối
                    $update_login_time = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
                    $update_login_time->execute(['id' => $admin['id']]);
                    
                    // Đăng nhập thành công
                    $_SESSION['user_id'] = $admin['id']; // Session chính để nhận diện đăng nhập
                    $_SESSION['admin_id'] = $admin['id']; // Giữ lại cho tương thích
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['admin_username'] = $admin['username']; // Giữ lại cho tương thích
                    $_SESSION['user_level'] = $admin['level']; // Lưu level tương ứng của user
                    
                    header('Location: ' . $base_url . '/admin/dashboard.php');
                    exit;
                }
            } else {
                $error = 'Sai tên đăng nhập hoặc mật khẩu!';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản trị copecute</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container img {
            max-width: 150px;
        }
        /* Thêm style cho nút đăng nhập Google */
        .btn-danger {
            background-color: #db4437;
            border-color: #db4437;
        }
        .btn-danger:hover {
            background-color: #c53727;
            border-color: #c53727;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo-container">
                <img src="../logo.png" alt="Logo">
            </div>
            <h2 class="text-center mb-4">Đăng Nhập Quản Trị</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php
            // Xử lý các lỗi từ đăng nhập Google
            if (isset($_GET['error'])) {
                $error_type = $_GET['error'];
                $error_message = '';
                
                switch ($error_type) {
                    case 'account_inactive':
                        $error_message = 'Tài khoản của bạn chưa được kích hoạt. Vui lòng liên hệ đội ngũ hỗ trợ!';
                        break;
                    case 'account_banned':
                        $error_message = 'Tài khoản của bạn đã bị khóa vĩnh viễn. Vui lòng liên hệ đội ngũ hỗ trợ!';
                        break;
                    case 'account_locked':
                        if (isset($_GET['date'])) {
                            $error_message = "Tài khoản của bạn đã bị khóa đến ngày {$_GET['date']}. Vui lòng liên hệ đội ngũ hỗ trợ!";
                        } else {
                            $error_message = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ đội ngũ hỗ trợ!';
                        }
                        break;
                    case 'not_admin':
                        $error_message = 'Tài khoản của bạn không có quyền truy cập trang quản trị.';
                        break;
                    case 'google_auth_failed':
                        $error_message = 'Đăng nhập Google thất bại. Vui lòng thử lại!';
                        break;
                    case 'google_token_failed':
                        $error_message = 'Không thể lấy thông tin từ Google. Vui lòng thử lại!';
                        break;
                    case 'google_error':
                    case 'google_init_error':
                        $error_message = 'Lỗi khi kết nối với Google. Vui lòng thử lại sau!';
                        break;
                    case 'db_error':
                        $error_message = 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau!';
                        break;
                    default:
                        $error_message = 'Đã xảy ra lỗi không xác định. Vui lòng thử lại!';
                }
                
                if (!empty($error_message)) {
                    echo '<div class="alert alert-danger">' . $error_message . '</div>';
                }
            }
            ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Đăng Nhập</button>
                    <a href="<?php echo $base_url; ?>/includes/auth/google_login.php?admin=1" class="btn btn-danger">
                        <i class="fab fa-google"></i> Đăng nhập bằng Google
                    </a>
                </div>
                <p class="mt-3 text-center small text-muted">Chỉ quản lý và quản trị viên mới có quyền truy cập</p>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

session_start();
require_once 'includes/config.php';

$error = '';

// Kiểm tra nếu người dùng đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/chat');
    exit;
}

// Xử lý lỗi từ URL
if (isset($_GET['error']) && $_GET['error'] === 'user_deleted') {
    $error = 'Tài khoản của bạn không tồn tại hoặc đã bị xóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.';
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
    } else {
        try {
            // Kiểm tra username
            $stmt = $pdo->prepare('SELECT id, username, password, level, is_acctive FROM users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Kiểm tra tài khoản có bị khóa hay chưa kích hoạt không
                if ($user['is_acctive'] === '0') {
                    $error = 'Tài khoản của bạn chưa được kích hoạt. Vui lòng liên hệ quản trị viên để kích hoạt tài khoản!';
                } elseif ($user['is_acctive'] === '2') {
                    $error = 'Tài khoản của bạn đã bị khóa vĩnh viễn. Vui lòng liên hệ quản trị viên để được hỗ trợ!';
                } elseif ($user['is_acctive'] !== '1') {
                    // Kiểm tra xem có phải bị khóa đến ngày cụ thể không
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $user['is_acctive'])) {
                        // Kiểm tra xem đã đến ngày mở khóa chưa
                        $lock_date = strtotime($user['is_acctive']);
                        $today = strtotime(date('Y-m-d'));
                        
                        if ($today >= $lock_date) {
                            // Đã đến hoặc qua ngày mở khóa, cập nhật trạng thái tài khoản
                            try {
                                $update_stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                                $update_stmt->execute(['id' => $user['id']]);
                                
                                // Cập nhật thời gian đăng nhập cuối
                                $update_login_time = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
                                $update_login_time->execute(['id' => $user['id']]);
                                
                                // Đăng nhập thành công sau khi mở khóa
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['user_level'] = $user['level'];
                                
                                header('Location: ' . $base_url . '/chat');
                                exit;
                            } catch (PDOException $e) {
                                $error = 'Lỗi hệ thống khi mở khóa tài khoản: ' . $e->getMessage();
                            }
                        } else {
                            // Chưa đến ngày mở khóa
                            $lock_date_display = date('d/m/Y', $lock_date);
                            $error = "Tài khoản của bạn đã bị khóa đến ngày {$lock_date_display}. Vui lòng liên hệ quản trị viên để được hỗ trợ!";
                        }
                    } else {
                        $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ!';
                    }
                } else {
                    // Cập nhật thời gian đăng nhập cuối
                    $update_login_time = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
                    $update_login_time->execute(['id' => $user['id']]);
                    
                    // Đăng nhập thành công
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_level'] = $user['level'];
                    
                    header('Location: ' . $base_url . '/chat');
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

// Include header
require_once 'includes/layouts/header.php';
?>

<div class="container">
    <div class="login-container mt-5">
        <div class="logo-container">
            <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="Logo">
        </div>
        <h2 class="text-center mb-4">Đăng Nhập</h2>
        
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
                    $error_message = 'Tài khoản của bạn chưa được kích hoạt. Vui lòng liên hệ quản trị viên để kích hoạt tài khoản!';
                    break;
                case 'account_banned':
                    $error_message = 'Tài khoản của bạn đã bị khóa vĩnh viễn. Vui lòng liên hệ quản trị viên để được hỗ trợ!';
                    break;
                case 'account_locked':
                    if (isset($_GET['date'])) {
                        $error_message = "Tài khoản của bạn đã bị khóa đến ngày {$_GET['date']}. Vui lòng liên hệ quản trị viên để được hỗ trợ!";
                    } else {
                        $error_message = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ!';
                    }
                    break;
                case 'google_auth_failed':
                    $error_message = 'Đăng nhập Google thất bại. Vui lòng thử lại!';
                    if (isset($_GET['reason'])) {
                        $error_message .= ' (Lý do: ' . htmlspecialchars($_GET['reason']) . ')';
                    }
                    break;
                case 'google_token_failed':
                    $error_message = 'Không thể lấy thông tin từ Google. Vui lòng thử lại!';
                    if (isset($_GET['reason'])) {
                        $error_message .= ' (Lý do: ' . htmlspecialchars($_GET['reason']) . ')';
                    }
                    break;
                case 'userinfo_failed':
                    $error_message = 'Không thể lấy thông tin người dùng từ Google. Vui lòng thử lại sau!';
                    break;
                case 'state_mismatch':
                    $error_message = 'Lỗi bảo mật khi đăng nhập bằng Google. Vui lòng thử lại!';
                    break;
                case 'account_creation_error':
                    $error_message = 'Lỗi khi tạo tài khoản mới. Vui lòng thử lại hoặc sử dụng đăng nhập thông thường!';
                    break;
                case 'google_error':
                case 'google_init_error':
                    $error_message = 'Lỗi khi kết nối với Google. Vui lòng thử lại sau!';
                    break;
                case 'db_error':
                    $error_message = 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau!';
                    if (isset($_GET['message']) && strlen($_GET['message']) > 0) {
                        error_log('DB Error: ' . $_GET['message']);
                        
                        // Hiển thị lỗi chi tiết cho admin (nếu cần)
                        // $error_message .= '<br><small>Chi tiết: ' . htmlspecialchars($_GET['message']) . '</small>';
                    }
                    break;
                case 'session_error':
                    $error_message = 'Lỗi khi xử lý phiên đăng nhập. Vui lòng thử lại!';
                    break;
                default:
                    $error_message = 'Đã xảy ra lỗi không xác định. Vui lòng thử lại!';
                    if (isset($_GET['message'])) {
                        error_log('Unknown error: ' . $_GET['message']);
                    }
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
                <a href="<?php echo $base_url; ?>/includes/auth/google_login.php" class="btn btn-danger">
                    <i class="fab fa-google"></i> Đăng nhập bằng Google
                </a>
            </div>
            <p class="mt-3 text-center">Chưa có tài khoản? <a href="<?php echo $base_url; ?>/register.php">Đăng ký ngay</a></p>
        </form>
    </div>
</div>

<?php
// Thêm Font Awesome cho icon Google
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
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

<?php
// Include footer
require_once 'includes/layouts/footer.php';
?> 
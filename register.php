<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

session_start();
require_once 'includes/config.php';

$error = '';
$success = '';

// Kiểm tra nếu người dùng đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/chat');
    exit;
}

// Kiểm tra xem đăng ký có được cho phép hay không
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'allow_registration'");
    $allow_registration = $stmt->fetchColumn();
    
    if (!$allow_registration) {
        $error = 'Đăng ký tài khoản mới đã bị tạm khóa. Vui lòng liên hệ quản trị viên!';
    }
} catch (PDOException $e) {
    // Nếu không tìm thấy cài đặt, mặc định cho phép đăng ký
    $allow_registration = true;
}

// Xử lý đăng ký sau khi xác thực Google thành công
if (isset($_SESSION['google_info']) && $allow_registration) {
    $google_info = $_SESSION['google_info'];
    $email = $google_info['email'] ?? '';
    $name = $google_info['name'] ?? '';
    $google_id = $google_info['sub'] ?? '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Kiểm tra đầu vào
        if (empty($username) || empty($password) || empty($confirm_password)) {
            $error = 'Vui lòng điền đầy đủ thông tin!';
        } elseif ($password !== $confirm_password) {
            $error = 'Mật khẩu nhập lại không khớp!';
        } elseif (strlen($password) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
        } else {
            try {
                // Kiểm tra tên đăng nhập đã tồn tại chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
                $stmt->execute(['username' => $username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác!';
                } else {
                    // Kiểm tra email đã tồn tại chưa
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
                    $stmt->execute(['email' => $email]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Email Google đã tồn tại trong hệ thống, vui lòng đăng nhập qua Google!';
                    } else {
                        // Lấy quota mặc định từ cài đặt
                        $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'default_quota'");
                        $default_quota = $stmt->fetchColumn() ?: 100; // Mặc định 100 nếu không tìm thấy
                        
                        // Mã hóa mật khẩu
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Thêm người dùng vào cơ sở dữ liệu
                        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, level, google_id, last_login) VALUES (:username, :email, :password, :level, :google_id, NOW())');
                        $stmt->execute([
                            'username' => $username,
                            'email' => $email,
                            'password' => $password_hash,
                            'level' => 0, // Mặc định là người dùng thường
                            'google_id' => $google_id
                        ]);
                        
                        // Lấy id của người dùng vừa đăng ký
                        $user_id = $pdo->lastInsertId();
                        
                        // Tạo profile cơ bản cho người dùng
                        $stmt = $pdo->prepare('INSERT INTO user_infos (user_id, full_name, avatar) VALUES (:user_id, :full_name, :avatar)');
                        $stmt->execute([
                            'user_id' => $user_id, 
                            'full_name' => $name,
                            'avatar' => $google_info['picture'] ?? ''
                        ]);
                        
                        // Tạo token cho người dùng
                        $token = bin2hex(random_bytes(32));
                        $stmt = $pdo->prepare('INSERT INTO user_tokens (user_id, token, quota) VALUES (:user_id, :token, :quota)');
                        $stmt->execute([
                            'user_id' => $user_id,
                            'token' => $token,
                            'quota' => $default_quota
                        ]);
                        
                        // Xóa thông tin Google tạm thời
                        unset($_SESSION['google_info']);
                        
                        // Đăng nhập người dùng
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['user_level'] = 0;
                        
                        header('Location: ' . $base_url . '/chat');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}

// Include header
require_once 'includes/layouts/header.php';
?>

<div class="container">
    <div class="register-container mt-5">
        <div class="logo-container">
            <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="Logo">
        </div>
        <h2 class="text-center mb-4">Đăng Ký Tài Khoản</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['google_info'])): ?>
            <!-- Hiển thị form đăng ký sau khi xác thực Google -->
            <div class="alert alert-success">
                <p><strong>Xác thực Google thành công!</strong></p>
                <p>Email: <?php echo htmlspecialchars($_SESSION['google_info']['email'] ?? ''); ?></p>
                <p>Tên: <?php echo htmlspecialchars($_SESSION['google_info']['name'] ?? ''); ?></p>
            </div>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Hoàn tất đăng ký</button>
                </div>
            </form>
        <?php else: ?>
            <!-- Hiển thị nút xác thực Google -->
            <div class="alert alert-info">
                Để đăng ký tài khoản, bạn cần xác thực qua Google trước.
            </div>
            
            <div class="d-grid gap-2">
                <a href="<?php echo $base_url; ?>/includes/auth/google_login.php?register=1" class="btn btn-danger">
                    <i class="fab fa-google"></i> Xác thực qua Google để đăng ký
                </a>
            </div>
        <?php endif; ?>
        
        <p class="mt-3 text-center">Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .register-container {
        max-width: 500px;
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
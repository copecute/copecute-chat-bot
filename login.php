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

// Kiểm tra xem đăng ký có được cho phép hay không (cho trường hợp đăng ký mới qua Google)
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'allow_registration'");
    $allow_registration = $stmt->fetchColumn();
    
    if (!$allow_registration && isset($_SESSION['google_info'])) {
        $error = 'Đăng ký tài khoản mới đã bị tạm khóa. Vui lòng liên hệ quản trị viên!';
        unset($_SESSION['google_info']); // Xóa dữ liệu Google nếu không được phép đăng ký
    }
} catch (PDOException $e) {
    // Nếu không tìm thấy cài đặt, mặc định cho phép đăng ký
    $allow_registration = true;
}

// Xử lý lỗi từ URL
if (isset($_GET['error']) && $_GET['error'] === 'user_deleted') {
    $error = 'Tài khoản của bạn không tồn tại hoặc đã bị xóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.';
}

// Xử lý đăng xuất và hủy đăng ký
if (isset($_GET['logout']) && $_GET['logout'] == 'google' && isset($_SESSION['google_info'])) {
    // Xóa thông tin Google trong session
    unset($_SESSION['google_info']);
    header('Location: ' . $base_url . '/login.php?message=cancel_register');
    exit;
}

// Hiển thị thông báo
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'cancel_register':
            $message = 'Bạn đã hủy quá trình đăng ký tài khoản.';
            break;
    }
}

// Xử lý đăng ký sau khi xác thực Google thành công
if (isset($_SESSION['google_info']) && $allow_registration && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $google_info = $_SESSION['google_info'];
    $email = $google_info['email'] ?? '';
    $name = $google_info['name'] ?? '';
    $google_id = $google_info['sub'] ?? '';
    
    // Tạo username từ email (lấy phần trước @)
    $username = strtolower(explode('@', $email)[0]);
    // Loại bỏ các ký tự không phải chữ cái, số, gạch dưới
    $username = preg_replace('/[^a-z0-9_]/i', '', $username);
    // Đảm bảo username không rỗng
    if (empty($username)) {
        $username = 'user' . rand(1000, 9999);
    }
    
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Kiểm tra đầu vào
    if (empty($password) || empty($confirm_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin mật khẩu!';
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
                // Nếu username đã tồn tại, thêm số ngẫu nhiên vào cuối
                $counter = 1;
                $original_username = $username;
                while (true) {
                    $username = $original_username . $counter;
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
                    $stmt->execute(['username' => $username]);
                    if ($stmt->fetchColumn() == 0) {
                        break;
                    }
                    $counter++;
                }
            }
            
            // Kiểm tra email đã tồn tại chưa (không cần thiết vì đã kiểm tra trong luồng Google login)
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
                
                $success = 'Tài khoản đã được tạo thành công với tên đăng nhập: ' . $username;
                
                // Chuyển hướng sau 2 giây
                header('refresh:2;url=' . $base_url . '/chat');
                $success .= '<br>Bạn sẽ được chuyển hướng đến trang chat sau 2 giây...';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
// Xử lý đăng nhập
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_SESSION['google_info']))) {
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
        
        <?php if (isset($_SESSION['google_info'])): ?>
            <!-- Hiển thị form đăng ký khi có thông tin Google nhưng chưa có tài khoản -->
            <h2 class="text-center mb-4">Tạo Tài Khoản Mới</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="alert alert-success">
                <p><strong>Xác thực Google thành công!</strong></p>
                <p>Email: <?php echo htmlspecialchars($_SESSION['google_info']['email'] ?? ''); ?></p>
                <p>Tên: <?php echo htmlspecialchars($_SESSION['google_info']['name'] ?? ''); ?></p>
                <?php
                    // Tạo username từ email và hiển thị cho người dùng
                    $email = $_SESSION['google_info']['email'] ?? '';
                    $username = strtolower(explode('@', $email)[0]);
                    $username = preg_replace('/[^a-z0-9_]/i', '', $username);
                    if (empty($username)) {
                        $username = 'user' . rand(1000, 9999);
                    }
                    
                    // Kiểm tra xem username đã tồn tại chưa
                    try {
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
                        $stmt->execute(['username' => $username]);
                        if ($stmt->fetchColumn() > 0) {
                            // Nếu username đã tồn tại, thêm số vào cuối
                            $counter = 1;
                            $original_username = $username;
                            while (true) {
                                $username = $original_username . $counter;
                                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
                                $stmt->execute(['username' => $username]);
                                if ($stmt->fetchColumn() == 0 || $counter > 100) {
                                    break;
                                }
                                $counter++;
                            }
                        }
                    } catch (PDOException $e) {
                        // Bỏ qua lỗi, không hiển thị
                    }
                ?>
                <p>Tên đăng nhập: <strong><?php echo htmlspecialchars($username); ?></strong></p>
                <p>Vui lòng tạo mật khẩu để hoàn tất đăng ký.</p>
            </div>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
                    <a href="<?php echo $base_url; ?>/login.php?logout=google" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Hủy đăng ký
                    </a>
                </div>
            </form>
        <?php else: ?>
            <!-- Hiển thị form đăng nhập bình thường -->
            <h2 class="text-center mb-4">Đăng Nhập</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
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
                            // error_log('DB Error: ' . $_GET['message']);
                            
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
                            // error_log('Unknown error: ' . $_GET['message']);
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
            </form>
        <?php endif; ?>
    </div>
</div>

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
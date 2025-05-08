<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Bắt đầu session nếu chưa bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Thêm các file cần thiết
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(__DIR__) . '/auth/google_config.php';

// Kiểm tra xem đang đăng ký hay đăng nhập
$is_admin_login = isset($_SESSION['google_login_admin']) && $_SESSION['google_login_admin'] === true;
$is_register = isset($_SESSION['google_login_register']) && $_SESSION['google_login_register'] === true;

// Nếu có lỗi
if (isset($_GET['error'])) {
    if ($is_admin_login) {
        header('Location: ' . $base_url . '/admin/index.php?error=google_auth_failed&reason=' . urlencode($_GET['error']));
    } elseif ($is_register) {
        header('Location: ' . $base_url . '/register.php?error=google_auth_failed&reason=' . urlencode($_GET['error']));
    } else {
        header('Location: ' . $base_url . '/login.php?error=google_auth_failed&reason=' . urlencode($_GET['error']));
    }
    exit;
}

// Nếu không có code, chuyển hướng về trang đăng nhập
if (!isset($_GET['code'])) {
    if ($is_admin_login) {
        header('Location: ' . $base_url . '/admin/index.php');
    } elseif ($is_register) {
        header('Location: ' . $base_url . '/register.php');
    } else {
        header('Location: ' . $base_url . '/login.php');
    }
    exit;
}

// Kiểm tra state để đảm bảo bảo mật
if (!isset($_GET['state']) || !isset($_SESSION['google_auth_state']) || $_GET['state'] !== $_SESSION['google_auth_state']) {
    if ($is_admin_login) {
        header('Location: ' . $base_url . '/admin/index.php?error=state_mismatch');
    } elseif ($is_register) {
        header('Location: ' . $base_url . '/register.php?error=state_mismatch');
    } else {
        header('Location: ' . $base_url . '/login.php?error=state_mismatch');
    }
    exit;
}

try {
    // Lấy token từ code
    $token_data = getGoogleToken($_GET['code']);
    
    // Nếu không lấy được token
    if (!$token_data || isset($token_data['error'])) {
        $error_reason = isset($token_data['error']) ? $token_data['error'] : 'unknown_error';
        
        // Log lỗi để debug
        error_log('Google Token Error: ' . print_r($token_data, true));
        
        if ($is_admin_login) {
            header('Location: ' . $base_url . '/admin/index.php?error=google_token_failed&reason=' . urlencode($error_reason));
        } elseif ($is_register) {
            header('Location: ' . $base_url . '/register.php?error=google_token_failed&reason=' . urlencode($error_reason));
        } else {
            header('Location: ' . $base_url . '/login.php?error=google_token_failed&reason=' . urlencode($error_reason));
        }
        exit;
    }
    
    // Lấy thông tin người dùng từ token
    $access_token = $token_data['access_token'];
    $user_info = getGoogleUserInfo($access_token);
    
    // Nếu không lấy được thông tin người dùng
    if (!$user_info) {
        // Log lỗi để debug
        error_log('Google User Info Error: Failed to get user info with token ' . substr($access_token, 0, 10) . '...');
        
        if ($is_admin_login) {
            header('Location: ' . $base_url . '/admin/index.php?error=userinfo_failed');
        } elseif ($is_register) {
            header('Location: ' . $base_url . '/register.php?error=userinfo_failed');
        } else {
            header('Location: ' . $base_url . '/login.php?error=userinfo_failed');
        }
        exit;
    }
    
    // Log thông tin người dùng (để debug)
    error_log('Google User Info: ' . print_r($user_info, true));
    
    // Lấy thông tin từ user_info
    $email = $user_info['email'] ?? '';
    $name = $user_info['name'] ?? '';
    $google_id = $user_info['sub'] ?? ''; // sub là ID trong Google
    $picture = $user_info['picture'] ?? '';
    
    // Nếu là quá trình đăng ký
    if ($is_register) {
        // Lưu thông tin Google vào session để sử dụng trong quá trình đăng ký
        $_SESSION['google_info'] = $user_info;
        
        // Chuyển hướng về trang đăng ký
        header('Location: ' . $base_url . '/register.php');
        exit;
    }
    
    // Kiểm tra xem email hoặc google_id đã tồn tại trong cơ sở dữ liệu chưa
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email OR google_id = :google_id');
    $stmt->execute(['email' => $email, 'google_id' => $google_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Tài khoản đã tồn tại
        
        // Kiểm tra tài khoản có bị khóa hay không
        if ($user['is_acctive'] === '0') {
            if ($is_admin_login) {
                header('Location: ' . $base_url . '/admin/index.php?error=account_inactive');
            } else {
                header('Location: ' . $base_url . '/login.php?error=account_inactive');
            }
            exit;
        } elseif ($user['is_acctive'] === '2') {
            if ($is_admin_login) {
                header('Location: ' . $base_url . '/admin/index.php?error=account_banned');
            } else {
                header('Location: ' . $base_url . '/login.php?error=account_banned');
            }
            exit;
        } elseif ($user['is_acctive'] !== '1') {
            // Kiểm tra xem có phải bị khóa đến ngày cụ thể không
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $user['is_acctive'])) {
                // Kiểm tra xem đã đến ngày mở khóa chưa
                $lock_date = strtotime($user['is_acctive']);
                $today = strtotime(date('Y-m-d'));
                
                if ($today >= $lock_date) {
                    // Đã đến hoặc qua ngày mở khóa, cập nhật trạng thái tài khoản
                    $update_stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                    $update_stmt->execute(['id' => $user['id']]);
                } else {
                    // Chưa đến ngày mở khóa
                    $lock_date_display = date('d/m/Y', $lock_date);
                    
                    if ($is_admin_login) {
                        header('Location: ' . $base_url . '/admin/index.php?error=account_locked&date=' . $lock_date_display);
                    } else {
                        header('Location: ' . $base_url . '/login.php?error=account_locked&date=' . $lock_date_display);
                    }
                    exit;
                }
            } else {
                if ($is_admin_login) {
                    header('Location: ' . $base_url . '/admin/index.php?error=account_locked');
                } else {
                    header('Location: ' . $base_url . '/login.php?error=account_locked');
                }
                exit;
            }
        }
        
        // Nếu tài khoản chưa liên kết với Google
        if (empty($user['google_id'])) {
            try {
                // Cập nhật Google ID cho tài khoản
                error_log('Cập nhật Google ID cho tài khoản ' . $user['id']);
                $stmt = $pdo->prepare('UPDATE users SET google_id = :google_id, updated_at = NOW() WHERE id = :id');
                $stmt->execute(['google_id' => $google_id, 'id' => $user['id']]);
            } catch (PDOException $e) {
                error_log('Lỗi khi cập nhật Google ID: ' . $e->getMessage());
                // Tiếp tục mà không dừng lại vì lỗi này không quan trọng
            }
        }
        
        try {
            // Đăng nhập thành công
            error_log('Đăng nhập thành công với user_id=' . $user['id']);
            
            // Cập nhật thời gian đăng nhập cuối
            $update_login_time = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
            $update_login_time->execute(['id' => $user['id']]);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_level'] = $user['level'];
            
            // Nếu là admin hoặc quản lý
            if ($user['level'] >= 1) {
                $_SESSION['admin_id'] = $user['id']; // Cho tương thích
                $_SESSION['admin_username'] = $user['username']; // Cho tương thích
                
                header('Location: ' . $base_url . '/admin/dashboard.php');
                exit;
            } else {
                // Nếu đăng nhập từ trang admin nhưng không phải admin/quản lý
                if ($is_admin_login) {
                    header('Location: ' . $base_url . '/admin/index.php?error=not_admin');
                    exit;
                }
                
                // Người dùng thông thường
                header('Location: ' . $base_url . '/chat');
                exit;
            }
        } catch (Exception $e) {
            error_log('Lỗi khi xử lý đăng nhập: ' . $e->getMessage());
            
            if ($is_admin_login) {
                header('Location: ' . $base_url . '/admin/index.php?error=session_error&message=' . urlencode($e->getMessage()));
            } else {
                header('Location: ' . $base_url . '/login.php?error=session_error&message=' . urlencode($e->getMessage()));
            }
            exit;
        }
        
    } else {
        // Tạo tài khoản mới từ thông tin Google
        try {
            // Log cho quá trình tạo tài khoản
            error_log('Tạo tài khoản mới với email: ' . $email);
            
            // Tạo username từ email
            $username_base = strtolower(explode('@', $email)[0]);
            // Loại bỏ các ký tự không phải chữ cái, số, gạch dưới
            $username_base = preg_replace('/[^a-z0-9_]/i', '', $username_base);
            // Đảm bảo username không rỗng
            if (empty($username_base)) {
                $username_base = 'user';
            }
            $username = $username_base;
            $counter = 1;
            
            // Kiểm tra xem username đã tồn tại chưa
            while (true) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
                $stmt->execute(['username' => $username]);
                if ($stmt->fetchColumn() == 0) {
                    break;
                }
                $username = $username_base . $counter;
                $counter++;
            }
            
            // Mật khẩu ngẫu nhiên cho tài khoản (người dùng có thể đổi sau)
            $password = bin2hex(random_bytes(8));
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Bắt đầu transaction
            $pdo->beginTransaction();
            $transaction_active = true;
            
            try {
                // Thêm người dùng mới
                $stmt = $pdo->prepare('
                    INSERT INTO users (username, email, password, google_id, level, is_acctive, created_at, updated_at, last_login) 
                    VALUES (:username, :email, :password, :google_id, 0, 1, NOW(), NOW(), NOW())
                ');
                $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => $password_hash,
                    'google_id' => $google_id,
                ]);
                
                $user_id = $pdo->lastInsertId();
                error_log('Đã tạo user id: ' . $user_id);
                
                // Thêm thông tin người dùng
                $stmt = $pdo->prepare('
                    INSERT INTO user_infos (user_id, full_name, avatar, created_at, updated_at) 
                    VALUES (:user_id, :full_name, :avatar, NOW(), NOW())
                ');
                $stmt->execute([
                    'user_id' => $user_id,
                    'full_name' => $name,
                    'avatar' => $picture
                ]);
                
                // Thêm token/quota cho người dùng mới
                $stmt = $pdo->prepare('
                    INSERT INTO user_tokens (user_id, token, quota) 
                    VALUES (:user_id, :token, :quota)
                ');
                
                // Lấy quota mặc định từ cài đặt
                $default_quota = 100;
                try {
                    $quota_stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'default_quota' AND category = 'chatbot'");
                    $quota_stmt->execute();
                    $result = $quota_stmt->fetch();
                    if ($result) {
                        $default_quota = (int)$result['setting_value'];
                    }
                } catch (PDOException $e) {
                    error_log('Lỗi khi lấy quota mặc định: ' . $e->getMessage());
                    // Giữ nguyên quota mặc định nếu có lỗi
                }
                
                $stmt->execute([
                    'user_id' => $user_id,
                    'token' => bin2hex(random_bytes(16)),
                    'quota' => $default_quota
                ]);
                
                // Commit transaction
                $pdo->commit();
                $transaction_active = false;
                
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_level'] = 0; // Người dùng thông thường
                
                // Chuyển hướng đến trang chính
                header('Location: ' . $base_url . '/chat');
                exit;
                
            } catch (PDOException $e) {
                // Rollback khi có lỗi
                if ($transaction_active && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Lỗi cơ sở dữ liệu khi tạo tài khoản: ' . $e->getMessage());
                header('Location: ' . $base_url . '/login.php?error=db_error&message=' . urlencode($e->getMessage()));
                exit;
            }
        } catch (Exception $e) {
            error_log('Lỗi chung khi tạo tài khoản: ' . $e->getMessage());
            header('Location: ' . $base_url . '/login.php?error=account_creation_error&message=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
} catch (Exception $e) {
    // Xử lý các lỗi khác
    header('Location: ' . $base_url . '/login.php?error=google_error&message=' . urlencode($e->getMessage()));
    exit;
}
?> 
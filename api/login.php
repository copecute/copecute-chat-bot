<?php
require_once 'config.php';

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Phương thức không được hỗ trợ', 405);
}

// Nhận dữ liệu JSON từ client
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!isset($input['username']) || !isset($input['password'])) {
    returnError('Thiếu thông tin đăng nhập');
}

$username = trim($input['username']);
$password = $input['password'];

if (empty($username) || empty($password)) {
    returnError('Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu');
}

try {
    // Kiểm tra username
    $stmt = $pdo->prepare('SELECT id, username, password, level, is_acctive, email, google_id FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        returnError('Sai tên đăng nhập hoặc mật khẩu', 401);
    }
    
    // Kiểm tra trạng thái tài khoản
    if ($user['is_acctive'] === '0') {
        returnError('Tài khoản của bạn chưa được kích hoạt', 403);
    } elseif ($user['is_acctive'] === '2') {
        returnError('Tài khoản của bạn đã bị khóa vĩnh viễn', 403);
    } elseif ($user['is_acctive'] !== '1') {
        // Kiểm tra nếu tài khoản bị khóa đến ngày cụ thể
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $user['is_acctive'])) {
            $lock_date = strtotime($user['is_acctive']);
            $today = strtotime(date('Y-m-d'));
            
            if ($today >= $lock_date) {
                // Đã đến hoặc qua ngày mở khóa, cập nhật trạng thái tài khoản
                $update_stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                $update_stmt->execute(['id' => $user['id']]);
            } else {
                // Chưa đến ngày mở khóa
                $lock_date_display = date('d/m/Y', $lock_date);
                returnError("Tài khoản của bạn đã bị khóa đến ngày {$lock_date_display}", 403);
            }
        } else {
            returnError('Tài khoản của bạn đã bị khóa', 403);
        }
    }
    
    // Lấy token của người dùng
    $stmt = $pdo->prepare('SELECT token, quota FROM user_tokens WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user['id']]);
    $token_info = $stmt->fetch();
    
    if (!$token_info) {
        // Nếu không có token, tạo token mới
        $token = bin2hex(random_bytes(32));
        $quota = 100; // Quota mặc định
        
        $stmt = $pdo->prepare('INSERT INTO user_tokens (user_id, token, quota) VALUES (:user_id, :token, :quota)');
        $stmt->execute([
            'user_id' => $user['id'],
            'token' => $token,
            'quota' => $quota
        ]);
    } else {
        $token = $token_info['token'];
        $quota = $token_info['quota'];
    }
    
    // Lấy thông tin người dùng
    $stmt = $pdo->prepare('SELECT full_name, avatar FROM user_infos WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user['id']]);
    $user_info = $stmt->fetch();
    
    if (!$user_info) {
        $user_info = [
            'full_name' => $user['username'],
            'avatar' => null
        ];
    }
    
    // Cập nhật thời gian đăng nhập cuối
    $update_login_time = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
    $update_login_time->execute(['id' => $user['id']]);
    
    // Trả về dữ liệu người dùng (không bao gồm mật khẩu)
    $response = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'level' => $user['level'],
        'full_name' => $user_info['full_name'],
        'avatar' => $user_info['avatar'],
        'token' => $token,
        'quota' => $quota
    ];
    
    returnSuccess($response);
    
} catch (PDOException $e) {
    returnError('Lỗi hệ thống: ' . $e->getMessage(), 500);
}
?> 
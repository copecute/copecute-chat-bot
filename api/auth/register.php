<?php
require_once '../config.php';

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Phương thức không được hỗ trợ', 405);
}

// Nhận dữ liệu JSON từ client
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
    returnError('Thiếu thông tin đăng ký');
}

$username = trim($input['username']);
$email = trim($input['email']);
$password = $input['password'];
$full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
$google_id = isset($input['google_id']) ? trim($input['google_id']) : null;
$avatar = isset($input['avatar']) ? trim($input['avatar']) : null;

// Kiểm tra đầu vào
if (empty($username) || empty($email) || empty($password)) {
    returnError('Vui lòng điền đầy đủ thông tin');
} elseif (strlen($password) < 6) {
    returnError('Mật khẩu phải có ít nhất 6 ký tự');
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    returnError('Email không hợp lệ');
}

try {
    // Kiểm tra xem đăng ký có được cho phép hay không
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'allow_registration'");
    $allow_registration = $stmt->fetchColumn();
    
    if (!$allow_registration) {
        returnError('Đăng ký tài khoản mới đã bị tạm khóa', 403);
    }
    
    // Kiểm tra tên đăng nhập đã tồn tại chưa
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    if ($stmt->fetchColumn() > 0) {
        returnError('Tên đăng nhập đã tồn tại, vui lòng chọn tên khác');
    }
    
    // Kiểm tra email đã tồn tại chưa
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        returnError('Email đã tồn tại, vui lòng sử dụng email khác');
    }
    
    // Lấy quota mặc định từ cài đặt
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'default_quota'");
    $default_quota = $stmt->fetchColumn() ?: 100; // Mặc định 100 nếu không tìm thấy
    
    // Mã hóa mật khẩu
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Thêm người dùng vào cơ sở dữ liệu
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, level, google_id, is_acctive, last_login) VALUES (:username, :email, :password, :level, :google_id, :is_acctive, NOW())');
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $password_hash,
            'level' => 0, // Mặc định là người dùng thường
            'google_id' => $google_id,
            'is_acctive' => 1 // Tài khoản mặc định đã kích hoạt
        ]);
        
        // Lấy id của người dùng vừa đăng ký
        $user_id = $pdo->lastInsertId();
        
        // Tạo profile cơ bản cho người dùng
        $stmt = $pdo->prepare('INSERT INTO user_infos (user_id, full_name, avatar) VALUES (:user_id, :full_name, :avatar)');
        $stmt->execute([
            'user_id' => $user_id, 
            'full_name' => $full_name ?: $username,
            'avatar' => $avatar
        ]);
        
        // Tạo token cho người dùng
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare('INSERT INTO user_tokens (user_id, token, quota) VALUES (:user_id, :token, :quota)');
        $stmt->execute([
            'user_id' => $user_id,
            'token' => $token,
            'quota' => $default_quota
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Trả về thông tin người dùng và token
        $response = [
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'level' => 0,
            'full_name' => $full_name ?: $username,
            'avatar' => $avatar,
            'token' => $token,
            'quota' => $default_quota
        ];
        
        returnSuccess($response);
        
    } catch (PDOException $e) {
        // Rollback khi có lỗi
        $pdo->rollBack();
        throw $e;
    }
} catch (PDOException $e) {
    returnError('Lỗi hệ thống: ' . $e->getMessage(), 500);
}
?> 
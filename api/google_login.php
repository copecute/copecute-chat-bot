<?php
require_once 'config.php';

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Phương thức không được hỗ trợ', 405);
}

// Nhận dữ liệu JSON từ client
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!isset($input['id_token'])) {
    returnError('Thiếu token xác thực Google');
}

$id_token = $input['id_token'];

try {
    // Tách token thành các phần
    $token_parts = explode('.', $id_token);
    if (count($token_parts) != 3) {
        returnError('Token không hợp lệ', 401);
    }
    
    // Giải mã phần payload
    $payload = json_decode(base64_decode(str_replace(
        ['-', '_'], 
        ['+', '/'], 
        $token_parts[1]
    )), true);
    
    // Kiểm tra payload
    if (!$payload || !isset($payload['sub']) || !isset($payload['email'])) {
        returnError('Token không hợp lệ hoặc thiếu thông tin', 401);
    }
    
    // Lấy thông tin người dùng từ payload
    $google_id = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'] ?? '';
    $picture = $payload['picture'] ?? '';
    
    // Kiểm tra xem email hoặc google_id đã tồn tại trong cơ sở dữ liệu chưa
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email OR google_id = :google_id');
    $stmt->execute(['email' => $email, 'google_id' => $google_id]);
    $user = $stmt->fetch();
    
    // Nếu người dùng chưa tồn tại, tạo tài khoản mới
    if (!$user) {
        // Tạo username duy nhất từ email
        $base_username = strtolower(explode('@', $email)[0]);
        $username = $base_username;
        $counter = 1;
        
        // Kiểm tra username có tồn tại không
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        
        // Nếu username đã tồn tại, thêm số ở cuối cho đến khi tìm được username chưa sử dụng
        while ($stmt->fetch()) {
            $username = $base_username . $counter;
            $counter++;
            $stmt->execute(['username' => $username]);
        }
        
        // Tạo mật khẩu ngẫu nhiên
        $random_password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
        
        // Thêm người dùng mới vào cơ sở dữ liệu
        $pdo->beginTransaction();
        
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, level, is_acctive, google_id, created_at, last_login) 
                                  VALUES (:username, :email, :password, :level, :is_acctive, :google_id, NOW(), NOW())');
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password,
                'level' => 0, // Người dùng bình thường
                'is_acctive' => '1', // Tài khoản đã kích hoạt
                'google_id' => $google_id
            ]);
            
            $user_id = $pdo->lastInsertId();
            
            // Thêm thông tin người dùng
            $stmt = $pdo->prepare('INSERT INTO user_infos (user_id, full_name, avatar) 
                                  VALUES (:user_id, :full_name, :avatar)');
            $stmt->execute([
                'user_id' => $user_id,
                'full_name' => $name,
                'avatar' => $picture
            ]);
            
            $pdo->commit();
            
            // Lấy thông tin người dùng vừa tạo
            $stmt = $pdo->prepare('SELECT id, username, email, level, is_acctive, google_id FROM users WHERE id = :id');
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            returnError('Lỗi khi tạo tài khoản: ' . $e->getMessage(), 500);
        }
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
    
    // Nếu user có email từ Google nhưng chưa có google_id, cập nhật google_id
    if (empty($user['google_id'])) {
        $stmt = $pdo->prepare('UPDATE users SET google_id = :google_id, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['google_id' => $google_id, 'id' => $user['id']]);
    }
    
    // Lấy hoặc tạo token cho người dùng
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
    
    // Trả về dữ liệu người dùng
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
    
} catch (Exception $e) {
    returnError('Lỗi khi xác thực: ' . $e->getMessage(), 500);
}
?> 
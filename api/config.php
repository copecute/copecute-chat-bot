<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Cấu hình chung cho API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');

// Xử lý yêu cầu OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Bắt đầu session nếu chưa bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include cấu hình chung từ file gốc
require_once dirname(__DIR__) . '/includes/config.php';

// Hàm kiểm tra trạng thái tài khoản
function checkAccountStatus($userId, $pdo) {
    try {
        $stmt = $pdo->prepare('SELECT is_acctive FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false; // Tài khoản không tồn tại
        }
        
        $is_acctive = $result['is_acctive'];
        
        // Trường hợp tài khoản đã bị khóa vĩnh viễn hoặc chưa kích hoạt
        if ($is_acctive === '0' || $is_acctive === '2') {
            return false;
        }
        
        // Trường hợp tài khoản đã kích hoạt
        if ($is_acctive === '1') {
            return true;
        }
        
        // Kiểm tra xem có phải bị khóa đến ngày cụ thể không
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $is_acctive)) {
            $lock_date = strtotime($is_acctive);
            $today = strtotime(date('Y-m-d'));
            
            if ($today >= $lock_date) {
                // Đã đến hoặc qua ngày mở khóa, cập nhật trạng thái tài khoản
                $update_stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                $update_stmt->execute(['id' => $userId]);
                return true;
            } else {
                // Chưa đến ngày mở khóa
                return false;
            }
        }
        
        // Mặc định trả về false nếu không rơi vào các trường hợp trên
        return false;
    } catch (PDOException $e) {
        // error_log('Lỗi kiểm tra trạng thái tài khoản: ' . $e->getMessage());
        return false;
    }
}

// Hàm kiểm tra token API
function validateApiToken($token, $pdo) {
    if (empty($token)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT user_id FROM user_tokens WHERE token = :token');
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false; // Token không hợp lệ
        }
        
        $userId = $result['user_id'];
        
        // Kiểm tra trạng thái tài khoản
        $isAccountActive = checkAccountStatus($userId, $pdo);
        if (!$isAccountActive) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Tài khoản đã bị khóa hoặc chưa được kích hoạt',
                'code' => 'account_locked'
            ]);
            exit;
        }
        
        return $userId;
    } catch (PDOException $e) {
        return false;
    }
}

// Hàm kiểm tra và trích xuất token từ header
function getAuthToken() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader)) {
        return null;
    }
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}

// Hàm trả về lỗi JSON
function returnError($message, $statusCode = 400, $errorCode = null) {
    http_response_code($statusCode);
    $response = ['error' => $message];
    
    if ($errorCode !== null) {
        $response['code'] = $errorCode;
    }
    
    echo json_encode($response);
    exit;
}

// Hàm trả về dữ liệu JSON thành công
function returnSuccess($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}
?> 
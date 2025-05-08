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

// Hàm kiểm tra token API
function validateApiToken($token, $pdo) {
    if (empty($token)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT user_id FROM user_tokens WHERE token = :token');
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch();
        
        return $result ? $result['user_id'] : false;
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
function returnError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit;
}

// Hàm trả về dữ liệu JSON thành công
function returnSuccess($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}
?> 
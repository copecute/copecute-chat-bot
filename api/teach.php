<?php
require_once 'config.php';

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Phương thức không được hỗ trợ', 405);
}

// Lấy token từ header Authorization
$token = getAuthToken();
if (!$token) {
    returnError('Không tìm thấy token xác thực', 401);
}

// Xác thực token
$user_id = validateApiToken($token, $pdo);
if (!$user_id) {
    returnError('Token không hợp lệ hoặc đã hết hạn', 401);
}

// Nhận dữ liệu JSON từ client
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!isset($input['keyword']) || !isset($input['reply'])) {
    returnError('Thiếu từ khóa hoặc câu trả lời');
}

$keyword = trim($input['keyword']);
$reply = trim($input['reply']);
$impolite = isset($input['impolite']) ? (bool)$input['impolite'] : false;

// Kiểm tra dữ liệu đầu vào
if (empty($keyword) || empty($reply)) {
    returnError('Vui lòng nhập đầy đủ từ khóa và câu trả lời');
} elseif (strlen($keyword) > 250) {
    returnError('Từ khóa không được vượt quá 250 ký tự');
} elseif (strlen($reply) > 250) {
    returnError('Câu trả lời không được vượt quá 250 ký tự');
}

try {
    // Kiểm tra user tồn tại
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $user_exists = $stmt->fetch();
    
    if (!$user_exists) {
        returnError('Tài khoản không tồn tại hoặc đã bị xóa', 404);
    }
    
    // Thêm yêu cầu dạy bot mới
    $stmt = $pdo->prepare("INSERT INTO teachbot_requests (user_id, keyword, reply, impolite, status, created_at) VALUES (:user_id, :keyword, :reply, :impolite, 'pending', NOW())");
    $stmt->execute([
        'user_id' => $user_id,
        'keyword' => $keyword,
        'reply' => $reply,
        'impolite' => $impolite ? 1 : 0
    ]);
    
    $request_id = $pdo->lastInsertId();
    
    // Lấy danh sách các yêu cầu đã gửi gần đây
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            keyword, 
            reply, 
            impolite, 
            status, 
            created_at, 
            updated_at,
            CASE 
                WHEN status = 'pending' THEN 'Đang chờ duyệt'
                WHEN status = 'approved' THEN 'Đã duyệt'
                WHEN status = 'rejected' THEN 'Đã từ chối'
            END as status_text
        FROM teachbot_requests 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute(['user_id' => $user_id]);
    $requests = $stmt->fetchAll();
    
    // Trả về thông tin yêu cầu dạy bot đã gửi
    returnSuccess([
        'message' => 'Yêu cầu dạy bot đã được gửi thành công',
        'request_id' => $request_id,
        'recent_requests' => $requests
    ]);
    
} catch (PDOException $e) {
    returnError('Lỗi hệ thống: ' . $e->getMessage(), 500);
}
?> 
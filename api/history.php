<?php
require_once 'config.php';

// Chỉ chấp nhận phương thức GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

// Tham số phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Đảm bảo giá trị hợp lệ
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 100) $limit = 20;

$offset = ($page - 1) * $limit;

try {
    // Kiểm tra user tồn tại
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $user_exists = $stmt->fetch();
    
    if (!$user_exists) {
        returnError('Tài khoản không tồn tại hoặc đã bị xóa', 404);
    }
    
    // Lấy tổng số lịch sử chat
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM chat_history WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $total = $stmt->fetch()['total'];
    
    // Lấy danh sách lịch sử chat
    $stmt = $pdo->prepare('
        SELECT 
            id, 
            user_input, 
            bot_response, 
            created_at
        FROM chat_history 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll();
    
    // Tính toán thông tin phân trang
    $total_pages = ceil($total / $limit);
    
    // Trả về kết quả
    returnSuccess([
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $total_pages,
        'history' => $history
    ]);
    
} catch (PDOException $e) {
    returnError('Lỗi hệ thống: ' . $e->getMessage(), 500);
}
?> 
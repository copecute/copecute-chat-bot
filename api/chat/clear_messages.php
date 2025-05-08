<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Bắt buộc sử dụng phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Phương thức không được hỗ trợ']);
    exit;
}

require_once '../config.php';

// Lấy token từ header
$token = getAuthToken();
if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Không tìm thấy token xác thực']);
    exit;
}

try {
    // Xác thực token và lấy user_id - sử dụng cùng hàm với chat.php
    $user_id = validateApiToken($token, $pdo);
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token không hợp lệ hoặc đã hết hạn']);
        exit;
    }

    // Kiểm tra xem người dùng có tồn tại không
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'user_deleted']);
        exit;
    }

    // Bắt đầu transaction
    $pdo->beginTransaction();

    try {
        // Xóa lịch sử chat từ bảng chat_history
        $stmt = $pdo->prepare('DELETE FROM chat_history WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);

        // Commit transaction
        $pdo->commit();

        // Trả về kết quả thành công
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa toàn bộ lịch sử chat'
        ]);
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $pdo->rollBack();
        throw $e;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi server: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi không xác định: ' . $e->getMessage()
    ]);
} 
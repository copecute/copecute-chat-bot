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
    // Xác thực token và lấy user_id
    $user_id = validateApiToken($token, $pdo);
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token không hợp lệ hoặc đã hết hạn']);
        exit;
    }

    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    // Lấy mật khẩu hiện tại và mật khẩu mới
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    // Kiểm tra mật khẩu
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vui lòng nhập đầy đủ thông tin']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Mật khẩu mới không khớp']);
        exit;
    }

    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
        exit;
    }

    // Lấy thông tin người dùng
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thông tin người dùng']);
        exit;
    }

    // Kiểm tra mật khẩu hiện tại
    if (!password_verify($currentPassword, $user['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Mật khẩu hiện tại không chính xác']);
        exit;
    }

    // Mã hóa mật khẩu mới
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Cập nhật mật khẩu mới
    $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :user_id');
    $stmt->execute([
        'password' => $newPasswordHash,
        'user_id' => $user_id
    ]);

    // Trả về kết quả thành công
    echo json_encode([
        'success' => true,
        'message' => 'Đổi mật khẩu thành công'
    ]);
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
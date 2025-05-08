<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Bắt buộc sử dụng phương thức GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    // Lấy thông tin chi tiết của người dùng
    $stmt = $pdo->prepare('
        SELECT u.id, u.username, u.email, 
               ui.full_name, ui.phone, ui.gender, ui.birthday, ui.address, ui.avatar, ui.created_at,
               ut.quota
        FROM users u
        LEFT JOIN user_infos ui ON u.id = ui.user_id
        LEFT JOIN user_tokens ut ON u.id = ut.user_id
        WHERE u.id = :user_id
    ');
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thông tin người dùng']);
        exit;
    }

    // Format thông tin người dùng để trả về
    $profileData = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'full_name' => $user['full_name'] ?? '',
        'phone' => $user['phone'] ?? '',
        'gender' => $user['gender'] ?? 'other',
        'birthday' => $user['birthday'] ?? '',
        'address' => $user['address'] ?? '',
        'avatar' => $user['avatar'] ?? '',
        'created_at' => $user['created_at'] ?? '',
        'quota' => (int)($user['quota'] ?? 0)
    ];

    // Trả về kết quả thành công
    echo json_encode([
        'success' => true,
        'data' => $profileData
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
<?php
require_once '../check_admin.php';

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'status' => 'error',
        'message' => 'Phương thức không được hỗ trợ'
    ]));
}

// Lấy cài đặt hệ thống
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE category = 'chatbot'");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Lỗi khi lấy cài đặt: ' . $e->getMessage()
    ]));
}

// Lấy action và user_id từ request
$action = $_POST['action'] ?? '';
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if (!$user_id) {
    die(json_encode([
        'status' => 'error',
        'message' => 'ID người dùng không hợp lệ'
    ]));
}

try {
    switch ($action) {
        case 'lock':
            // Không cho phép khóa tài khoản của chính mình
            if ($user_id == $_SESSION['user_id']) {
                die(json_encode([
                    'status' => 'error',
                    'message' => 'Không thể khóa tài khoản của chính mình'
                ]));
            }

            $lock_type = $_POST['lock_type'] ?? '';
            $lock_date = $_POST['lock_date'] ?? '';
            
            // Xác định trạng thái dựa trên loại khóa
            if ($lock_type === 'permanent') {
                $status = '2';
            } elseif ($lock_type === 'inactive') {
                $status = '0';
            } elseif ($lock_type === 'temporary' && !empty($lock_date)) {
                $status = $lock_date;
            } else {
                die(json_encode([
                    'status' => 'error',
                    'message' => 'Thông tin khóa tài khoản không hợp lệ'
                ]));
            }
            
            // Cập nhật trạng thái
            $stmt = $pdo->prepare('UPDATE users SET is_acctive = :status WHERE id = :user_id');
            $stmt->execute([
                'status' => $status,
                'user_id' => $user_id
            ]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Đã cập nhật trạng thái tài khoản thành công'
            ]);
            break;
            
        case 'unlock':
            $stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Đã mở khóa tài khoản thành công'
            ]);
            break;
            
        case 'reset_quota':
            // Lấy giá trị quota mặc định từ cài đặt
            $default_quota = $settings['default_quota'] ?? 100;
            
            // Reset quota về giá trị mặc định
            $stmt = $pdo->prepare('UPDATE user_tokens SET quota = :quota WHERE user_id = :user_id');
            $stmt->execute([
                'quota' => $default_quota,
                'user_id' => $user_id
            ]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Đã reset quota thành công'
            ]);
            break;
            
        case 'delete':
            // Không cho phép xóa tài khoản admin
            if ($user_id == 1) {
                die(json_encode([
                    'status' => 'error',
                    'message' => 'Không thể xóa tài khoản admin'
                ]));
            }
            
            $pdo->beginTransaction();
            
            // Xóa từ bảng user_tokens
            $stmt = $pdo->prepare('DELETE FROM user_tokens WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            // Xóa từ bảng user_infos
            $stmt = $pdo->prepare('DELETE FROM user_infos WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            // Xóa từ bảng chat_history
            $stmt = $pdo->prepare('DELETE FROM chat_history WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            // Cuối cùng xóa từ bảng users
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            $pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Đã xóa người dùng thành công'
            ]);
            break;
            
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Hành động không được hỗ trợ'
            ]);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}

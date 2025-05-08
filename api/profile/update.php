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

    // Lấy các trường cần cập nhật
    $fullName = $data['full_name'] ?? null;
    $phone = $data['phone'] ?? null;
    $gender = $data['gender'] ?? null;
    $birthday = $data['birthday'] ?? null;
    $address = $data['address'] ?? null;
    $avatar = $data['avatar'] ?? null;

    // Bắt đầu transaction
    $pdo->beginTransaction();

    try {
        // Cập nhật thông tin cá nhân
        $updateFields = [];
        $updateParams = ['user_id' => $user_id];

        if ($fullName !== null) {
            $updateFields[] = 'full_name = :full_name';
            $updateParams['full_name'] = $fullName;
        }

        if ($phone !== null) {
            $updateFields[] = 'phone = :phone';
            $updateParams['phone'] = $phone;
        }

        if ($gender !== null) {
            $updateFields[] = 'gender = :gender';
            $updateParams['gender'] = $gender;
        }

        if ($birthday !== null) {
            $updateFields[] = 'birthday = :birthday';
            $updateParams['birthday'] = $birthday;
        }

        if ($address !== null) {
            $updateFields[] = 'address = :address';
            $updateParams['address'] = $address;
        }

        if ($avatar !== null) {
            $updateFields[] = 'avatar = :avatar';
            $updateParams['avatar'] = $avatar;
        }

        if (!empty($updateFields)) {
            // Kiểm tra nếu có bản ghi trong user_infos chưa
            $stmt = $pdo->prepare('SELECT 1 FROM user_infos WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            $hasUserInfo = $stmt->fetchColumn();

            if ($hasUserInfo) {
                // Nếu đã có, thực hiện UPDATE
                $sql = 'UPDATE user_infos SET ' . implode(', ', $updateFields) . ' WHERE user_id = :user_id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateParams);
            } else {
                // Nếu chưa có, thực hiện INSERT
                $fields = ['user_id'];
                $values = [':user_id'];
                $insertParams = ['user_id' => $user_id];

                foreach ($updateParams as $key => $value) {
                    if ($key !== 'user_id') {
                        $fields[] = str_replace(':', '', $key);
                        $values[] = ':' . $key;
                        $insertParams[$key] = $value;
                    }
                }

                $sql = 'INSERT INTO user_infos (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($insertParams);
            }
        }

        // Commit transaction
        $pdo->commit();

        // Trả về kết quả thành công
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công',
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
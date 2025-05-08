<?php
require_once '../check_admin.php';

// Đảm bảo file chỉ xử lý yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không được cho phép.']);
    exit;
}

// Kiểm tra quyền quản lý từ khóa
$can_manage_teachbot = $is_admin || $_SESSION['level'] >= 1; // Cả quản trị viên và quản lý đều có quyền

if (!$can_manage_teachbot) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này.']);
    exit;
}

// Lấy hành động từ request
$action = isset($_POST['action']) ? $_POST['action'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Kiểm tra ID hợp lệ
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID không hợp lệ.']);
    exit;
}

// Xử lý theo hành động
switch ($action) {
    case 'approve':
        handleApprove($id);
        break;
    case 'reject':
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        handleReject($id, $reason);
        break;
    case 'update':
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        $reply = isset($_POST['reply']) ? trim($_POST['reply']) : '';
        $impolite = isset($_POST['impolite']) ? (int)$_POST['impolite'] : 0;
        handleUpdate($id, $keyword, $reply, $impolite);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ.']);
        exit;
}

/**
 * Xử lý duyệt yêu cầu
 * @param int $id ID yêu cầu
 */
function handleApprove($id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Lấy thông tin yêu cầu
        $stmt = $pdo->prepare("SELECT * FROM teachbot_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy yêu cầu.']);
            return;
        }
        
        // Debug impolite value
        error_log("Request impolite value: " . print_r($request['impolite'], true));
        
        // Kiểm tra trạng thái
        if ($request['status'] !== 'pending') {
            echo json_encode(['status' => 'error', 'message' => 'Yêu cầu này đã được xử lý trước đó.']);
            return;
        }
        
        // Hàm đếm ký tự thực tế (bỏ qua dấu nháy kép và các ký tự đặc biệt)
        function countActualCharacters($str) {
            // Chuyển chuỗi về dạng UTF-8 nếu chưa phải
            $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            // Đếm số ký tự thực tế bằng mb_strlen
            return mb_strlen($str, 'UTF-8');
        }
        
        // Kiểm tra độ dài từ khóa và câu trả lời
        if (countActualCharacters($request['keyword']) > 250) {
            echo json_encode(['status' => 'error', 'message' => 'Từ khóa không được vượt quá 250 ký tự.']);
            return;
        }

        if (countActualCharacters($request['reply']) > 250) {
            echo json_encode(['status' => 'error', 'message' => 'Câu trả lời không được vượt quá 250 ký tự.']);
            return;
        }
        
        // Kiểm tra xem từ khóa đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM keywords WHERE keyword = ?");
        $stmt->execute([$request['keyword']]);
        $keyword_id = $stmt->fetchColumn();
        
        if (!$keyword_id) {
            // Thêm từ khóa mới
            $stmt = $pdo->prepare("INSERT INTO keywords (keyword, impolite) VALUES (?, ?)");
            $stmt->execute([$request['keyword'], $request['impolite']]);
            $keyword_id = $pdo->lastInsertId();
        }
        
        // Thêm câu trả lời mới
        $stmt = $pdo->prepare("INSERT INTO replies (keyword_id, reply, impolite) VALUES (?, ?, ?)");
        $stmt->execute([$keyword_id, $request['reply'], $request['impolite']]);
        
        // Cập nhật trạng thái yêu cầu
        $stmt = $pdo->prepare("UPDATE teachbot_requests SET status = 'approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Yêu cầu đã được duyệt thành công.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}

/**
 * Xử lý từ chối yêu cầu
 * @param int $id ID yêu cầu
 * @param string $reason Lý do từ chối
 */
function handleReject($id, $reason) {
    global $pdo;
    
    try {
        // Lấy thông tin yêu cầu
        $stmt = $pdo->prepare("SELECT * FROM teachbot_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy yêu cầu.']);
            return;
        }
        
        // Kiểm tra trạng thái
        if ($request['status'] !== 'pending') {
            echo json_encode(['status' => 'error', 'message' => 'Yêu cầu này đã được xử lý trước đó.']);
            return;
        }
        
        // Cập nhật trạng thái yêu cầu
        $stmt = $pdo->prepare("UPDATE teachbot_requests SET status = 'rejected', admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reason, $id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Yêu cầu đã được từ chối.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}

/**
 * Xử lý cập nhật thông tin yêu cầu
 * @param int $id ID yêu cầu
 * @param string $keyword Từ khóa mới
 * @param string $reply Câu trả lời mới
 * @param int $impolite Trạng thái thô lỗ (0/1)
 */
function handleUpdate($id, $keyword, $reply, $impolite) {
    global $pdo;
    
    // Debug
    error_log("Update impolite value: " . print_r($impolite, true));
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($keyword) || empty($reply)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ từ khóa và câu trả lời.']);
        return;
    }
    
    // Hàm đếm ký tự thực tế (bỏ qua dấu nháy kép và các ký tự đặc biệt)
    function countActualCharacters($str) {
        // Chuyển chuỗi về dạng UTF-8 nếu chưa phải
        $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        // Đếm số ký tự thực tế bằng mb_strlen
        return mb_strlen($str, 'UTF-8');
    }
    
    if (countActualCharacters($keyword) > 250) {
        echo json_encode(['status' => 'error', 'message' => 'Từ khóa không được vượt quá 250 ký tự.']);
        return;
    }
    
    if (countActualCharacters($reply) > 250) {
        echo json_encode(['status' => 'error', 'message' => 'Câu trả lời không được vượt quá 250 ký tự.']);
        return;
    }
    
    try {
        // Lấy thông tin yêu cầu
        $stmt = $pdo->prepare("SELECT * FROM teachbot_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy yêu cầu.']);
            return;
        }
        
        // Kiểm tra trạng thái
        if ($request['status'] !== 'pending') {
            echo json_encode(['status' => 'error', 'message' => 'Chỉ có thể chỉnh sửa yêu cầu đang chờ duyệt.']);
            return;
        }
        
        // Cập nhật thông tin yêu cầu
        $stmt = $pdo->prepare("UPDATE teachbot_requests SET keyword = ?, reply = ?, impolite = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$keyword, $reply, $impolite, $id]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Đã cập nhật thông tin yêu cầu thành công.',
            'data' => [
                'keyword' => $keyword,
                'reply' => $reply,
                'impolite' => $impolite
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
} 
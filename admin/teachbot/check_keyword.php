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

// Lấy từ khóa cần kiểm tra
$keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';

if (empty($keyword)) {
    echo json_encode(['status' => 'error', 'message' => 'Từ khóa không được để trống.']);
    exit;
}

if (strlen($keyword) > 250) {
    echo json_encode(['status' => 'error', 'message' => 'Từ khóa không được vượt quá 250 ký tự.']);
    exit;
}

try {
    // Kiểm tra xem từ khóa đã tồn tại chưa
    $stmt = $pdo->prepare("
        SELECT k.id, r.reply 
        FROM keywords k
        LEFT JOIN replies r ON k.id = r.keyword_id
        WHERE k.keyword = ?
    ");
    $stmt->execute([$keyword]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo kết quả trả về
    if (!empty($results)) {
        $replies = array_map(function($item) {
            return htmlspecialchars($item['reply']);
        }, $results);
        
        echo json_encode([
            'status' => 'success',
            'exists' => true,
            'keyword_id' => $results[0]['id'],
            'replies' => $replies
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'exists' => false
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
} 
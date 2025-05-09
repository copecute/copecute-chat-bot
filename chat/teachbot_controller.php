<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Bạn chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method không hợp lệ']);
    exit;
}

// Kiểm tra dữ liệu gửi lên
if (!isset($_POST['keyword']) || !isset($_POST['reply'])) {
    echo json_encode(['error' => 'Thiếu thông tin bắt buộc']);
    exit;
}

$keyword = trim($_POST['keyword']);
$reply = trim($_POST['reply']);
$impolite = isset($_POST['impolite']) && ($_POST['impolite'] == 1 || $_POST['impolite'] == 'true' || $_POST['impolite'] == 'on') ? 1 : 0;

// Debug
// error_log("Received impolite value: " . print_r($_POST['impolite'], true));
// error_log("Processed impolite value: $impolite");

// Hàm đếm ký tự thực tế (bỏ qua dấu nháy kép và các ký tự đặc biệt)
function countActualCharacters($str) {
    // Chuyển chuỗi về dạng UTF-8 nếu chưa phải
    $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    // Đếm số ký tự thực tế bằng mb_strlen
    return mb_strlen($str, 'UTF-8');
}

// Kiểm tra độ dài
if (countActualCharacters($keyword) > 250) {
    echo json_encode(['error' => 'Từ khóa không được vượt quá 250 ký tự']);
    exit;
}

if (countActualCharacters($reply) > 250) {
    echo json_encode(['error' => 'Câu trả lời không được vượt quá 250 ký tự']);
    exit;
}

try {
    // Kiểm tra xem từ khóa đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT id FROM keywords WHERE keyword = :keyword");
    $stmt->execute(['keyword' => $keyword]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Từ khóa này đã tồn tại trong hệ thống']);
        exit;
    }

    // Thêm yêu cầu dạy bot mới
    $stmt = $pdo->prepare("INSERT INTO teachbot_requests (user_id, keyword, reply, impolite) VALUES (:user_id, :keyword, :reply, :impolite)");
    $stmt->execute([
        'user_id' => $user_id,
        'keyword' => $keyword,
        'reply' => $reply,
        'impolite' => $impolite
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cảm ơn bạn đã dạy bot! Yêu cầu của bạn đã được gửi và sẽ được xem xét bởi quản trị viên.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
} 
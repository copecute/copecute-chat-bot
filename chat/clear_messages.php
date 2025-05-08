<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Xóa lịch sử chat từ bảng chat_history
    $stmt = $pdo->prepare('DELETE FROM chat_history WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $history_deleted = $stmt->rowCount();
    
    // Trả về kết quả thành công
    echo json_encode([
        'success' => true, 
        'history_deleted' => $history_deleted
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?> 
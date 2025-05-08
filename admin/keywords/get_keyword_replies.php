<?php
require_once '../check_admin.php';

// Kiểm tra keyword_id
if (!isset($_GET['keyword_id']) || !is_numeric($_GET['keyword_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid keyword ID']);
    exit;
}

$keyword_id = (int)$_GET['keyword_id'];

try {
    // Lấy thông tin từ khóa
    $stmt = $pdo->prepare("SELECT keyword, impolite FROM keywords WHERE id = :id");
    $stmt->execute(['id' => $keyword_id]);
    $keyword_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$keyword_data) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Keyword not found']);
        exit;
    }
    
    $keyword = $keyword_data['keyword'];
    $impolite = $keyword_data['impolite'];
    
    // Lấy danh sách câu trả lời
    $stmt = $pdo->prepare("SELECT id, reply, impolite FROM replies WHERE keyword_id = :keyword_id ORDER BY id DESC");
    $stmt->execute([
        'keyword_id' => $keyword_id
    ]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'keyword' => $keyword,
        'impolite' => $impolite,
        'replies' => $replies
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 
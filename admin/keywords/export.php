<?php
require_once '../check_admin.php';

// Kiểm tra quyền xuất dữ liệu từ khóa
$can_export_keywords = true; // Cả quản lý và quản trị viên đều có thể xuất dữ liệu

// Xác định loại từ khóa cần xuất (mặc định là lịch sự)
$type = isset($_GET['type']) && $_GET['type'] === 'impolite' ? 1 : 0;
$type_name = $type ? 'thô lỗ' : 'lịch sự';
$file_name = $type ? 'impolite_keywords_' : 'polite_keywords_';
$file_name .= date('Y-m-d') . '.json';

// Kiểm tra quyền
if (!$can_export_keywords) {
    header('Content-Type: text/plain');
    echo "Bạn không có quyền xuất dữ liệu từ khóa!";
    exit;
}

// Truy vấn lấy từ khóa và câu trả lời
try {
    // Lấy tất cả từ khóa có câu trả lời theo loại
    $query = "SELECT DISTINCT k.id, k.keyword
              FROM keywords k
              JOIN replies r ON k.id = r.keyword_id
              WHERE r.impolite = :impolite
              ORDER BY k.keyword";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['impolite' => $type]);
    $keywords = $stmt->fetchAll();
    
    // Dữ liệu xuất ra JSON
    $export_data = [];
    
    // Lấy câu trả lời cho mỗi từ khóa
    foreach ($keywords as $keyword) {
        $stmt = $pdo->prepare("SELECT reply FROM replies WHERE keyword_id = :keyword_id AND impolite = :impolite");
        $stmt->execute([
            'keyword_id' => $keyword['id'],
            'impolite' => $type
        ]);
        $replies = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Thêm vào dữ liệu xuất
        if (!empty($replies)) {
            $export_data[$keyword['keyword']] = $replies;
        }
    }
    
    // Thiết lập header cho file JSON download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    
    // Xuất JSON đẹp với định dạng UTF-8
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // Hiển thị lỗi nếu có
    header('Content-Type: text/plain');
    echo "Lỗi: " . $e->getMessage();
}
exit; 
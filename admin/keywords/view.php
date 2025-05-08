<?php
require_once '../check_admin.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}


// Kiểm tra tham số id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . $base_url . '/admin/keywords/index.php');
    exit;
}

$keyword_id = (int)$_GET['id'];
$error = '';
$success = '';

// Lấy thông tin từ khóa
try {
    $stmt = $pdo->prepare("SELECT * FROM keywords WHERE id = :id");
    $stmt->execute(['id' => $keyword_id]);
    $keyword_data = $stmt->fetch();
    
    if (!$keyword_data) {
        // Từ khóa không tồn tại
        header('Location: ' . $base_url . '/admin/keywords/index.php');
        exit;
    }
    
    // Xác định loại từ khóa từ dữ liệu
    $type = $keyword_data['impolite'];
    $type_name = $type ? 'thô lỗ' : 'lịch sự';
    
    // Lấy danh sách câu trả lời cho từ khóa này
    $stmt = $pdo->prepare("SELECT * FROM replies WHERE keyword_id = :keyword_id");
    $stmt->execute([
        'keyword_id' => $keyword_id
    ]);
    $replies = $stmt->fetchAll();
    
    // Tổng số câu trả lời
    $total_replies = count($replies);
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý xóa từ khóa
if (isset($_POST['delete_keyword'])) {
    try {
        $pdo->beginTransaction();
        
        // Xóa tất cả câu trả lời của từ khóa
        $stmt = $pdo->prepare("DELETE FROM replies WHERE keyword_id = :keyword_id");
        $stmt->execute(['keyword_id' => $keyword_id]);
        
        // Xóa từ khóa
        $stmt = $pdo->prepare("DELETE FROM keywords WHERE id = :id");
        $stmt->execute(['id' => $keyword_id]);
        
        $pdo->commit();
        
        // Chuyển hướng sau khi xóa
        header('Location: ' . $base_url . '/admin/keywords/index.php?type=' . ($type ? 'impolite' : 'polite') . '&success=deleted');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Xử lý xóa câu trả lời
if (isset($_GET['delete_reply']) && is_numeric($_GET['delete_reply'])) {
    $reply_id = (int)$_GET['delete_reply'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM replies WHERE id = :id AND keyword_id = :keyword_id");
        $stmt->execute([
            'id' => $reply_id,
            'keyword_id' => $keyword_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            $success = 'Đã xóa câu trả lời thành công!';
            
            // Cập nhật lại danh sách câu trả lời
            $stmt = $pdo->prepare("SELECT * FROM replies WHERE keyword_id = :keyword_id");
            $stmt->execute([
                'keyword_id' => $keyword_id
            ]);
            $replies = $stmt->fetchAll();
            $total_replies = count($replies);
        }
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Bao gồm header
include_once('../layout/header.php');
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Chi tiết từ khóa</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/admin/keywords/index.php?type=<?php echo $type ? 'impolite' : 'polite'; ?>">Từ khóa & Phản hồi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chi tiết từ khóa</li>
        </ol>
    </nav>
</div>

<!-- Thông báo -->
<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Action buttons -->
<div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div>
            <a href="<?php echo $base_url; ?>/admin/keywords/index.php?type=<?php echo $type ? 'impolite' : 'polite'; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
        <div>
            <a href="<?php echo $base_url; ?>/admin/keywords/edit.php?id=<?php echo $keyword_id; ?>&type=<?php echo $type ? 'impolite' : 'polite'; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Chỉnh sửa
            </a>
            <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash-alt me-1"></i> Xóa từ khóa
            </button>
        </div>
    </div>
</div>

<!-- Keyword info -->
<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Thông tin từ khóa</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">ID:</small>
                    <div class="font-weight-bold"><?php echo $keyword_data['id']; ?></div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Từ khóa (tối đa 250 ký tự):</small>
                    <div class="p-2 bg-light rounded border"><?php echo htmlspecialchars($keyword_data['keyword']); ?></div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Loại từ khóa:</small>
                    <span class="badge <?php echo $type ? 'bg-danger' : 'bg-success'; ?>"><?php echo $type_name; ?></span>
                </div>
                
                <?php if (isset($keyword_data['created_at'])): ?>
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Ngày tạo:</small>
                    <div><?php echo date('d/m/Y H:i', strtotime($keyword_data['created_at'])); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Số câu trả lời:</small>
                    <div>
                        <span class="badge bg-info fs-6"><?php echo $total_replies; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Danh sách câu trả lời</h6>
                <a href="<?php echo $base_url; ?>/admin/keywords/edit.php?id=<?php echo $keyword_id; ?>#add-reply" class="btn btn-sm btn-success">
                    <i class="fas fa-plus-circle"></i> Thêm câu trả lời
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($replies)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-comments fa-3x mb-3 text-gray-300"></i>
                            <p>Không có câu trả lời nào cho từ khóa này.</p>
                            <a href="<?php echo $base_url; ?>/admin/keywords/edit.php?id=<?php echo $keyword_id; ?>#add-reply" class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-plus-circle"></i> Thêm câu trả lời mới
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Câu trả lời</th>
                                    <th width="100">Loại</th>
                                    <th width="160">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($replies as $index => $reply): ?>
                                <tr>
                                    <td><?php echo $reply['id']; ?></td>
                                    <td>
                                        <div class="p-2 bg-light rounded border">
                                            <?php echo htmlspecialchars($reply['reply']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo isset($reply['impolite']) && $reply['impolite'] ? 'danger' : 'success'; ?>">
                                            <?php echo isset($reply['impolite']) && $reply['impolite'] ? 'Thô lỗ' : 'Lịch sự'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo $base_url; ?>/admin/keywords/edit.php?id=<?php echo $keyword_id; ?>&edit_reply=<?php echo $reply['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" onclick="confirmDeleteReply(<?php echo $reply['id']; ?>)" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Keyword Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa từ khóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Bạn có chắc chắn muốn xóa từ khóa này và <strong>tất cả câu trả lời</strong> của nó?
                </div>
                <p class="mb-0">Từ khóa: <strong><?php echo htmlspecialchars($keyword_data['keyword']); ?></strong></p>
                <p>Số câu trả lời sẽ bị xóa: <strong><?php echo $total_replies; ?></strong></p>
                <p class="mb-0 text-danger"><small>Hành động này không thể hoàn tác.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form method="post">
                    <button type="submit" name="delete_keyword" class="btn btn-danger">Xóa vĩnh viễn</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDeleteReply(replyId) {
        if (confirm('Bạn có chắc chắn muốn xóa câu trả lời này?')) {
            window.location.href = '?id=<?php echo $keyword_id; ?>&delete_reply=' + replyId;
        }
    }
</script>

<?php include_once('../layout/footer.php'); ?> 
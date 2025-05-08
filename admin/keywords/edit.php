<?php
require_once '../check_admin.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

// Kiểm tra quyền chỉnh sửa từ khóa
$can_manage_keywords = true; // Quản lý và quản trị viên đều có thể quản lý từ khóa

$error = '';
$success = '';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

// Kiểm tra tham số id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . $base_url . '/admin/keywords/index.php');
    exit;
}

$keyword_id = (int)$_GET['id'];

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
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý chỉnh sửa từ khóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_keyword'])) {
    // Kiểm tra quyền hạn
    if (!$can_manage_keywords) {
        $error = 'Bạn không có quyền chỉnh sửa từ khóa!';
    } else {
        $new_keyword = trim($_POST['keyword']);
        $new_impolite = isset($_POST['impolite']) ? 1 : 0;
        
        if (empty($new_keyword)) {
            $error = 'Vui lòng nhập từ khóa!';
        } elseif (strlen($new_keyword) > 250) {
            $error = 'Từ khóa không được vượt quá 250 ký tự!';
        } else {
            try {
                // Kiểm tra từ khóa mới đã tồn tại chưa (nếu từ khóa thay đổi)
                if ($new_keyword != $keyword_data['keyword'] || $new_impolite != $keyword_data['impolite']) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM keywords WHERE keyword = :keyword AND impolite = :impolite AND id != :id");
                    $stmt->execute([
                        'keyword' => $new_keyword,
                        'impolite' => $new_impolite,
                        'id' => $keyword_id
                    ]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Từ khóa này đã tồn tại trong hệ thống!';
                    } else {
                        // Cập nhật từ khóa
                        $stmt = $pdo->prepare("UPDATE keywords SET keyword = :keyword, impolite = :impolite WHERE id = :id");
                        $stmt->execute([
                            'keyword' => $new_keyword,
                            'impolite' => $new_impolite,
                            'id' => $keyword_id
                        ]);
                        
                        $success = 'Đã cập nhật từ khóa thành công!';
                        $keyword_data['keyword'] = $new_keyword;
                        $keyword_data['impolite'] = $new_impolite;
                        $type = $new_impolite;
                        $type_name = $type ? 'thô lỗ' : 'lịch sự';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý thêm câu trả lời mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    // Kiểm tra quyền hạn
    if (!$can_manage_keywords) {
        $error = 'Bạn không có quyền thêm câu trả lời!';
    } else {
        $reply = trim($_POST['reply']);
        $reply_impolite = isset($_POST['reply_impolite']) ? 1 : $keyword_data['impolite']; // Mặc định theo từ khóa
        
        if (empty($reply)) {
            $error = 'Vui lòng nhập câu trả lời!';
        } elseif (strlen($reply) > 250) {
            $error = 'Câu trả lời không được vượt quá 250 ký tự!';
        } else {
            try {
                // Kiểm tra câu trả lời đã tồn tại chưa
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM replies WHERE keyword_id = :keyword_id AND reply = :reply AND impolite = :impolite");
                $stmt->execute([
                    'keyword_id' => $keyword_id,
                    'reply' => $reply,
                    'impolite' => $reply_impolite
                ]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Câu trả lời này đã tồn tại!';
                } else {
                    // Thêm câu trả lời mới
                    $stmt = $pdo->prepare("INSERT INTO replies (keyword_id, reply, impolite) VALUES (:keyword_id, :reply, :impolite)");
                    $stmt->execute([
                        'keyword_id' => $keyword_id,
                        'reply' => $reply,
                        'impolite' => $reply_impolite
                    ]);
                    
                    $success = 'Đã thêm câu trả lời mới thành công!';
                    
                    // Cập nhật lại danh sách câu trả lời
                    $stmt = $pdo->prepare("SELECT * FROM replies WHERE keyword_id = :keyword_id");
                    $stmt->execute([
                        'keyword_id' => $keyword_id
                    ]);
                    $replies = $stmt->fetchAll();
                }
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý chỉnh sửa câu trả lời
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reply'])) {
    // Kiểm tra quyền hạn
    if (!$can_manage_keywords) {
        $error = 'Bạn không có quyền chỉnh sửa câu trả lời!';
    } else {
        $reply_id = $_POST['reply_id'];
        $reply = trim($_POST['reply_text']);
        $reply_impolite = isset($_POST['reply_impolite']) ? 1 : 0;
        
        if (empty($reply)) {
            $error = 'Vui lòng nhập câu trả lời!';
        } elseif (strlen($reply) > 250) {
            $error = 'Câu trả lời không được vượt quá 250 ký tự!';
        } else {
            try {
                // Kiểm tra câu trả lời đã tồn tại chưa
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM replies WHERE keyword_id = :keyword_id AND reply = :reply AND id != :id AND impolite = :impolite");
                $stmt->execute([
                    'keyword_id' => $keyword_id,
                    'reply' => $reply,
                    'id' => $reply_id,
                    'impolite' => $reply_impolite
                ]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Câu trả lời này đã tồn tại!';
                } else {
                    // Cập nhật câu trả lời
                    $stmt = $pdo->prepare("UPDATE replies SET reply = :reply, impolite = :impolite WHERE id = :id");
                    $stmt->execute([
                        'reply' => $reply,
                        'impolite' => $reply_impolite,
                        'id' => $reply_id
                    ]);
                    
                    $success = 'Đã cập nhật câu trả lời thành công!';
                    
                    // Cập nhật lại danh sách câu trả lời
                    $stmt = $pdo->prepare("SELECT * FROM replies WHERE keyword_id = :keyword_id");
                    $stmt->execute([
                        'keyword_id' => $keyword_id
                    ]);
                    $replies = $stmt->fetchAll();
                }
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý xóa câu trả lời
if (isset($_GET['delete_reply']) && is_numeric($_GET['delete_reply'])) {
    // Kiểm tra quyền hạn
    if (!$can_manage_keywords) {
        $error = 'Bạn không có quyền xóa câu trả lời!';
    } else {
        $reply_id = $_GET['delete_reply'];
        
        try {
            // Xóa câu trả lời
            $stmt = $pdo->prepare("DELETE FROM replies WHERE id = :id AND keyword_id = :keyword_id");
            $stmt->execute([
                'id' => $reply_id,
                'keyword_id' => $keyword_id
            ]);
            
            $success = 'Đã xóa câu trả lời thành công!';
            
            // Cập nhật lại danh sách câu trả lời
            $stmt = $pdo->prepare("SELECT * FROM replies WHERE keyword_id = :keyword_id");
            $stmt->execute([
                'keyword_id' => $keyword_id
            ]);
            $replies = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Lấy thông tin câu trả lời cần chỉnh sửa (nếu có)
$edit_reply_data = null;
if (isset($_GET['edit_reply']) && is_numeric($_GET['edit_reply'])) {
    $edit_reply_id = (int)$_GET['edit_reply'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM replies WHERE id = :id AND keyword_id = :keyword_id");
        $stmt->execute([
            'id' => $edit_reply_id,
            'keyword_id' => $keyword_id
        ]);
        $edit_reply_data = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Bao gồm header
include_once('../layout/header.php');
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Chỉnh sửa từ khóa</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/admin/keywords/index.php">Từ khóa & Phản hồi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa từ khóa</li>
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
            <a href="<?php echo $base_url; ?>/admin/keywords/view.php?id=<?php echo $keyword_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại xem chi tiết
            </a>
        </div>
        <div>
            <a href="<?php echo $base_url; ?>/admin/keywords/index.php" class="btn btn-primary">
                <i class="fas fa-list me-1"></i> Danh sách từ khóa
            </a>
        </div>
    </div>
</div>

<!-- Edit Keyword Form -->
<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Chỉnh sửa từ khóa</h6>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="keyword" class="form-label">Từ khóa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword_data['keyword']); ?>" maxlength="250" required>
                        <div class="form-text">Chỉnh sửa từ khóa (tối đa 250 ký tự)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label d-block">Loại từ khóa</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="impolite" id="polite" value="0" <?php echo $type === 0 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="polite">Lịch sự</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="impolite" id="impolite" value="1" <?php echo $type === 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="impolite">Thô lỗ</label>
                        </div>
                    </div>
                    
                    <button type="submit" name="edit_keyword" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Lưu thay đổi
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <!-- Add/Edit Reply Form -->
        <div class="card mb-4" id="add-reply">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold"><?php echo $edit_reply_data ? 'Chỉnh sửa câu trả lời' : 'Thêm câu trả lời mới'; ?></h6>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <?php if ($edit_reply_data): ?>
                        <input type="hidden" name="reply_id" value="<?php echo $edit_reply_data['id']; ?>">
                        <div class="mb-3">
                            <label for="reply_text" class="form-label">Câu trả lời <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reply_text" name="reply_text" rows="3" maxlength="250" required><?php echo htmlspecialchars($edit_reply_data['reply']); ?></textarea>
                            <div class="form-text">Chỉnh sửa câu trả lời (tối đa 250 ký tự)</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="reply_impolite" name="reply_impolite" <?php echo isset($edit_reply_data['impolite']) && $edit_reply_data['impolite'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="reply_impolite">Câu trả lời thô lỗ</label>
                                <div class="form-text">Mặc định theo loại từ khóa (<?php echo $type_name; ?>), bạn có thể thay đổi.</div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" name="edit_reply" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Cập nhật câu trả lời
                            </button>
                            <a href="<?php echo $base_url; ?>/admin/keywords/edit.php?id=<?php echo $keyword_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Hủy
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="reply" class="form-label">Câu trả lời <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reply" name="reply" rows="3" maxlength="250" required></textarea>
                            <div class="form-text">Nhập câu trả lời mới (tối đa 250 ký tự)</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="reply_impolite" name="reply_impolite" <?php echo $type ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="reply_impolite">Câu trả lời thô lỗ</label>
                                <div class="form-text">Mặc định theo loại từ khóa (<?php echo $type_name; ?>), bạn có thể thay đổi.</div>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_reply" class="btn btn-success">
                            <i class="fas fa-plus-circle me-1"></i> Thêm câu trả lời
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Reply List -->
        <div class="card mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Danh sách câu trả lời</h6>
                <span class="badge bg-info"><?php echo count($replies); ?> câu trả lời</span>
            </div>
            <div class="card-body">
                <?php if (empty($replies)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-comments fa-3x mb-3 text-gray-300"></i>
                            <p>Không có câu trả lời nào cho từ khóa này.</p>
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
                                <?php foreach ($replies as $reply): ?>
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
                                            <a href="<?php echo $base_url; ?>/admin/keywords/edit.php?id=<?php echo $keyword_id; ?>&edit_reply=<?php echo $reply['id']; ?>#add-reply" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Chỉnh sửa">
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

<script>
    function confirmDeleteReply(replyId) {
        if (confirm('Bạn có chắc chắn muốn xóa câu trả lời này?')) {
            window.location.href = '<?php echo $base_url; ?>/admin/keywords/edit.php?id=<?php echo $keyword_id; ?>&delete_reply=' + replyId;
        }
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hàm tạo bộ đếm ký tự
    function createCharacterCounter(inputElement, maxLength) {
        const counterDiv = document.createElement('div');
        counterDiv.className = 'text-muted small mt-1';
        inputElement.parentNode.appendChild(counterDiv);

        function updateCounter() {
            const remaining = maxLength - inputElement.value.length;
            counterDiv.textContent = `Còn lại ${remaining} ký tự`;
            if (remaining <= 50) {
                counterDiv.className = 'text-warning small mt-1';
            } else {
                counterDiv.className = 'text-muted small mt-1';
            }
        }

        inputElement.addEventListener('input', updateCounter);
        updateCounter(); // Hiển thị số ký tự ban đầu
    }

    // Tạo bộ đếm cho từ khóa
    const keywordInput = document.getElementById('keyword');
    if (keywordInput) {
        createCharacterCounter(keywordInput, 250);
    }

    // Tạo bộ đếm cho câu trả lời mới
    const replyInput = document.getElementById('reply');
    if (replyInput) {
        createCharacterCounter(replyInput, 250);
    }

    // Tạo bộ đếm cho chỉnh sửa câu trả lời
    const replyTextInput = document.getElementById('reply_text');
    if (replyTextInput) {
        createCharacterCounter(replyTextInput, 250);
    }
});
</script>

<?php include_once('../layout/footer.php'); ?> 
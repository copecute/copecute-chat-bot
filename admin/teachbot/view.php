<?php
require_once '../check_admin.php';

// Kiểm tra quyền quản lý từ khóa
$can_manage_teachbot = $is_admin || $_SESSION['level'] >= 1; // Cả quản trị viên và quản lý đều có quyền

if (!$can_manage_teachbot) {
    header('Location: ' . $base_url . '/admin/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Lấy ID yêu cầu
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: ' . $base_url . '/admin/teachbot/index.php');
    exit;
}

// Lấy thông tin chi tiết yêu cầu
try {
    $stmt = $pdo->prepare("
        SELECT tr.*, u.username, u.email, ui.full_name
        FROM teachbot_requests tr
        LEFT JOIN users u ON tr.user_id = u.id
        LEFT JOIN user_infos ui ON u.id = ui.user_id
        WHERE tr.id = ?
    ");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        header('Location: ' . $base_url . '/admin/teachbot/index.php?error=request_not_found');
        exit;
    }
    
    // Lấy số lượng yêu cầu của người dùng
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM teachbot_requests
        WHERE user_id = ?
    ");
    $stmt->execute([$request['user_id']]);
    $user_requests_count = $stmt->fetchColumn();
    
    // Lấy số lượng yêu cầu đã được duyệt của người dùng
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM teachbot_requests
        WHERE user_id = ? AND status = 'approved'
    ");
    $stmt->execute([$request['user_id']]);
    $user_approved_count = $stmt->fetchColumn();
    
    // Lấy yêu cầu mới nhất của người dùng
    $stmt = $pdo->prepare("
        SELECT * FROM teachbot_requests
        WHERE user_id = ? AND id != ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$request['user_id'], $id]);
    $other_requests = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Bao gồm header
include_once('../layout/header.php');
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Chi tiết yêu cầu dạy bot</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/teachbot/index.php">Dạy Bot</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chi tiết yêu cầu #<?php echo $id; ?></li>
        </ol>
    </nav>
</div>

<!-- Thông báo -->
<?php if (!empty($error)): ?>
<div class="card border-danger mb-3">
    <div class="card-body text-danger">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="card border-success mb-3">
    <div class="card-body text-success">
        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
    </div>
</div>
<?php endif; ?>

<!-- Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-left-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Yêu cầu #<?php echo $request['id']; ?></h5>
                        <p class="text-muted mb-0">
                            <small>Gửi ngày: <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?></small>
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo $base_url; ?>/admin/teachbot/index.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Quay lại
                        </a>
                        <?php if ($request['status'] === 'pending'): ?>
                        <button type="button" class="btn btn-sm btn-success" onclick="approveRequest(<?php echo $request['id']; ?>)">
                            <i class="fas fa-check me-1"></i> Duyệt
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal(<?php echo $request['id']; ?>)">
                            <i class="fas fa-times me-1"></i> Từ chối
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chi tiết yêu cầu -->
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Thông tin yêu cầu</h6>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="font-weight-bold">Trạng thái</h6>
                    <span class="badge bg-<?php 
                        echo $request['status'] === 'pending' ? 'warning' : 
                            ($request['status'] === 'approved' ? 'success' : 'danger'); 
                    ?> p-2">
                        <?php 
                        echo $request['status'] === 'pending' ? 'Đang chờ duyệt' : 
                            ($request['status'] === 'approved' ? 'Đã duyệt' : 'Đã từ chối'); 
                        ?>
                    </span>
                    <?php if ($request['status'] === 'rejected' && !empty($request['admin_notes'])): ?>
                    <div class="mt-2">
                        <p class="text-muted mb-1">Lý do từ chối:</p>
                        <div class="card border-danger">
                            <div class="card-body py-2 text-danger">
                                <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($request['status'] === 'pending'): ?>
                <!-- Hiển thị form chỉnh sửa mặc định cho yêu cầu đang chờ duyệt -->
                <form id="editRequestForm">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="edit_keyword" class="form-label">Từ khóa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_keyword" name="keyword" 
                               value="<?php echo htmlspecialchars($request['keyword']); ?>" maxlength="250" required>
                        <div class="form-text">Chỉnh sửa từ khóa (tối đa 250 ký tự)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_reply" class="form-label">Câu trả lời <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_reply" name="reply" rows="4" maxlength="250" required><?php echo htmlspecialchars($request['reply']); ?></textarea>
                        <div class="form-text">Chỉnh sửa câu trả lời (tối đa 250 ký tự)</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_impolite" name="impolite" value="1" <?php echo $request['impolite'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="edit_impolite">
                            Đánh dấu là nội dung thô lỗ/không lịch sự
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" id="saveChangesBtn">
                            <i class="fas fa-save me-1"></i> Lưu thay đổi
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <!-- Hiển thị thông tin ở dạng chỉ đọc cho yêu cầu đã duyệt hoặc từ chối -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Từ khóa</h6>
                        <p><?php echo htmlspecialchars($request['keyword']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Loại</h6>
                        <span class="badge <?php echo $request['impolite'] ? 'bg-danger' : 'bg-success'; ?>">
                            <?php echo $request['impolite'] ? 'Thô lỗ' : 'Lịch sự'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h6 class="font-weight-bold">Câu trả lời</h6>
                    <div class="card">
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($request['reply'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($request['status'] === 'pending'): ?>
                <hr>
                
                <h6 class="font-weight-bold">Kiểm tra trùng lặp</h6>
                
                <div class="mb-3">
                    <button class="btn btn-outline-primary btn-sm" type="button" onclick="checkKeyword('<?php echo htmlspecialchars(addslashes($request['keyword'])); ?>')">
                        <i class="fas fa-search me-1"></i> Kiểm tra từ khóa
                    </button>
                    <button class="btn btn-outline-primary btn-sm" type="button" id="checkEditedKeyword">
                        <i class="fas fa-search me-1"></i> Kiểm tra từ khóa đã chỉnh sửa
                    </button>
                </div>
                
                <div id="keywordResult" class="mb-4" style="display: none;"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Thông tin người gửi -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Thông tin người gửi</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <?php echo strtoupper(substr($request['username'], 0, 1)); ?>
                        </div>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0"><?php echo htmlspecialchars($request['full_name'] ?: $request['username']); ?></h6>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($request['email']); ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h4><?php echo $user_requests_count; ?></h4>
                        <p class="text-muted mb-0">Tổng yêu cầu</p>
                    </div>
                    <div class="col-6">
                        <h4><?php echo $user_approved_count; ?></h4>
                        <p class="text-muted mb-0">Đã duyệt</p>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <a href="<?php echo $base_url; ?>/admin/users/edit.php?id=<?php echo $request['user_id']; ?>" class="btn btn-sm btn-outline-primary d-block">
                        <i class="fas fa-user me-1"></i> Xem hồ sơ người dùng
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Các yêu cầu khác -->
        <?php if (!empty($other_requests)): ?>
        <div class="card shadow-sm">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Yêu cầu gần đây của người dùng</h6>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($other_requests as $other): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0">
                            <a href="<?php echo $base_url; ?>/admin/teachbot/view.php?id=<?php echo $other['id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($other['keyword']); ?>
                            </a>
                        </p>
                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($other['created_at'])); ?></small>
                    </div>
                    <span class="badge bg-<?php 
                        echo $other['status'] === 'pending' ? 'warning' : 
                            ($other['status'] === 'approved' ? 'success' : 'danger'); 
                    ?>">
                        <?php 
                        echo $other['status'] === 'pending' ? 'Chờ duyệt' : 
                            ($other['status'] === 'approved' ? 'Đã duyệt' : 'Từ chối'); 
                        ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Từ chối -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Từ chối yêu cầu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <input type="hidden" id="requestId" name="requestId" value="<?php echo $request['id']; ?>">
                    <div class="mb-3">
                        <label for="rejectReason" class="form-label">Lý do từ chối:</label>
                        <textarea class="form-control" id="rejectReason" name="rejectReason" rows="3" placeholder="Nhập lý do từ chối (tùy chọn)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="rejectRequest()">Từ chối</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Hàm kiểm tra từ khóa
function checkKeyword(keyword) {
    fetch('<?php echo $base_url; ?>/admin/teachbot/check_keyword.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'keyword=' + encodeURIComponent(keyword)
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('keywordResult');
        resultDiv.style.display = 'block';
        
        if (data.exists) {
            let html = '<div class="card border-warning">';
            html += '<div class="card-header bg-warning text-dark"><h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Từ khóa đã tồn tại!</h6></div>';
            html += '<div class="card-body">';
            html += '<p class="mb-2">Từ khóa này đã tồn tại trong hệ thống với các câu trả lời sau:</p>';
            html += '<ul>';
            data.replies.forEach(reply => {
                html += '<li>' + reply + '</li>';
            });
            html += '</ul>';
            html += '<p class="mb-0">Bạn vẫn có thể duyệt yêu cầu này để thêm một câu trả lời mới vào từ khóa.</p>';
            html += '</div></div>';
            resultDiv.innerHTML = html;
        } else {
            resultDiv.innerHTML = '<div class="card border-success"><div class="card-body"><i class="fas fa-check-circle me-2"></i> Từ khóa này chưa tồn tại trong hệ thống.</div></div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const resultDiv = document.getElementById('keywordResult');
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="card border-danger"><div class="card-body text-danger"><i class="fas fa-exclamation-circle me-2"></i> Có lỗi xảy ra khi kiểm tra từ khóa.</div></div>';
    });
}

// Hàm duyệt yêu cầu
function approveRequest(id) {
    Swal.fire({
        title: 'Xác nhận duyệt',
        text: 'Bạn có chắc chắn muốn duyệt yêu cầu này?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Duyệt',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            // Gửi yêu cầu duyệt
            fetch('<?php echo $base_url; ?>/admin/teachbot/process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=approve&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Thành công!',
                        text: data.message,
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: data.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Đã xảy ra lỗi khi xử lý yêu cầu',
                    icon: 'error'
                });
            });
        }
    });
}

// Hiển thị modal từ chối
function showRejectModal(id) {
    var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}

// Hàm từ chối yêu cầu
function rejectRequest() {
    var id = document.getElementById('requestId').value;
    var reason = document.getElementById('rejectReason').value;
    
    // Gửi yêu cầu từ chối
    fetch('<?php echo $base_url; ?>/admin/teachbot/process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=reject&id=' + id + '&reason=' + encodeURIComponent(reason)
    })
    .then(response => response.json())
    .then(data => {
        // Đóng modal
        var rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
        rejectModal.hide();
        
        if (data.status === 'success') {
            Swal.fire({
                title: 'Thành công!',
                text: data.message,
                icon: 'success'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Lỗi!',
                text: data.message,
                icon: 'error'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Lỗi!',
            text: 'Đã xảy ra lỗi khi xử lý yêu cầu',
            icon: 'error'
        });
    });
}

// Cập nhật hàm xử lý chỉnh sửa yêu cầu
document.addEventListener('DOMContentLoaded', function() {
    const saveChangesBtn = document.getElementById('saveChangesBtn');
    const checkEditedKeywordBtn = document.getElementById('checkEditedKeyword');
    
    if (saveChangesBtn) {
        saveChangesBtn.addEventListener('click', function() {
            const form = document.getElementById('editRequestForm');
            const requestId = form.querySelector('input[name="request_id"]').value;
            const keyword = form.querySelector('input[name="keyword"]').value;
            const reply = form.querySelector('textarea[name="reply"]').value;
            const impolite = form.querySelector('input[name="impolite"]').checked ? 1 : 0;
            
            if (!keyword.trim() || !reply.trim()) {
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Vui lòng nhập đầy đủ từ khóa và câu trả lời.',
                    icon: 'error'
                });
                return;
            }
            
            // Gửi dữ liệu cập nhật
            fetch('<?php echo $base_url; ?>/admin/teachbot/process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update&id=' + requestId + 
                      '&keyword=' + encodeURIComponent(keyword) + 
                      '&reply=' + encodeURIComponent(reply) + 
                      '&impolite=' + impolite
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Thành công!',
                        text: data.message,
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: data.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Đã xảy ra lỗi khi cập nhật yêu cầu',
                    icon: 'error'
                });
            });
        });
    }
    
    if (checkEditedKeywordBtn) {
        checkEditedKeywordBtn.addEventListener('click', function() {
            const keyword = document.getElementById('edit_keyword').value.trim();
            if (keyword) {
                checkKeyword(keyword);
            } else {
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Vui lòng nhập từ khóa để kiểm tra.',
                    icon: 'error'
                });
            }
        });
    }

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

    // Tạo bộ đếm cho từ khóa và câu trả lời
    const keywordInput = document.getElementById('edit_keyword');
    const replyInput = document.getElementById('edit_reply');

    if (keywordInput) createCharacterCounter(keywordInput, 250);
    if (replyInput) createCharacterCounter(replyInput, 250);
});
</script>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: white;
    font-weight: bold;
}

.border-left-primary {
    border-left: 4px solid #4e73df;
}
</style>

<?php include_once('../layout/footer.php'); ?> 
<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Kết nối đến config
require_once '../includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Kiểm tra nếu là tài khoản admin (id = 1)
if ($_SESSION['user_id'] == 1) {
    header('Location: ' . $base_url . '/index.php?error=admin_delete');
    exit();
}

// Xử lý xóa tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_POST['username'];
    
    // Kiểm tra lại một lần nữa để đảm bảo an toàn
    if ($user_id == 1) {
        $error_message = "Không thể xóa tài khoản này!";
        error_log("Attempt to delete admin account blocked");
    } else {
        // Debug
        error_log("Attempting to delete account for user_id: " . $user_id);
        error_log("Username provided: " . $username);
        error_log("Session username: " . $_SESSION['username']);
        
        // Kiểm tra username
        if ($username !== $_SESSION['username']) {
            $error_message = "Username không chính xác!";
            error_log("Username mismatch. Delete cancelled.");
        } else {
            try {
                // Bắt đầu transaction
                $pdo->beginTransaction();
                
                // Debug
                error_log("Starting transaction for user deletion");
                
                // Xóa tài khoản người dùng
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                error_log("Deleted user account for user_id: " . $user_id);
                
                // Commit transaction
                $pdo->commit();
                error_log("Transaction committed successfully");
                
                // Xóa session
                session_destroy();
                error_log("Session destroyed");
                
                // Chuyển hướng về trang chủ
                header('Location: ../index.php?deleted=1');
                exit();
                
            } catch (PDOException $e) {
                // Rollback nếu có lỗi
                $pdo->rollBack();
                $error_message = "Có lỗi xảy ra khi xóa tài khoản. Vui lòng thử lại sau.";
                error_log("Error deleting account: " . $e->getMessage());
            }
        }
    }
}

// Include header
require_once '../includes/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                        <h2 class="h3 mb-3">Xóa Tài Khoản</h2>
                        <p class="text-muted">Hành động này không thể hoàn tác. Vui lòng đọc kỹ thông tin bên dưới trước khi tiếp tục.</p>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h4 class="alert-heading h5 mb-3">Những điều cần biết:</h4>
                        <ul class="mb-0 ps-3">
                            <li>Tất cả dữ liệu của bạn sẽ bị xóa vĩnh viễn</li>
                            <li>Lịch sử chat và cài đặt sẽ bị mất</li>
                            <li>Không thể khôi phục tài khoản sau khi xóa</li>
                            <li>Bạn sẽ cần tạo tài khoản mới nếu muốn sử dụng lại dịch vụ</li>
                        </ul>
                    </div>

                    <form method="POST" action="" class="mt-4" id="deleteForm">
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                            <label class="form-check-label" for="confirmCheck">
                                Tôi hiểu rằng đây là hành động không thể hoàn tác và đồng ý xóa tài khoản của mình
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="fas fa-trash-alt me-2"></i>Xóa Tài Khoản
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-circle text-danger fa-3x mb-3"></i>
                    <p>Vui lòng nhập username của bạn để xác nhận xóa tài khoản.</p>
                </div>
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           placeholder="Type <?php echo htmlspecialchars($_SESSION['username']); ?> to delete">
                </div>
                <div class="countdown mt-3 text-center" style="display: none;">
                    <p class="mb-2">Tài khoản sẽ bị xóa sau:</p>
                    <h3 class="countdown-timer text-danger">5</h3>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('deleteForm');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const usernameInput = document.getElementById('username');
    const countdownDiv = document.querySelector('.countdown');
    const countdownTimer = document.querySelector('.countdown-timer');
    const deleteModal = document.getElementById('deleteModal');
    const modal = bootstrap.Modal.getInstance(deleteModal) || new bootstrap.Modal(deleteModal);
    
    let countdownInterval;
    
    confirmDeleteBtn.addEventListener('click', function() {
        const username = usernameInput.value.trim();
        const correctUsername = '<?php echo $_SESSION['username']; ?>';
        
        if (username !== correctUsername) {
            alert('Username không chính xác!');
            return;
        }
        
        // Ẩn nút xác nhận và hiển thị đếm ngược
        confirmDeleteBtn.style.display = 'none';
        countdownDiv.style.display = 'block';
        
        let count = 5;
        countdownTimer.textContent = count;
        
        countdownInterval = setInterval(() => {
            count--;
            countdownTimer.textContent = count;
            
            if (count <= 0) {
                clearInterval(countdownInterval);
                // Thêm input username và confirm_delete vào form
                const usernameField = document.createElement('input');
                usernameField.type = 'hidden';
                usernameField.name = 'username';
                usernameField.value = username;
                
                const confirmDeleteField = document.createElement('input');
                confirmDeleteField.type = 'hidden';
                confirmDeleteField.name = 'confirm_delete';
                confirmDeleteField.value = '1';
                
                deleteForm.appendChild(usernameField);
                deleteForm.appendChild(confirmDeleteField);
                
                // Submit form
                deleteForm.submit();
            }
        }, 1000);
    });
    
    // Reset modal khi đóng
    deleteModal.addEventListener('hidden.bs.modal', function() {
        usernameInput.value = '';
        confirmDeleteBtn.style.display = 'block';
        countdownDiv.style.display = 'none';
        clearInterval(countdownInterval);
    });
});
</script>

<style>
.card {
    border: none;
    border-radius: 15px;
}

.shadow-sm {
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}

.alert {
    border-radius: 10px;
}

.form-check-input:checked {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-danger {
    padding: 0.75rem 1.5rem;
}

.btn-outline-secondary {
    padding: 0.75rem 1.5rem;
}

.modal-content {
    border: none;
    border-radius: 15px;
}

.countdown-timer {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 0;
}

.form-control:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>

<?php
// Include footer
require_once '../includes/layouts/footer.php';
?>

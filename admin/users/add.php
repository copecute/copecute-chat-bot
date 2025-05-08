<?php
require_once '../check_admin.php';

// Kiểm tra quyền thêm người dùng - chỉ quản trị viên có quyền
$can_add_user = $is_admin; // Chỉ cấp 2 (quản trị viên) có quyền

$error = '';
$success = '';

// Lấy cài đặt hệ thống
try {
    // Lấy giá trị default_quota từ system_settings
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE category = 'chatbot' AND setting_key = 'default_quota'");
    $stmt->execute();
    $default_quota = $stmt->fetchColumn();
    
    // Nếu không tìm thấy, sử dụng giá trị mặc định 1000
    if ($default_quota === false) {
        $default_quota = 1000;
    }
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy cài đặt: ' . $e->getMessage();
    $default_quota = 1000; // Giá trị mặc định nếu có lỗi
}

// Xử lý thêm người dùng mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (!$can_add_user) {
        $error = 'Bạn không có quyền thêm người dùng mới!';
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $is_admin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0;
        $quota = isset($_POST['quota']) ? (int)$_POST['quota'] : $default_quota;
        
        // Kiểm tra đầu vào
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Vui lòng điền đầy đủ thông tin!';
        } elseif ($password !== $confirm_password) {
            $error = 'Mật khẩu nhập lại không khớp!';
        } elseif (strlen($password) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ!';
        } elseif ($quota < 0) {
            $error = 'Quota không thể là số âm!';
        } else {
            try {
                // Kiểm tra tên đăng nhập đã tồn tại chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
                $stmt->execute(['username' => $username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác!';
                } else {
                    // Kiểm tra email đã tồn tại chưa
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
                    $stmt->execute(['email' => $email]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Email đã tồn tại, vui lòng sử dụng email khác!';
                    } else {
                        // Mã hóa mật khẩu
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Bắt đầu transaction
                        $pdo->beginTransaction();
                        
                        // Thêm người dùng vào cơ sở dữ liệu
                        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, level) VALUES (:username, :email, :password, :level)');
                        $stmt->execute([
                            'username' => $username,
                            'email' => $email,
                            'password' => $password_hash,
                            'level' => $is_admin // 0 = user, 1 = manager, 2 = admin
                        ]);
                        
                        // Lấy id của người dùng vừa đăng ký
                        $user_id = $pdo->lastInsertId();
                        
                        // Tạo profile cơ bản cho người dùng
                        $stmt = $pdo->prepare('INSERT INTO user_infos (user_id) VALUES (:user_id)');
                        $stmt->execute(['user_id' => $user_id]);
                        
                        // Tạo token cho người dùng
                        $token = bin2hex(random_bytes(32));
                        $stmt = $pdo->prepare('INSERT INTO user_tokens (user_id, token, quota) VALUES (:user_id, :token, :quota)');
                        $stmt->execute([
                            'user_id' => $user_id,
                            'token' => $token,
                            'quota' => $quota
                        ]);
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        $success = 'Đã thêm người dùng mới thành công!';
                        
                        // Chuyển hướng về trang danh sách sau 1 giây
                        header('Refresh: 1; url=index.php');
                    }
                }
            } catch (PDOException $e) {
                // Rollback nếu có lỗi
                $pdo->rollBack();
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}

// Import header
require_once '../layout/header.php';
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Thêm người dùng mới</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/admin/users/index.php">Quản lý người dùng</a></li>
            <li class="breadcrumb-item active" aria-current="page">Thêm mới</li>
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

<!-- Form thêm người dùng -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-user-plus me-2"></i> Thông tin người dùng
                </h5>
            </div>
            <div class="card-body">
                <form method="post" action="" id="addUserForm">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="form-label fw-bold">
                                    Tên đăng nhập <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                </div>
                                <div class="form-text">Tên đăng nhập phải từ 3-20 ký tự, chỉ chứa chữ cái, số và dấu gạch dưới</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label fw-bold">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                                <div class="form-text">Email sẽ được sử dụng để đăng nhập và khôi phục mật khẩu</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label fw-bold">
                                    Mật khẩu <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label fw-bold">
                                    Xác nhận mật khẩu <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Nhập lại mật khẩu để xác nhận</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="quota" class="form-label fw-bold">
                                    Quota <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-chart-pie"></i>
                                    </span>
                                    <input type="number" class="form-control" id="quota" name="quota" 
                                           value="<?php echo htmlspecialchars((string)$default_quota) ?? 100; ?>" required min="0">
                                </div>
                                <div class="form-text">Số lượng tin nhắn được phép gửi</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold d-block">Vai trò</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_admin" id="role_user" value="0" checked>
                                    <label class="form-check-label" for="role_user">
                                        <i class="fas fa-user text-secondary me-1"></i> Người dùng
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_admin" id="role_manager" value="1">
                                    <label class="form-check-label" for="role_manager">
                                        <i class="fas fa-user-tie text-info me-1"></i> Quản lý
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_admin" id="role_admin" value="2">
                                    <label class="form-check-label" for="role_admin">
                                        <i class="fas fa-user-shield text-primary me-1"></i> Quản trị viên
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white border-top-0 text-end">
                <a href="<?php echo $base_url; ?>/admin/users/index.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i> Hủy
                </a>
                <button type="submit" form="addUserForm" name="add_user" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Thêm người dùng
                </button>
    </div>
        </div>
                </div>
                
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-info-circle me-2"></i> Hướng dẫn
                </h5>
            </div>
            <div class="card-body">
                <div class="guide-section mb-4">
                    <h6 class="guide-title mb-3">
                        <i class="fas fa-user text-primary me-2"></i>
                        Tên đăng nhập
                    </h6>
                    <ul class="guide-list ps-4 mb-0">
                        <li>Phải là duy nhất trong hệ thống</li>
                        <li>Chỉ chứa chữ cái, số và dấu gạch dưới</li>
                        <li>Độ dài từ 3-20 ký tự</li>
                    </ul>
                </div>
                
                <div class="guide-section mb-4">
                    <h6 class="guide-title mb-3">
                        <i class="fas fa-envelope text-info me-2"></i>
                        Email
                    </h6>
                    <ul class="guide-list ps-4 mb-0">
                        <li>Phải là email hợp lệ</li>
                        <li>Dùng để đăng nhập và khôi phục mật khẩu</li>
                        <li>Không được trùng với email đã tồn tại</li>
                    </ul>
                </div>
                
                <div class="guide-section mb-4">
                    <h6 class="guide-title mb-3">
                        <i class="fas fa-lock text-warning me-2"></i>
                        Mật khẩu
                    </h6>
                    <ul class="guide-list ps-4 mb-0">
                        <li>Tối thiểu 6 ký tự</li>
                        <li>Nên bao gồm chữ hoa, chữ thường và số</li>
                        <li>Tránh sử dụng thông tin cá nhân</li>
                    </ul>
                </div>
                
                <div class="guide-section">
                    <h6 class="guide-title mb-3">
                        <i class="fas fa-chart-pie text-success me-2"></i>
                        Quota & Vai trò
                    </h6>
                    <ul class="guide-list ps-4 mb-0">
                        <li>Quota mặc định là <?php echo htmlspecialchars((string)$default_quota) ?? 100; ?> tin nhắn</li>
                        <li>Có thể điều chỉnh tùy theo nhu cầu</li>
                        <li>Vai trò quyết định các chức năng có thể sử dụng</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Thêm style cho phần hướng dẫn */
.guide-section {
    position: relative;
    padding-left: 10px;
    border-left: 3px solid #e9ecef;
}

.guide-section:hover {
    border-left-color: #0d6efd;
}

.guide-title {
    font-weight: 600;
    color: #344767;
}

.guide-list {
    list-style-type: none;
    margin: 0;
}

.guide-list li {
    position: relative;
    padding-left: 20px;
    margin-bottom: 8px;
    color: #67748e;
}

.guide-list li:last-child {
    margin-bottom: 0;
}

.guide-list li::before {
    content: "";
    position: absolute;
    left: 0;
    top: 8px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #0d6efd;
}

.card {
    transition: all 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.input-group-text {
    background-color: #f8f9fa;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    // Toggle confirm password visibility
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPassword = document.getElementById('confirm_password');
    
    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    // Form validation
    const form = document.getElementById('addUserForm');
    form.addEventListener('submit', function(event) {
        if (password.value !== confirmPassword.value) {
            event.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Mật khẩu xác nhận không khớp',
                confirmButtonText: 'Đã hiểu'
            });
        }
    });
});
</script>

<?php
// Import footer
require_once '../layout/footer.php';
?> 
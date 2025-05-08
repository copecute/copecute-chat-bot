<?php
require_once '../check_admin.php';

// Kiểm tra quyền chỉnh sửa người dùng
$can_edit_user = true; // Cả quản lý và quản trị viên đều có thể chỉnh sửa

$error = '';
$success = '';

// Lấy cài đặt hệ thống
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE category = 'chatbot'");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy cài đặt: ' . $e->getMessage();
}

// Không cần xử lý đăng xuất ở đây nữa vì đã xử lý trong header.php

// Kiểm tra tham số id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$edit_id = (int)$_GET['id'];

// Kiểm tra người dùng quản trị (ID = 1) chỉ có thể được chỉnh sửa bởi chính họ hoặc quản trị viên khác
if ($edit_id == 1 && $user_id != 1 && !$is_admin) {
    header('Location: index.php?error=no_permission');
    exit;
}

try {
    // Lấy thông tin người dùng cần chỉnh sửa
    $stmt = $pdo->prepare('
        SELECT u.*, 
               u.level as is_admin,
               u.is_acctive as is_active,
               ui.full_name, ui.gender, ui.birthday, ui.address, ui.phone, ui.avatar,
               ut.quota as quota_limit,
               COALESCE((
                   SELECT COUNT(*) 
                   FROM chat_history 
                   WHERE user_id = u.id
               ), 0) as quota_used
        FROM users u
        LEFT JOIN user_infos ui ON u.id = ui.user_id
        LEFT JOIN user_tokens ut ON u.id = ut.user_id
        WHERE u.id = :id
    ');
    $stmt->execute(['id' => $edit_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Không tìm thấy người dùng
        header('Location: index.php?error=user_not_found');
        exit;
    }
    
    // Đặt giá trị mặc định cho các trường
    $user['quota_limit'] = $user['quota_limit'] ?? 100;
    $user['quota_used'] = $user['quota_used'] ?? 0;
    $user['is_admin'] = $user['is_admin'] ?? 0;
    $user['is_active'] = $user['is_active'] ?? 0;
    
    // Lấy thông tin profile của người dùng
    $stmt = $pdo->prepare('SELECT * FROM user_infos WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $edit_id]);
    $profile = $stmt->fetch();
    
    // Nếu không có profile, tạo một mảng trống
    if (!$profile) {
        $profile = [
            'full_name' => '',
            'gender' => 'other',
            'birthday' => null,
            'address' => '',
            'phone' => ''
        ];
    }
    
    // Lấy thông tin token/quota
    $stmt = $pdo->prepare('SELECT * FROM user_tokens WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $edit_id]);
    $token_info = $stmt->fetch();
    
    // Mặc định nếu không có token
    if (!$token_info) {
        $token_info = [
            'token' => '',
            'quota' => 0
        ];
    }
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    if (!$can_edit_user) {
        $error = 'Bạn không có quyền chỉnh sửa thông tin người dùng!';
    } else {
        try {
            // Thu thập dữ liệu từ form
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $gender = $_POST['gender'] ?? 'other';
            $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
            $address = trim($_POST['address'] ?? '');
            $is_admin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0;
            $quota = isset($_POST['quota']) ? (int)$_POST['quota'] : 100;
            
            // Kiểm tra email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email không hợp lệ!';
            } elseif ($quota < 0) {
                $error = 'Quota không thể là số âm!';
            } else {
                // Kiểm tra email đã tồn tại chưa (nếu thay đổi)
                if ($email != $user['email']) {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id != :id');
                    $stmt->execute(['email' => $email, 'id' => $edit_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Email đã tồn tại, vui lòng sử dụng email khác!';
                    }
                }
            }
            
            // Nếu không có lỗi, tiến hành cập nhật
            if (empty($error)) {
                $pdo->beginTransaction();
                
                // Cập nhật thông tin cơ bản
                $stmt = $pdo->prepare('UPDATE users SET email = :email, level = :level WHERE id = :id');
                $stmt->execute([
                    'email' => $email,
                    'level' => $is_admin,
                    'id' => $edit_id
                ]);
                
                // Kiểm tra và cập nhật/tạo mới profile
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_infos WHERE user_id = :user_id');
                $stmt->execute(['user_id' => $edit_id]);
                
                if ($stmt->fetchColumn() > 0) {
                    // Cập nhật profile
                    $stmt = $pdo->prepare('
                        UPDATE user_infos 
                        SET full_name = :full_name, 
                            gender = :gender, 
                            birthday = :birthday, 
                            address = :address, 
                            phone = :phone,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = :user_id
                    ');
                } else {
                    // Tạo mới profile
                    $stmt = $pdo->prepare('
                        INSERT INTO user_infos 
                        (user_id, full_name, gender, birthday, address, phone) 
                        VALUES (:user_id, :full_name, :gender, :birthday, :address, :phone)
                    ');
                }
                
                $stmt->execute([
                    'user_id' => $edit_id,
                    'full_name' => $full_name,
                    'gender' => $gender,
                    'birthday' => $birthday,
                    'address' => $address,
                    'phone' => $phone
                ]);
                
                // Cập nhật quota
                $stmt = $pdo->prepare('
                    UPDATE user_tokens 
                    SET quota = :quota 
                    WHERE user_id = :user_id
                ');
                    $stmt->execute([
                        'quota' => $quota,
                        'user_id' => $edit_id
                    ]);
                
                // Xử lý thay đổi mật khẩu (nếu có)
                if (!empty($_POST['new_password'])) {
                    if (strlen($_POST['new_password']) < 6) {
                        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
                    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                        $error = 'Mật khẩu mới nhập lại không khớp!';
                    } else {
                        $password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                        $stmt->execute(['password' => $password_hash, 'id' => $edit_id]);
                    }
                }
                
                if (empty($error)) {
                    $pdo->commit();
                    $success = 'Đã cập nhật thông tin người dùng thành công!';
                    
                    // Cập nhật lại thông tin hiển thị
                    $stmt = $pdo->prepare('
                        SELECT u.*, 
                               u.level as is_admin,
                               u.is_acctive as is_active,
                               ui.full_name, ui.gender, ui.birthday, ui.address, ui.phone, ui.avatar,
                               ut.quota as quota_limit,
                               COALESCE((
                                   SELECT COUNT(*) 
                                   FROM chat_history 
                                   WHERE user_id = u.id
                               ), 0) as quota_used
                        FROM users u
                        LEFT JOIN user_infos ui ON u.id = ui.user_id
                        LEFT JOIN user_tokens ut ON u.id = ut.user_id
                        WHERE u.id = :id
                    ');
                    $stmt->execute(['id' => $edit_id]);
                    $user = $stmt->fetch();
                    
                    // Đặt giá trị mặc định cho các trường
                    $user['quota_limit'] = $user['quota_limit'] ?? 100;
                    $user['quota_used'] = $user['quota_used'] ?? 0;
                    $user['is_admin'] = $user['is_admin'] ?? 0;
                    $user['is_active'] = $user['is_active'] ?? 0;
                    $user['full_name'] = $user['full_name'] ?? '';
                    $user['gender'] = $user['gender'] ?? 'other';
                    $user['birthday'] = $user['birthday'] ?? null;
                    $user['address'] = $user['address'] ?? '';
                    $user['phone'] = $user['phone'] ?? '';
                } else {
                    $pdo->rollBack();
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Xử lý reset quota
if (isset($_GET['reset_quota'])) {
    try {
        // Reset quota về mặc định từ cài đặt hệ thống
        $default_quota = $settings['default_quota'] ?? 100; // Sử dụng 100 nếu không tìm thấy trong settings
        $stmt = $pdo->prepare('UPDATE user_tokens SET quota = :quota WHERE user_id = :user_id');
        $stmt->execute([
            'quota' => $default_quota,
            'user_id' => $edit_id
        ]);
        $success = 'Đã reset quota thành công!';
        
        // Refresh lại trang để cập nhật thông tin
        header("Location: edit.php?id=" . $edit_id . "&success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Xử lý toggle status
if (isset($_GET['toggle_status'])) {
    try {
        $new_status = $_GET['toggle_status'];
        
        // Cập nhật trạng thái mới
        $stmt = $pdo->prepare('UPDATE users SET is_acctive = :status WHERE id = :id');
        $stmt->execute([
            'status' => $new_status,
            'id' => $edit_id
        ]);
        
        // Tạo message thông báo
        switch($new_status) {
            case '0':
                $message = 'chuyển về trạng thái chưa kích hoạt';
                break;
            case '1':
                $message = 'kích hoạt';
                break;
            case '2':
                $message = 'khóa vĩnh viễn';
                break;
            default:
                $message = 'cập nhật trạng thái';
        }
        
        $success = 'Đã ' . $message . ' tài khoản thành công!';
        
        // Refresh lại trang để cập nhật thông tin
        header("Location: edit.php?id=" . $edit_id . "&success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Thêm hàm helper để hiển thị trạng thái
function getUserStatusBadge($status) {
    switch($status) {
        case '0':
            return '<span class="badge bg-warning">Chưa kích hoạt</span>';
        case '1':
            return '<span class="badge bg-success">Đang hoạt động</span>';
        case '2':
            return '<span class="badge bg-danger">Đã khóa vĩnh viễn</span>';
        default:
            // Kiểm tra nếu là ngày (định dạng Y-m-d)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $status)) {
                $lock_date = DateTime::createFromFormat('Y-m-d', $status);
                $now = new DateTime();
                if ($lock_date > $now) {
                    // Chuyển đổi sang định dạng hiển thị d/m/Y
                    $display_date = $lock_date->format('d/m/Y');
                    return '<span class="badge bg-danger">Bị khóa đến ' . $display_date . '</span>';
                } else {
                    // Tự động mở khóa nếu đã hết hạn
                    global $pdo, $edit_id;
                    try {
                        $stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                        $stmt->execute(['id' => $edit_id]);
                        return '<span class="badge bg-success">Đang hoạt động</span>';
                    } catch (PDOException $e) {
                        return '<span class="badge bg-danger">Lỗi cập nhật trạng thái</span>';
                    }
                }
            }
            return '<span class="badge bg-secondary">Không xác định (' . $status . ')</span>';
    }
}

// Import header
require_once '../layout/header.php';
?>

<!-- Thêm SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Chỉnh sửa người dùng</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/admin/users/index.php">Quản lý người dùng</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa</li>
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

<!-- Form chỉnh sửa -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-user-edit me-2"></i> Thông tin người dùng
                    </h5>
                    <?php echo getUserStatusBadge($user['is_active']); ?>
    </div>
            </div>
            <div class="card-body">
                <form method="post" action="" id="editUserForm">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="form-label fw-bold">
                                    Tên đăng nhập
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
            </div>
                                <div class="form-text">Tên đăng nhập không thể thay đổi</div>
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
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="form-text">Email sẽ được sử dụng để đăng nhập và khôi phục mật khẩu</div>
            </div>
        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="new_password" class="form-label fw-bold">
                                    Mật khẩu mới
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Để trống nếu không muốn thay đổi mật khẩu</div>
                            </div>
    </div>
    
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label fw-bold">
                                    Xác nhận mật khẩu mới
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Nhập lại mật khẩu mới để xác nhận</div>
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
                                           value="<?php echo htmlspecialchars((string)($user['quota_limit'] ?? $settings['default_quota'] ?? 100)); ?>" 
                                           required min="0">
                                </div>
                                <div class="progress mt-2" style="height: 6px;">
                                    <?php 
                                    $quota_percent = $user['quota_limit'] > 0 ? ($user['quota_used'] / $user['quota_limit']) * 100 : 0;
                                    $quota_color = $quota_percent > 80 ? 'danger' : ($quota_percent > 50 ? 'warning' : 'success');
                                    ?>
                                    <div class="progress-bar bg-<?php echo $quota_color; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $quota_percent; ?>%"></div>
                                </div>
                                <div class="form-text">
                                    Đã sử dụng: <?php echo number_format($user['quota_used']); ?>/<?php echo number_format($user['quota_limit']); ?> tin nhắn
                                </div>
                            </div>
                    </div>
                    
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold d-block">Vai trò</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_admin" id="role_user" 
                                           value="0" <?php echo ($user['is_admin'] == 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="role_user">
                                        <i class="fas fa-user text-secondary me-1"></i> Người dùng
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_admin" id="role_manager" 
                                           value="1" <?php echo ($user['is_admin'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="role_manager">
                                        <i class="fas fa-user-tie text-info me-1"></i> Quản lý
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_admin" id="role_admin" 
                                           value="2" <?php echo ($user['is_admin'] == 2) ? 'checked' : ''; ?>>
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
                    <i class="fas fa-arrow-left me-2"></i> Quay lại
                </a>
                <button type="submit" form="editUserForm" name="update_user" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Lưu thay đổi
                </button>
            </div>
        </div>
                    </div>
                    
    <div class="col-lg-4">
        <!-- Thông tin tài khoản -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-info-circle me-2"></i> Thông tin tài khoản
                </h5>
                    </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">ID</span>
                        <span class="fw-bold"><?php echo $user['id']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Ngày đăng ký</span>
                        <span class="fw-bold"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Lần cuối đăng nhập</span>
                        <span class="fw-bold"><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập'; ?></span>
                    </li>
                </ul>
                    </div>
                </div>
                
        <!-- Thao tác nhanh -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-cog me-2"></i> Thao tác nhanh
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-warning" onclick="resetQuota()">
                        <i class="fas fa-redo me-2"></i> Reset Quota
                    </button>
                    <div class="btn-group">
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <?php if ($user['is_active'] === '0'): ?>
                                <button type="button" class="btn btn-success" onclick="toggleStatus(<?php echo $edit_id; ?>, '1')">
                                    <i class="fas fa-unlock me-2"></i> Kích hoạt tài khoản
                                </button>
                            <?php elseif ($user['is_active'] === '1'): ?>
                                <button type="button" class="btn btn-warning" onclick="showLockModal()">
                                    <i class="fas fa-clock me-2"></i> Khóa tạm thời
                                </button>
                                <button type="button" class="btn btn-danger" onclick="toggleStatus(<?php echo $edit_id; ?>, '2')">
                                    <i class="fas fa-lock me-2"></i> Khóa vĩnh viễn
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleStatus(<?php echo $edit_id; ?>, '0')">
                                    <i class="fas fa-ban me-2"></i> Chuyển về chưa kích hoạt
                                </button>
                            <?php elseif ($user['is_active'] === '2'): ?>
                                <button type="button" class="btn btn-success" onclick="toggleStatus(<?php echo $edit_id; ?>, '1')">
                                    <i class="fas fa-unlock me-2"></i> Mở khóa
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-success" onclick="toggleStatus(<?php echo $edit_id; ?>, '1')">
                                    <i class="fas fa-unlock me-2"></i> Mở khóa ngay
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleStatus(<?php echo $edit_id; ?>, '0')">
                                    <i class="fas fa-ban me-2"></i> Chuyển về chưa kích hoạt
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i> Không thể khóa tài khoản của chính mình
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($user['is_admin'] != 2): ?>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $user['id']; ?>)">
                        <i class="fas fa-trash me-2"></i> Xóa tài khoản
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
                    </div>
                </div>
                
<!-- Modal khóa tài khoản -->
<div class="modal fade" id="lockAccountModal" tabindex="-1" aria-labelledby="lockAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lockAccountModalLabel">Khóa tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    <div class="mb-3">
                    <label class="form-label">Chọn loại khóa:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="lock_type" id="lockTemporary" value="temporary" checked>
                        <label class="form-check-label" for="lockTemporary">
                            Khóa tạm thời
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="lock_type" id="lockPermanent" value="permanent">
                        <label class="form-check-label" for="lockPermanent">
                            Khóa vĩnh viễn
                        </label>
                    </div>
                </div>
                <div class="mb-3" id="lockDateGroup">
                    <label for="lockDate" class="form-label">Chọn ngày mở khóa:</label>
                    <input type="date" class="form-control" id="lockDate" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="submitLock()">Khóa tài khoản</button>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: all 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-group .btn {
    flex: 1;
    min-width: 120px;
}

.modal-content {
    border: none;
    border-radius: 0.5rem;
}

.modal-header {
    border-bottom: 1px solid rgba(0,0,0,.1);
    background-color: #f8f9fa;
    border-radius: 0.5rem 0.5rem 0 0;
}

.modal-footer {
    border-top: 1px solid rgba(0,0,0,.1);
    background-color: #f8f9fa;
    border-radius: 0 0 0.5rem 0.5rem;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>

<script>
function handleUserAction(action, userId, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('user_id', userId);
    
    // Thêm các dữ liệu bổ sung nếu có
    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value);
    }
    
    fetch('<?php echo $base_url; ?>/admin/users/user-control.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            Swal.fire({
                title: 'Thành công!',
                text: result.message,
                icon: 'success'
            }).then(() => {
                // Reload trang để cập nhật dữ liệu
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Lỗi!',
                text: result.message,
                icon: 'error'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Lỗi!',
            text: 'Có lỗi xảy ra khi xử lý yêu cầu',
            icon: 'error'
        });
    });
}

function resetQuota() {
    Swal.fire({
        title: 'Xác nhận reset quota?',
        text: 'Bạn có chắc chắn muốn đặt lại quota về mặc định không?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Reset',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            handleUserAction('reset_quota', <?php echo $edit_id; ?>);
        }
    });
}

function toggleStatus(userId, newStatus) {
    if (newStatus === '1') {
        Swal.fire({
            title: 'Xác nhận mở khóa?',
            text: 'Bạn có chắc chắn muốn mở khóa tài khoản này không?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Mở khóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                handleUserAction('unlock', userId);
            }
        });
    } else if (newStatus === '2') {
        Swal.fire({
            title: 'Xác nhận khóa vĩnh viễn?',
            text: 'Bạn có chắc chắn muốn khóa vĩnh viễn tài khoản này không?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Khóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                handleUserAction('lock', userId, { lock_type: 'permanent' });
            }
        });
    } else if (newStatus === '0') {
        Swal.fire({
            title: 'Xác nhận chuyển trạng thái?',
            text: 'Bạn có chắc chắn muốn chuyển tài khoản về trạng thái chưa kích hoạt không?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#0d6efd',
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                handleUserAction('lock', userId, { lock_type: 'inactive' });
            }
        });
    }
}

function confirmDelete(userId) {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: 'Bạn có chắc chắn muốn xóa tài khoản này? Hành động này không thể hoàn tác!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', userId);
            
            fetch('<?php echo $base_url; ?>/admin/users/user-control.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    Swal.fire({
                        title: 'Thành công!',
                        text: result.message,
                        icon: 'success'
                    }).then(() => {
                        // Chuyển hướng về trang danh sách người dùng
                        window.location.href = '../users';
                    });
                } else {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: result.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi xử lý yêu cầu',
                    icon: 'error'
                });
            });
        }
    });
}

function showLockModal() {
    const modal = new bootstrap.Modal(document.getElementById('lockAccountModal'));
    modal.show();
}

function submitLock() {
    const userId = <?php echo $edit_id; ?>;
    const lockType = document.querySelector('input[name="lock_type"]:checked').value;
    
    if (lockType === 'temporary') {
        const lockDate = document.getElementById('lockDate').value;
        if (!lockDate) {
            Swal.fire({
                title: 'Lỗi!',
                text: 'Vui lòng chọn ngày mở khóa',
                icon: 'error'
            });
            return;
        }
        handleUserAction('lock', userId, {
            lock_type: lockType,
            lock_date: lockDate
        });
    } else {
        handleUserAction('lock', userId, {
            lock_type: lockType
        });
    }
    
    // Đóng modal
    const lockModal = bootstrap.Modal.getInstance(document.getElementById('lockAccountModal'));
    lockModal.hide();
}

// Xử lý hiển thị/ẩn trường ngày khóa
document.getElementById('lockTemporary').addEventListener('change', function() {
    document.getElementById('lockDateGroup').style.display = 'block';
});

document.getElementById('lockPermanent').addEventListener('change', function() {
    document.getElementById('lockDateGroup').style.display = 'none';
});
</script>

<?php
// Import footer
require_once '../layout/footer.php';
?> 
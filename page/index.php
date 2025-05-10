<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

session_start();
require_once './includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$error = '';
$success = '';

try {
    // Lấy thông tin người dùng
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch();
    
    // Lấy thông tin profile của người dùng
    $stmt = $pdo->prepare('SELECT * FROM user_infos WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $profile = $stmt->fetch();
    
    // Lấy token/quota của người dùng
    $stmt = $pdo->prepare('SELECT quota FROM user_tokens WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $token_info = $stmt->fetch();
    
    $quota = $token_info ? $token_info['quota'] : 0;
    $quota = max(0, $quota); // Đảm bảo quota không âm
    
    // Sử dụng avatar từ profile hoặc mặc định
    $avatar_url = !empty($profile['avatar']) ? $profile['avatar'] : '/assets/img/avatar/default.png';
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Cập nhật avatar nếu được gửi từ form
        if (isset($_POST['avatar_url']) && !empty($_POST['avatar_url'])) {
            $avatar_url = $_POST['avatar_url'];
            
            $stmt = $pdo->prepare('UPDATE user_infos SET avatar = :avatar WHERE user_id = :user_id');
            $stmt->execute([
                'avatar' => $avatar_url,
                'user_id' => $user_id
            ]);
        }
        
        // Thông tin cơ bản
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = $_POST['gender'] ?? 'other';
        $address = trim($_POST['address'] ?? '');
        $birthday = $_POST['birthday'] ?? null;
        
        // Cập nhật thông tin cá nhân
        $stmt = $pdo->prepare('
            UPDATE user_infos 
            SET full_name = :full_name, phone = :phone, gender = :gender, 
                address = :address, birthday = :birthday
            WHERE user_id = :user_id
        ');
        
        $stmt->execute([
            'full_name' => $full_name,
            'phone' => $phone,
            'gender' => $gender,
            'address' => $address,
            'birthday' => $birthday,
            'user_id' => $user_id
        ]);
        
        // Cập nhật email
        if (!empty($_POST['email']) && $_POST['email'] !== $user['email']) {
            $email = trim($_POST['email']);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email không hợp lệ!';
            } else {
                // Kiểm tra email đã tồn tại chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id != :user_id');
                $stmt->execute(['email' => $email, 'user_id' => $user_id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email đã tồn tại, vui lòng sử dụng email khác!';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET email = :email WHERE id = :user_id');
                    $stmt->execute(['email' => $email, 'user_id' => $user_id]);
                }
            }
        }
        
        // Thay đổi mật khẩu (nếu có)
        if (!empty($_POST['new_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password)) {
                $error = 'Vui lòng nhập mật khẩu hiện tại!';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Mật khẩu hiện tại không chính xác!';
            } elseif (strlen($new_password) < 6) {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Mật khẩu mới nhập lại không khớp!';
            } else {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :user_id');
                $stmt->execute(['password' => $password_hash, 'user_id' => $user_id]);
            }
        }
        
        // Làm mới dữ liệu sau khi cập nhật
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch();
        
        $stmt = $pdo->prepare('SELECT * FROM user_infos WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        $profile = $stmt->fetch();
        
        // Cập nhật avatar_url từ database
        $avatar_url = !empty($profile['avatar']) ? $profile['avatar'] : '/assets/img/avatar/default.png';
        
        if (empty($error)) {
            $success = 'Cập nhật thông tin thành công!';
        }
        
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Include header
require_once './includes/layouts/header.php';
?>

    <style>
    /* Màu sắc và biến chung */
    :root {
        --primary-gradient: linear-gradient(135deg, #4e73df 0%, #3a5dd6 100%);
        --secondary-gradient: linear-gradient(135deg, #1cc88a 0%, #16a673 100%);
        --accent-gradient: linear-gradient(135deg, #f6c23e 0%, #f4b619 100%);
        --card-shadow: 0 10px 20px rgba(0,0,0,0.08);
        --avatar-border: 5px solid white;
    }
    
    .profile-wrapper {
        background-color: #f8f9fc;
        border-radius: 20px;
        padding: 20px;
        max-width: 1100px;
        margin: 30px auto;
    }
    
    .profile-header {
        position: relative;
        background: white;
        overflow: hidden;
        border-radius: 15px;
        margin-bottom: 25px;
        box-shadow: var(--card-shadow);
        padding: 30px;
    }
    
    .profile-cover-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .profile-avatar-section {
        display: flex;
        align-items: center;
    }
    
    .avatar-container {
        position: relative;
        margin-right: 20px;
    }
    
    .avatar-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #4e73df;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .avatar-upload {
        position: absolute;
        bottom: 10px;
        right: 5px;
        background: var(--primary-gradient);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        border: 2px solid white;
        box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        transition: all 0.3s;
    }
    
    .avatar-upload:hover {
        transform: scale(1.1);
    }
    
    .profile-info {
        padding-bottom: 0;
    }
    
    .profile-name {
        font-size: 24px;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }
    
    .profile-email {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .profile-role {
        display: inline-block;
        background: var(--primary-gradient);
        color: white;
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .profile-stats {
        display: flex;
        margin-bottom: 0;
        padding-right: 20px;
    }
    
    .stats-item {
        text-align: center;
        margin-left: 25px;
        position: relative;
    }
    
    .stats-item:not(:last-child):after {
        content: '';
        position: absolute;
        right: -15px;
        top: 50%;
        transform: translateY(-50%);
        height: 70%;
        width: 1px;
        background-color: rgba(0,0,0,0.1);
    }
    
    .stats-value {
        font-size: 22px;
        font-weight: 700;
        color: #4e73df;
        line-height: 1;
    }
    
    .stats-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .profile-content {
        display: flex;
        flex-wrap: wrap;
        gap: 25px;
    }
    
    .content-card {
            background: white;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .content-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    
    .content-card-header {
        padding: 20px 25px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }
    
    .card-body {
        padding: 25px;
    }
    
    .card-row {
        margin-bottom: 20px;
    }
    
    .tabs-container {
        background-color: #f8f9fc;
            border-radius: 10px;
        padding: 5px;
        display: flex;
        margin-bottom: 20px;
    }
    
    .tab-item {
        flex: 1;
        text-align: center;
        padding: 12px 15px;
        font-weight: 600;
        color: #666;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.3s;
    }
    
    .tab-item.active {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 3px 10px rgba(78, 115, 223, 0.3);
    }
    
    .form-control {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        padding: 12px 15px;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    
    .input-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
    }
    
    .form-group-icon .form-control {
        padding-left: 45px;
    }
    
    .profile-form-submit {
        text-align: right;
        margin-top: 30px;
    }
    
    .btn-submit {
        background: var(--primary-gradient);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        transition: all 0.3s;
    }
    
    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(78, 115, 223, 0.4);
    }
    
    .btn-submit:active {
        transform: translateY(-1px);
    }
    
    .btn-submit i {
        margin-right: 8px;
    }
    
    /* Responsive */
    @media (max-width: 991px) {
        .profile-cover-content {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .profile-avatar-section {
            flex-direction: column;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .avatar-container {
            margin-right: 0;
            margin-bottom: 15px;
        }
        
        .profile-info {
            margin-bottom: 15px;
        }
        
        .profile-stats {
            justify-content: center;
            padding-right: 0;
        }
    }
    
    @media (max-width: 576px) {
        .profile-wrapper {
            padding: 10px;
            margin: 15px auto;
        }
        
        .avatar-image {
            width: 120px;
            height: 120px;
        }
        
        .profile-name {
            font-size: 20px;
        }
        
        .stats-item {
            margin-left: 15px;
        }
        
        .card-body {
            padding: 20px;
        }
        }
    </style>

<div class="profile-wrapper">
    <!-- Thông báo -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header hồ sơ -->
            <div class="profile-header">
        <div class="profile-cover-content">
            <div class="profile-avatar-section">
                <div class="avatar-container">
                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" class="avatar-image" id="avatarPreview">
                    <div class="avatar-upload" id="upload" title="Tải lên avatar mới">
                        <i class="fas fa-camera"></i>
                    </div>
                    <input style="display:none" type="file" id="f" accept="image/*">
                </div>
                
                <div class="profile-info">
                    <h2 class="profile-name"><?php echo htmlspecialchars($username); ?></h2>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    <div class="profile-role">
                        <i class="fas fa-user-tag me-1"></i> <?php echo getUserRoleName($user_level); ?>
                    </div>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stats-item">
                    <div class="stats-value"><?php echo $quota; ?></div>
                    <div class="stats-label">Quota</div>
                </div>
                
                <div class="stats-item">
                    <div class="stats-value"><?php echo $profile && $profile['created_at'] ? date('d/m/y', strtotime($profile['created_at'])) : '-'; ?></div>
                    <div class="stats-label">Ngày tạo</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Nội dung hồ sơ -->
    <div class="profile-content">
        <!-- Thông tin cá nhân -->
        <div class="content-card w-100">
            <div class="content-card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-circle me-2"></i>Hồ sơ cá nhân
                </h3>
            </div>
            
            <div class="card-body">
                <div class="tabs-container" id="profileTabs">
                    <div class="tab-item active" data-target="info-tab">
                        <i class="fas fa-user me-2"></i>Thông tin
                    </div>
                    <div class="tab-item" data-target="security-tab">
                        <i class="fas fa-lock me-2"></i>Bảo mật
                    </div>
                </div>
                
                <!-- Tab thông tin cá nhân -->
                <div class="tab-content" id="info-tab">
                    <form method="post" action="" id="profileForm">
                        <input type="hidden" name="avatar_url" id="avatar_url" value="<?php echo htmlspecialchars($avatar_url); ?>">
                        
                        <div class="row card-row">
                            <div class="col-md-6 mb-4">
                                <label for="username" class="form-label fw-bold">Tên đăng nhập</label>
                                <div class="position-relative form-group-icon">
                                    <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                                </div>
                            <small class="text-muted">Tên đăng nhập không thể thay đổi</small>
                        </div>
                        
                            <div class="col-md-6 mb-4">
                                <label for="email" class="form-label fw-bold">Email</label>
                                <div class="position-relative form-group-icon">
                                    <i class="fas fa-envelope input-icon"></i>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row card-row">
                            <div class="col-md-6 mb-4">
                                <label for="full_name" class="form-label fw-bold">Họ và tên</label>
                                <div class="position-relative form-group-icon">
                                    <i class="fas fa-id-card input-icon"></i>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">
                                </div>
                        </div>
                        
                            <div class="col-md-6 mb-4">
                                <label for="phone" class="form-label fw-bold">Số điện thoại</label>
                                <div class="position-relative form-group-icon">
                                    <i class="fas fa-phone input-icon"></i>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row card-row">
                            <div class="col-md-6 mb-4">
                                <label for="gender" class="form-label fw-bold">Giới tính</label>
                                <div class="position-relative form-group-icon">
                                    <i class="fas fa-venus-mars input-icon"></i>
                                    <select class="form-select form-control" id="gender" name="gender">
                                <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Nam</option>
                                <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Nữ</option>
                                <option value="other" <?php echo ($profile['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Khác</option>
                            </select>
                                </div>
                        </div>
                        
                            <div class="col-md-6 mb-4">
                                <label for="birthday" class="form-label fw-bold">Ngày sinh</label>
                                <div class="position-relative form-group-icon">
                                    <i class="fas fa-birthday-cake input-icon"></i>
                            <input type="date" class="form-control" id="birthday" name="birthday" value="<?php echo htmlspecialchars($profile['birthday'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-row">
                            <label for="address" class="form-label fw-bold">Địa chỉ</label>
                            <div class="position-relative form-group-icon">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="profile-form-submit">
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-save"></i> Cập nhật thông tin
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Tab bảo mật -->
                <div class="tab-content" id="security-tab" style="display: none;">
                    <form method="post" action="">
                        <div class="card-row">
                            <label for="current_password" class="form-label fw-bold">Mật khẩu hiện tại</label>
                            <div class="position-relative form-group-icon">
                                <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                        </div>
                        
                        <div class="card-row">
                            <label for="new_password" class="form-label fw-bold">Mật khẩu mới</label>
                            <div class="position-relative form-group-icon">
                                <i class="fas fa-key input-icon"></i>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <small class="text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                        </div>
                        
                        <div class="card-row">
                            <label for="confirm_password" class="form-label fw-bold">Xác nhận mật khẩu mới</label>
                            <div class="position-relative form-group-icon">
                                <i class="fas fa-check-circle input-icon"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="profile-form-submit">
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-shield-alt"></i> Thay đổi mật khẩu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script Imgur Upload -->
<script src="/assets/script/imgur.js" type="text/javascript"></script>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý tabs
        const tabs = document.querySelectorAll('.tab-item');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Xóa active class từ tất cả tabs
                tabs.forEach(t => t.classList.remove('active'));
                // Thêm active class cho tab được click
                this.classList.add('active');
                
                // Ẩn tất cả nội dung
                const tabContents = document.querySelectorAll('.tab-content');
                tabContents.forEach(content => {
                    content.style.display = 'none';
                });
                
                // Hiển thị nội dung tương ứng
                const targetId = this.getAttribute('data-target');
                document.getElementById(targetId).style.display = 'block';
            });
        });
        
        // Khai báo các phần tử
        const uploadBtn = document.querySelector("#upload");
        const fileInput = document.querySelector("#f");
        const avatarPreview = document.querySelector("#avatarPreview");
        const avatarUrlInput = document.querySelector("#avatar_url");
        
        // Upload avatar khi click
        if (uploadBtn) {
            uploadBtn.onclick = function() {
                fileInput.click();
            }
        }
        
        // Xử lý upload ảnh lên Imgur
        imgur("#f", {
            loading: function(load) {
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            },
            loaded: function(link, type, size, time) {
                // Cập nhật ảnh đại diện ngay lập tức
                avatarPreview.src = link;
                
                // Cập nhật giá trị input ẩn
                avatarUrlInput.value = link;
                
                // Reset nút upload
                uploadBtn.innerHTML = '<i class="fas fa-camera"></i>';
                
                // Hiển thị thông báo
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i> <strong>Thành công!</strong> Đã tải lên avatar mới. Vui lòng nhấn "Cập nhật thông tin" để lưu thay đổi.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Thêm thông báo vào đầu trang
                document.querySelector('.profile-wrapper').prepend(alertDiv);
                
                // Tự động đóng thông báo sau 5 giây
                setTimeout(function() {
                    alertDiv.classList.remove('show');
                    setTimeout(function() {
                        alertDiv.remove();
                    }, 150);
                }, 5000);
            }
        });
    });
</script>

<?php
// Include footer
require_once './includes/layouts/footer.php';
?> 
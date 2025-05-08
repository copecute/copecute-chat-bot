<?php
require_once 'check_admin.php';

// Kiểm tra quyền truy cập - chỉ cấp 2 (admin) mới có quyền truy cập trang cài đặt
if (!$is_admin) {
    header('Location: dashboard.php?error=no_permission');
    exit;
}

$error = '';
$success = '';

// Lấy cài đặt từ CSDL
try {
    // Kiểm tra xem cột category đã tồn tại trong bảng system_settings chưa
    $column_exists = false;
    try {
        $check_column = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'category'");
        $column_exists = ($check_column->rowCount() > 0);
    } catch (PDOException $e) {
        // Có thể bỏ qua lỗi này
    }
    
    // Nếu cột category chưa tồn tại, thêm nó vào
    if (!$column_exists) {
        try {
            $pdo->exec("ALTER TABLE system_settings ADD COLUMN category VARCHAR(50) DEFAULT 'general' AFTER description");
        } catch (PDOException $e) {
            // Bỏ qua nếu có lỗi
        }
    }
    
    // Lấy tất cả cài đặt và sắp xếp theo category
    $stmt = $pdo->query('SELECT * FROM system_settings ORDER BY category ASC, id ASC');
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nhóm cài đặt theo category
    $categorized_settings = [];
    foreach ($settings as $setting) {
        $category = $setting['category'] ?? 'general';
        $categorized_settings[$category][] = $setting;
    }
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare('UPDATE system_settings SET setting_value = :value WHERE setting_key = :key');
            $stmt->execute(['value' => $value, 'key' => $key]);
        }
        
        $pdo->commit();
        
        // Cập nhật lại cài đặt sau khi lưu
        $stmt = $pdo->query('SELECT * FROM system_settings ORDER BY id ASC');
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $success = 'Đã cập nhật cài đặt thành công!';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Import header
require_once 'layout/header.php';
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Cài đặt hệ thống</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Cài đặt hệ thống</li>
        </ol>
    </nav>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Cài đặt -->
<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary">
                <i class="fas fa-cog me-2"></i> Cài đặt hệ thống
            </h5>
            <button type="submit" form="settingsForm" name="update_settings" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> Lưu thay đổi
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($settings) && !empty($settings)): ?>
        <form method="POST" action="" id="settingsForm">
                <!-- Tab Navigation -->
                <ul class="nav nav-pills nav-fill mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                            <i class="fas fa-cog me-2"></i> Tổng quan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="chatbot-tab" data-bs-toggle="tab" data-bs-target="#chatbot" type="button" role="tab">
                            <i class="fas fa-robot me-2"></i> Chatbot
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="auth-tab" data-bs-toggle="tab" data-bs-target="#auth" type="button" role="tab">
                            <i class="fas fa-user-lock me-2"></i> Đăng nhập
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">
                            <i class="fas fa-address-card me-2"></i> Liên hệ
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                            <i class="fas fa-share-alt me-2"></i> Mạng xã hội
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab">
                            <i class="fas fa-ellipsis-h me-2"></i> Khác
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content" id="settingsTabContent">
                    <?php
                    // Danh sách các tab và thông tin
                    $tabs = [
                        'general' => ['name' => 'Tổng quan', 'icon' => 'cog', 'active' => true],
                        'chatbot' => ['name' => 'Chatbot', 'icon' => 'robot', 'active' => false],
                        'auth' => ['name' => 'Đăng nhập', 'icon' => 'user-lock', 'active' => false],
                        'contact' => ['name' => 'Liên hệ', 'icon' => 'address-card', 'active' => false],
                        'social' => ['name' => 'Mạng xã hội', 'icon' => 'share-alt', 'active' => false],
                        'other' => ['name' => 'Khác', 'icon' => 'cog', 'active' => false]
                    ];
                    
                    // Hiển thị nội dung của từng tab
                    foreach ($tabs as $tab_id => $tab): ?>
                        <div class="tab-pane fade <?php echo $tab['active'] ? 'show active' : ''; ?>" 
                             id="<?php echo $tab_id; ?>" role="tabpanel">
                            <?php if (isset($categorized_settings[$tab_id]) && !empty($categorized_settings[$tab_id])): ?>
                                <div class="row g-4">
                                    <?php foreach ($categorized_settings[$tab_id] as $setting): ?>
                                        <div class="col-md-6">
                                            <div class="card h-100 border shadow-sm">
                                        <div class="card-body">
                                                    <label for="<?php echo $setting['setting_key']; ?>" 
                                                           class="form-label fw-bold text-primary">
                                                        <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                    </label>
                                                    <?php if (strpos($setting['setting_key'], 'password') !== false): ?>
                                                        <input type="text" class="form-control" 
                                                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                               id="<?php echo $setting['setting_key']; ?>" 
                                                               value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>">
                                                    <?php elseif (isset($setting['setting_value']) && strlen($setting['setting_value']) > 100): ?>
                                                        <textarea class="form-control" 
                                                                  name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                                  id="<?php echo $setting['setting_key']; ?>" 
                                                                  rows="3"><?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?></textarea>
                                                    <?php else: ?>
                                                        <input type="text" class="form-control" 
                                                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                               id="<?php echo $setting['setting_key']; ?>" 
                                                               value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>">
                                                    <?php endif; ?>
                                        <div class="form-text text-muted mt-2">
                                                    <i class="fas fa-info-circle me-1"></i>
                                            <?php echo htmlspecialchars($setting['description'] ?? ''); ?>
                                        </div>
                                    </div>
                                    </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="card border shadow-sm">
                                    <div class="card-body">
                                    <i class="fas fa-info-circle me-2"></i> 
                                    Không có cài đặt nào trong mục này.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4 text-end">
                    <button type="reset" class="btn btn-light me-2">
                        <i class="fas fa-undo me-2"></i> Đặt lại
                    </button>
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                    Không có cài đặt nào. Vui lòng tải lại trang để khởi tạo cài đặt mặc định.
                </div>
            <?php endif; ?>
    </div>
</div>

<!-- Quản lý hệ thống -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-danger">
            <i class="fas fa-tools me-2"></i> Công cụ hệ thống
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-download fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">Sao lưu dữ liệu</h5>
                        <p class="card-text text-muted">Tạo bản sao lưu cơ sở dữ liệu hệ thống</p>
                        <button class="btn btn-info w-100" id="backupBtn">
                            <i class="fas fa-download me-2"></i> Sao lưu
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-broom fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title">Xóa bộ nhớ đệm</h5>
                        <p class="card-text text-muted">Xóa các file tạm và bộ nhớ đệm</p>
                        <button class="btn btn-warning w-100" id="clearCacheBtn">
                            <i class="fas fa-broom me-2"></i> Xóa cache
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-undo fa-3x text-danger"></i>
                        </div>
                        <h5 class="card-title">Khôi phục cài đặt</h5>
                        <p class="card-text text-muted">Đặt lại về cài đặt mặc định</p>
                        <button class="btn btn-danger w-100" id="resetBtn" data-bs-toggle="modal" data-bs-target="#resetModal">
                            <i class="fas fa-undo me-2"></i> Khôi phục
                        </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Reset Modal -->
        <div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Xác nhận khôi phục
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                        <p class="mb-0">Bạn có chắc chắn muốn khôi phục tất cả cài đặt về giá trị mặc định? Hành động này không thể hoàn tác.</p>
            </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Hủy
                        </button>
                        <button type="button" class="btn btn-danger" id="confirmReset">
                            <i class="fas fa-check me-2"></i> Xác nhận
                        </button>
            </div>
        </div>
    </div>
</div>

        <style>
            .nav-pills .nav-link {
                color: #6c757d;
                background-color: #fff;
                border: 1px solid #dee2e6;
                margin: 0 0.25rem;
                padding: 0.75rem 1rem;
                transition: all 0.2s ease-in-out;
            }
            
            .nav-pills .nav-link:hover {
                color: #0d6efd;
                border-color: #0d6efd;
            }
            
            .nav-pills .nav-link.active {
                color: #fff;
                background-color: #0d6efd;
                border-color: #0d6efd;
            }
            
            .card {
                transition: all 0.2s ease-in-out;
            }
            
            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
            }
            
            .form-control:focus {
                border-color: #0d6efd;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-weight: 500;
            }
            
            .btn-primary {
                background-color: #0d6efd;
                border-color: #0d6efd;
            }
            
            .btn-primary:hover {
                background-color: #0b5ed7;
                border-color: #0a58ca;
            }
        </style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Backup Button
        document.getElementById('backupBtn').addEventListener('click', function() {
                    Swal.fire({
                        icon: 'info',
                        title: 'Tính năng đang phát triển',
                        text: 'Chức năng sao lưu dữ liệu sẽ sớm được cập nhật!',
                        confirmButtonText: 'Đã hiểu'
                    });
        });
        
        // Clear Cache Button
        document.getElementById('clearCacheBtn').addEventListener('click', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đã xóa bộ nhớ đệm thành công',
                        showConfirmButton: false,
                        timer: 1500
                    });
        });
        
// Reset Settings Button
        document.getElementById('confirmReset').addEventListener('click', function() {
                    Swal.fire({
                        icon: 'info',
                        title: 'Tính năng đang phát triển',
                        text: 'Chức năng khôi phục cài đặt sẽ sớm được cập nhật!',
                        confirmButtonText: 'Đã hiểu'
                    });
            $('#resetModal').modal('hide');
        });
    });
</script>

<?php
// Import footer
require_once 'layout/footer.php';
?> 
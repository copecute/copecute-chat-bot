<?php
require_once 'check_admin.php';

// Kiểm tra quyền thay đổi chế độ bảo trì
$can_change_maintenance = $is_admin; // Chỉ cấp 2 (quản trị viên) có quyền

$error = '';
$success = '';

// Lấy trạng thái bảo trì hiện tại
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $maintenance_mode = $stmt->fetchColumn();
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
    $maintenance_mode = '0'; // Mặc định tắt
}

// Xử lý bật/tắt chế độ bảo trì
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_maintenance'])) {
    if (!$can_change_maintenance) {
        $error = 'Bạn không có quyền thay đổi chế độ bảo trì!';
    } else {
        $new_status = ($maintenance_mode == '1') ? '0' : '1';
        
        try {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = :value WHERE setting_key = 'maintenance_mode'");
            $stmt->execute(['value' => $new_status]);
            
            $maintenance_mode = $new_status;
            $success = 'Đã ' . ($new_status == '1' ? 'bật' : 'tắt') . ' chế độ bảo trì thành công!';
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Import header
require_once 'layout/header.php';
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý Chế độ Bảo trì</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chế độ Bảo trì</li>
        </ol>
    </nav>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Trạng thái Bảo trì</h6>
                <?php if ($maintenance_mode == '1'): ?>
                    <span class="badge bg-danger">Đang bật</span>
                <?php else: ?>
                    <span class="badge bg-success">Đang tắt</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="mb-4 text-center">
                    <?php if ($maintenance_mode == '1'): ?>
                        <div class="maintenance-status-icon mb-3">
                            <i class="fas fa-exclamation-triangle fa-6x text-danger"></i>
                        </div>
                        <h5 class="text-danger mb-3">Chế độ bảo trì đang BẬT</h5>
                        <p class="text-muted">
                            Khi chế độ bảo trì được bật, chỉ quản trị viên và quản lý mới có thể truy cập hệ thống.
                            <br>Người dùng thông thường sẽ thấy trang thông báo bảo trì.
                        </p>
                    <?php else: ?>
                        <div class="maintenance-status-icon mb-3">
                            <i class="fas fa-check-circle fa-6x text-success"></i>
                        </div>
                        <h5 class="text-success mb-3">Chế độ bảo trì đang TẮT</h5>
                        <p class="text-muted">
                            Hệ thống đang hoạt động bình thường và tất cả người dùng có thể truy cập.
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($can_change_maintenance): ?>
                        <form method="POST">
                            <button type="submit" name="toggle_maintenance" class="btn btn-lg <?php echo $maintenance_mode == '1' ? 'btn-success' : 'btn-danger'; ?>">
                                <?php if ($maintenance_mode == '1'): ?>
                                    <i class="fas fa-toggle-off me-2"></i> Tắt chế độ bảo trì
                                <?php else: ?>
                                    <i class="fas fa-toggle-on me-2"></i> Bật chế độ bảo trì
                                <?php endif; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> Bạn không có quyền thay đổi chế độ bảo trì. Chỉ quản trị viên mới có quyền này.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Xem trước trang bảo trì</h6>
            </div>
            <div class="card-body p-0">
                <iframe src="<?php echo $base_url; ?>/admin/preview_maintenance.php" style="width:100%; height:300px; border:none;"></iframe>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Thông tin</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h5 class="text-gray-800"><i class="fas fa-question-circle me-2 text-primary"></i> Chế độ bảo trì là gì?</h5>
                    <p class="text-muted">
                        Chế độ bảo trì cho phép bạn tạm thời đóng cửa trang web đối với người dùng thông thường
                        trong khi vẫn cho phép quản trị viên và quản lý truy cập vào hệ thống.
                        Điều này rất hữu ích khi bạn cần thực hiện các thay đổi lớn, nâng cấp cơ sở dữ liệu,
                        hoặc giải quyết các vấn đề về hệ thống.
                    </p>
                </div>
                
                <div>
                    <h5 class="text-gray-800"><i class="fas fa-users me-2 text-primary"></i> Ai có thể truy cập khi bật chế độ bảo trì?</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Cấp độ người dùng</th>
                                    <th>Truy cập khi bảo trì</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-primary">Quản trị viên (cấp 2)</span></td>
                                    <td><i class="fas fa-check text-success"></i> Toàn quyền truy cập</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info">Quản lý (cấp 1)</span></td>
                                    <td><i class="fas fa-check text-success"></i> Toàn quyền truy cập</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">Người dùng (cấp 0)</span></td>
                                    <td><i class="fas fa-times text-danger"></i> Thấy trang bảo trì</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Import footer
require_once 'layout/footer.php';
?> 
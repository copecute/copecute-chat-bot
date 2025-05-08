<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Kết nối đến config
require_once 'includes/config.php';

// Khởi tạo biến chứa dữ liệu liên hệ
$contact_settings = [];
$error_message = '';

// Lấy thông tin liên hệ từ cài đặt
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE category IN ('contact', 'social')");
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        $contact_settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Kiểm tra xem đã có đủ thông tin liên hệ chưa
    $required_keys = ['contact_email', 'support_email', 'contact_phone', 'contact_address', 'working_hours'];
    foreach ($required_keys as $key) {
        if (!isset($contact_settings[$key])) {
            throw new Exception("Thiếu thông tin cài đặt: $key");
        }
    }
    
    // Phân tách địa chỉ và giờ làm việc
    $address_parts = explode(',', $contact_settings['contact_address']);
    $working_hours_parts = explode(',', $contact_settings['working_hours']);
} catch (Exception $e) {
    $error_message = 'Không thể tải thông tin liên hệ: ' . $e->getMessage();
} catch (PDOException $e) {
    $error_message = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
}

// Include header
require_once 'includes/layouts/header.php';
?>

<!-- Main Content -->
<div class="container py-5">
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4 fw-bold mb-4" data-aos="fade-up">Liên hệ với chúng tôi</h1>
            <p class="lead text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                Bạn có câu hỏi hoặc đề xuất? Hãy liên hệ với chúng tôi, đội ngũ copecute luôn sẵn sàng hỗ trợ bạn.
            </p>
        </div>
    </div>
    
    <?php if ($error_message): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($contact_settings)): ?>
    <!-- Contact Info & Email Guide -->
    <div class="row">
        <!-- Contact Info -->
        <div class="col-lg-4 mb-5 mb-lg-0" data-aos="fade-right">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4">Thông tin liên hệ</h3>
                    
                    <div class="d-flex mb-4">
                        <div class="icon-box me-3">
                            <i class="fas fa-map-marker-alt text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Địa chỉ</h5>
                            <p class="text-muted mb-0">
                                <?php echo nl2br(htmlspecialchars($contact_settings['contact_address'] ?? 'N/A')); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="icon-box me-3">
                            <i class="fas fa-envelope text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Email</h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_settings['contact_email'] ?? 'N/A'); ?></p>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_settings['support_email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="icon-box me-3">
                            <i class="fas fa-phone text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Điện thoại</h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_settings['contact_phone'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="icon-box me-3">
                            <i class="fas fa-clock text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Giờ làm việc</h5>
                            <?php foreach ($working_hours_parts as $hours): ?>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars(trim($hours)); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="fw-bold mb-3">Kết nối với chúng tôi</h5>
                    <div class="social-links">
                        <a href="<?php echo htmlspecialchars($contact_settings['facebook_url'] ?? '#'); ?>" class="btn btn-outline-primary me-2 rounded-circle">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars($contact_settings['twitter_url'] ?? '#'); ?>" class="btn btn-outline-primary me-2 rounded-circle">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars($contact_settings['instagram_url'] ?? '#'); ?>" class="btn btn-outline-primary me-2 rounded-circle">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars($contact_settings['youtube_url'] ?? '#'); ?>" class="btn btn-outline-primary rounded-circle">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Email Contact Guide -->
        <div class="col-lg-8" data-aos="fade-left">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-4">Liên hệ qua Email</h3>
                    
                    <div class="email-contact-guide">
                        <div class="mb-4">
                            <h5 class="fw-bold"><i class="fas fa-envelope-open-text me-2 text-primary"></i> Gửi Email cho chúng tôi</h5>
                            <p class="text-muted">
                                Để liên hệ với đội ngũ hỗ trợ của copecute, vui lòng gửi email đến địa chỉ bên dưới. 
                                Chúng tôi cam kết sẽ phản hồi bạn trong vòng 24 giờ làm việc.
                            </p>
                        </div>
                        
                        <div class="email-box p-4 bg-light rounded mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-envelope-square text-primary me-3" style="font-size: 2rem;"></i>
                                <div>
                                    <h5 class="fw-bold mb-0">Email hỗ trợ chính thức</h5>
                                    <p class="mb-0 text-primary fw-bold"><?php echo htmlspecialchars($contact_settings['support_email'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <hr class="my-3">
                            <p class="mb-0"><strong>Thời gian phản hồi:</strong> Trong vòng 24 giờ làm việc</p>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="fw-bold"><i class="fas fa-list-ol me-2 text-primary"></i> Nội dung email nên bao gồm</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Họ và tên của bạn</li>
                                <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Chủ đề liên hệ</li>
                                <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Nội dung chi tiết cần hỗ trợ</li>
                                <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Thông tin liên hệ để chúng tôi phản hồi</li>
                            </ul>
                        </div>
                        
                        <div class="text-center">
                            <a href="mailto:<?php echo htmlspecialchars($contact_settings['support_email'] ?? ''); ?>" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-paper-plane me-2"></i> Gửi Email Ngay
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once 'includes/layouts/footer.php';
?> 
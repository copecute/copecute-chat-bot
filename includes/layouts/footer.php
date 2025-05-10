<?php
// Đảm bảo rằng config đã được tải
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
    // Trường hợp footer được gọi trực tiếp, tải config
    require_once __DIR__ . '/../config.php';
}

// Lấy thông tin cài đặt cho footer
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings 
                          WHERE category = 'contact' OR category = 'social'");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
}
?>

    <!-- Footer -->
    <footer class="footer mt-5 py-4 bg-white border-top">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <a href="<?php echo $base_url; ?>/" class="footer-brand d-flex align-items-center mb-3">
                        <img src="<?php echo $base_url; ?>/logo.png" alt="copecute" height="40">
                        <span class="ms-2 fw-bold text-primary fs-4">copecute</span>
                    </a>
                    <p class="text-muted">
                        copecute chat bot là chatbot thông minh giúp bạn trò chuyện, học hỏi và giải trí.
                    </p>
                    <div class="social-links">
                        <a href="<?php echo htmlspecialchars($settings['facebook_url'] ?? '#'); ?>" class="me-2 text-muted"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="<?php echo htmlspecialchars($settings['twitter_url'] ?? '#'); ?>" class="me-2 text-muted"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="<?php echo htmlspecialchars($settings['instagram_url'] ?? '#'); ?>" class="me-2 text-muted"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="<?php echo htmlspecialchars($settings['youtube_url'] ?? '#'); ?>" class="text-muted"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-6 col-lg-2 offset-lg-1 mb-4 mb-lg-0">
                    <h5 class="fw-bold mb-3 text-dark">Liên kết</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo $base_url; ?>/" class="text-decoration-none text-muted">Trang chủ</a></li>
                        <li class="mb-2"><a href="<?php echo $base_url; ?>/features.php" class="text-decoration-none text-muted">Tính năng</a></li>
                        <li class="mb-2"><a href="<?php echo $base_url; ?>/about.php" class="text-decoration-none text-muted">Giới thiệu</a></li>
                        <li class="mb-2"><a href="<?php echo $base_url; ?>/contact.php" class="text-decoration-none text-muted">Liên hệ</a></li>
                    </ul>
                </div>
                
                <div class="col-6 col-lg-2 mb-4 mb-lg-0">
                    <h5 class="fw-bold mb-3 text-dark">Hỗ trợ</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo $base_url; ?>/document" class="text-decoration-none text-muted">Tài liệu</a></li>
                        <li class="mb-2"><a href="<?php echo $base_url; ?>/page/privacy-policy.php" class="text-decoration-none text-muted">Chính sách</a></li>
                        <li class="mb-2"><a href="<?php echo $base_url; ?>/page/terms-of-use.php" class="text-decoration-none text-muted">Điều khoản</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3">
                    <h5 class="fw-bold mb-3 text-dark">Liên hệ</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2 text-muted"><i class="fas fa-map-marker-alt me-2"></i> <?php echo htmlspecialchars($settings['contact_address'] ?? 'N/A'); ?></li>
                        <li class="mb-2 text-muted"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($settings['contact_email'] ?? 'N/A'); ?></li>
                        <li class="mb-2 text-muted"><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($settings['contact_phone'] ?? 'N/A'); ?></li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-7 mb-3 mb-md-0">
                    <p class="text-muted mb-0">
                        <?php echo htmlspecialchars($settings['footer_text'] ?? 'N/A'); ?>
                    </p>
                </div>
                <div class="col-md-5 text-md-end">
                    <p class="text-muted mb-0">
                        <a href="<?php echo $base_url; ?>/privacy.php" class="text-decoration-none text-muted me-3">Chính sách riêng tư</a>
                        <a href="<?php echo $base_url; ?>/terms.php" class="text-decoration-none text-muted">Điều khoản sử dụng</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- AOS - Animate On Scroll -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    
    <script>
        // Khởi tạo AOS (Animate On Scroll)
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Xử lý user dropdown
        $(document).ready(function() {
            $('#userDropdownToggle').on('click', function(e) {
                e.preventDefault();
                $('#userDropdownMenu').toggleClass('show');
            });
            
            // Đóng dropdown khi click ra ngoài
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.user-dropdown').length) {
                    $('#userDropdownMenu').removeClass('show');
                }
            });
        });
    </script>
</body>
</html> 
<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Kết nối đến cơ sở dữ liệu và kiểm tra chế độ bảo trì
require_once 'includes/config.php';

$logged_in = isset($_SESSION['user_id']);
$user_level = isset($_SESSION['user_level']) ? $_SESSION['user_level'] : 0;

// Lấy thông tin cài đặt hệ thống
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('site_name', 'site_description', 'footer_text')");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // tính sau
}

// Đếm số người dùng
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_users = 0;
}

// Đếm số từ khóa và câu trả lời
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM keywords");
    $total_keywords = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM replies");
    $total_replies = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_keywords = 0;
    $total_replies = 0;
}

// Sử dụng header chung
require_once 'includes/layouts/header.php';
?>

<!-- Phần CSS riêng của trang chủ -->
    <style>
        /* Header Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2a4bb7 100%);
            color: white;
        padding: 120px 0 100px;
            position: relative;
            overflow: hidden;
        }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('img/pattern.svg');
        background-size: cover;
        opacity: 0.1;
        z-index: 0;
    }
        
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to top left, white 49%, transparent 51%);
        }
    
    .hero-content {
        position: relative;
        z-index: 1;
    }
        
        .hero-title {
            font-size: 3.5rem;
        font-weight: 800;
            margin-bottom: 1.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
        margin-bottom: 2.5rem;
            opacity: 0.9;
        font-weight: 300;
        }
        
        .hero-image {
            max-width: 100%;
        position: relative;
        z-index: 1;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
    
    .btn-cta {
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        margin: 0 10px 10px 0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    
    .btn-cta::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 0%;
        height: 100%;
        background-color: rgba(255,255,255,0.1);
        transition: all 0.3s;
        z-index: -1;
    }
    
    .btn-cta:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .btn-cta:hover::before {
        width: 100%;
    }
    
    .btn-light {
        background-color: white;
        color: var(--primary-color);
    }
    
    .btn-light:hover {
        color: var(--primary-color);
    }
    
    .btn-outline-light {
        border: 2px solid white;
        background-color: transparent;
    }
    
    .btn-outline-light:hover {
        background-color: rgba(255,255,255,0.1);
        border-color: white;
    }
    
    .hero-shape {
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        z-index: 2;
    }
    
    .floating-card {
        position: absolute;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 20px;
        color: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        animation: float-slow 8s ease-in-out infinite;
        z-index: 1;
    }
    
    .floating-card-1 {
        top: 15%;
        right: 0;
        max-width: 200px;
    }
    
    .floating-card-2 {
        bottom: 20%;
        right: 10%;
        max-width: 180px;
    }
    
    @keyframes float-slow {
        0% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(2deg); }
        100% { transform: translateY(0) rotate(0deg); }
    }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background-color: white;
        }
        
        .feature-card {
            padding: 30px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        /* Stats Section */
        .stats-section {
            padding: 60px 0;
            background-color: var(--primary-color);
            color: white;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: block;
        }
        
        .stat-label {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background-color: white;
            text-align: center;
        }
        
        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
        }
        
        /* Admin Link */
        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
        }
        
        .admin-button {
            padding: 10px 15px;
            border-radius: 30px;
            background-color: var(--dark-color);
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .admin-button:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-image {
                margin-top: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                text-align: center;
                padding: 60px 0;
            }
            
            .stat-item {
                margin-bottom: 30px;
            }
        }
    </style>

    <!-- Header Section -->
    <header class="hero-section">
        <div class="container">
            <div class="row align-items-center">
            <div class="col-lg-6 hero-content" data-aos="fade-right">
                <h1 class="hero-title">Trò chuyện thông minh với <?php echo htmlspecialchars($settings['site_name']); ?></h1>
                    <p class="hero-subtitle"><?php echo htmlspecialchars($settings['site_description']); ?></p>
                    <div class="hero-buttons">
                        <?php if ($logged_in): ?>
                        <a href="<?php echo $base_url; ?>/chat/index.php" class="btn btn-light btn-cta">
                                <i class="fas fa-comments"></i> Bắt đầu trò chuyện
                            </a>
                        <?php else: ?>
                        <a href="<?php echo $base_url; ?>/login.php" class="btn btn-light btn-cta">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập
                            </a>
                        <a href="<?php echo $base_url; ?>/register.php" class="btn btn-outline-light btn-cta">
                                <i class="fas fa-user-plus"></i> Đăng ký
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                <img src="img/robot.svg" alt="copecute" class="hero-image" onerror="this.src='/assets/img/copecute/cope7.png'; this.style.maxWidth='80%';">
                
                <!-- Floating Stats Cards -->
                <div class="floating-card floating-card-1">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users fa-2x me-3"></i>
                        <div>
                            <strong><?php echo number_format($total_users); ?>+</strong>
                            <div>Người dùng</div>
                        </div>
                    </div>
                </div>
                
                <div class="floating-card floating-card-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-comments fa-2x me-3"></i>
                        <div>
                            <strong><?php echo number_format($total_replies); ?>+</strong>
                            <div>Câu trả lời</div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="mb-4" data-aos="fade-up">Tính năng nổi bật</h2>
                    <p class="lead" data-aos="fade-up" data-aos-delay="200">
                    copecute là chatbot thông minh có khả năng học hỏi và cải thiện qua mỗi cuộc trò chuyện
                    </p>
                </div>
            </div>
            
    <div class="row">
                <div class="col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h4>Trí thông minh nhân tạo</h4>
                    <p>Được xây dựng trên nền tảng trí tuệ nhân tạo tiên tiến, copecute có thể hiểu và phản hồi các câu hỏi của bạn một cách chính xác.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4>Trò chuyện tự nhiên</h4>
                    <p>Trò chuyện với copecute như với một người bạn thực sự. Bot có thể hiểu ngữ cảnh và cung cấp phản hồi phù hợp.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h4>Riêng tư & Bảo mật</h4>
                        <p>Bảo vệ thông tin cá nhân của bạn là ưu tiên hàng đầu của chúng tôi. Dữ liệu trò chuyện được mã hóa và bảo vệ.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h4>Lịch sử trò chuyện</h4>
                        <p>Lưu trữ và truy cập lịch sử trò chuyện của bạn để tiếp tục cuộc trò chuyện bất cứ lúc nào.</p>
            </div>
        </div>

                <div class="col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="500">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h4>Phản hồi nhanh chóng</h4>
                    <p>copecute phản hồi tức thì với thời gian chờ tối thiểu, giúp cuộc trò chuyện diễn ra trôi chảy.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h4>Luôn học hỏi</h4>
                    <p>copecute liên tục học hỏi từ mỗi cuộc trò chuyện để cải thiện khả năng và cung cấp trải nghiệm tốt hơn.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_users); ?></span>
                        <span class="stat-label">Người dùng</span>
        </div>
    </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_keywords); ?></span>
                        <span class="stat-label">Từ khóa</span>
                    </div>
</div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_replies); ?></span>
                        <span class="stat-label">Câu trả lời</span>
            </div>
            </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto" data-aos="fade-up">
                    <h2 class="cta-title">Bắt đầu trò chuyện ngay bây giờ</h2>
                <p class="lead mb-4">Tham gia cùng hàng nghìn người dùng khác và trải nghiệm cuộc trò chuyện thông minh với copecute.</p>
                    
                    <?php if ($logged_in): ?>
                    <a href="<?php echo $base_url; ?>/chat/index.php" class="btn btn-primary btn-cta">
                            <i class="fas fa-comments"></i> Tiếp tục trò chuyện
                        </a>
                    <?php else: ?>
                    <a href="<?php echo $base_url; ?>/login.php" class="btn btn-primary btn-cta">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    <a href="<?php echo $base_url; ?>/register.php" class="btn btn-outline-primary btn-cta">
                            <i class="fas fa-user-plus"></i> Đăng ký
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
<?php
// Import footer
require_once 'includes/layouts/footer.php';
?>

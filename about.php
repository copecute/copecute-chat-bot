<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Kết nối đến config
require_once 'includes/config.php';

// Include header
require_once 'includes/layouts/header.php';
?>

<!-- Main Content -->
<div class="container py-5">
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4 fw-bold mb-4" data-aos="fade-up">Về copecute</h1>
            <p class="lead text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                Chúng tôi đang xây dựng tương lai của giao tiếp thông minh với copecute - chatbot tiên tiến.
            </p>
        </div>
    </div>
    
    <div class="row mb-5">
        <div class="col-md-6" data-aos="fade-right">
            <img src="https://via.placeholder.com/600x400" alt="Về chúng tôi" class="img-fluid rounded shadow-sm">
        </div>
        <div class="col-md-6" data-aos="fade-left">
            <h2 class="fw-bold mb-4">Câu chuyện của chúng tôi</h2>
            <p class="mb-4">
                copecute ra đời với sứ mệnh tạo ra một trợ lý ảo thông minh, thân thiện và hữu ích. Chúng tôi tin rằng công nghệ 
                AI nên trở nên dễ tiếp cận và giúp ích cho mọi người trong cuộc sống hàng ngày.
            </p>
            <p>
                Từ năm 2023, nhóm phát triển của chúng tôi đã không ngừng cải tiến copecute, tích hợp các công nghệ xử lý ngôn ngữ 
                tự nhiên tiên tiến để mang đến trải nghiệm trò chuyện mượt mà và thông minh nhất.
            </p>
        </div>
    </div>
    
    <div class="row py-5">
        <div class="col-12 text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Giá trị cốt lõi</h2>
            <p class="text-muted">Những nguyên tắc định hướng cho mọi quyết định của chúng tôi</p>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <div class="icon-box mb-4">
                        <i class="fas fa-brain text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Đổi mới</h4>
                    <p class="text-muted">
                        Chúng tôi luôn tìm kiếm những cách tiếp cận mới để cải thiện trải nghiệm người dùng và công nghệ AI.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <div class="icon-box mb-4">
                        <i class="fas fa-shield-alt text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Bảo mật</h4>
                    <p class="text-muted">
                        Bảo vệ dữ liệu và quyền riêng tư của người dùng là ưu tiên hàng đầu trong mọi tính năng chúng tôi phát triển.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <div class="icon-box mb-4">
                        <i class="fas fa-users text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Hợp tác</h4>
                    <p class="text-muted">
                        Chúng tôi tin vào sức mạnh của cộng đồng và sự hợp tác để xây dựng một sản phẩm tốt hơn cho tất cả mọi người.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row py-5">
        <div class="col-12 text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Đội ngũ của chúng tôi</h2>
            <p class="text-muted">Những người đứng sau copecute</p>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card border-0 shadow-sm h-100">
                <img src="https://via.placeholder.com/300x300" class="card-img-top" alt="Thành viên đội ngũ">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1">Nguyễn Văn A</h5>
                    <p class="text-muted mb-3">Nhà sáng lập & CEO</p>
                    <div class="social-links">
                        <a href="#" class="me-2 text-muted"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="me-2 text-muted"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card border-0 shadow-sm h-100">
                <img src="https://via.placeholder.com/300x300" class="card-img-top" alt="Thành viên đội ngũ">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1">Trần Thị B</h5>
                    <p class="text-muted mb-3">Giám đốc kỹ thuật</p>
                    <div class="social-links">
                        <a href="#" class="me-2 text-muted"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="me-2 text-muted"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card border-0 shadow-sm h-100">
                <img src="https://via.placeholder.com/300x300" class="card-img-top" alt="Thành viên đội ngũ">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1">Lê Văn C</h5>
                    <p class="text-muted mb-3">Kỹ sư AI</p>
                    <div class="social-links">
                        <a href="#" class="me-2 text-muted"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="me-2 text-muted"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
            <div class="card border-0 shadow-sm h-100">
                <img src="https://via.placeholder.com/300x300" class="card-img-top" alt="Thành viên đội ngũ">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1">Phạm Thị D</h5>
                    <p class="text-muted mb-3">UX/UI Designer</p>
                    <div class="social-links">
                        <a href="#" class="me-2 text-muted"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="me-2 text-muted"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/layouts/footer.php';
?> 
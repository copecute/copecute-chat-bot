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
            <h1 class="display-4 fw-bold mb-4" data-aos="fade-up">Tính năng copecute</h1>
            <p class="lead text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                Khám phá những tính năng mạnh mẽ giúp copecute trở thành chatbot thông minh nhất hiện nay.
            </p>
        </div>
    </div>
    
    <!-- Feature Section 1 -->
    <div class="row align-items-center mb-5 py-5">
        <div class="col-lg-6 order-lg-2" data-aos="fade-left">
            <img src="https://via.placeholder.com/600x400" alt="Trò chuyện tự nhiên" class="img-fluid rounded shadow-sm">
        </div>
        <div class="col-lg-6 order-lg-1" data-aos="fade-right">
            <div class="p-4">
                <h2 class="fw-bold mb-4">Trò chuyện tự nhiên</h2>
                <p class="mb-4">
                    copecute được phát triển với công nghệ xử lý ngôn ngữ tự nhiên tiên tiến, cho phép chatbot hiểu và 
                    phản hồi các câu hỏi, yêu cầu của bạn một cách tự nhiên và thông minh.
                </p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Hiểu ngữ cảnh cuộc trò chuyện</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Phản hồi thông minh, chính xác</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Hỗ trợ nhiều chủ đề đa dạng</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Feature Section 2 -->
    <div class="row align-items-center mb-5 py-5">
        <div class="col-lg-6" data-aos="fade-right">
            <img src="https://via.placeholder.com/600x400" alt="Học hỏi liên tục" class="img-fluid rounded shadow-sm">
        </div>
        <div class="col-lg-6" data-aos="fade-left">
            <div class="p-4">
                <h2 class="fw-bold mb-4">Học hỏi liên tục</h2>
                <p class="mb-4">
                    copecute có khả năng học hỏi từ mỗi cuộc trò chuyện, giúp chatbot ngày càng thông minh hơn 
                    và cá nhân hóa trải nghiệm cho từng người dùng theo thời gian.
                </p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Ghi nhớ thông tin cá nhân của bạn</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Học hỏi từ phản hồi của người dùng</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Cải thiện độ chính xác theo thời gian</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Feature Section 3 -->
    <div class="row align-items-center mb-5 py-5">
        <div class="col-lg-6 order-lg-2" data-aos="fade-left">
            <img src="https://via.placeholder.com/600x400" alt="Bảo mật dữ liệu" class="img-fluid rounded shadow-sm">
        </div>
        <div class="col-lg-6 order-lg-1" data-aos="fade-right">
            <div class="p-4">
                <h2 class="fw-bold mb-4">Bảo mật dữ liệu tối đa</h2>
                <p class="mb-4">
                    copecute được xây dựng với tiêu chuẩn bảo mật cao nhất, đảm bảo thông tin cá nhân và cuộc trò chuyện 
                    của bạn luôn được bảo vệ an toàn.
                </p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Mã hóa đầu cuối (end-to-end)</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Không lưu trữ dữ liệu nhạy cảm</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Tuân thủ các quy định bảo mật dữ liệu</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Features Grid -->
    <div class="row py-5">
        <div class="col-12 text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Tính năng nổi bật khác</h2>
            <p class="text-muted">Những công nghệ tiên tiến làm nên copecute</p>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="icon-box mb-3">
                        <i class="fas fa-bolt text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Phản hồi tức thì</h4>
                    <p class="text-muted">
                        copecute đáp ứng các yêu cầu của bạn trong thời gian thực với độ trễ tối thiểu, mang lại trải nghiệm mượt mà.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="icon-box mb-3">
                        <i class="fas fa-language text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Đa ngôn ngữ</h4>
                    <p class="text-muted">
                        Hỗ trợ nhiều ngôn ngữ khác nhau, giúp người dùng toàn cầu có thể trò chuyện với copecute dễ dàng.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="icon-box mb-3">
                        <i class="fas fa-plug text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Tích hợp dễ dàng</h4>
                    <p class="text-muted">
                        copecute cung cấp API linh hoạt, cho phép tích hợp vào website, ứng dụng di động hoặc nền tảng khác.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="400">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="icon-box mb-3">
                        <i class="fas fa-chart-line text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Phân tích dữ liệu</h4>
                    <p class="text-muted">
                        Cung cấp báo cáo và thống kê chi tiết về các cuộc trò chuyện, giúp tối ưu hóa trải nghiệm người dùng.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="500">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="icon-box mb-3">
                        <i class="fas fa-robot text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Cá nhân hóa</h4>
                    <p class="text-muted">
                        copecute ghi nhớ sở thích, thói quen và lịch sử trò chuyện để tạo trải nghiệm cá nhân hóa cho mỗi người dùng.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="600">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="icon-box mb-3">
                        <i class="fas fa-cloud text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Đồng bộ đa thiết bị</h4>
                    <p class="text-muted">
                        Đồng bộ cuộc trò chuyện giữa các thiết bị khác nhau, giúp bạn có thể tiếp tục trò chuyện ở bất kỳ đâu.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CTA Section -->
    <div class="row py-5">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 bg-primary text-white shadow-lg rounded-lg" data-aos="fade-up">
                <div class="card-body p-5 text-center">
                    <h2 class="fw-bold mb-4">Sẵn sàng trải nghiệm copecute?</h2>
                    <p class="lead mb-4">
                        Đăng ký ngay hôm nay và khám phá sự khác biệt với trợ lý ảo thông minh nhất.
                    </p>
                    <div class="mt-4">
                        <a href="<?php echo $base_url; ?>/login.php" class="btn btn-light btn-lg px-5 me-3">
                            <i class="fas fa-user-plus me-2"></i> Đăng ký ngay
                        </a>
                        <a href="<?php echo $base_url; ?>/chat/" class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-comments me-2"></i> Dùng thử
                        </a>
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
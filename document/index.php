<?php
define('ALLOW_ACCESS', true);
require_once 'layouts/header.php';
?>

<h1 class="h2">Tổng quan về API</h1>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Giới thiệu</h5>
                <p class="card-text">
                    CopeCute API cung cấp các endpoint để tích hợp chatbot vào ứng dụng của bạn. 
                    API này cho phép bạn thực hiện các chức năng như xác thực người dùng, 
                    gửi và nhận tin nhắn, quản lý hồ sơ người dùng và truy xuất lịch sử chat.
                </p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Base URL</h5>
                <p class="card-text">
                    Tất cả các request API đều được gửi đến base URL sau:
                </p>
                <pre><code class="language-bash"><?php echo API_URL; ?></code></pre>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Xác thực</h5>
                <p class="card-text">
                    API sử dụng JWT (JSON Web Token) để xác thực. Token được gửi trong header của mỗi request:
                </p>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}</code></pre>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Các endpoint chính</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-key me-2"></i>
                        <strong>Xác thực:</strong> Đăng nhập và quản lý token
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-comments me-2"></i>
                        <strong>Chat:</strong> Gửi và nhận tin nhắn
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-user me-2"></i>
                        <strong>Profile:</strong> Quản lý thông tin người dùng
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-history me-2"></i>
                        <strong>Lịch sử:</strong> Truy xuất lịch sử chat
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Tài nguyên hữu ích</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo $base_url; ?>/document/authentication.php" class="text-decoration-none">
                            <i class="fas fa-book me-2"></i>
                            Hướng dẫn xác thực
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo $base_url; ?>" target="_blank" class="text-decoration-none">
                            <i class="fas fa-globe me-2"></i>
                            Website chính thức
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Hỗ trợ</h5>
                <p class="card-text">
                    Nếu bạn cần hỗ trợ, vui lòng liên hệ:
                </p>
                <ul class="list-unstyled">
                    <li>
                        <i class="fas fa-envelope me-2"></i>
                        <a href="mailto:copesocute@gmail.com" class="text-decoration-none">
                            copesocute@gmail.com
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?> 
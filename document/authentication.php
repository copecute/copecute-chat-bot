<?php
// Tài liệu API - Xác thực
define('ALLOW_ACCESS', true);
require_once 'layouts/header.php';
?>
<h1 class="h2">Xác thực API</h1>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Tổng quan về xác thực</h5>
                <p class="card-text">
                    API của CopeCute sử dụng JWT (JSON Web Token) để xác thực người dùng.
                    Mỗi request đến API cần được xác thực (trừ một số endpoint công khai)
                    bằng cách gửi token trong header.
                </p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Đăng nhập</h5>
                <p class="card-text">
                    Để lấy token xác thực, bạn cần đăng nhập thông qua endpoint sau:
                </p>
                <pre><code class="language-bash">POST: <?php echo API_URL; ?>/auth/login.php</code></pre>

                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Content-Type: application/json</code></pre>

                <h6 class="mt-4">Tham số (JSON - Raw data):</h6>
                <pre><code class="language-json">{
    "username": "tên_đăng_nhập",
    "password": "mật_khẩu"
}</code></pre>

                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "data": {
        "user_id": 1,
        "username": "user123",
        "email": "user@example.com",
        "level": "0",
        "full_name": "Nguyễn Văn A",
        "avatar": "https://example.com/avatar.jpg",
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "quota": 100
    }
}</code></pre>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Đăng nhập/đăng ký Google</h5>
                <p class="card-text">
                    Để đăng nhập bằng Google, sử dụng endpoint sau:
                </p>
                <pre><code class="language-bash">POST: <?php echo API_URL; ?>/auth/google_login.php</code></pre>

                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Content-Type: application/json</code></pre>

                <h6 class="mt-4">Tham số (JSON - Raw data):</h6>
                <pre><code class="language-json">{
    "id_token": "google_id_token_từ_frontend"
}</code></pre>

                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "data": {
        "user_id": 1,
        "username": "google_123456789",
        "email": "user@gmail.com",
        "level": "0",
        "full_name": "Nguyễn Văn A",
        "avatar": "https://lh3.googleusercontent.com/...",
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "quota": 100
    }
}</code></pre>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Sử dụng token</h5>
                <p class="card-text">
                    Sau khi có token, bạn cần gửi nó trong header của mỗi request:
                </p>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}</code></pre>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Token có thời hạn 1 tháng hoặc đến khi đăng nhập lại. Sau thời gian này, bạn cần đăng nhập lại để lấy token mới.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Mã lỗi phổ biến</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>401 Unauthorized</strong>
                        <p class="mb-0 text-muted">Token không hợp lệ hoặc đã hết hạn</p>
                    </li>
                    <li class="list-group-item">
                        <strong>403 Forbidden</strong>
                        <p class="mb-0 text-muted">Tài khoản bị khóa hoặc không có quyền truy cập</p>
                    </li>
                    <li class="list-group-item">
                        <strong>422 Unprocessable Entity</strong>
                        <p class="mb-0 text-muted">Dữ liệu không hợp lệ (ví dụ: mật khẩu quá ngắn)</p>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Lưu ý quan trọng</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-key me-2"></i>
                        Mật khẩu phải có ít nhất 6 ký tự
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-user-shield me-2"></i>
                        Tên đăng nhập phải là duy nhất trong hệ thống
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-envelope me-2"></i>
                        Email phải có định dạng hợp lệ và duy nhất
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-lock me-2"></i>
                        Không bao giờ lưu trữ token ở nơi không an toàn
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-quote-left me-2"></i>
                        Mỗi tài khoản được cấp một số lượng quota nhất định để sử dụng chatbot
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
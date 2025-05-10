<?php
define('ALLOW_ACCESS', true);
require_once 'layouts/header.php';
?>
<h1 class="h2">Profile API</h1>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Tổng quan</h5>
                <p class="card-text">
                    Profile API cho phép bạn quản lý thông tin người dùng, bao gồm việc lấy thông tin, cập nhật hồ sơ và thay đổi mật khẩu. Tất cả các request đều yêu cầu xác thực bằng JWT token.
                </p>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Lấy thông tin profile</h5>
                <p class="card-text">Để lấy thông tin profile của người dùng:</p>
                <pre><code class="language-bash">GET: <?php echo API_URL; ?>/profile/index.php</code></pre>
                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}</code></pre>
                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "data": {
        "user_id": 1,
        "username": "user123",
        "email": "user@example.com",
        "full_name": "Nguyễn Văn A",
        "phone": "0912345678",
        "gender": "male",
        "birthday": "1990-01-01",
        "address": "Hà Nội, Việt Nam",
        "avatar": "https://example.com/avatar.jpg",
        "created_at": "2024-03-10T15:30:00Z",
        "quota": 100
    }
}</code></pre>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Cập nhật profile</h5>
                <p class="card-text">Để cập nhật thông tin profile:</p>
                <pre><code class="language-bash">POST: <?php echo API_URL; ?>/profile/update.php</code></pre>
                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}
Content-Type: application/json</code></pre>
                <h6 class="mt-4">Tham số (JSON - Raw data):</h6>
                <pre><code class="language-json">{
    "full_name": "Nguyễn Văn A",
    "phone": "0912345678",
    "gender": "male",
    "birthday": "1990-01-01",
    "address": "Hà Nội, Việt Nam",
    "avatar": "https://example.com/avatar.jpg"
}</code></pre>
                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "message": "Cập nhật thông tin thành công"
}</code></pre>
                <p class="mt-3 text-muted">Lưu ý: Bạn chỉ cần gửi các trường cần cập nhật.</p>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Đổi mật khẩu</h5>
                <p class="card-text">Để thay đổi mật khẩu:</p>
                <pre><code class="language-bash">POST: <?php echo API_URL; ?>/profile/change_password.php</code></pre>
                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}
Content-Type: application/json</code></pre>
                <h6 class="mt-4">Tham số (JSON - Raw data):</h6>
                <pre><code class="language-json">{
    "current_password": "mật_khẩu_hiện_tại",
    "new_password": "mật_khẩu_mới",
    "confirm_password": "xác_nhận_mật_khẩu_mới"
}</code></pre>
                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "message": "Đổi mật khẩu thành công"
}</code></pre>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Mã lỗi phổ biến</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>401 Unauthorized</strong><p class="mb-0 text-muted">Token không hợp lệ hoặc đã hết hạn</p></li>
                    <li class="list-group-item"><strong>400 Bad Request</strong><p class="mb-0 text-muted">Dữ liệu gửi lên không hợp lệ</p></li>
                    <li class="list-group-item"><strong>403 Forbidden</strong><p class="mb-0 text-muted">Không có quyền thực hiện hành động</p></li>
                    <li class="list-group-item"><strong>404 Not Found</strong><p class="mb-0 text-muted">Không tìm thấy tài nguyên</p></li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Lưu ý quan trọng</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fas fa-key me-2"></i>Mật khẩu mới phải có ít nhất 6 ký tự</li>
                    <li class="list-group-item"><i class="fas fa-lock me-2"></i>Không bao giờ gửi mật khẩu qua kết nối không bảo mật</li>
                    <li class="list-group-item"><i class="fas fa-image me-2"></i>URL avatar nên là đường dẫn tuyệt đối đến ảnh đã tải lên</li>
                    <li class="list-group-item"><i class="fas fa-user-shield me-2"></i>Thông tin cá nhân được bảo mật và chỉ hiển thị cho chính người dùng</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?> 
<?php
define('ALLOW_ACCESS', true);
require_once 'layouts/header.php';
?>
<h1 class="h2">Chat API</h1>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Tổng quan</h5>
                <p class="card-text">
                    Chat API cho phép bạn gửi tin nhắn đến chatbot và nhận phản hồi. API này yêu cầu xác thực bằng JWT token và hỗ trợ nhiều tính năng như gửi tin nhắn, nhận phản hồi, và quản lý cuộc trò chuyện.
                </p>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Gửi tin nhắn</h5>
                <p class="card-text">Để gửi tin nhắn đến chatbot, sử dụng endpoint sau:</p>
                <pre><code class="language-bash">POST: <?php echo API_URL; ?>/chat/chat.php</code></pre>
                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}
Content-Type: application/json</code></pre>
                <h6 class="mt-4">Tham số (JSON - Raw data):</h6>
                <pre><code class="language-json">{
    "message": "Xin chào, bạn có thể giúp tôi không?"
}</code></pre>
                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "data": {
        "bot_response": "Xin chào! Tôi có thể giúp gì cho bạn?",
        "quota": 99,
        "is_default_response": false,
        "is_used_quota": true
    }
}</code></pre>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Lấy lịch sử chat</h5>
                <p class="card-text">Để lấy lịch sử chat:</p>
                <pre><code class="language-bash">GET: <?php echo API_URL; ?>/chat/history.php?page=1&limit=20</code></pre>
                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}</code></pre>
                <h6 class="mt-4">Tham số truy vấn:</h6>
                <ul>
                    <li><code>page</code> (tùy chọn): Số trang, mặc định là 1</li>
                    <li><code>limit</code> (tùy chọn): Số lượng tin nhắn mỗi trang, mặc định là 20, tối đa 100</li>
                </ul>
                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "data": {
        "total": 25,
        "page": 1,
        "limit": 20,
        "total_pages": 2,
        "history": [
            {
                "id": "123",
                "user_input": "Xin chào, bạn có thể giúp tôi không?",
                "bot_response": "Xin chào! Tôi có thể giúp gì cho bạn?",
                "created_at": "2024-03-10T15:30:00Z"
            },
            // ... các tin nhắn khác
        ]
    }
}</code></pre>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Xóa lịch sử chat</h5>
                <p class="card-text">Để xóa toàn bộ lịch sử chat:</p>
                <pre><code class="language-bash">POST: <?php echo API_URL; ?>/chat/clear_messages.php</code></pre>
                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}</code></pre>
                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "message": "Đã xóa toàn bộ lịch sử chat"
}</code></pre>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Dạy chatbot</h5>
                <p class="card-text">Để dạy chatbot câu trả lời mới (chỉ dành cho admin):</p>
                <pre><code class="language-bash">POST: <?php echo API_URL; ?>/chat/teach.php</code></pre>
                <h6 class="mt-4">Headers:</h6>
                <pre><code class="language-bash">Authorization: Bearer {your_jwt_token}
Content-Type: application/json</code></pre>
                <h6 class="mt-4">Tham số (JSON - Raw data):</h6>
                <pre><code class="language-json">{
    "keyword": "xin chào",
    "reply": "Chào bạn! Mình có thể giúp gì cho bạn?",
    "impolite": 0
}</code></pre>
                <h6 class="mt-4">Response:</h6>
                <pre><code class="language-json">{
    "success": true,
    "message": "Dạy chatbot thành công"
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
                    <li class="list-group-item"><strong>429 Too Many Requests</strong><p class="mb-0 text-muted">Vượt quá giới hạn request</p></li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Lưu ý quan trọng</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fas fa-info-circle me-2"></i>Mỗi tin nhắn tiêu thụ 1 quota (trừ trường hợp là câu trả lời mặc định)</li>
                    <li class="list-group-item"><i class="fas fa-exclamation-triangle me-2"></i>Khi hết quota, chatbot sẽ ngừng trả lời</li>
                    <li class="list-group-item"><i class="fas fa-history me-2"></i>Lịch sử chat được lưu trữ và gắn với tài khoản của bạn</li>
                    <li class="list-group-item"><i class="fas fa-shield-alt me-2"></i>Chức năng dạy chatbot chỉ dành cho người quản trị</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?> 
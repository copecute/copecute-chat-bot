<?php
define('ALLOW_ACCESS', true);
require_once 'layouts/header.php';
?>
<h1 class="h2">Lịch sử Chat</h1>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Tổng quan</h5>
                <p class="card-text">
                    API cho phép bạn truy xuất lịch sử các cuộc trò chuyện với chatbot. Bạn có thể lấy lịch sử chat của người dùng hiện tại với đầy đủ thông tin về nội dung tin nhắn và thời gian.
                </p>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Lấy lịch sử chat</h5>
                <p class="card-text">
                    Để lấy lịch sử chat của người dùng hiện tại với phân trang:
                </p>
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
            {
                "id": "122",
                "user_input": "Thời tiết hôm nay thế nào?",
                "bot_response": "Tôi không có thông tin thời tiết thực tế. Bạn có thể kiểm tra dự báo thời tiết trên các ứng dụng chuyên dụng.",
                "created_at": "2024-03-10T15:25:00Z"
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
                <p class="card-text">
                    Để xóa toàn bộ lịch sử chat của người dùng hiện tại:
                </p>
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
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Lưu ý</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-user me-2"></i>
                        Mỗi người dùng chỉ có thể xem lịch sử chat của chính mình
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-history me-2"></i>
                        Lịch sử chat được sắp xếp theo thời gian từ mới đến cũ
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-trash me-2"></i>
                        Khi xóa lịch sử chat, toàn bộ tin nhắn sẽ bị xóa vĩnh viễn
                    </li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Mã lỗi phổ biến</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>401 Unauthorized</strong><p class="mb-0 text-muted">Token không hợp lệ hoặc đã hết hạn</p></li>
                    <li class="list-group-item"><strong>400 Bad Request</strong><p class="mb-0 text-muted">Tham số không hợp lệ</p></li>
                    <li class="list-group-item"><strong>404 Not Found</strong><p class="mb-0 text-muted">Không tìm thấy lịch sử chat</p></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?> 
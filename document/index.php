<?php
// Tài liệu API - Trang chủ
include_once 'header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="index.php" class="list-group-item list-group-item-action active">Tổng quan</a>
                <a href="authentication.php" class="list-group-item list-group-item-action">Xác thực</a>
                <a href="login.php" class="list-group-item list-group-item-action">API Đăng nhập</a>
                <a href="register.php" class="list-group-item list-group-item-action">API Đăng ký</a>
                <a href="google_login.php" class="list-group-item list-group-item-action">API Đăng nhập Google</a>
                <a href="google_login_flutter.php" class="list-group-item list-group-item-action">Tích hợp Flutter</a>
                <a href="chat.php" class="list-group-item list-group-item-action">API Chat</a>
                <a href="teach.php" class="list-group-item list-group-item-action">API Dạy Bot</a>
                <a href="history.php" class="list-group-item list-group-item-action">API Lịch sử Chat</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Tài liệu API copecute Chatbot</h1>
                </div>
                <div class="card-body">
                    <h2>Giới thiệu</h2>
                    <p>Chào mừng bạn đến với tài liệu API copecute Chatbot. Tài liệu này cung cấp thông tin chi tiết về các API khả dụng để tích hợp với ứng dụng của bạn.</p>
                    
                    <h2>API có sẵn</h2>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>API</th>
                                    <th>Mô tả</th>
                                    <th>Phương thức</th>
                                    <th>Xác thực</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><a href="login.php">/api/login.php</a></td>
                                    <td>Đăng nhập và lấy token</td>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td>Không</td>
                                </tr>
                                <tr>
                                    <td><a href="register.php">/api/login.php</a></td>
                                    <td>Đăng ký tài khoản mới</td>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td>Không</td>
                                </tr>
                                <tr>
                                    <td><a href="google_login.php">/api/google_login.php</a></td>
                                    <td>Đăng nhập bằng Google</td>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td>Không</td>
                                </tr>
                                <tr>
                                    <td><a href="chat.php">/api/chat.php</a></td>
                                    <td>Gửi tin nhắn và nhận phản hồi từ chatbot</td>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td>Bearer Token</td>
                                </tr>
                                <tr>
                                    <td><a href="teach.php">/api/teach.php</a></td>
                                    <td>Dạy chatbot từ khóa và phản hồi mới</td>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td>Bearer Token</td>
                                </tr>
                                <tr>
                                    <td><a href="history.php">/api/history.php</a></td>
                                    <td>Lấy lịch sử chat của người dùng</td>
                                    <td><span class="badge bg-primary">GET</span></td>
                                    <td>Bearer Token</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h2>Hướng dẫn tích hợp</h2>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fab fa-android text-success"></i> Tích hợp Flutter</h5>
                                    <p class="card-text">Hướng dẫn chi tiết cách tích hợp API đăng nhập Google vào ứng dụng Flutter.</p>
                                    <a href="google_login_flutter.php" class="btn btn-outline-primary btn-sm">Xem hướng dẫn</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-code text-primary"></i> API Documentation</h5>
                                    <p class="card-text">Xem chi tiết về các API có sẵn và cách sử dụng chúng.</p>
                                    <a href="authentication.php" class="btn btn-outline-primary btn-sm">Xem API Docs</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h2>Bắt đầu</h2>
                    <p>Để bắt đầu sử dụng API, bạn cần:</p>
                    <ol>
                        <li>Đăng ký tài khoản mới hoặc đăng nhập để lấy token xác thực</li>
                        <li>Sử dụng token trong các yêu cầu API cần xác thực</li>
                        <li>Gửi yêu cầu HTTP đến endpoint tương ứng với phương thức và tham số hợp lệ</li>
                    </ol>
                    
                    <h2>Định dạng phản hồi</h2>
                    <p>Tất cả các API đều trả về phản hồi dưới dạng JSON với cấu trúc như sau:</p>
                    <div class="bg-light p-3 rounded">
                        <pre><code>{
  "success": true,
  "data": { ... }
}</code></pre>
                    </div>
                    <p>Hoặc trong trường hợp lỗi:</p>
                    <div class="bg-light p-3 rounded">
                        <pre><code>{
  "error": "Thông báo lỗi"
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 
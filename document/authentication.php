<?php
// Tài liệu API - Xác thực
include_once 'header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="index.php" class="list-group-item list-group-item-action">Tổng quan</a>
                <a href="authentication.php" class="list-group-item list-group-item-action active">Xác thực</a>
                <a href="login.php" class="list-group-item list-group-item-action">API Đăng nhập</a>
                <a href="register.php" class="list-group-item list-group-item-action">API Đăng ký</a>
                <a href="chat.php" class="list-group-item list-group-item-action">API Chat</a>
                <a href="teach.php" class="list-group-item list-group-item-action">API Dạy Bot</a>
                <a href="history.php" class="list-group-item list-group-item-action">API Lịch sử Chat</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Xác thực API</h1>
                </div>
                <div class="card-body">
                    <h2>Xác thực với Bearer Token</h2>
                    <p>Xác thực API được thực hiện thông qua <strong>Bearer Token</strong>. Đây là cách API xác định và xác thực người dùng của bạn.</p>
                    
                    <h3>Cách lấy token</h3>
                    <p>Để lấy Bearer Token, bạn cần đăng nhập vào hệ thống qua <a href="login.php">API đăng nhập</a> hoặc <a href="register.php">API đăng ký</a>. Sau khi đăng nhập hoặc đăng ký thành công, bạn sẽ nhận được token trong phản hồi.</p>
                    
                    <h3>Cách sử dụng token</h3>
                    <p>Để sử dụng token trong các API yêu cầu xác thực, bạn cần thêm header <code>Authorization</code> vào yêu cầu HTTP của mình.</p>
                    
                    <div class="alert alert-info">
                        <strong>Authorization: Bearer [your_token]</strong>
                    </div>
                    
                    <h3>Ví dụ về xác thực</h3>
                    <p>Dưới đây là ví dụ về cách thêm header xác thực vào yêu cầu HTTP:</p>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-header">Ví dụ cURL</div>
                        <div class="card-body">
                            <pre><code>curl -X POST "https://yourdomain.com/api/chat.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxMjM0NTY3ODkwLCJ1c2VybmFtZSI6ImpvaG5kb2UifQ.kD3h" \
  -d '{"message": "Xin chào"}'</code></pre>
                        </div>
                    </div>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-header">JavaScript (Fetch API)</div>
                        <div class="card-body">
                            <pre><code>const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxMjM0NTY3ODkwLCJ1c2VybmFtZSI6ImpvaG5kb2UifQ.kD3h';

fetch('https://yourdomain.com/api/chat.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    message: 'Xin chào'
  })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));</code></pre>
                        </div>
                    </div>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-header">Android (Java)</div>
                        <div class="card-body">
                            <pre><code>OkHttpClient client = new OkHttpClient();

String token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxMjM0NTY3ODkwLCJ1c2VybmFtZSI6ImpvaG5kb2UifQ.kD3h";
String url = "https://yourdomain.com/api/chat.php";

JSONObject jsonObject = new JSONObject();
jsonObject.put("message", "Xin chào");

RequestBody body = RequestBody.create(
    MediaType.parse("application/json"), jsonObject.toString());

Request request = new Request.Builder()
    .url(url)
    .addHeader("Content-Type", "application/json")
    .addHeader("Authorization", "Bearer " + token)
    .post(body)
    .build();

client.newCall(request).enqueue(new Callback() {
    @Override
    public void onFailure(Call call, IOException e) {
        e.printStackTrace();
    }

    @Override
    public void onResponse(Call call, Response response) throws IOException {
        if (response.isSuccessful()) {
            String responseData = response.body().string();
            // Xử lý dữ liệu phản hồi
        }
    }
});</code></pre>
                        </div>
                    </div>
                    
                    <h3>Lỗi xác thực</h3>
                    <p>Nếu token không hợp lệ hoặc đã hết hạn, API sẽ trả về lỗi 401 Unauthorized:</p>
                    
                    <div class="bg-light p-3 rounded">
                        <pre><code>{
  "error": "Token không hợp lệ hoặc đã hết hạn"
}</code></pre>
                    </div>
                    
                    <h3>Bảo mật token</h3>
                    <p>Lưu ý quan trọng:</p>
                    <ul>
                        <li>Luôn giữ token an toàn và không chia sẻ chúng</li>
                        <li>Lưu trữ token một cách an toàn trong ứng dụng của bạn</li>
                        <li>Tất cả các yêu cầu API nên được thực hiện qua HTTPS để bảo mật token</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 
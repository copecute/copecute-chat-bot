<?php
// Tài liệu API - Đăng nhập Google
include_once 'header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="index.php" class="list-group-item list-group-item-action">Tổng quan</a>
                <a href="authentication.php" class="list-group-item list-group-item-action">Xác thực</a>
                <a href="login.php" class="list-group-item list-group-item-action">API Đăng nhập</a>
                <a href="register.php" class="list-group-item list-group-item-action">API Đăng ký</a>
                <a href="google_login.php" class="list-group-item list-group-item-action active">API Đăng nhập Google</a>
                <a href="chat.php" class="list-group-item list-group-item-action">API Chat</a>
                <a href="teach.php" class="list-group-item list-group-item-action">API Dạy Bot</a>
                <a href="history.php" class="list-group-item list-group-item-action">API Lịch sử Chat</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">API Đăng nhập Google</h1>
                </div>
                <div class="card-body">
                    <h2>Tổng quan</h2>
                    <p>API này cho phép ứng dụng Android xác thực người dùng bằng Google và lấy token xác thực để sử dụng trong các API khác.</p>
                    
                    <div class="endpoint mb-4">
                        <div class="method method-post">POST</div> /api/google_login.php
                    </div>
                    
                    <h2>Quy trình đăng nhập Google</h2>
                    <ol>
                        <li>Thực hiện xác thực Google trên ứng dụng Android sử dụng Google Sign-In API</li>
                        <li>Sau khi xác thực thành công, nhận ID token từ Google</li>
                        <li>Gửi ID token này đến API google_login.php</li>
                        <li>API sẽ xác minh token và trả về thông tin người dùng cùng API token</li>
                    </ol>
                    
                    <h2>Yêu cầu</h2>
                    <h3>Headers</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tên</th>
                                    <th>Yêu cầu</th>
                                    <th>Mô tả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Content-Type</td>
                                    <td>Bắt buộc</td>
                                    <td>application/json</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h3>Tham số yêu cầu</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tham số</th>
                                    <th>Kiểu</th>
                                    <th>Yêu cầu</th>
                                    <th>Mô tả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>id_token</td>
                                    <td>string</td>
                                    <td>Bắt buộc</td>
                                    <td>ID token nhận được từ Google Sign-In trên ứng dụng Android</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h3>Ví dụ yêu cầu</h3>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>{
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjFlOWdkazcifQ.ewogImlzcyI6ICJodHRwczovL2FjY291bnRzLmdvb2dsZS5jb20iLAogICJhenAiOiAiMTA3MzgzMzcwNDIzMC0wN2pnN3JqMmI5OWcxM2ZpcW1hczM5MDJiNDlkOTFzOS5hcHBzLmdvb2dsZXVzZXJjb250ZW50LmNvbSIsCiAiYXVkIjogIjEwNzM4MzM3MDQyMzAtMDdqZzdyajJiOTlnMTNmaXFtYXMzOTAyYjQ5ZDkxczku..."
}</code></pre>
                    </div>
                    
                    <h2>Phản hồi</h2>
                    <h3>Phản hồi thành công (200 OK)</h3>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>{
  "success": true,
  "data": {
    "user_id": 12,
    "username": "johndoe",
    "email": "johndoe@gmail.com",
    "level": 0,
    "full_name": "John Doe",
    "avatar": "https://lh3.googleusercontent.com/a/AAcHTtfgEy_example_image_url",
    "token": "7b8f83045a1a16b8b89df8c161a3d9f2d6975c3f6b76a11e29cf3f47e86a0978",
    "quota": 100
  }
}</code></pre>
                    </div>
                    
                    <h3>Phản hồi lỗi</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mã lỗi</th>
                                    <th>Mô tả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>400</td>
                                    <td>Thiếu token xác thực Google</td>
                                </tr>
                                <tr>
                                    <td>401</td>
                                    <td>Token không hợp lệ hoặc thiếu thông tin</td>
                                </tr>
                                <tr>
                                    <td>403</td>
                                    <td>Tài khoản bị khóa hoặc chưa kích hoạt</td>
                                </tr>
                                <tr>
                                    <td>500</td>
                                    <td>Lỗi server khi xử lý yêu cầu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h2>Hướng dẫn tích hợp trên Android</h2>
                    <h3>Bước 1: Thiết lập Google Sign-In trong ứng dụng Android</h3>
                    <p>Thêm thư viện Google Sign-In vào file build.gradle:</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <pre><code>dependencies {
    implementation 'com.google.android.gms:play-services-auth:20.4.1'
}</code></pre>
                    </div>
                    
                    <h3>Bước 2: Khởi tạo Google Sign-In trong ứng dụng</h3>
                    <div class="bg-light p-3 rounded mb-3">
                        <pre><code>private lateinit var googleSignInClient: GoogleSignInClient

// Trong phương thức onCreate() hoặc khởi tạo Activity
val gso = GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
    .requestIdToken("YOUR_WEB_CLIENT_ID") // Client ID từ Google Cloud Console
    .requestEmail()
    .build()

googleSignInClient = GoogleSignIn.getClient(this, gso)</code></pre>
                    </div>
                    
                    <h3>Bước 3: Bắt đầu quy trình đăng nhập Google</h3>
                    <div class="bg-light p-3 rounded mb-3">
                        <pre><code>private fun signIn() {
    val signInIntent = googleSignInClient.signInIntent
    startActivityForResult(signInIntent, RC_SIGN_IN)
}</code></pre>
                    </div>
                    
                    <h3>Bước 4: Xử lý kết quả đăng nhập</h3>
                    <div class="bg-light p-3 rounded mb-3">
                        <pre><code>override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
    super.onActivityResult(requestCode, resultCode, data)

    if (requestCode == RC_SIGN_IN) {
        val task = GoogleSignIn.getSignedInAccountFromIntent(data)
        try {
            val account = task.getResult(ApiException::class.java)
            val idToken = account?.idToken
            
            // Gửi ID token đến server
            if (idToken != null) {
                sendTokenToServer(idToken)
            }
        } catch (e: ApiException) {
            // Xử lý lỗi đăng nhập
            Log.w("GoogleSignIn", "Google sign in failed", e)
        }
    }
}</code></pre>
                    </div>
                    
                    <h3>Bước 5: Gửi token đến API</h3>
                    <div class="bg-light p-3 rounded mb-3">
                        <pre><code>private fun sendTokenToServer(idToken: String) {
    // Sử dụng Retrofit, OkHttp hoặc bất kỳ thư viện HTTP nào
    val apiService = RetrofitClient.getApiService()
    val tokenRequest = TokenRequest(idToken)
    
    apiService.googleLogin(tokenRequest).enqueue(object : Callback&lt;LoginResponse&gt; {
        override fun onResponse(call: Call&lt;LoginResponse&gt;, response: Response&lt;LoginResponse&gt;) {
            if (response.isSuccessful) {
                val loginResponse = response.body()
                // Lưu thông tin đăng nhập và token
                saveUserInfo(loginResponse)
                // Chuyển đến màn hình chính
                navigateToMainScreen()
            } else {
                // Xử lý lỗi từ server
                handleError(response)
            }
        }
        
        override fun onFailure(call: Call&lt;LoginResponse&gt;, t: Throwable) {
            // Xử lý lỗi kết nối
            handleNetworkError(t)
        }
    })
}</code></pre>
                    </div>
                    
                    <h3>Cấu trúc yêu cầu API với Retrofit</h3>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>// Model classes
data class TokenRequest(val id_token: String)

data class LoginResponse(
    val success: Boolean,
    val data: UserData?
)

data class UserData(
    val user_id: Int,
    val username: String,
    val email: String,
    val level: Int,
    val full_name: String,
    val avatar: String?,
    val token: String,
    val quota: Int
)

// API interface
interface ApiService {
    @POST("api/google_login.php")
    fun googleLogin(@Body tokenRequest: TokenRequest): Call&lt;LoginResponse&gt;
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 
<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Cấu hình Google OAuth2
$google_client_id = '372915420416-dkg8ht28i9p9qoog9diunlk2d1p2ovmt.apps.googleusercontent.com'; // Điền Google Client ID của bạn tại đây
$google_client_secret = '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'; // Điền Google Client Secret của bạn tại đây
$google_redirect_url = $base_url . '/includes/auth/google_callback.php'; // URL chuyển hướng sau khi đăng nhập Google

// Một số URL của Google OAuth2
$google_auth_url = 'https://accounts.google.com/o/oauth2/auth';
$google_token_url = 'https://oauth2.googleapis.com/token';
$google_userinfo_url = 'https://www.googleapis.com/oauth2/v3/userinfo';

// Thiết lập các phạm vi quyền truy cập
$google_scopes = [
    'email',
    'profile',
    'openid'
];

// Kiểm tra xem có cài đặt Google OAuth trong cơ sở dữ liệu không
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('google_client_id', 'google_client_secret') AND category = 'auth'");
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'google_client_id') {
            $google_client_id = $row['setting_value'];
        } elseif ($row['setting_key'] === 'google_client_secret') {
            $google_client_secret = $row['setting_value'];
        }
    }
} catch (PDOException $e) {
    // Xử lý lỗi khi truy vấn cơ sở dữ liệu
    // error_log('Lỗi khi lấy cấu hình Google OAuth: ' . $e->getMessage());
}

// Hàm tạo URL đăng nhập Google
function getGoogleAuthUrl() {
    global $google_auth_url, $google_client_id, $google_redirect_url, $google_scopes;
    
    // Không mã hóa URL cho scope ở đây, để http_build_query làm việc đó
    $scope = implode(' ', $google_scopes);
    $state = bin2hex(random_bytes(16)); // Tạo state ngẫu nhiên để bảo mật
    
    // Lưu state vào session để kiểm tra sau
    $_SESSION['google_auth_state'] = $state;
    
    // Tạo URL đăng nhập
    $auth_url = $google_auth_url . '?' . http_build_query([
        'client_id' => $google_client_id,
        'redirect_uri' => $google_redirect_url,
        'response_type' => 'code',
        'scope' => $scope,
        'state' => $state,
        'prompt' => 'select_account consent',
        'access_type' => 'offline'
    ]);
    
    return $auth_url;
}

// Hàm lấy thông tin token từ code
function getGoogleToken($code) {
    global $google_token_url, $google_client_id, $google_client_secret, $google_redirect_url;
    
    // Tạo dữ liệu POST để lấy token
    $post_data = [
        'code' => $code,
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect_url,
        'grant_type' => 'authorization_code'
    ];
    
    // Khởi tạo cURL
    $ch = curl_init($google_token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    // Lấy kết quả
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Chuyển đổi JSON thành mảng PHP
    $token_data = json_decode($result, true);
    
    return $token_data;
}

// Hàm lấy thông tin người dùng từ token
function getGoogleUserInfo($access_token) {
    global $google_userinfo_url;
    
    // Khởi tạo cURL
    $ch = curl_init($google_userinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    
    // Lấy kết quả
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Chuyển đổi JSON thành mảng PHP
    $user_info = json_decode($result, true);
    
    return $user_info;
}
?> 
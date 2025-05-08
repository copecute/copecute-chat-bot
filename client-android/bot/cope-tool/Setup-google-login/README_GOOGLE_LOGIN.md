# Hướng dẫn cấu hình đăng nhập Google cho ứng dụng Copecute

## Lỗi đăng nhập Google và cách khắc phục

Nếu bạn gặp lỗi `PlatformException(sign_in_failed, com.google.android.gms.common.api.ApiException: 10:, null, null)` khi đăng nhập Google, hãy làm theo hướng dẫn dưới đây để khắc phục:

## Bước 1: Tạo Google Cloud Project và OAuth 2.0 Client ID

1. Truy cập [Google Cloud Console](https://console.cloud.google.com/)
2. Tạo dự án mới hoặc sử dụng dự án hiện có
3. Trong menu bên trái, chọn "APIs & Services" > "Credentials"
4. Nhấp vào "CREATE CREDENTIALS" và chọn "OAuth client ID"
5. Chọn loại ứng dụng là "Android" và điền thông tin:
   - Name: Copecute Android
   - Package name: `com.copecute.bot` (phải trùng với applicationId trong build.gradle.kts)
   - SHA-1 certificate fingerprint: Lấy từ terminal bằng lệnh sau:
   
   ```bash
   cd android
   ./gradlew signingReport
   ```
   
6. Sau đó tạo thêm một OAuth client ID cho Web application:
   - Name: Copecute Web
   - Authorized JavaScript origins: Thêm https://copecute.minhgiang.pro
   - Authorized redirect URIs: Thêm https://copecute.minhgiang.pro/api/google_login.php
   
7. Ghi lại Web client ID (sẽ có dạng `1234567890-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com`)

## Bước 2: Cập nhật file strings.xml

Mở file `android/app/src/main/res/values/strings.xml` và thay thế server_client_id bằng Web client ID từ bước 1:

```xml
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">Copecute Chatbot</string>
    <!-- Thay thế bằng Web client ID từ Google Cloud Console -->
    <string name="server_client_id">1234567890-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com</string>
</resources>
```

## Bước 3: Cập nhật file google-services.json

Bạn có hai lựa chọn:

### Cách 1: Sử dụng Firebase (Khuyên dùng)

1. Truy cập [Firebase Console](https://console.firebase.google.com/)
2. Thêm dự án Android vào Firebase
3. Tải xuống file google-services.json và thay thế file hiện tại trong thư mục android/app

### Cách 2: Tạo file google-services.json thủ công

Cập nhật thông tin trong file `android/app/google-services.json`:

```json
{
  "project_info": {
    "project_number": "YOUR_PROJECT_NUMBER",
    "project_id": "copecute-app",
    "storage_bucket": "copecute-app.appspot.com"
  },
  "client": [
    {
      "client_info": {
        "mobilesdk_app_id": "1:YOUR_PROJECT_NUMBER:android:1234567890abcdef",
        "android_client_info": {
          "package_name": "com.copecute.bot"
        }
      },
      "oauth_client": [
        {
          "client_id": "YOUR_WEB_CLIENT_ID.apps.googleusercontent.com",
          "client_type": 3
        },
        {
          "client_id": "YOUR_ANDROID_CLIENT_ID.apps.googleusercontent.com",
          "client_type": 1,
          "android_info": {
            "package_name": "com.copecute.bot",
            "certificate_hash": "YOUR_SHA1_CERTIFICATE_HASH"
          }
        }
      ],
      "api_key": [
        {
          "current_key": "YOUR_API_KEY"
        }
      ],
      "services": {
        "appinvite_service": {
          "other_platform_oauth_client": []
        }
      }
    }
  ],
  "configuration_version": "1"
}
```

## Bước 4: Cập nhật AuthService

Mở file `lib/services/auth_service.dart` và bỏ chú thích dòng serverClientId:

```dart
// Google Sign-In instance với serverClientId
final GoogleSignIn _googleSignIn = GoogleSignIn(
  scopes: ['email', 'profile'],
  serverClientId: 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com',
);
```

Thay thế 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com' bằng Web client ID thực tế từ Google Cloud Console.

## Bước 5: Cấu hình NDK version

Đảm bảo rằng NDK version đã được cấu hình đúng trong file `android/app/build.gradle.kts`:

```kotlin
android {
    namespace = "com.copecute.bot"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = "27.0.12077973"
    
    // ...
}
```

## Bước 6: Kiểm tra AndroidManifest.xml

Đảm bảo file `android/app/src/main/AndroidManifest.xml` có cấu hình INTERNET permission và usesCleartextTraffic:

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <uses-permission android:name="android.permission.INTERNET" />
    
    <application
        android:label="Copecute"
        android:name="${applicationName}"
        android:icon="@mipmap/launcher_icon"
        android:usesCleartextTraffic="true">
        <!-- ... -->
    </application>
</manifest>
```

## Bước 7: Build và chạy ứng dụng

Sau khi hoàn tất các bước trên, hãy build lại ứng dụng:

```bash
flutter clean
flutter pub get
flutter run
```

## Chế độ Debug

Để kiểm tra quá trình đăng nhập Google, bạn có thể bật chế độ phát triển trong ứng dụng để sử dụng tài khoản giả lập khi gặp lỗi kết nối server:

```dart
AuthService authService = Provider.of<AuthService>(context, listen: false);
authService.toggleDevMode(); // Chuyển đổi giữa chế độ phát triển và sản phẩm
```

## Các lỗi thường gặp và cách khắc phục

### Lỗi 10: Không thể kết nối Google Play Services

- Kiểm tra thiết bị có cài đặt Google Play Services không
- Cập nhật Google Play Services lên phiên bản mới nhất
- Xác nhận rằng thiết bị có Internet

### Lỗi 12501: Đăng nhập bị hủy

- Người dùng đã hủy đăng nhập, không cần xử lý

### Lỗi 16: Lỗi xác thực

- Kiểm tra SHA-1 fingerprint trong Google Cloud Console
- Đảm bảo package name khớp với Android applicationId
- Xác nhận Web client ID đã được cấu hình đúng

### Lỗi "No Firebase App" hoặc "MissingPluginException"

- Chạy `flutter clean` và `flutter pub get`
- Khởi động lại ứng dụng

## Nâng cao: Test trên nhều thiết bị

Khi test trên nhiều thiết bị, bạn cần thêm SHA-1 fingerprint của từng thiết bị vào Google Cloud Console. Để lấy SHA-1:

- Trên Windows:
  ```
  keytool -list -v -keystore "%USERPROFILE%\.android\debug.keystore" -alias androiddebugkey -storepass android -keypass android
  ```

- Trên MacOS/Linux:
  ```
  keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
  ```

Sau đó thêm SHA-1 fingerprint vào OAuth 2.0 Client ID.

## Hỗ trợ

Nếu bạn vẫn gặp vấn đề, vui lòng liên hệ với nhóm phát triển Copecute để được hỗ trợ. 
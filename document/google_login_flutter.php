<?php
// Tài liệu API - Đăng nhập Google với Flutter
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
                <a href="google_login.php" class="list-group-item list-group-item-action">API Đăng nhập Google</a>
                <a href="google_login_flutter.php" class="list-group-item list-group-item-action active">Tích hợp Flutter</a>
                <a href="chat.php" class="list-group-item list-group-item-action">API Chat</a>
                <a href="teach.php" class="list-group-item list-group-item-action">API Dạy Bot</a>
                <a href="history.php" class="list-group-item list-group-item-action">API Lịch sử Chat</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Tích hợp Đăng nhập Google với Flutter</h1>
                </div>
                <div class="card-body">
                    <h2>Tổng quan</h2>
                    <p>Hướng dẫn này sẽ giúp bạn tích hợp đăng nhập Google vào ứng dụng Flutter và kết nối với API đăng nhập Google của SimSimi.</p>
                    
                    <h2>Cài đặt gói</h2>
                    <p>Thêm các gói cần thiết vào file <code>pubspec.yaml</code> của bạn:</p>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>dependencies:
  flutter:
    sdk: flutter
  google_sign_in: ^6.3.0  # Gói đăng nhập Google
  http: ^1.4.0            # Gói HTTP request
  shared_preferences: ^2.5.3  # Lưu trữ local
  provider: ^6.1.2        # State management</code></pre>
                    </div>
                    
                    <h2>Tạo User Model</h2>
                    <p>Tạo file <code>user_model.dart</code> trong thư mục <code>models</code>:</p>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>class User {
  final int userId;
  final String username;
  final String email;
  final int level;
  final String fullName;
  final String? avatar;
  final String token;
  final int quota;

  User({
    required this.userId,
    required this.username,
    required this.email,
    required this.level,
    required this.fullName,
    this.avatar,
    required this.token,
    required this.quota,
  });

  factory User.fromJson(Map&lt;String, dynamic&gt; json) {
    return User(
      userId: json['user_id'],
      username: json['username'],
      email: json['email'],
      level: json['level'],
      fullName: json['full_name'],
      avatar: json['avatar'],
      token: json['token'],
      quota: json['quota'],
    );
  }

  Map&lt;String, dynamic&gt; toJson() {
    return {
      'user_id': userId,
      'username': username,
      'email': email,
      'level': level,
      'full_name': fullName,
      'avatar': avatar,
      'token': token,
      'quota': quota,
    };
  }
}</code></pre>
                    </div>
                    
                    <h2>Tạo Auth Service</h2>
                    <p>Tạo file <code>auth_service.dart</code> trong thư mục <code>services</code>:</p>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';

class AuthService extends ChangeNotifier {
  User? _currentUser;
  bool _isLoading = false;
  String? _errorMessage;

  // Thay đổi URL API cho phù hợp với máy chủ của bạn
  final String _baseUrl = 'https://yourdomain.com/api';

  // Google Sign-In instance
  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
  );

  User? get currentUser => _currentUser;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _currentUser != null;
  String? get errorMessage => _errorMessage;

  AuthService() {
    // Khởi tạo và tự động load user từ local storage
    _loadUserFromPrefs();
  }

  // Lấy user từ SharedPreferences
  Future&lt;void&gt; _loadUserFromPrefs() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final userJson = prefs.getString('user');
      
      if (userJson != null) {
        final userMap = json.decode(userJson);
        _currentUser = User.fromJson(userMap);
      }
    } catch (e) {
      debugPrint('Error loading user: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Lưu user vào SharedPreferences
  Future&lt;void&gt; _saveUserToPrefs(User user) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      prefs.setString('user', json.encode(user.toJson()));
    } catch (e) {
      debugPrint('Error saving user: $e');
    }
  }

  // Đăng nhập bằng Google
  Future&lt;bool&gt; signInWithGoogle() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // Khởi tạo quá trình đăng nhập Google
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      if (googleUser == null) {
        _errorMessage = 'Đăng nhập Google bị hủy';
        _isLoading = false;
        notifyListeners();
        return false;
      }

      // Lấy thông tin xác thực
      final GoogleSignInAuthentication googleAuth = await googleUser.authentication;
      final String idToken = googleAuth.idToken!;

      // Gửi ID token đến server API
      final response = await http.post(
        Uri.parse('$_baseUrl/google_login.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'id_token': idToken}),
      );

      if (response.statusCode == 200) {
        final responseData = json.decode(response.body);
        
        if (responseData['success'] == true && responseData['data'] != null) {
          _currentUser = User.fromJson(responseData['data']);
          await _saveUserToPrefs(_currentUser!);
          _isLoading = false;
          notifyListeners();
          return true;
        } else {
          _errorMessage = responseData['error'] ?? 'Đăng nhập thất bại';
        }
      } else {
        final errorResponse = json.decode(response.body);
        _errorMessage = errorResponse['error'] ?? 'Lỗi kết nối đến server';
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Sign in error: $e');
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  // Đăng xuất
  Future&lt;void&gt; signOut() async {
    _isLoading = true;
    notifyListeners();

    try {
      await _googleSignIn.signOut();
      
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('user');
      
      _currentUser = null;
    } catch (e) {
      _errorMessage = 'Lỗi khi đăng xuất: $e';
      debugPrint('Sign out error: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  // Lấy token xác thực cho các API request
  String? getAuthToken() {
    return _currentUser?.token;
  }

  // Xóa lỗi
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}</code></pre>
                    </div>
                    
                    <h2>Cập nhật file main.dart</h2>
                    <p>Cập nhật file <code>main.dart</code> để sử dụng Provider và AuthService:</p>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'services/auth_service.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (context) => AuthService(),
      child: Consumer&lt;AuthService&gt;(
        builder: (context, authService, _) {
          return MaterialApp(
            title: 'SimSimi Chatbot',
            debugShowCheckedModeBanner: false,
            theme: ThemeData(
              colorScheme: ColorScheme.fromSeed(
                seedColor: Colors.deepPurple,
                brightness: Brightness.light,
              ),
              useMaterial3: true,
              // ... cấu hình theme khác
            ),
            home: authService.isLoading
                ? const LoadingScreen()
                : authService.isLoggedIn
                    ? const HomeScreen()
                    : const LoginScreen(),
          );
        },
      ),
    );
  }
}</code></pre>
                    </div>
                    
                    <h2>Tạo màn hình đăng nhập</h2>
                    <p>Tạo file <code>login_screen.dart</code> trong thư mục <code>screens</code>:</p>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  State&lt;LoginScreen&gt; createState() => _LoginScreenState();
}

class _LoginScreenState extends State&lt;LoginScreen&gt; {
  @override
  Widget build(BuildContext context) {
    final authService = Provider.of&lt;AuthService&gt;(context);

    // Nếu đã đăng nhập, chuyển đến màn hình chính
    if (authService.isLoggedIn) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (context) => const HomeScreen(),
          ),
        );
      });
    }

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Logo
                Image.asset(
                  'assets/images/logo.png',
                  height: 120,
                ),
                const SizedBox(height: 40),
                
                // Tiêu đề
                const Text(
                  'SimSimi Chatbot',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Colors.deepPurple,
                  ),
                ),
                const SizedBox(height: 16),
                
                // Mô tả
                const Text(
                  'Trò chuyện với bot thông minh nhất',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 60),
                
                // Hiển thị thông báo lỗi
                if (authService.errorMessage != null)
                  Container(
                    padding: const EdgeInsets.all(12),
                    margin: const EdgeInsets.only(bottom: 20),
                    decoration: BoxDecoration(
                      color: Colors.red.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      authService.errorMessage!,
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.red.shade900),
                    ),
                  ),
                
                // Nút đăng nhập Google
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: authService.isLoading 
                        ? null 
                        : () async {
                            final success = await authService.signInWithGoogle();
                            if (success && mounted) {
                              Navigator.of(context).pushReplacement(
                                MaterialPageRoute(
                                  builder: (context) => const HomeScreen(),
                                ),
                              );
                            }
                          },
                    icon: Image.asset(
                      'assets/images/google_logo.png',
                      height: 24,
                    ),
                    label: Text(
                      authService.isLoading 
                          ? 'Đang đăng nhập...' 
                          : 'Đăng nhập với Google',
                    ),
                    // ... style configuration
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}</code></pre>
                    </div>
                    
                    <h2>Cấu hình cho Android</h2>
                    <p>Cập nhật file <code>android/app/build.gradle</code> để thêm SHA-1 certificate fingerprint:</p>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>defaultConfig {
    applicationId "com.yourdomain.simsimi"
    minSdkVersion 21  // Tối thiểu cho Google Sign-In
    targetSdkVersion flutter.targetSdkVersion
    versionCode flutterVersionCode.toInteger()
    versionName flutterVersionName
}</code></pre>
                    </div>
                    
                    <p>Cập nhật file <code>android/app/src/main/AndroidManifest.xml</code> để thêm Internet permission:</p>
                    <div class="bg-light p-3 rounded mb-4">
                        <pre><code>&lt;manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.yourdomain.simsimi"&gt;
    &lt;uses-permission android:name="android.permission.INTERNET"/&gt;
    
    &lt;!-- ... Phần còn lại của manifest --&gt;
&lt;/manifest&gt;</code></pre>
                    </div>
                    
                    <h2>Bước tiếp theo</h2>
                    <p>Sau khi đã cài đặt tích hợp đăng nhập Google, bạn có thể tiếp tục tạo các màn hình khác như màn hình chat và tích hợp các API khác như <a href="chat.php">API Chat</a>.</p>
                    
                    <div class="alert alert-info mt-4">
                        <strong>Lưu ý:</strong> Đảm bảo bạn đã cấu hình đúng Google Cloud Project và đã thêm SHA-1 fingerprint của ứng dụng Android vào Firebase Console để đăng nhập Google hoạt động đúng.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 
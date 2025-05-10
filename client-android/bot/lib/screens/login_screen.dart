import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:copecute/utils/auth_service.dart';
import 'package:copecute/screens/chat_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);

    // Nếu đã đăng nhập, chuyển đến màn hình chính
    if (authService.isLoggedIn) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (context) => const ChatScreen(),
          ),
        );
      });
    }

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
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
                    'copecute Chatbot',
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
                        color:
                            _getErrorBackgroundColor(authService.errorMessage!),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Column(
                        children: [
                          Text(
                            authService.errorMessage!,
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color:
                                  _getErrorTextColor(authService.errorMessage!),
                              fontSize: 14,
                            ),
                          ),
                          if (_shouldShowContactAdmin(
                              authService.errorMessage!))
                            Padding(
                              padding: const EdgeInsets.only(top: 8.0),
                              child: Text(
                                'Liên hệ: admin@copecute.com',
                                style: TextStyle(
                                  color: _getErrorTextColor(
                                      authService.errorMessage!),
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),

                  // Nút đăng nhập Google
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: authService.isLoading
                          ? null
                          : () async {
                              final success =
                                  await authService.signInWithGoogle();
                              if (success && mounted) {
                                Navigator.of(context).pushReplacement(
                                  MaterialPageRoute(
                                    builder: (context) => const ChatScreen(),
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
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: Colors.black87,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                          side: BorderSide(color: Colors.grey.shade300),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Chính sách bảo mật
                  const Text(
                    'Bằng cách đăng nhập, bạn đồng ý với Điều khoản sử dụng và Chính sách bảo mật của chúng tôi',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  // Xác định màu nền dựa trên loại lỗi
  Color _getErrorBackgroundColor(String errorMessage) {
    if (errorMessage.contains('đã bị khóa') ||
        errorMessage.contains('chưa được kích hoạt') ||
        errorMessage.contains('tạm khóa')) {
      return Colors.orange.shade100;
    }
    return Colors.red.shade100;
  }

  // Xác định màu chữ dựa trên loại lỗi
  Color _getErrorTextColor(String errorMessage) {
    if (errorMessage.contains('đã bị khóa') ||
        errorMessage.contains('chưa được kích hoạt') ||
        errorMessage.contains('tạm khóa')) {
      return Colors.orange.shade900;
    }
    return Colors.red.shade900;
  }

  // Kiểm tra xem có nên hiển thị thông tin liên hệ admin không
  bool _shouldShowContactAdmin(String errorMessage) {
    return errorMessage.contains('đã bị khóa') ||
        errorMessage.contains('chưa được kích hoạt') ||
        errorMessage.contains('tạm khóa') ||
        errorMessage.contains('liên hệ quản trị viên');
  }
}

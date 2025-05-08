import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class User {
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
    required this.avatar,
    required this.token,
    required this.quota,
  });

  factory User.fromJson(Map<String, dynamic> json) {
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

  Map<String, dynamic> toJson() {
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
}

class AuthService extends ChangeNotifier {
  User? _currentUser;
  bool _isLoading = false;
  String? _errorMessage;

  // API URL
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
  Future<void> _loadUserFromPrefs() async {
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
  Future<void> _saveUserToPrefs(User user) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      prefs.setString('user', json.encode(user.toJson()));
    } catch (e) {
      debugPrint('Error saving user: $e');
    }
  }

  // Đăng nhập bằng Google
  Future<bool> signInWithGoogle() async {
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
      final GoogleSignInAuthentication googleAuth =
          await googleUser.authentication;
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
  Future<void> signOut() async {
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
}

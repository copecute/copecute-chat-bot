import 'dart:convert';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';

class AuthService extends ChangeNotifier {
  User? _currentUser;
  bool _isLoading = false;
  String? _errorMessage;
  bool _registrationLocked = false;

  // API URL
  final String _baseUrl = 'https://copecute.minhgiang.pro/api';

  // Google Sign-In instance
  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
    serverClientId:
        '372915420416-dkg8ht28i9p9qoog9diunlk2d1p2ovmt.apps.googleusercontent.com',
  );

  User? get currentUser => _currentUser;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _currentUser != null;
  String? get errorMessage => _errorMessage;
  bool get registrationLocked => _registrationLocked;

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

  // Xử lý mã lỗi Google Sign-In
  String _getGoogleSignInErrorMessage(dynamic error) {
    try {
      if (error.toString().contains('ApiException: 10:')) {
        return 'Lỗi kết nối đến dịch vụ Google Play. Kiểm tra kết nối Internet và thử lại.';
      } else if (error.toString().contains('ApiException: 12501')) {
        return 'Đăng nhập bị hủy bởi người dùng.';
      } else if (error.toString().contains('ApiException: 16:')) {
        return 'Lỗi xác thực. Vui lòng kiểm tra thông tin đăng nhập.';
      } else if (error.toString().contains('network_error')) {
        return 'Lỗi kết nối mạng. Vui lòng kiểm tra kết nối Internet của bạn.';
      } else if (error.toString().contains('sign_in_required')) {
        return 'Yêu cầu đăng nhập lại.';
      } else if (error.toString().contains('canceled')) {
        return 'Đăng nhập bị hủy.';
      } else {
        return 'Lỗi đăng nhập: ${error.toString()}';
      }
    } catch (_) {
      return 'Lỗi không xác định khi đăng nhập: $error';
    }
  }

  // Xử lý mã lỗi HTTP từ server
  String _parseServerErrorMessage(dynamic responseData) {
    if (responseData is Map<String, dynamic> &&
        responseData.containsKey('error')) {
      final errorMessage = responseData['error'];

      // Xử lý các trường hợp lỗi đặc biệt
      if (errorMessage.contains('Đăng ký tài khoản mới đã bị tạm khóa')) {
        _registrationLocked = true;
        return 'Đăng ký tài khoản mới đã bị tạm khóa. Vui lòng liên hệ quản trị viên!';
      } else if (errorMessage
          .contains('Tài khoản của bạn chưa được kích hoạt')) {
        return 'Tài khoản của bạn chưa được kích hoạt. Vui lòng liên hệ quản trị viên để kích hoạt tài khoản!';
      } else if (errorMessage
          .contains('Tài khoản của bạn đã bị khóa vĩnh viễn')) {
        return 'Tài khoản của bạn đã bị khóa vĩnh viễn. Vui lòng liên hệ quản trị viên để được hỗ trợ!';
      } else if (errorMessage
          .contains('Tài khoản của bạn đã bị khóa đến ngày')) {
        // Trích xuất ngày từ thông báo lỗi
        final dateRegex = RegExp(
            r'Tài khoản của bạn đã bị khóa đến ngày (\d{2}/\d{2}/\d{4})');
        final match = dateRegex.firstMatch(errorMessage);
        final unlockDate = match?.group(1) ?? 'không xác định';
        return 'Tài khoản của bạn đã bị khóa đến ngày $unlockDate. Vui lòng liên hệ quản trị viên để được hỗ trợ!';
      } else if (errorMessage.contains('Tài khoản của bạn đã bị khóa')) {
        return 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ!';
      }

      return errorMessage;
    }
    return 'Lỗi không xác định từ server';
  }

  // Đăng nhập bằng Google
  Future<bool> signInWithGoogle() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      debugPrint('Bắt đầu quá trình đăng nhập Google...');

      // Đăng xuất trước để tránh lỗi phiên
      try {
        await _googleSignIn.signOut();
        debugPrint('Đã đăng xuất Google trước khi đăng nhập lại');
      } catch (e) {
        debugPrint('Không thể đăng xuất Google: $e');
        // Tiếp tục thực hiện đăng nhập ngay cả khi không thể đăng xuất
      }

      // Khởi tạo quá trình đăng nhập Google
      final GoogleSignInAccount? googleUser;
      try {
        googleUser = await _googleSignIn.signIn();
      } catch (e) {
        _errorMessage = _getGoogleSignInErrorMessage(e);
        debugPrint('Lỗi khi gọi signIn: $_errorMessage');
        _isLoading = false;
        notifyListeners();
        return false;
      }

      if (googleUser == null) {
        _errorMessage = 'Đăng nhập Google bị hủy';
        _isLoading = false;
        notifyListeners();
        debugPrint('Người dùng đã hủy đăng nhập Google');
        return false;
      }

      debugPrint(
          'Đã đăng nhập Google thành công với email: ${googleUser.email}');

      // Lấy thông tin xác thực
      debugPrint('Đang lấy token xác thực...');
      final GoogleSignInAuthentication googleAuth;
      try {
        googleAuth = await googleUser.authentication;
      } catch (e) {
        debugPrint('Lỗi khi lấy thông tin xác thực: $e');
        _errorMessage = 'Không thể lấy thông tin xác thực: $e';
        _isLoading = false;
        notifyListeners();
        return false;
      }

      final String? idToken = googleAuth.idToken;
      final String? accessToken = googleAuth.accessToken;

      debugPrint('ID Token: ${idToken != null ? "Có" : "Không có"}');
      debugPrint('Access Token: ${accessToken != null ? "Có" : "Không có"}');

      if (idToken == null || idToken.isEmpty) {
        _errorMessage = 'Không thể lấy token xác thực từ Google';
        _isLoading = false;
        notifyListeners();
        debugPrint('ID token từ Google là null hoặc trống');
        return false;
      }

      // Tạo URL API login
      final apiUrl = 'https://copecute.minhgiang.pro/api/auth/google_login.php';
      debugPrint('Gửi request đến: $apiUrl');

      // Chuẩn bị dữ liệu
      final Map<String, dynamic> loginData = {
        'id_token': idToken,
        'access_token': accessToken,
        'email': googleUser.email,
        'name': googleUser.displayName ?? '',
        'photo': googleUser.photoUrl ?? '',
      };

      debugPrint('Dữ liệu gửi đi: ${loginData.toString()}');

      // Gửi ID token đến server API
      final response = await http.post(
        Uri.parse(apiUrl),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(loginData),
      );

      debugPrint('Phản hồi từ server: HTTP ${response.statusCode}');
      if (response.statusCode == 200) {
        try {
          final responseData = json.decode(response.body);
          final responsePreview = response.body.length > 100
              ? '${response.body.substring(0, 100)}...'
              : response.body;
          debugPrint('Phản hồi JSON: $responsePreview');

          if (responseData['success'] == true && responseData['data'] != null) {
            _currentUser = User.fromJson(responseData['data']);
            await _saveUserToPrefs(_currentUser!);
            _isLoading = false;
            notifyListeners();
            debugPrint('Đăng nhập thành công');
            return true;
          } else {
            _errorMessage = _parseServerErrorMessage(responseData);
            debugPrint('Server trả về lỗi: $_errorMessage');
          }
        } catch (e) {
          _errorMessage = 'Lỗi xử lý dữ liệu: $e';
          debugPrint('Lỗi xử lý JSON: $e');
        }
      } else {
        try {
          final errorResponse = json.decode(response.body);
          _errorMessage = _parseServerErrorMessage(errorResponse);
        } catch (e) {
          _errorMessage = 'Lỗi kết nối đến server: ${response.statusCode}';
        }
        debugPrint('HTTP Error: $_errorMessage');
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi khi đăng nhập: $e';
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

  // Cập nhật thông tin người dùng
  void updateUser(User user) {
    _currentUser = user;
    _saveUserToPrefs(user);
    notifyListeners();
  }

  // Cập nhật thông tin hồ sơ người dùng
  Future<bool> updateUserProfile(Map<String, dynamic> userData) async {
    if (_currentUser == null) return false;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // Tạo user mới với thông tin cập nhật
      final updatedUser = User(
        userId: _currentUser!.userId,
        username: _currentUser!.username,
        email: _currentUser!.email,
        level: _currentUser!.level,
        fullName: userData['full_name'] ?? _currentUser!.fullName,
        avatar: userData['avatar'] ?? _currentUser!.avatar,
        token: _currentUser!.token,
        quota: _currentUser!.quota,
        bio: userData['bio'] ?? _currentUser!.bio,
        phone: userData['phone'] ?? _currentUser!.phone,
        gender: userData['gender'] ?? _currentUser!.gender,
        birthday: userData['birthday'] ?? _currentUser!.birthday,
        address: userData['address'] ?? _currentUser!.address,
        createdAt: _currentUser!.createdAt,
      );

      // Cập nhật người dùng
      _currentUser = updatedUser;
      _saveUserToPrefs(updatedUser);

      return true;
    } catch (e) {
      _errorMessage = 'Lỗi cập nhật thông tin: $e';
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}

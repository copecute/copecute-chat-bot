import 'dart:convert';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';
import '../utils/constants.dart';

class AuthService extends ChangeNotifier {
  User? _currentUser;
  bool _isLoading = false;
  String? _errorMessage;

  // Sử dụng URL từ AppConstants
  final String _baseUrl = AppConstants.apiUrl;

  // Chuyển đổi giữa URL sản phẩm và phát triển
  bool _isDevMode = false;

  String get baseUrl =>
      _isDevMode ? AppConstants.devApiUrl : AppConstants.apiUrl;

  // Google Sign-In instance với serverClientId
  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
    // Thêm dòng dưới đây và chú thích lại nếu gặp lỗi
    serverClientId:
        '372915420416-dkg8ht28i9p9qoog9diunlk2d1p2ovmt.apps.googleusercontent.com',
  );

  User? get currentUser => _currentUser;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _currentUser != null;
  String? get errorMessage => _errorMessage;
  bool get isDevMode => _isDevMode;

  // Chuyển đổi chế độ phát triển
  void toggleDevMode() {
    _isDevMode = !_isDevMode;
    debugPrint(
        'Đã chuyển sang ${_isDevMode ? "chế độ phát triển" : "chế độ sản phẩm"}');
    debugPrint('API URL hiện tại: $baseUrl');
    notifyListeners();
  }

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
      final userJson = prefs.getString(AppConstants.userPrefKey);

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
      prefs.setString(AppConstants.userPrefKey, json.encode(user.toJson()));
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

  // Đăng nhập bằng Google
  Future<bool> signInWithGoogle() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      debugPrint('Bắt đầu quá trình đăng nhập Google...');

      // Đăng xuất trước đề phòng lỗi phiên đăng nhập
      try {
        await _googleSignIn.signOut();
        debugPrint('Đã đăng xuất Google trước khi đăng nhập lại');
      } catch (e) {
        debugPrint('Không thể đăng xuất Google: $e');
        // Tiếp tục thực hiện đăng nhập ngay cả khi không thể đăng xuất
      }

      // Khởi tạo quá trình đăng nhập Google
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
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

      debugPrint(
          'ID Token: ${idToken != null ? 'Có (${idToken.substring(0, math.min(20, idToken.length))}...)' : 'Không có'}');
      debugPrint(
          'Access Token: ${accessToken != null ? 'Có (${accessToken.substring(0, math.min(20, accessToken.length))}...)' : 'Không có'}');

      if (idToken == null || idToken.isEmpty) {
        _errorMessage = 'Không thể lấy token xác thực';
        _isLoading = false;
        notifyListeners();
        debugPrint('Không nhận được ID token từ Google');
        return false;
      }

      // Chuẩn bị thông tin người dùng từ Google account
      final userData = {
        'id_token': idToken,
        'access_token': accessToken,
        'email': googleUser.email,
        'name': googleUser.displayName ?? '',
        'photo': googleUser.photoUrl ?? '',
        'id': googleUser.id,
      };

      debugPrint('Thông tin người dùng: $userData');

      // Xử lý đăng nhập dựa trên chế độ
      if (_isDevMode) {
        try {
          debugPrint('Thử kết nối tới ${baseUrl}/google_login.php');

          // Gửi ID token đến server API
          final response = await http.post(
            Uri.parse('${baseUrl}/google_login.php'),
            headers: {'Content-Type': 'application/json'},
            body: json.encode(userData),
          );

          debugPrint('Phản hồi từ máy chủ: ${response.statusCode}');
          debugPrint('Nội dung phản hồi: ${response.body}');

          if (response.statusCode == 200) {
            final responseData = json.decode(response.body);

            if (responseData['success'] == true &&
                responseData['data'] != null) {
              _currentUser = User.fromJson(responseData['data']);
              await _saveUserToPrefs(_currentUser!);
              _isLoading = false;
              notifyListeners();
              return true;
            } else {
              _errorMessage = responseData['error'] ?? 'Đăng nhập thất bại';
            }
          } else {
            try {
              final errorResponse = json.decode(response.body);
              _errorMessage = errorResponse['error'] ??
                  'Lỗi kết nối đến server: ${response.statusCode}';
            } catch (e) {
              _errorMessage = 'Lỗi kết nối đến server: ${response.statusCode}';
            }
          }
        } catch (e) {
          debugPrint('Lỗi khi kết nối đến server dev: $e');

          // Tạo user giả lập cho mục đích test
          debugPrint('Tạo user giả lập để test');
          _currentUser = User(
            userId: 1,
            username: googleUser.displayName ??
                'user${DateTime.now().millisecondsSinceEpoch}',
            email: googleUser.email,
            level: 0,
            fullName: googleUser.displayName ?? 'Người dùng',
            avatar: googleUser.photoUrl,
            token: 'token_test_${DateTime.now().millisecondsSinceEpoch}',
            quota: 100,
          );

          await _saveUserToPrefs(_currentUser!);
          _isLoading = false;
          notifyListeners();

          // Đăng nhập thành công với user giả lập
          return true;
        }
      } else {
        // Kết nối server thật (sản phẩm)
        bool isSuccess = false;
        String errorMsg = '';

        // Thử nghiệm với nhiều endpoints khác nhau
        List<String> endpoints = [
          '${baseUrl}/google_login.php',
          '${AppConstants.baseUrl}/google_login.php',
          '${AppConstants.baseUrl}/login.php'
        ];

        for (String endpoint in endpoints) {
          if (isSuccess) break; // Nếu đã thành công thì không cần thử tiếp

          try {
            debugPrint('Thử kết nối tới $endpoint');
            final response = await http.post(
              Uri.parse(endpoint),
              headers: {'Content-Type': 'application/json'},
              body: json.encode(userData),
            );

            debugPrint('Phản hồi từ $endpoint: ${response.statusCode}');
            debugPrint('Nội dung phản hồi: ${response.body}');

            if (response.statusCode == 200) {
              try {
                final responseData = json.decode(response.body);

                if (responseData['success'] == true &&
                    responseData['data'] != null) {
                  _currentUser = User.fromJson(responseData['data']);
                  await _saveUserToPrefs(_currentUser!);
                  isSuccess = true;
                  break;
                } else {
                  errorMsg = responseData['error'] ?? 'Đăng nhập thất bại';
                }
              } catch (e) {
                debugPrint('Lỗi khi phân tích dữ liệu JSON: $e');
                errorMsg = 'Lỗi khi phân tích dữ liệu: $e';
              }
            } else {
              try {
                final errorResponse = json.decode(response.body);
                errorMsg = errorResponse['error'] ??
                    'Lỗi kết nối đến server: ${response.statusCode}';
              } catch (e) {
                errorMsg = 'Lỗi kết nối đến server: ${response.statusCode}';
              }
            }
          } catch (e) {
            debugPrint('Lỗi khi kết nối đến endpoint $endpoint: $e');
            errorMsg = 'Lỗi kết nối đến server: $e';
          }
        }

        if (isSuccess) {
          _isLoading = false;
          notifyListeners();
          return true;
        } else {
          // Nếu tất cả các phương pháp kết nối thất bại và đang ở chế độ phát triển
          // hoặc trong trường hợp khẩn cấp, tạo user giả lập để tiếp tục
          if (_isDevMode) {
            debugPrint('Tạo user giả lập để test trong trường hợp khẩn cấp');
            _currentUser = User(
              userId: 1,
              username: googleUser.displayName ??
                  'user${DateTime.now().millisecondsSinceEpoch}',
              email: googleUser.email,
              level: 0,
              fullName: googleUser.displayName ?? 'Người dùng',
              avatar: googleUser.photoUrl,
              token: 'token_test_${DateTime.now().millisecondsSinceEpoch}',
              quota: 100,
            );

            await _saveUserToPrefs(_currentUser!);
            _isLoading = false;
            notifyListeners();
            return true;
          }

          _errorMessage = errorMsg;
        }
      }
    } catch (e) {
      _errorMessage = _getGoogleSignInErrorMessage(e);
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
      await prefs.remove(AppConstants.userPrefKey);

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

  // Cập nhật thông tin người dùng
  Future<bool> updateUserProfile(Map<String, dynamic> userData) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final token = getAuthToken();
      if (token == null) {
        _errorMessage = 'Không có token xác thực';
        _isLoading = false;
        notifyListeners();
        return false;
      }

      // Gửi yêu cầu cập nhật thông tin đến server
      final response = await http.post(
        Uri.parse('${baseUrl}/update_profile.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: json.encode(userData),
      );

      if (response.statusCode == 200) {
        final responseData = json.decode(response.body);

        if (responseData['success'] == true) {
          // Cập nhật thông tin người dùng hiện tại
          if (_currentUser != null) {
            final updatedUser = User(
              userId: _currentUser!.userId,
              username: _currentUser!.username,
              email: _currentUser!.email,
              level: _currentUser!.level,
              fullName: userData['fullName'] ?? _currentUser!.fullName,
              avatar: userData['avatar'] ?? _currentUser!.avatar,
              token: _currentUser!.token,
              quota: _currentUser!.quota,
              bio: userData['bio'] ?? _currentUser!.bio,
            );

            _currentUser = updatedUser;
            await _saveUserToPrefs(_currentUser!);
          }

          _isLoading = false;
          notifyListeners();
          return true;
        } else {
          _errorMessage = responseData['error'] ?? 'Cập nhật thất bại';
        }
      } else {
        final errorResponse = json.decode(response.body);
        _errorMessage = errorResponse['error'] ?? 'Lỗi kết nối đến server';
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Update profile error: $e');
    }

    // Nếu đang ở chế độ phát triển, cho phép cập nhật offline
    if (_isDevMode && _currentUser != null) {
      debugPrint(
          'Đang ở chế độ phát triển, cập nhật thông tin người dùng offline');

      final updatedUser = User(
        userId: _currentUser!.userId,
        username: _currentUser!.username,
        email: _currentUser!.email,
        level: _currentUser!.level,
        fullName: userData['fullName'] ?? _currentUser!.fullName,
        avatar: userData['avatar'] ?? _currentUser!.avatar,
        token: _currentUser!.token,
        quota: _currentUser!.quota,
        bio: userData['bio'] ?? _currentUser!.bio,
      );

      _currentUser = updatedUser;
      await _saveUserToPrefs(_currentUser!);

      _isLoading = false;
      notifyListeners();
      return true;
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  // Xóa lỗi
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/user_model.dart';
import '../utils/constants.dart';
import '../utils/app_routes.dart';
import 'package:provider/provider.dart';
import '../utils/auth_service.dart';

class ProfileService extends ChangeNotifier {
  bool _isLoading = false;
  String? _errorMessage;
  User? _profileData;

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  User? get profileData => _profileData;

  // Lấy thông tin hồ sơ
  Future<User?> getProfile(String token, [BuildContext? context]) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final response = await http.get(
        Uri.parse(AppConstants.profileEndpoint),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      debugPrint('Profile response status: ${response.statusCode}');

      if (response.statusCode == 401 && context != null && context.mounted) {
        // Token không hợp lệ, đăng xuất và chuyển hướng
        await _handleUnauthorized(context);
        return null;
      }

      if (response.statusCode == 200) {
        final responseData = json.decode(response.body);

        if (responseData['success'] == true) {
          final profileData = responseData['data'];

          // Tạo user từ dữ liệu profile
          final user = await _getUpdatedUser(token, profileData, context);
          _profileData = user;

          return user;
        } else {
          _errorMessage =
              responseData['error'] ?? 'Không thể lấy thông tin hồ sơ';
          debugPrint('Error from server: $_errorMessage');
          return null;
        }
      } else {
        _errorMessage = 'Lỗi kết nối: ${response.statusCode}';
        debugPrint('HTTP error: $_errorMessage');
        return null;
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Error getting profile: $e');
      return null;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Cập nhật thông tin hồ sơ
  Future<bool> updateProfile(String token, Map<String, dynamic> data,
      [BuildContext? context]) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final response = await http.post(
        Uri.parse(AppConstants.profileUpdateEndpoint),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: json.encode(data),
      );

      debugPrint('Update profile response status: ${response.statusCode}');

      if (response.statusCode == 401 && context != null && context.mounted) {
        // Token không hợp lệ, đăng xuất và chuyển hướng
        await _handleUnauthorized(context);
        return false;
      }

      if (response.statusCode == 200) {
        final responseData = json.decode(response.body);

        if (responseData['success'] == true) {
          // Cập nhật thành công, cập nhật lại dữ liệu hồ sơ
          await getProfile(token, context);
          return true;
        } else {
          _errorMessage =
              responseData['error'] ?? 'Không thể cập nhật thông tin hồ sơ';
          debugPrint('Error from server: $_errorMessage');
          return false;
        }
      } else {
        _errorMessage = 'Lỗi kết nối: ${response.statusCode}';
        debugPrint('HTTP error: $_errorMessage');
        return false;
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Error updating profile: $e');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Đổi mật khẩu
  Future<bool> changePassword(String token, String currentPassword,
      String newPassword, String confirmPassword,
      [BuildContext? context]) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final response = await http.post(
        Uri.parse(AppConstants.changePasswordEndpoint),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: json.encode({
          'current_password': currentPassword,
          'new_password': newPassword,
          'confirm_password': confirmPassword,
        }),
      );

      debugPrint('Change password response status: ${response.statusCode}');

      if (response.statusCode == 401 && context != null && context.mounted) {
        // Token không hợp lệ, đăng xuất và chuyển hướng
        await _handleUnauthorized(context);
        return false;
      }

      if (response.statusCode == 200) {
        final responseData = json.decode(response.body);

        if (responseData['success'] == true) {
          return true;
        } else {
          _errorMessage = responseData['error'] ?? 'Không thể đổi mật khẩu';
          debugPrint('Error from server: $_errorMessage');
          return false;
        }
      } else {
        try {
          // Phân tích phản hồi lỗi để lấy thông báo chi tiết
          final errorResponse = json.decode(response.body);
          _errorMessage =
              errorResponse['error'] ?? 'Lỗi kết nối: ${response.statusCode}';

          // Phân tích mã lỗi 400 để hiển thị thông báo cụ thể
          if (response.statusCode == 400) {
            if (_errorMessage != null &&
                _errorMessage!.contains('current_password')) {
              _errorMessage = 'Mật khẩu hiện tại không đúng';
            } else if (_errorMessage != null &&
                (_errorMessage!.contains('match') ||
                    _errorMessage!.contains('confirm'))) {
              _errorMessage = 'Mật khẩu nhập lại không khớp với mật khẩu mới';
            } else if (_errorMessage != null &&
                (_errorMessage!.contains('length') ||
                    _errorMessage!.contains('short'))) {
              _errorMessage = 'Mật khẩu mới quá ngắn (tối thiểu 6 ký tự)';
            } else if (_errorMessage != null &&
                _errorMessage!.contains('same')) {
              _errorMessage = 'Mật khẩu mới không được trùng với mật khẩu cũ';
            } else if (_errorMessage != null &&
                _errorMessage!.contains('complex')) {
              _errorMessage =
                  'Mật khẩu mới quá đơn giản, cần bao gồm chữ hoa, chữ thường và số';
            } else {
              // Hiển thị thông báo chung nếu không xác định được lỗi cụ thể
              _errorMessage =
                  'Có lỗi khi đổi mật khẩu. Vui lòng kiểm tra lại thông tin nhập vào.';
            }

            // In thông báo lỗi chi tiết cho nhà phát triển
            debugPrint('Chi tiết lỗi đổi mật khẩu: $_errorMessage');
          }

          debugPrint('HTTP error: $_errorMessage');
        } catch (e) {
          _errorMessage = 'Lỗi kết nối: ${response.statusCode}';
          debugPrint('HTTP error: $_errorMessage');
        }
        return false;
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Error changing password: $e');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Xử lý lỗi xác thực token
  Future<void> _handleUnauthorized(BuildContext context) async {
    _errorMessage = 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại';

    // Đăng xuất người dùng
    final authService = Provider.of<AuthService>(context, listen: false);
    await authService.signOut();

    // Chuyển hướng đến trang đăng nhập
    if (context.mounted) {
      Navigator.of(context).pushNamedAndRemoveUntil(
        AppRoutes.login,
        (route) => false,
      );
    }
  }

  // Cập nhật thông tin người dùng từ dữ liệu profile
  Future<User?> _getUpdatedUser(String token, Map<String, dynamic> profileData,
      BuildContext? context) async {
    // Nếu có context, cập nhật thông tin người dùng trong AuthService
    if (context != null && context.mounted) {
      final authService = Provider.of<AuthService>(context, listen: false);
      final currentUser = authService.currentUser;

      if (currentUser != null) {
        // Tạo user mới với thông tin cập nhật từ profile
        final updatedUser = User(
          userId: currentUser.userId,
          username: profileData['username'] ?? currentUser.username,
          email: profileData['email'] ?? currentUser.email,
          level: currentUser.level,
          fullName: profileData['full_name'] ?? currentUser.fullName,
          avatar: profileData['avatar'] ?? currentUser.avatar,
          token: currentUser.token,
          quota: profileData['quota'] != null
              ? int.parse(profileData['quota'].toString())
              : currentUser.quota,
          bio: profileData['bio'] ?? currentUser.bio,
          phone: profileData['phone'],
          gender: profileData['gender'] ?? 'other',
          birthday: profileData['birthday'],
          address: profileData['address'],
          createdAt: profileData['created_at'],
        );

        // Cập nhật người dùng trong AuthService
        authService.updateUser(updatedUser);

        return updatedUser;
      }
    }

    // Nếu không có context hoặc không có người dùng hiện tại, trả về null
    return null;
  }

  // Xóa thông báo lỗi
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}

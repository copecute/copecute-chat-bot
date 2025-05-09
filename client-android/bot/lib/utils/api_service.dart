import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'auth_service.dart';
import 'app_routes.dart';
import 'constants.dart';

/// API Service class để xử lý tất cả các HTTP request và tự động xử lý các mã lỗi 401 Unauthorized
class ApiService {
  final BuildContext context;
  late AuthService _authService;

  ApiService(this.context) {
    _authService = Provider.of<AuthService>(context, listen: false);
  }

  /// Factory constructor để dễ dàng khởi tạo
  static ApiService of(BuildContext context) => ApiService(context);

  /// Thực hiện HTTP GET request
  Future<Map<String, dynamic>> get(String endpoint,
      {Map<String, String>? headers}) async {
    try {
      final token = _authService.getAuthToken();
      final requestHeaders = {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
        ...?headers,
      };

      debugPrint('GET request to: $endpoint');
      final response = await http.get(
        Uri.parse(endpoint),
        headers: requestHeaders,
      );

      return _handleResponse(response);
    } catch (e) {
      debugPrint('Error during GET request: $e');
      return {'success': false, 'error': 'Đã xảy ra lỗi: $e'};
    }
  }

  /// Thực hiện HTTP POST request
  Future<Map<String, dynamic>> post(
    String endpoint, {
    Map<String, dynamic>? body,
    Map<String, String>? headers,
  }) async {
    try {
      final token = _authService.getAuthToken();
      final requestHeaders = {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
        ...?headers,
      };

      debugPrint('POST request to: $endpoint');
      debugPrint('POST body: $body');

      final response = await http.post(
        Uri.parse(endpoint),
        headers: requestHeaders,
        body: body != null ? json.encode(body) : null,
      );

      return _handleResponse(response);
    } catch (e) {
      debugPrint('Error during POST request: $e');
      return {'success': false, 'error': 'Đã xảy ra lỗi: $e'};
    }
  }

  /// Thực hiện HTTP PUT request
  Future<Map<String, dynamic>> put(
    String endpoint, {
    Map<String, dynamic>? body,
    Map<String, String>? headers,
  }) async {
    try {
      final token = _authService.getAuthToken();
      final requestHeaders = {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
        ...?headers,
      };

      debugPrint('PUT request to: $endpoint');
      final response = await http.put(
        Uri.parse(endpoint),
        headers: requestHeaders,
        body: body != null ? json.encode(body) : null,
      );

      return _handleResponse(response);
    } catch (e) {
      debugPrint('Error during PUT request: $e');
      return {'success': false, 'error': 'Đã xảy ra lỗi: $e'};
    }
  }

  /// Thực hiện HTTP DELETE request
  Future<Map<String, dynamic>> delete(
    String endpoint, {
    Map<String, dynamic>? body,
    Map<String, String>? headers,
  }) async {
    try {
      final token = _authService.getAuthToken();
      final requestHeaders = {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
        ...?headers,
      };

      debugPrint('DELETE request to: $endpoint');
      final response = await http.delete(
        Uri.parse(endpoint),
        headers: requestHeaders,
        body: body != null ? json.encode(body) : null,
      );

      return _handleResponse(response);
    } catch (e) {
      debugPrint('Error during DELETE request: $e');
      return {'success': false, 'error': 'Đã xảy ra lỗi: $e'};
    }
  }

  /// Xử lý response từ server
  Map<String, dynamic> _handleResponse(http.Response response) {
    debugPrint('Response status code: ${response.statusCode}');
    debugPrint(
        'Response body preview: ${_truncateResponseBody(response.body)}');

    try {
      if (response.statusCode == 401) {
        // Xử lý lỗi 401 Unauthorized
        _handleUnauthorized();
        return {
          'success': false,
          'error': 'Phiên đăng nhập đã hết hạn',
          'code': 'unauthorized'
        };
      }

      final responseData = json.decode(response.body);

      if (response.statusCode >= 200 && response.statusCode < 300) {
        // Nếu response có cấu trúc chuẩn {success: true, data: {...}}
        if (responseData is Map && responseData.containsKey('success')) {
          return responseData as Map<String, dynamic>;
        }
        // Nếu không, wrap lại
        return {'success': true, 'data': responseData};
      } else {
        // Xử lý lỗi với các mã khác
        String errorMessage = 'Lỗi không xác định';
        String? errorCode;

        if (responseData is Map) {
          if (responseData.containsKey('error')) {
            errorMessage = responseData['error'].toString();
          }
          if (responseData.containsKey('code')) {
            errorCode = responseData['code'].toString();
          }
        }

        return {
          'success': false,
          'error': errorMessage,
          if (errorCode != null) 'code': errorCode,
          'status': response.statusCode,
        };
      }
    } catch (e) {
      debugPrint('Error parsing response: $e');
      return {
        'success': false,
        'error': 'Lỗi xử lý dữ liệu: $e',
        'status': response.statusCode,
      };
    }
  }

  /// Xử lý khi token không hợp lệ hoặc hết hạn (mã 401)
  Future<void> _handleUnauthorized() async {
    debugPrint('Token is invalid or expired. Logging out...');

    // Đăng xuất người dùng
    await _authService.signOut();

    // Hiển thị thông báo cho người dùng nếu đang ở màn hình chính
    // và không ở trong dialog hay một modal khác
    _showLogoutDialog();

    // Chuyển hướng về màn hình đăng nhập - giảm thời gian chờ để tránh vấn đề UI
    Future.delayed(const Duration(milliseconds: 500), () {
      if (context.mounted) {
        Navigator.of(context).pushNamedAndRemoveUntil(
          AppRoutes.login,
          (route) => false,
        );
      }
    });
  }

  /// Hiển thị dialog thông báo đăng xuất
  void _showLogoutDialog() {
    // Chỉ hiển thị dialog thông báo nếu không có dialog/modal đang mở
    if (context.mounted) {
      // Kiểm tra xem có đang ở trong một dialog hay không
      bool isInDialog = false;
      Navigator.of(context, rootNavigator: true).popUntil((route) {
        isInDialog = route.isCurrent && route is DialogRoute;
        return true;
      });

      // Nếu không ở trong dialog, hiển thị thông báo
      if (!isInDialog) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text(
                'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.'),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 3),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  /// Rút gọn nội dung response để in log
  String _truncateResponseBody(String body) {
    if (body.length > 200) {
      return '${body.substring(0, 200)}...';
    }
    return body;
  }
}

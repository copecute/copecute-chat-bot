import 'dart:convert';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/message.dart';
import '../utils/constants.dart';
import '../utils/app_routes.dart';
import 'auth_service.dart';
import 'package:provider/provider.dart';

class ChatService extends ChangeNotifier {
  List<Message> _messages = [];
  bool _isLoading = false;
  bool _isTyping = false;
  String? _errorMessage;

  List<Message> get messages => _messages;
  bool get isLoading => _isLoading;
  bool get isTyping => _isTyping;
  String? get errorMessage => _errorMessage;

  // Phương thức trợ giúp để chuyển đổi dữ liệu từ nhiều định dạng khác nhau
  List<Message> _parseHistoryData(dynamic data) {
    List<Message> result = [];

    try {
      if (data == null) return result;

      if (data is List) {
        // Kiểu 1: [{"message": "...", "is_user": true, "timestamp": "..."}]
        debugPrint('Data is a List with ${data.length} items');
        for (var item in data) {
          if (item is Map) {
            result.add(Message(
              text: item['message'] ?? '',
              isSentByUser: item['is_user'] == 1 || item['is_user'] == true,
              timestamp: DateTime.parse(
                  item['timestamp'] ?? DateTime.now().toString()),
            ));
          }
        }
      } else if (data is Map) {
        // Kiểu 2: {"1": {"message": "...", "is_user": true, "timestamp": "..."}, "2": {...}}
        debugPrint('Data is a Map with ${data.length} items');
        data.forEach((key, value) {
          if (value is Map) {
            result.add(Message(
              text: value['message'] ?? '',
              isSentByUser: value['is_user'] == 1 || value['is_user'] == true,
              timestamp: DateTime.parse(
                  value['timestamp'] ?? DateTime.now().toString()),
            ));
          }
        });
      } else {
        // Kiểu không hỗ trợ
        debugPrint('Unsupported data type: ${data.runtimeType}');
      }

      // Sắp xếp theo thời gian
      if (result.isNotEmpty) {
        result.sort((a, b) => a.timestamp.compareTo(b.timestamp));
      }
    } catch (e) {
      debugPrint('Error parsing history data: $e');
    }

    return result;
  }

  // Thêm tin nhắn vào danh sách
  void _addMessage(Message message) {
    // Tìm vị trí chèn phù hợp dựa trên timestamp
    int insertIndex = _messages.length;
    for (int i = 0; i < _messages.length; i++) {
      if (message.timestamp.isBefore(_messages[i].timestamp)) {
        insertIndex = i;
        break;
      }
    }

    // Chèn tin nhắn vào vị trí thích hợp
    _messages.insert(insertIndex, message);

    // Đảm bảo dữ liệu đã sẵn sàng trước khi thông báo UI
    Future.microtask(() {
      notifyListeners();
    });
  }

  // Gửi tin nhắn và nhận phản hồi
  Future<Message?> sendMessage(String text, String token,
      [BuildContext? context]) async {
    try {
      _isLoading = true;
      _isTyping = true; // Bắt đầu hiệu ứng đang nhập
      notifyListeners();

      // Tạo tin nhắn người dùng
      final userMessage = Message(
        text: text,
        isSentByUser: true,
        timestamp: DateTime.now(),
      );

      // Thêm tin nhắn vào danh sách
      _addMessage(userMessage);

      debugPrint('Sending message to server: $text');
      debugPrint('API endpoint: ${AppConstants.chatEndpoint}');

      // Gửi tin nhắn lên server
      final response = await http.post(
        Uri.parse(AppConstants.chatEndpoint),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token'
        },
        body: json.encode({
          'message': text,
        }),
      );

      debugPrint('Response status code: ${response.statusCode}');
      debugPrint('Response body: ${response.body}');

      // Xử lý lỗi 401 Unauthorized
      if (response.statusCode == 401) {
        _errorMessage = 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại';
        debugPrint('Auth error: $_errorMessage');
        _isLoading = false;
        _isTyping = false;
        notifyListeners();

        // Nếu context được cung cấp, chuyển hướng đến màn hình đăng nhập
        if (context != null) {
          // Đăng xuất người dùng hiện tại
          final authService = AuthService();
          await authService.signOut();

          // Chuyển hướng đến màn hình đăng nhập
          Navigator.of(context).pushNamedAndRemoveUntil(
            AppRoutes.login,
            (route) => false,
          );
        }

        return null;
      }

      // Giả lập thời gian đang nhập tin nhắn (2-3 giây) - giảm thời gian chờ
      final random = math.Random();
      final typingDuration =
          Duration(milliseconds: 1500 + random.nextInt(1500));
      await Future.delayed(typingDuration);

      // Đã nhận được phản hồi, kết thúc hiệu ứng đang nhập
      _isTyping = false;
      notifyListeners();

      if (response.statusCode == 200) {
        final responseData = json.decode(response.body);
        debugPrint('Parsed response data: $responseData');

        if (responseData['success'] == true) {
          // Tạo tin nhắn phản hồi từ bot
          // Lấy bot_response từ trường data
          String botResponse = 'Không có phản hồi từ server';

          // Kiểm tra nhiều định dạng khác nhau của dữ liệu phản hồi
          if (responseData.containsKey('data') && responseData['data'] is Map) {
            final data = responseData['data'] as Map;
            if (data.containsKey('bot_response')) {
              botResponse = data['bot_response'].toString();
            } else if (data.containsKey('response')) {
              botResponse = data['response'].toString();
            }
          } else if (responseData.containsKey('bot_response')) {
            // Hỗ trợ trường bot_response trực tiếp trong response
            botResponse = responseData['bot_response'].toString();
          } else if (responseData.containsKey('response')) {
            // Hỗ trợ cấu trúc response cũ nếu có
            botResponse = responseData['response'].toString();
          } else if (responseData.containsKey('message')) {
            // Hỗ trợ thêm trường message
            botResponse = responseData['message'].toString();
          }

          debugPrint('Bot response: $botResponse');

          // Xác định xem có phải là phản hồi mặc định không
          bool isDefaultResponse = false;
          if (responseData.containsKey('data') && responseData['data'] is Map) {
            final data = responseData['data'] as Map;
            if (data.containsKey('is_default_response')) {
              isDefaultResponse = data['is_default_response'] == true;
              debugPrint('Is default response: $isDefaultResponse');
            }
          }

          final botMessage = Message(
            text: botResponse,
            isSentByUser: false,
            timestamp: DateTime.now(),
            isDefaultResponse: isDefaultResponse,
          );

          // Thêm tin nhắn vào danh sách
          _addMessage(botMessage);

          _isLoading = false;
          notifyListeners();
          return botMessage;
        } else {
          _errorMessage = responseData['error'] ?? 'Lỗi không xác định';
          debugPrint('Error from server: $_errorMessage');
          _isLoading = false;
          notifyListeners();
          return null;
        }
      } else {
        _errorMessage = 'Lỗi kết nối: ${response.statusCode}';
        debugPrint('HTTP error: $_errorMessage');
        _isLoading = false;
        notifyListeners();
        return null;
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Error sending message: $e');
      _isLoading = false;
      _isTyping = false;
      notifyListeners();

      // Tạo phản hồi giả khi gặp lỗi
      final fakeResponse = Message(
        text:
            'Xin lỗi, có lỗi xảy ra khi kết nối đến server. Vui lòng thử lại sau.',
        isSentByUser: false,
        timestamp: DateTime.now(),
      );
      _addMessage(fakeResponse);

      return fakeResponse;
    }
  }

  // Lấy lịch sử chat từ server
  Future<void> fetchChatHistory(String token, [BuildContext? context]) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      debugPrint('Fetching chat history from: ${AppConstants.historyEndpoint}');

      final response = await http.get(
        Uri.parse(AppConstants.historyEndpoint),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token'
        },
      );

      debugPrint('History response status: ${response.statusCode}');

      // Xử lý lỗi 401 Unauthorized
      if (response.statusCode == 401) {
        _errorMessage = 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại';
        debugPrint('Auth error: $_errorMessage');
        _isLoading = false;
        notifyListeners();

        // Nếu context được cung cấp, chuyển hướng đến màn hình đăng nhập
        if (context != null) {
          // Đăng xuất người dùng hiện tại
          final authService = AuthService();
          await authService.signOut();

          // Chuyển hướng đến màn hình đăng nhập
          Navigator.of(context).pushNamedAndRemoveUntil(
            AppRoutes.login,
            (route) => false,
          );
        }

        return;
      }

      if (response.statusCode == 200) {
        final responseData = json.decode(response.body);

        // In ra dữ liệu để gỡ lỗi
        debugPrint('Response from history API: ${response.body}');

        if (responseData['success'] == true) {
          // Xử lý dữ liệu lịch sử chat
          dynamic historyData;

          // Kiểm tra các cấu trúc dữ liệu khác nhau từ server
          if (responseData.containsKey('data')) {
            final data = responseData['data'];
            debugPrint('Found history data in "data" field');

            // Kiểm tra nếu data là Map và có chứa trường history
            if (data is Map && data.containsKey('history')) {
              historyData = data['history'];
              debugPrint(
                  'Found history array in data.history with ${historyData is List ? historyData.length : 0} items');
            } else {
              historyData = data;
            }
          } else if (responseData.containsKey('messages')) {
            historyData = responseData['messages'];
            debugPrint('Found history data in "messages" field');
          } else if (responseData.containsKey('history')) {
            historyData = responseData['history'];
            debugPrint('Found history data in "history" field');
          }

          if (historyData != null) {
            // Chuyển đổi dữ liệu lịch sử thành tin nhắn
            List<Message> parsedMessages = [];

            // Nếu historyData là danh sách các tin nhắn
            if (historyData is List) {
              debugPrint(
                  'Processing history list with ${historyData.length} items');

              for (var item in historyData) {
                if (item is Map) {
                  // Tạo tin nhắn người dùng
                  if (item.containsKey('user_input')) {
                    final userMsg = Message(
                      text: item['user_input'] ?? '',
                      isSentByUser: true,
                      timestamp: item.containsKey('created_at')
                          ? DateTime.parse(item['created_at'])
                          : DateTime.now(),
                    );
                    parsedMessages.add(userMsg);

                    // Tạo tin nhắn bot
                    if (item.containsKey('bot_response')) {
                      final botMsg = Message(
                        text: item['bot_response'] ?? '',
                        isSentByUser: false,
                        timestamp: item.containsKey('created_at')
                            ? DateTime.parse(item['created_at'])
                                .add(const Duration(seconds: 1))
                            : DateTime.now().add(const Duration(seconds: 1)),
                      );
                      parsedMessages.add(botMsg);
                    }
                  } else if (item.containsKey('message')) {
                    // Định dạng trước đó
                    parsedMessages.add(Message(
                      text: item['message'] ?? '',
                      isSentByUser:
                          item['is_user'] == 1 || item['is_user'] == true,
                      timestamp: DateTime.parse(
                          item['timestamp'] ?? DateTime.now().toString()),
                    ));
                  }
                }
              }

              debugPrint(
                  'Parsed ${parsedMessages.length} messages from history data');
            } else {
              // Sử dụng phương thức cũ để phân tích
              parsedMessages = _parseHistoryData(historyData);
            }

            // Cập nhật danh sách tin nhắn chỉ khi có dữ liệu hợp lệ
            if (parsedMessages.isNotEmpty) {
              // Sắp xếp tin nhắn theo thời gian tăng dần trước khi thêm vào danh sách
              parsedMessages.sort((a, b) => a.timestamp.compareTo(b.timestamp));

              _messages.clear();
              _messages.addAll(parsedMessages);
              debugPrint(
                  'Added ${parsedMessages.length} messages from history');
            } else {
              debugPrint('No valid messages found in history data');
              // Chỉ thêm tin nhắn chào mừng nếu danh sách tin nhắn trống
              if (_messages.isEmpty) {
                _messages.add(Message(
                  text:
                      'Xin chào! Tôi là Copecute, tôi có thể giúp gì cho bạn?',
                  isSentByUser: false,
                  timestamp: DateTime.now(),
                ));
                debugPrint('Added welcome message as history was empty');
              }
            }
          } else {
            debugPrint('No history data fields found in response');
            // Chỉ thêm tin nhắn chào mừng nếu danh sách tin nhắn trống
            if (_messages.isEmpty) {
              _messages.add(Message(
                text: 'Xin chào! Tôi là Copecute, tôi có thể giúp gì cho bạn?',
                isSentByUser: false,
                timestamp: DateTime.now(),
              ));
              debugPrint('Added welcome message as no history data was found');
            }
          }
        } else {
          _errorMessage = responseData['error'] ?? 'Không thể lấy lịch sử chat';
          debugPrint('Error from server: $_errorMessage');

          // Thêm tin nhắn chào mừng nếu không có lịch sử và danh sách tin nhắn trống
          if (_messages.isEmpty) {
            _messages.add(Message(
              text: 'Xin chào! Tôi là Copecute, tôi có thể giúp gì cho bạn?',
              isSentByUser: false,
              timestamp: DateTime.now(),
            ));
            debugPrint('Added welcome message due to server error');
          }
        }
      } else {
        _errorMessage = 'Lỗi kết nối: ${response.statusCode}';
        debugPrint(
            'HTTP error: ${response.statusCode}, body: ${response.body}');

        // Thêm tin nhắn chào mừng nếu không có tin nhắn
        if (_messages.isEmpty) {
          _messages.add(Message(
            text: 'Xin chào! Tôi là Copecute, tôi có thể giúp gì cho bạn?',
            isSentByUser: false,
            timestamp: DateTime.now(),
          ));
          debugPrint('Added welcome message due to connection error');
        }
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi khi lấy lịch sử: $e';
      debugPrint('Error fetching chat history: $e');

      // Nếu không lấy được lịch sử và danh sách tin nhắn trống, tạo tin nhắn chào mừng
      if (_messages.isEmpty) {
        _messages.add(Message(
          text: 'Xin chào! Tôi là Copecute, tôi có thể giúp gì cho bạn?',
          isSentByUser: false,
          timestamp: DateTime.now(),
        ));
        debugPrint('Added welcome message due to exception');
      }
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Tạo tin nhắn chào mừng dựa trên thời gian
  String _getWelcomeMessage(String? username) {
    var hour = DateTime.now().hour;
    String timeOfDay;

    if (hour < 12) {
      timeOfDay = "sáng";
    } else if (hour < 14) {
      timeOfDay = "trưa";
    } else if (hour < 18) {
      timeOfDay = "chiều";
    } else {
      timeOfDay = "tối";
    }

    return "Chào buổi $timeOfDay${username != null ? ', $username' : ''}! Tôi là copecute. Bạn có thể hỏi tôi bất cứ điều gì!";
  }

  // Xóa lịch sử chat
  Future<bool> clearChatHistory(String token, [BuildContext? context]) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      // Làm sạch token trước khi gửi
      final cleanToken = token.trim();
      debugPrint(
          'Clearing chat history from: ${AppConstants.clearHistoryEndpoint}');
      debugPrint('Using cleaned token: $cleanToken');

      final response = await http.post(
        Uri.parse(AppConstants.clearHistoryEndpoint),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $cleanToken',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
      );

      debugPrint('Request headers: ${response.request?.headers}');
      debugPrint('Response status code: ${response.statusCode}');
      debugPrint('Response body: ${response.body}');

      if (response.statusCode == 200) {
        Map<String, dynamic> responseData;
        try {
          responseData = json.decode(response.body);
        } catch (e) {
          debugPrint('Error parsing response: $e');
          _errorMessage = 'Lỗi khi xử lý phản hồi từ server';
          return false;
        }

        if (responseData['success'] == true) {
          // Xóa tin nhắn trong bộ nhớ
          _messages.clear();

          // Thêm tin nhắn chào mừng mới - không sử dụng username lấy từ Provider
          _addMessage(Message(
            text: _getWelcomeMessage(
                null), // Truyền null thay vì lấy từ AuthService
            isSentByUser: false,
            timestamp: DateTime.now(),
          ));

          return true;
        }
      } else if (response.statusCode == 401) {
        _errorMessage = 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại';
        debugPrint('Auth error: $_errorMessage');

        // Xóa tin nhắn trong bộ nhớ
        _messages.clear();
        notifyListeners();

        // Xử lý lỗi 401 nếu có context
        if (context != null && context.mounted) {
          // Sử dụng Future.microtask để tránh lỗi lifecycle
          Future.microtask(() {
            if (context.mounted) {
              // Hiển thị thông báo
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text(
                      'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại'),
                  backgroundColor: Colors.red,
                ),
              );

              // Chuyển hướng đến trang đăng nhập
              Navigator.of(context).pushNamedAndRemoveUntil(
                AppRoutes.login,
                (route) => false,
              );
            }
          });
        }

        return false;
      }

      _errorMessage = 'Không thể xóa lịch sử chat';
      return false;
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Error in clearChatHistory: $e');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Gửi yêu cầu dạy bot
  Future<bool> teachBot(String question, String answer, String token,
      [bool isImpolite = false, BuildContext? context]) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      // Bật debug mode để in thêm thông tin
      const bool debug_mode = true;

      debugPrint(
          'Teaching bot - Question: $question, Answer: $answer, Impolite: $isImpolite');

      // Kiểm tra dữ liệu đầu vào
      if (question.trim().isEmpty || answer.trim().isEmpty) {
        _errorMessage = 'Thiếu từ khóa hoặc câu trả lời';
        debugPrint('Empty input error: $_errorMessage');
        _isLoading = false;
        notifyListeners();
        return false;
      }

      // Thử tuần tự các endpoints khác nhau
      List<String> endpointsToTry = [
        AppConstants.teachEndpoint, // API chính
        AppConstants.teachBackupEndpoint, // API dự phòng
      ];

      http.Response? response;
      String? successEndpoint;

      // Thử từng endpoint cho đến khi thành công
      for (var endpoint in endpointsToTry) {
        try {
          debugPrint('Đang thử endpoint: $endpoint');

          // Sử dụng phương thức POST với JSON
          response = await http.post(
            Uri.parse(endpoint),
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $token',
            },
            body: json.encode({
              'question': question,
              'answer': answer,
              'keyword': question, // Thêm keyword cho tương thích
              'reply': answer, // Thêm reply cho tương thích
              'impolite': isImpolite ? '1' : '0', // Thêm giá trị impolite
            }),
          );

          if (debug_mode) {
            debugPrint(
                'Endpoint $endpoint - Response status: ${response.statusCode}');
            debugPrint('Response body: ${response.body}');
          }

          // Nếu thành công hoặc ít nhất nhận được phản hồi 400 (Bad Request), thoát khỏi vòng lặp
          if (response.statusCode == 200 ||
              response.statusCode == 201 ||
              (response.body.contains('success') &&
                  !response.body.contains('false'))) {
            successEndpoint = endpoint;
            break;
          }
        } catch (e) {
          debugPrint('Lỗi khi thử endpoint $endpoint: $e');
          // Tiếp tục thử endpoint tiếp theo
        }
      }

      // Nếu không có endpoint nào thành công
      if (response == null) {
        _errorMessage = 'Không thể kết nối đến server';
        debugPrint('No successful endpoint');
        _isLoading = false;
        notifyListeners();
        return false;
      }

      // Hiển thị thông tin về endpoint đã sử dụng thành công
      if (successEndpoint != null) {
        debugPrint('Sử dụng endpoint thành công: $successEndpoint');
      }

      debugPrint('Final response status: ${response.statusCode}');
      debugPrint('Final response body: ${response.body}');

      // Xử lý lỗi 401 Unauthorized
      if (response != null &&
          (response.statusCode == 401 ||
              response.body.contains('chưa đăng nhập'))) {
        _errorMessage = 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại';
        debugPrint('Auth error: $_errorMessage');
        _isLoading = false;
        notifyListeners();

        // Nếu context được cung cấp, chuyển hướng đến màn hình đăng nhập
        if (context != null) {
          // Đăng xuất người dùng hiện tại
          final authService = AuthService();
          await authService.signOut();

          // Chuyển hướng đến màn hình đăng nhập
          Navigator.of(context).pushNamedAndRemoveUntil(
            AppRoutes.login,
            (route) => false,
          );
        }

        return false;
      }

      // Phân tích dữ liệu phản hồi
      if (response.statusCode == 200 ||
          response.statusCode == 201 ||
          (response.body.contains('success') &&
              !response.body.contains('false'))) {
        try {
          final responseData = json.decode(response.body);
          if (responseData['success'] == true ||
              responseData.containsKey('message')) {
            _isLoading = false;
            notifyListeners();
            return true;
          } else if (!responseData.containsKey('error')) {
            // Nếu không có lỗi trong phản hồi, coi như thành công
            _isLoading = false;
            notifyListeners();
            return true;
          } else {
            _errorMessage = responseData['error'] ?? 'Không thể dạy bot';
            debugPrint('Server error: $_errorMessage');
            _isLoading = false;
            notifyListeners();
            return false;
          }
        } catch (e) {
          // Nếu không phân tích được JSON nhưng có nội dung phản hồi tích cực
          if (response.body.contains('success') ||
              response.body.contains('cảm ơn') ||
              response.body.contains('thành công')) {
            _isLoading = false;
            notifyListeners();
            return true;
          }

          _errorMessage = 'Lỗi khi phân tích dữ liệu phản hồi: $e';
          debugPrint('JSON parse error: $_errorMessage');
          _isLoading = false;
          notifyListeners();
          return false;
        }
      } else if (response.statusCode == 400) {
        // Xử lý lỗi Bad Request
        try {
          final errorData = json.decode(response.body);
          _errorMessage = errorData['error'] ?? 'Lỗi yêu cầu không hợp lệ';
        } catch (e) {
          _errorMessage = 'Lỗi yêu cầu không hợp lệ: ${response.body}';
        }
        debugPrint('Bad request error: $_errorMessage');
        _isLoading = false;
        notifyListeners();
        return false;
      } else {
        _errorMessage = 'Lỗi kết nối: ${response.statusCode}';
        debugPrint('HTTP error: $_errorMessage');
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi khi dạy bot: $e';
      debugPrint('Exception: $_errorMessage');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Xóa lỗi
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  // Xóa tất cả tin nhắn
  void clearMessages() {
    _messages.clear();
    notifyListeners();
  }
}

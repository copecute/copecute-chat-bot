import 'dart:convert';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import '../models/message.dart';
import '../utils/constants.dart';
import '../utils/api_service.dart';
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

      debugPrint('Parsing history data: ${data.runtimeType}');

      if (data is Map && data.containsKey('history')) {
        // Cấu trúc API mới: {"total":"1","page":1,"limit":20,"total_pages":1,"history":[...]}
        debugPrint('Found history key in data map');
        final historyList = data['history'];

        if (historyList is List) {
          debugPrint('History is a List with ${historyList.length} items');

          for (var item in historyList) {
            if (item is Map) {
              // Cấu trúc API: {"id":"212","user_input":"test","bot_response":"Test...","created_at":"..."}

              // Tạo tin nhắn người dùng
              if (item.containsKey('user_input') &&
                  item['user_input'] != null) {
                final userMessage = Message(
                  text: item['user_input'].toString(),
                  isSentByUser: true,
                  timestamp: item.containsKey('created_at')
                      ? DateTime.parse(item['created_at'])
                      : DateTime.now(),
                );
                result.add(userMessage);
              }

              // Tạo tin nhắn bot
              if (item.containsKey('bot_response') &&
                  item['bot_response'] != null) {
                final botMessage = Message(
                  text: item['bot_response'].toString(),
                  isSentByUser: false,
                  timestamp: item.containsKey('created_at')
                      ? DateTime.parse(item['created_at'])
                          .add(Duration(milliseconds: 500))
                      : DateTime.now().add(Duration(milliseconds: 500)),
                );
                result.add(botMessage);
              }
            }
          }
        }
      } else if (data is List) {
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
        debugPrint('Data is a generic Map with ${data.length} items');
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
        debugPrint('Parsed ${result.length} messages');
      } else {
        debugPrint('No messages parsed from response');
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
  Future<Message?> sendMessage(String text, BuildContext context) async {
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

      // Sử dụng ApiService để gửi tin nhắn
      final apiService = ApiService.of(context);
      final response = await apiService.post(
        AppConstants.chatEndpoint,
        body: {'message': text},
      );

      // Giả lập thời gian đang nhập tin nhắn (2-3 giây) - giảm thời gian chờ
      final random = math.Random();
      final typingDuration =
          Duration(milliseconds: 1500 + random.nextInt(1500));
      await Future.delayed(typingDuration);

      // Đã nhận được phản hồi, kết thúc hiệu ứng đang nhập
      _isTyping = false;
      notifyListeners();

      if (response['success'] == true) {
        // Tạo tin nhắn phản hồi từ bot
        // Lấy bot_response từ trường data
        String botResponse = 'Không có phản hồi từ server';
        final responseData = response['data'];

        // Kiểm tra nhiều định dạng khác nhau của dữ liệu phản hồi
        if (responseData is Map) {
          if (responseData.containsKey('bot_response')) {
            botResponse = responseData['bot_response'].toString();
          } else if (responseData.containsKey('response')) {
            botResponse = responseData['response'].toString();
          }
        } else if (response.containsKey('bot_response')) {
          // Hỗ trợ trường bot_response trực tiếp trong response
          botResponse = response['bot_response'].toString();
        } else if (response.containsKey('response')) {
          // Hỗ trợ cấu trúc response cũ nếu có
          botResponse = response['response'].toString();
        } else if (response.containsKey('message')) {
          // Hỗ trợ thêm trường message
          botResponse = response['message'].toString();
        }

        debugPrint('Bot response: $botResponse');

        // Xác định xem có phải là phản hồi mặc định không
        bool isDefaultResponse = false;
        if (responseData is Map) {
          if (responseData.containsKey('is_default_response')) {
            isDefaultResponse = responseData['is_default_response'] == true;
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
        _errorMessage = response['error'] ?? 'Lỗi không xác định';
        debugPrint('Error from server: $_errorMessage');
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
  Future<void> fetchChatHistory(BuildContext context) async {
    try {
      _isLoading = true;
      notifyListeners();

      final apiService = ApiService.of(context);
      debugPrint('Fetching chat history...');
      final response = await apiService.get(AppConstants.historyEndpoint);

      debugPrint('Got response with success=${response['success']}');

      if (response['success'] == true) {
        final historyData = response['data'];
        debugPrint('Got history data, type: ${historyData.runtimeType}');

        if (historyData is Map && historyData.containsKey('history')) {
          final historyList = historyData['history'];
          debugPrint('History list has ${historyList.length} items');
        }

        final historyMessages = _parseHistoryData(historyData);
        debugPrint(
            'Parsed ${historyMessages.length} messages from history data');

        // Cập nhật danh sách tin nhắn
        _messages = historyMessages;
        debugPrint('Updated messages list, length: ${_messages.length}');
        _isLoading = false;
        notifyListeners();
      } else {
        _errorMessage = response['error'] ?? 'Không thể lấy lịch sử chat';
        debugPrint('Error from server: $_errorMessage');
        _isLoading = false;
        notifyListeners();
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Error fetching history: $e');
      _isLoading = false;
      notifyListeners();
    }
  }

  // Xóa lịch sử chat
  Future<bool> clearChatHistory(BuildContext context) async {
    try {
      _isLoading = true;
      notifyListeners();

      final apiService = ApiService.of(context);
      final response = await apiService.post(AppConstants.clearHistoryEndpoint);

      if (response['success'] == true) {
        // Xóa lịch sử thành công, cập nhật danh sách tin nhắn
        _messages = [];
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = response['error'] ?? 'Không thể xóa lịch sử chat';
        debugPrint('Error from server: $_errorMessage');
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Error clearing history: $e');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Dạy bot
  Future<bool> teachBot(String question, String answer, bool isImpolite,
      BuildContext context) async {
    try {
      _isLoading = true;
      notifyListeners();

      final apiService = ApiService.of(context);

      // Thêm logs để debug
      debugPrint(
          'Teaching bot: keyword="$question", reply="$answer", impolite=$isImpolite');

      final response = await apiService.post(
        AppConstants.teachBotEndpoint,
        body: {
          'keyword': question, // Sửa từ 'question' sang 'keyword'
          'reply': answer, // Sửa từ 'answer' sang 'reply'
          'impolite':
              isImpolite ? 1 : 0, // Sửa từ 'is_impolite' sang 'impolite'
        },
      );

      // Kiểm tra xem ApiService đã xử lý lỗi 401 chưa
      if (response.containsKey('code') && response['code'] == 'unauthorized') {
        debugPrint('Got 401 response: User has been logged out');
        _errorMessage = 'Phiên đăng nhập đã hết hạn';
        _isLoading = false;
        notifyListeners();
        return false;
      }

      if (response['success'] == true) {
        debugPrint('Bot teaching successful');
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = response['error'] ?? 'Không thể dạy bot';
        debugPrint('Error from server: $_errorMessage');
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Đã xảy ra lỗi: $e';
      debugPrint('Error teaching bot: $e');
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
}

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../utils/app_routes.dart';
import '../utils/app_theme.dart';
import '../models/message.dart';
import '../widgets/chat_bubble.dart';
import '../widgets/typing_indicator.dart';
import '../providers/settings_provider.dart';
import '../utils/auth_service.dart';
import '../services/chat_service.dart';
import 'login_screen.dart';
import 'settings_screen.dart';
import 'profile_screen.dart';
import 'dart:io';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final FocusNode _focusNode = FocusNode();
  bool _isFirstLoad = true;
  bool _isLoading = false;

  // Messenger blue color
  static const Color messengerBlue = Color(0xFF0084FF);

  @override
  void initState() {
    super.initState();

    // Đặt callback sau khi xây dựng frame đầu tiên để tải lịch sử chat
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadChatHistory();

      // Đăng ký lắng nghe sự thay đổi của tin nhắn để cuộn xuống cuối
      final chatService = Provider.of<ChatService>(context, listen: false);
      chatService.addListener(_onChatServiceUpdated);
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // Xử lý logic khác nếu cần, nhưng không gọi _loadChatHistory ở đây
  }

  // Xử lý khi ChatService cập nhật tin nhắn mới
  void _onChatServiceUpdated() {
    final chatService = Provider.of<ChatService>(context, listen: false);
    // Chỉ cuộn xuống khi có tin nhắn mới và không đang tải
    if (!_isLoading &&
        chatService.messages.isNotEmpty &&
        !chatService.isLoading) {
      _scrollToBottom(forceScroll: true);
    }
  }

  // Tải lịch sử chat từ server
  Future<void> _loadChatHistory() async {
    final authService = Provider.of<AuthService>(context, listen: false);
    final chatService = Provider.of<ChatService>(context, listen: false);

    if (!authService.isLoggedIn || authService.getAuthToken() == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Bạn cần đăng nhập lại để xem lịch sử chat'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    // Hiển thị loading indicator
    setState(() {
      _isLoading = true;
    });

    try {
      // Truyền context cho ChatService để xử lý lỗi 401
      await chatService.fetchChatHistory(authService.getAuthToken()!, context);

      // Nếu không có lỗi từ ChatService nhưng lịch sử chat trống
      if (chatService.messages.isEmpty && chatService.errorMessage == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Không tìm thấy lịch sử chat nào'),
            backgroundColor: Colors.orange,
          ),
        );
      }

      // Nếu có lỗi, hiển thị thông báo
      if (chatService.errorMessage != null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Lỗi: ${chatService.errorMessage}'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Đã xảy ra lỗi khi tải lịch sử chat: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      // Ẩn loading indicator
      if (mounted) {
        setState(() {
          _isLoading = false;
        });

        // Đảm bảo cuộn xuống cuối sau khi tải xong và UI đã cập nhật
        // Cuộn xuống sau một khoảng thời gian để đảm bảo ListView đã render
        if (chatService.messages.isNotEmpty) {
          // Delay lâu hơn để đảm bảo ListView đã được render hoàn toàn
          Future.delayed(const Duration(milliseconds: 500), () {
            _scrollToBottom(forceScroll: true);
          });
        }
      }
    }
  }

  @override
  void dispose() {
    // Hủy đăng ký lắng nghe
    final chatService = Provider.of<ChatService>(context, listen: false);
    chatService.removeListener(_onChatServiceUpdated);

    _messageController.dispose();
    _scrollController.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _sendMessage() async {
    // Bỏ ký tự xuống dòng nếu có trước khi kiểm tra text rỗng
    final messageText = _messageController.text.trim();
    if (messageText.isEmpty) return;

    // Lấy token xác thực từ AuthService
    final authService = Provider.of<AuthService>(context, listen: false);
    final token = authService.getAuthToken();

    if (token == null) {
      // Hiển thị thông báo lỗi nếu không có token
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Bạn cần đăng nhập lại để tiếp tục.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    // Xóa text và đưa TextField về trạng thái sạch
    _messageController.clear();

    // Sử dụng ChatService để gửi tin nhắn
    final chatService = Provider.of<ChatService>(context, listen: false);

    // Cuộn xuống cuối danh sách tin nhắn ngay khi gửi tin nhắn (trước khi gọi API)
    _scrollToBottom(forceScroll: true);

    // Gửi tin nhắn và đợi phản hồi từ server - truyền context để xử lý lỗi 401
    await chatService.sendMessage(messageText, token, context);

    // Cuộn xuống cuối danh sách tin nhắn một lần nữa sau khi nhận phản hồi
    _scrollToBottom(forceScroll: true);
  }

  // Hiển thị hộp thoại dạy bot với tin nhắn ban đầu
  void _showTeachBotForMessage(String initialQuestion) {
    final questionController = TextEditingController(text: initialQuestion);
    final answerController = TextEditingController();
    // Biến để theo dõi trạng thái teaching và nội dung thô lỗ
    bool isTeaching = false;
    bool isImpolite = false;

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final dialogBackgroundColor =
        isDarkMode ? const Color(0xFF212121) : Colors.white;
    final textColor = isDarkMode ? Colors.white : Colors.black87;
    final hintColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;
    final accentColor = messengerBlue;

    showDialog(
      context: context,
      builder: (BuildContext dialogContext) {
        return StatefulBuilder(builder: (context, setState) {
          return AlertDialog(
            backgroundColor: dialogBackgroundColor,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(20),
            ),
            title: Column(
              children: [
                const Text(
                  'Dạy Bot',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Divider(
                    color: isDarkMode
                        ? Colors.grey.shade700
                        : Colors.grey.shade300),
              ],
            ),
            content: isTeaching
                ? Container(
                    height: 150,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        CircularProgressIndicator(
                          valueColor:
                              AlwaysStoppedAnimation<Color>(accentColor),
                        ),
                        const SizedBox(height: 20),
                        Text(
                          'Đang dạy bot...',
                          style: TextStyle(
                            color: textColor,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Vui lòng đợi trong giây lát',
                          style: TextStyle(
                            color: hintColor,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  )
                : Container(
                    width: double.maxFinite,
                    child: SingleChildScrollView(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Câu hỏi hoặc từ khóa',
                            style: TextStyle(
                              color: textColor,
                              fontWeight: FontWeight.w500,
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 8),
                          TextField(
                            controller: questionController,
                            style: TextStyle(color: textColor),
                            decoration: InputDecoration(
                              hintText: 'Ví dụ: Bạn tên là gì?',
                              hintStyle: TextStyle(color: hintColor),
                              fillColor: isDarkMode
                                  ? Colors.grey.shade800
                                  : Colors.grey.shade100,
                              filled: true,
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 14,
                              ),
                            ),
                          ),
                          const SizedBox(height: 20),
                          Text(
                            'Câu trả lời',
                            style: TextStyle(
                              color: textColor,
                              fontWeight: FontWeight.w500,
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 8),
                          TextField(
                            controller: answerController,
                            style: TextStyle(color: textColor),
                            decoration: InputDecoration(
                              hintText: 'Ví dụ: Tôi là Copecute!',
                              hintStyle: TextStyle(color: hintColor),
                              fillColor: isDarkMode
                                  ? Colors.grey.shade800
                                  : Colors.grey.shade100,
                              filled: true,
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 14,
                              ),
                            ),
                            maxLines: 4,
                          ),
                          const SizedBox(height: 16),
                          // Thêm nút tick "Đây là nội dung thô lỗ/không lịch sự"
                          InkWell(
                            onTap: () {
                              setState(() {
                                isImpolite = !isImpolite;
                              });
                            },
                            borderRadius: BorderRadius.circular(8),
                            child: Padding(
                              padding:
                                  const EdgeInsets.symmetric(vertical: 8.0),
                              child: Row(
                                children: [
                                  Container(
                                    width: 24,
                                    height: 24,
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(4),
                                      border: Border.all(
                                        color: isImpolite
                                            ? accentColor
                                            : hintColor,
                                        width: 2,
                                      ),
                                      color: isImpolite
                                          ? accentColor
                                          : Colors.transparent,
                                    ),
                                    child: isImpolite
                                        ? const Icon(
                                            Icons.check,
                                            size: 18,
                                            color: Colors.white,
                                          )
                                        : null,
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Text(
                                      'Đây là nội dung thô lỗ/không lịch sự',
                                      style: TextStyle(
                                        color: textColor,
                                        fontSize: 14,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 4),
                          Padding(
                            padding: const EdgeInsets.only(left: 36.0),
                            child: Text(
                              'Đánh dấu nếu câu trả lời chứa ngôn ngữ thô lỗ hoặc không phù hợp cho mọi đối tượng',
                              style: TextStyle(
                                color: hintColor,
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
            actions: isTeaching
                ? []
                : [
                    TextButton(
                      onPressed: () {
                        Navigator.of(dialogContext).pop();
                      },
                      style: TextButton.styleFrom(
                        foregroundColor: hintColor,
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 10),
                      ),
                      child: const Text('Hủy'),
                    ),
                    ElevatedButton(
                      onPressed: () async {
                        if (questionController.text.trim().isEmpty ||
                            answerController.text.trim().isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: const Text(
                                'Vui lòng nhập đầy đủ câu hỏi và câu trả lời.',
                              ),
                              backgroundColor: Colors.red,
                              behavior: SnackBarBehavior.floating,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                          );
                          return;
                        }

                        final authService =
                            Provider.of<AuthService>(context, listen: false);
                        final chatService =
                            Provider.of<ChatService>(context, listen: false);
                        final token = authService.getAuthToken();

                        if (token == null) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: const Text(
                                  'Bạn cần đăng nhập lại để tiếp tục.'),
                              backgroundColor: Colors.red,
                              behavior: SnackBarBehavior.floating,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                          );
                          Navigator.of(dialogContext).pop();
                          return;
                        }

                        // Chuyển sang trạng thái đang dạy
                        setState(() {
                          isTeaching = true;
                        });

                        // Gửi yêu cầu dạy bot, bao gồm cả trạng thái impolite
                        bool success = false;
                        try {
                          success = await chatService.teachBot(
                            questionController.text.trim(),
                            answerController.text.trim(),
                            token,
                            isImpolite, // Truyền giá trị isImpolite
                            context, // Truyền context để xử lý lỗi 401
                          );
                        } catch (e) {
                          debugPrint('Lỗi khi dạy bot: $e');
                        }

                        // Đóng dialog và hiển thị kết quả nếu widget vẫn tồn tại
                        if (mounted) {
                          Navigator.of(dialogContext).pop();
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(success
                                  ? 'Đã dạy bot thành công!'
                                  : 'Không thể dạy bot: ${chatService.errorMessage ?? "Lỗi không xác định"}'),
                              backgroundColor:
                                  success ? Colors.green : Colors.red,
                              behavior: SnackBarBehavior.floating,
                              margin: const EdgeInsets.all(8),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                          );
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: accentColor,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 10),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Text('Lưu'),
                    ),
                  ],
          );
        });
      },
    );
  }

  void _scrollToBottom({bool forceScroll = false}) {
    // Đảm bảo rằng scrollController đã được gắn với widget
    if (!_scrollController.hasClients) return;

    // Khi forceScroll = true, sẽ sử dụng delay ngắn hơn để tránh chờ đợi
    final delay = forceScroll ? 50 : 100;

    // Đặt lịch sau vi-task để đảm bảo layout đã hoàn thành
    Future.microtask(() {
      if (_scrollController.hasClients) {
        try {
          // Cuộn nhanh hơn với duration ngắn hơn
          _scrollController.animateTo(
            _scrollController.position.maxScrollExtent,
            duration: const Duration(milliseconds: 100),
            curve: Curves.easeOut,
          );

          // Nếu force scroll, thử cuộn lại nhiều lần sau các khoảng thời gian khác nhau
          if (forceScroll) {
            // Lần thứ hai, sau 100ms
            Future.delayed(const Duration(milliseconds: 100), () {
              if (_scrollController.hasClients) {
                _scrollController.animateTo(
                  _scrollController.position.maxScrollExtent,
                  duration: const Duration(milliseconds: 100),
                  curve: Curves.easeOut,
                );
              }
            });

            // Lần thứ ba, sau 300ms
            Future.delayed(const Duration(milliseconds: 300), () {
              if (_scrollController.hasClients) {
                _scrollController.animateTo(
                  _scrollController.position.maxScrollExtent,
                  duration: const Duration(milliseconds: 100),
                  curve: Curves.easeOut,
                );
              }
            });

            // Lần cuối, sau 600ms để đảm bảo chắc chắn
            Future.delayed(const Duration(milliseconds: 600), () {
              if (_scrollController.hasClients) {
                _scrollController.jumpTo(
                  _scrollController.position.maxScrollExtent,
                );
              }
            });
          }
        } catch (e) {
          debugPrint('Error scrolling to bottom: $e');
        }
      }
    });
  }

  // Xử lý xóa tin nhắn
  Future<void> _clearMessages() async {
    // Hiển thị dialog xác nhận
    final bool? confirm = await showDialog<bool>(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Xác nhận xóa'),
          content:
              const Text('Bạn có chắc chắn muốn xóa tất cả tin nhắn không?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Hủy'),
            ),
            TextButton(
              onPressed: () => Navigator.of(context).pop(true),
              style: TextButton.styleFrom(
                foregroundColor: Colors.red,
              ),
              child: const Text('Xóa'),
            ),
          ],
        );
      },
    );

    if (confirm == true) {
      final authService = Provider.of<AuthService>(context, listen: false);
      final chatService = Provider.of<ChatService>(context, listen: false);
      final token = authService.getAuthToken();

      if (token == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Bạn cần đăng nhập lại để thực hiện thao tác này'),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }

      setState(() {
        _isLoading = true;
      });

      try {
        final success = await chatService.clearChatHistory(token, context);
        if (success) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Đã xóa tất cả tin nhắn'),
                backgroundColor: Colors.green,
              ),
            );
          }
        } else if (chatService.errorMessage != null) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('Lỗi: ${chatService.errorMessage}'),
                backgroundColor: Colors.red,
              ),
            );
          }
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Đã xảy ra lỗi: $e'),
              backgroundColor: Colors.red,
            ),
          );
        }
      } finally {
        if (mounted) {
          setState(() {
            _isLoading = false;
          });
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    final settings = settingsProvider.settings;
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final authService = Provider.of<AuthService>(context);
    final user = authService.currentUser;
    final chatService = Provider.of<ChatService>(context);
    final messages = chatService.messages;

    return Scaffold(
      backgroundColor:
          isDarkMode ? const Color(0xFF121212) : const Color(0xFFF2F2F2),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: isDarkMode ? const Color(0xFF1E1E1E) : Colors.white,
        leading: CircleAvatar(
          radius: 18,
          backgroundColor: const Color(0xFFE4E6EB).withOpacity(0.3),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(18),
            child: Image.asset(
              'assets/images/bot_avatar.png',
              width: 36,
              height: 36,
              fit: BoxFit.cover,
              errorBuilder: (context, error, stackTrace) => const Icon(
                Icons.smart_toy_rounded,
                color: messengerBlue,
                size: 20,
              ),
            ),
          ),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Copecute',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              'Hoạt động',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.normal,
                color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600,
              ),
            ),
          ],
        ),
        actions: [
          // Nút tải lại lịch sử
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Tải lại lịch sử',
            onPressed: _loadChatHistory,
          ),
          // Nút xóa tin nhắn
          IconButton(
            icon: const Icon(Icons.delete_outline),
            onPressed: chatService.messages.isEmpty ? null : _clearMessages,
            tooltip: 'Xóa tất cả tin nhắn',
          ),
          // Nút dạy bot
          IconButton(
            icon: const Icon(Icons.lightbulb_outline),
            tooltip: 'Dạy bot',
            onPressed: () {
              // Hiển thị hộp thoại dạy bot với chuỗi rỗng
              _showTeachBotForMessage('');
            },
          ),
        ],
      ),
      body: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.only(bottom: 8.0),
          child: Column(
            children: [
              // Thông báo lỗi nếu có
              if (chatService.errorMessage != null)
                Container(
                  padding: const EdgeInsets.all(8),
                  color: Colors.red.shade100,
                  child: Row(
                    children: [
                      const Icon(Icons.error_outline, color: Colors.red),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          chatService.errorMessage!,
                          style: TextStyle(color: Colors.red.shade900),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () {
                          chatService.clearError();
                        },
                        color: Colors.red.shade900,
                        iconSize: 16,
                      ),
                    ],
                  ),
                ),

              // Chat messages
              Expanded(
                child: _isLoading
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            CircularProgressIndicator(
                              color: messengerBlue,
                            ),
                            const SizedBox(height: 16),
                            Text(
                              'Đang tải tin nhắn...',
                              style: TextStyle(
                                color: isDarkMode
                                    ? Colors.grey.shade400
                                    : Colors.grey.shade600,
                              ),
                            ),
                          ],
                        ),
                      )
                    : Container(
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF121212)
                              : const Color(0xFFF2F2F2),
                          image: settings.useCustomBackground
                              ? DecorationImage(
                                  image: () {
                                    // Debug
                                    debugPrint(
                                        'useCustomBackground: ${settings.useCustomBackground}');
                                    debugPrint(
                                        'customBackgroundImagePath: ${settings.customBackgroundImagePath}');

                                    if (settings.customBackgroundImagePath !=
                                        null) {
                                      return FileImage(File(settings
                                              .customBackgroundImagePath!))
                                          as ImageProvider;
                                    } else {
                                      String assetPath = isDarkMode
                                          ? 'assets/images/bg-chat-dark.png'
                                          : 'assets/images/bg-chat-light.png';
                                      debugPrint(
                                          'Using asset image: $assetPath');
                                      return AssetImage(assetPath);
                                    }
                                  }(),
                                  repeat:
                                      settings.customBackgroundImagePath != null
                                          ? ImageRepeat.noRepeat
                                          : ImageRepeat.repeat,
                                  opacity: settings.backgroundOpacity,
                                  fit:
                                      settings.customBackgroundImagePath != null
                                          ? BoxFit.cover
                                          : null,
                                )
                              : null,
                        ),
                        child: messages.isEmpty
                            ? Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Container(
                                      width: 100,
                                      height: 100,
                                      decoration: BoxDecoration(
                                        color: isDarkMode
                                            ? Colors.grey.shade800
                                            : Colors.grey.shade200,
                                        shape: BoxShape.circle,
                                      ),
                                      child: Icon(
                                        Icons.chat_bubble_outline_rounded,
                                        size: 50,
                                        color: isDarkMode
                                            ? Colors.grey.shade600
                                            : Colors.grey.shade400,
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    Text(
                                      'Hãy bắt đầu cuộc trò chuyện!',
                                      style: TextStyle(
                                        color: isDarkMode
                                            ? Colors.grey.shade400
                                            : Colors.grey.shade600,
                                        fontSize: 16,
                                      ),
                                    ),
                                  ],
                                ),
                              )
                            : Stack(
                                children: [
                                  // Tin nhắn
                                  ListView.builder(
                                    controller: _scrollController,
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 16,
                                      vertical: 16,
                                    ),
                                    itemCount: messages.length,
                                    itemBuilder: (context, index) {
                                      final message = messages[index];
                                      final showAvatar = index == 0 ||
                                          messages[index - 1].isSentByUser !=
                                              message.isSentByUser;

                                      return Padding(
                                        padding:
                                            const EdgeInsets.only(bottom: 4.0),
                                        child: ChatBubble(
                                          message: message,
                                          showAvatar: showAvatar,
                                          fontSize: settings.fontSize,
                                          onTeachBot: message
                                                      .isDefaultResponse &&
                                                  !message.isSentByUser
                                              ? () => _showTeachBotForMessage(
                                                  messages[index - 1].text)
                                              : null,
                                        ),
                                      );
                                    },
                                  ),

                                  // Hiệu ứng đang nhập
                                  if (chatService.isTyping)
                                    Positioned(
                                      bottom: 0,
                                      left: 16,
                                      child: Padding(
                                        padding:
                                            const EdgeInsets.only(bottom: 8.0),
                                        child: TypingIndicator(),
                                      ),
                                    ),
                                ],
                              ),
                      ),
              ),

              // Input area
              Container(
                padding: EdgeInsets.only(
                  left: 8.0,
                  right: 8.0,
                  top: 8.0,
                  bottom: MediaQuery.of(context).padding.bottom +
                      8.0, // Thêm padding bottom để không bị vướng thanh điều hướng
                ),
                decoration: BoxDecoration(
                  color: isDarkMode ? const Color(0xFF1E1E1E) : Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      spreadRadius: 1,
                      blurRadius: 3,
                      offset: const Offset(0, -1),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16.0),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2C2C2C)
                              : const Color(0xFFEFF2F5),
                          borderRadius: BorderRadius.circular(24),
                        ),
                        child: TextField(
                          controller: _messageController,
                          focusNode: _focusNode,
                          onChanged: (value) {
                            if (settings.enterToSend && value.endsWith('\n')) {
                              _messageController.text =
                                  value.substring(0, value.length - 1);
                              _messageController.selection =
                                  TextSelection.fromPosition(
                                TextPosition(
                                    offset: _messageController.text.length),
                              );
                              if (_messageController.text.trim().isNotEmpty) {
                                _sendMessage();
                              }
                            }
                          },
                          decoration: InputDecoration(
                            hintText: 'Nhập tin nhắn...',
                            border: InputBorder.none,
                            hintStyle: TextStyle(
                              color: isDarkMode
                                  ? Colors.grey.shade500
                                  : Colors.grey.shade600,
                            ),
                          ),
                          style: TextStyle(
                            fontSize: settings.fontSize,
                            color: isDarkMode ? Colors.white : Colors.black,
                          ),
                          textCapitalization: TextCapitalization.sentences,
                          keyboardType: TextInputType.multiline,
                          maxLines: null,
                          textInputAction: settings.enterToSend
                              ? TextInputAction.send
                              : TextInputAction.newline,
                          onSubmitted: (value) {
                            if (settings.enterToSend) {
                              _sendMessage();
                            }
                          },
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      decoration: const BoxDecoration(
                        shape: BoxShape.circle,
                        color: messengerBlue,
                      ),
                      child: IconButton(
                        icon: const Icon(Icons.send_rounded),
                        color: Colors.white,
                        padding: const EdgeInsets.all(0),
                        constraints: const BoxConstraints(
                          minWidth: 40,
                          minHeight: 40,
                        ),
                        onPressed: _sendMessage,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

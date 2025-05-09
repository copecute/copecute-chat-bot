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

  // Thêm hàm lấy lời chào theo thời gian
  String _getGreeting() {
    final hour = DateTime.now().hour;
    if (hour >= 5 && hour < 11) {
      return 'Chào buổi sáng';
    } else if (hour >= 11 && hour < 14) {
      return 'Chào buổi trưa';
    } else if (hour >= 14 && hour < 18) {
      return 'Chào buổi chiều';
    } else {
      return 'Chào buổi tối';
    }
  }

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
      await chatService.fetchChatHistory(context);

      // Nếu không có lỗi từ ChatService và lịch sử chat trống, thêm tin nhắn chào mừng
      if (chatService.messages.isEmpty && chatService.errorMessage == null) {
        final user = authService.currentUser;
        final greeting = _getGreeting();
        final welcomeMessage = Message(
          text:
              '${greeting}, ${user?.fullName ?? user?.username ?? 'bạn'}! Tôi là Copecute. Bạn có thể hỏi tôi bất cứ điều gì!',
          timestamp: DateTime.now(),
          isSentByUser: false,
          isDefaultResponse: false,
        );
        chatService.addMessage(welcomeMessage);
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
    await chatService.sendMessage(messageText, context);

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
                            isImpolite,
                            context,
                          );

                          // Nếu có lỗi unauthorized, đóng dialog
                          if (chatService.errorMessage ==
                              'Phiên đăng nhập đã hết hạn') {
                            // Ngay lập tức đóng dialog dạy bot nếu user đã bị đăng xuất
                            if (mounted &&
                                Navigator.of(dialogContext).canPop()) {
                              Navigator.of(dialogContext).pop();
                            }
                            return; // Thoát sớm vì user đã bị đưa về trang đăng nhập
                          }
                        } catch (e) {
                          debugPrint('Lỗi khi dạy bot: $e');
                        }

                        // Đóng dialog và hiển thị kết quả nếu widget vẫn tồn tại
                        if (mounted) {
                          // Kiểm tra một lần nữa xem dialog có thể đóng không
                          if (Navigator.of(dialogContext).canPop()) {
                            Navigator.of(dialogContext).pop();
                          }

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
        // Sử dụng clearChatHistory với context để xử lý lỗi 401
        final success = await chatService.clearChatHistory(context);
        if (success) {
          if (mounted) {
            // Thêm tin nhắn chào mừng sau khi xóa thành công
            final user = authService.currentUser;
            final greeting = _getGreeting();
            final welcomeMessage = Message(
              text:
                  '${greeting}, ${user?.fullName ?? user?.username ?? 'bạn'}! Tôi là Copecute. Bạn có thể hỏi tôi bất cứ điều gì!',
              timestamp: DateTime.now(),
              isSentByUser: false,
              isDefaultResponse: false,
            );
            chatService.addMessage(welcomeMessage);

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

    // Tạo gradient màu chủ đạo
    final primaryGradient = LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: isDarkMode
          ? [const Color(0xFF1A237E), const Color(0xFF0D47A1)]
          : [const Color(0xFF42A5F5), const Color(0xFF2196F3)],
    );

    // Màu sắc chủ đạo
    final primaryColor =
        isDarkMode ? const Color(0xFF2979FF) : const Color(0xFF2196F3);
    final secondaryColor =
        isDarkMode ? const Color(0xFF64B5F6) : const Color(0xFF90CAF9);
    final backgroundColor =
        isDarkMode ? const Color(0xFF0A0E21) : const Color(0xFFF5F9FF);
    final cardColor = isDarkMode ? const Color(0xFF1A1D33) : Colors.white;
    final inputBgColor =
        isDarkMode ? const Color(0xFF272B40) : const Color(0xFFE3F2FD);

    // Sắc thái sáng tối
    final lightShade = isDarkMode
        ? Colors.white.withOpacity(0.05)
        : Colors.black.withOpacity(0.05);
    final shadowColor = isDarkMode ? Colors.black54 : Colors.black12;

    return Scaffold(
      backgroundColor: backgroundColor,
      body: SafeArea(
        child: Column(
          children: [
            // Header mới với thiết kế đẹp hơn và avatar người dùng - không bo tròn ở dưới
            Container(
              decoration: BoxDecoration(
                gradient: primaryGradient,
                boxShadow: [
                  BoxShadow(
                    color: shadowColor,
                    blurRadius: 10,
                    spreadRadius: 2,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.only(
                  left: 16.0,
                  right: 16.0,
                  top: 12.0,
                  bottom: 16.0,
                ),
                child: Row(
                  children: [
                    // Avatar bot
                    Container(
                      height: 50,
                      width: 50,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(25),
                        border: Border.all(color: Colors.white, width: 2),
                        boxShadow: [
                          BoxShadow(
                            color: shadowColor,
                            blurRadius: 8,
                            spreadRadius: 1,
                          ),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(25),
                        child: Image.asset(
                          'assets/images/bot_avatar.png',
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            color: secondaryColor,
                            child: const Icon(
                              Icons.smart_toy_rounded,
                              color: Colors.white,
                              size: 30,
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    // Thông tin
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text(
                                'Copecute',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(width: 4),
                              Container(
                                width: 8,
                                height: 8,
                                decoration: BoxDecoration(
                                  color: Colors.greenAccent,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Hoạt động',
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.white.withOpacity(0.8),
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Menu nút actions
                    Row(
                      children: [
                        _buildHeaderButton(
                          icon: Icons.refresh,
                          tooltip: 'Tải lại lịch sử',
                          onPressed: _loadChatHistory,
                        ),
                        _buildHeaderButton(
                          icon: Icons.delete_outline,
                          tooltip: 'Xóa tin nhắn',
                          onPressed: chatService.messages.isEmpty
                              ? null
                              : _clearMessages,
                          disabled: chatService.messages.isEmpty,
                        ),
                        _buildHeaderButton(
                          icon: Icons.lightbulb_outline,
                          tooltip: 'Dạy bot',
                          onPressed: () => _showTeachBotForMessage(''),
                        ),
                        // Avatar người dùng với menu
                        PopupMenuButton(
                          offset: const Offset(0, 10),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          color: cardColor,
                          itemBuilder: (BuildContext context) {
                            // Xác định các màu sắc cho menu
                            final isDarkMode =
                                Theme.of(context).brightness == Brightness.dark;
                            final textColor =
                                isDarkMode ? Colors.white : Colors.black87;
                            final subtitleColor = isDarkMode
                                ? Colors.grey.shade400
                                : Colors.grey.shade600;
                            final dividerColor = isDarkMode
                                ? Colors.grey.shade800
                                : Colors.grey.shade200;
                            final primaryColor = isDarkMode
                                ? const Color(0xFF2979FF)
                                : const Color(0xFF2196F3);

                            return [
                              // Header với thông tin người dùng
                              PopupMenuItem(
                                enabled: false,
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 8),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Container(
                                          width: 50,
                                          height: 50,
                                          decoration: BoxDecoration(
                                            shape: BoxShape.circle,
                                            border: Border.all(
                                                color: primaryColor, width: 2),
                                          ),
                                          child: ClipRRect(
                                            borderRadius:
                                                BorderRadius.circular(25),
                                            child: user?.avatar != null
                                                ? Image.network(
                                                    user!.avatar!,
                                                    fit: BoxFit.cover,
                                                    errorBuilder:
                                                        (_, __, ___) =>
                                                            Container(
                                                      color: primaryColor
                                                          .withOpacity(0.7),
                                                      child: const Icon(
                                                        Icons.person,
                                                        color: Colors.white,
                                                        size: 30,
                                                      ),
                                                    ),
                                                  )
                                                : Container(
                                                    color: primaryColor
                                                        .withOpacity(0.7),
                                                    child: const Icon(
                                                      Icons.person,
                                                      color: Colors.white,
                                                      size: 30,
                                                    ),
                                                  ),
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                user?.fullName ?? 'Người dùng',
                                                style: TextStyle(
                                                  fontSize: 16,
                                                  fontWeight: FontWeight.bold,
                                                  color: textColor,
                                                ),
                                              ),
                                              const SizedBox(height: 2),
                                              Text(
                                                user?.email ?? '',
                                                style: TextStyle(
                                                  fontSize: 13,
                                                  color: subtitleColor,
                                                ),
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 12),
                                    Divider(color: dividerColor, height: 1),
                                  ],
                                ),
                              ),

                              // Trang cá nhân
                              PopupMenuItem(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 12),
                                onTap: () {
                                  // Delay để đảm bảo menu đã đóng trước khi chuyển trang
                                  Future.delayed(
                                      const Duration(milliseconds: 50), () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (context) =>
                                            const ProfileScreen(),
                                      ),
                                    );
                                  });
                                },
                                child: Row(
                                  children: [
                                    Icon(Icons.person_outline,
                                        color: primaryColor, size: 20),
                                    const SizedBox(width: 12),
                                    Text(
                                      'Trang cá nhân',
                                      style: TextStyle(
                                        fontSize: 15,
                                        color: textColor,
                                      ),
                                    ),
                                  ],
                                ),
                              ),

                              // Cài đặt
                              PopupMenuItem(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 12),
                                onTap: () {
                                  Future.delayed(
                                      const Duration(milliseconds: 50), () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (context) =>
                                            const SettingsScreen(),
                                      ),
                                    );
                                  });
                                },
                                child: Row(
                                  children: [
                                    Icon(Icons.settings_outlined,
                                        color: primaryColor, size: 20),
                                    const SizedBox(width: 12),
                                    Text(
                                      'Cài đặt',
                                      style: TextStyle(
                                        fontSize: 15,
                                        color: textColor,
                                      ),
                                    ),
                                  ],
                                ),
                              ),

                              // Đăng xuất
                              PopupMenuItem(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 12),
                                onTap: () async {
                                  Future.delayed(
                                      const Duration(milliseconds: 50),
                                      () async {
                                    bool confirmLogout = await showDialog(
                                          context: context,
                                          builder:
                                              (BuildContext dialogContext) {
                                            return AlertDialog(
                                              title: const Text(
                                                  'Xác nhận đăng xuất'),
                                              content: const Text(
                                                  'Bạn có chắc chắn muốn đăng xuất?'),
                                              actions: [
                                                TextButton(
                                                  onPressed: () => Navigator.of(
                                                          dialogContext)
                                                      .pop(false),
                                                  child: const Text('Hủy'),
                                                ),
                                                TextButton(
                                                  onPressed: () => Navigator.of(
                                                          dialogContext)
                                                      .pop(true),
                                                  style: TextButton.styleFrom(
                                                    foregroundColor: Colors.red,
                                                  ),
                                                  child:
                                                      const Text('Đăng xuất'),
                                                ),
                                              ],
                                            );
                                          },
                                        ) ??
                                        false;

                                    if (confirmLogout) {
                                      await authService.signOut();
                                      // Chuyển đến trang đăng nhập sau khi đăng xuất
                                      if (context.mounted) {
                                        Navigator.of(context)
                                            .pushAndRemoveUntil(
                                          MaterialPageRoute(
                                              builder: (context) =>
                                                  const LoginScreen()),
                                          (route) => false,
                                        );
                                      }
                                    }
                                  });
                                },
                                child: Row(
                                  children: [
                                    const Icon(Icons.logout,
                                        color: Colors.red, size: 20),
                                    const SizedBox(width: 12),
                                    Text(
                                      'Đăng xuất',
                                      style: TextStyle(
                                        fontSize: 15,
                                        color: Colors.red,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ];
                          },
                          child: Container(
                            height: 40,
                            width: 40,
                            margin: const EdgeInsets.only(left: 8),
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white, width: 2),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black26,
                                  blurRadius: 6,
                                ),
                              ],
                            ),
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(20),
                              child: user?.avatar != null &&
                                      user!.avatar!.isNotEmpty
                                  ? Image.network(
                                      user.avatar!,
                                      fit: BoxFit.cover,
                                      errorBuilder: (_, __, ___) => Container(
                                        color: primaryColor.withOpacity(0.7),
                                        child: Icon(
                                          Icons.person,
                                          color: Colors.white,
                                          size: 24,
                                        ),
                                      ),
                                    )
                                  : Container(
                                      color: primaryColor.withOpacity(0.7),
                                      child: const Icon(
                                        Icons.person,
                                        color: Colors.white,
                                        size: 24,
                                      ),
                                    ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),

            // Chat messages area
            Expanded(
              child: _isLoading
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: cardColor,
                              borderRadius: BorderRadius.circular(24),
                              boxShadow: [
                                BoxShadow(
                                  color: shadowColor,
                                  blurRadius: 12,
                                  spreadRadius: 2,
                                ),
                              ],
                            ),
                            child: Column(
                              children: [
                                SizedBox(
                                  width: 50,
                                  height: 50,
                                  child: CircularProgressIndicator(
                                    valueColor: AlwaysStoppedAnimation<Color>(
                                        primaryColor),
                                    strokeWidth: 3,
                                  ),
                                ),
                                const SizedBox(height: 16),
                                Text(
                                  'Đang tải tin nhắn...',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w500,
                                    fontSize: 16,
                                    color: isDarkMode
                                        ? Colors.white
                                        : Colors.black87,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    )
                  : Container(
                      decoration: BoxDecoration(
                        image: DecorationImage(
                          image: () {
                            debugPrint(
                                'useCustomBackground: ${settings.useCustomBackground}');
                            debugPrint(
                                'customBackgroundImagePath: ${settings.customBackgroundImagePath}');

                            if (settings.useCustomBackground &&
                                settings.customBackgroundImagePath != null) {
                              return FileImage(
                                      File(settings.customBackgroundImagePath!))
                                  as ImageProvider;
                            } else {
                              String assetPath = isDarkMode
                                  ? 'assets/images/bg-chat-dark.png'
                                  : 'assets/images/bg-chat-light.png';
                              debugPrint('Using asset image: $assetPath');
                              return AssetImage(assetPath);
                            }
                          }(),
                          repeat: settings.customBackgroundImagePath != null
                              ? ImageRepeat.noRepeat
                              : ImageRepeat.repeat,
                          fit: settings.customBackgroundImagePath != null
                              ? BoxFit.cover
                              : null,
                        ),
                      ),
                      child: Stack(
                        children: [
                          // Overlay màu đen với opacity
                          Positioned.fill(
                            child: Container(
                              color: Colors.black.withOpacity(isDarkMode
                                      ? 1 -
                                          settings
                                              .backgroundOpacity // Đảo ngược opacity cho dark mode
                                      : (1 - settings.backgroundOpacity) *
                                          0.5 // Giảm một nửa opacity cho light mode
                                  ),
                            ),
                          ),
                          // Chat messages
                          Positioned.fill(
                            child: messages.isEmpty
                                ? const SizedBox()
                                : ListView.builder(
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

                                      bool showDateHeader = false;
                                      String? dateHeader;

                                      if (index == 0) {
                                        showDateHeader = true;
                                        dateHeader = _formatDateHeader(
                                            message.timestamp);
                                      } else {
                                        final prevDate = _formatDate(
                                            messages[index - 1].timestamp);
                                        final currDate =
                                            _formatDate(message.timestamp);
                                        if (prevDate != currDate) {
                                          showDateHeader = true;
                                          dateHeader = _formatDateHeader(
                                              message.timestamp);
                                        }
                                      }

                                      return Column(
                                        children: [
                                          if (showDateHeader)
                                            Padding(
                                              padding: const EdgeInsets.only(
                                                top: 8.0,
                                                bottom: 16.0,
                                              ),
                                              child:
                                                  _buildDateHeader(dateHeader!),
                                            ),
                                          Padding(
                                            padding: const EdgeInsets.only(
                                                bottom: 4.0),
                                            child: ChatBubble(
                                              message: message,
                                              showAvatar: showAvatar,
                                              fontSize: settings.fontSize,
                                              onTeachBot: message
                                                          .isDefaultResponse &&
                                                      !message.isSentByUser
                                                  ? () =>
                                                      _showTeachBotForMessage(
                                                          index > 0
                                                              ? messages[
                                                                      index - 1]
                                                                  .text
                                                              : '')
                                                  : null,
                                            ),
                                          ),
                                        ],
                                      );
                                    },
                                  ),
                          ),

                          // Hiệu ứng đang nhập
                          if (chatService.isTyping)
                            Positioned(
                              bottom: 4,
                              left: 16,
                              child: TypingIndicator(),
                            ),
                        ],
                      ),
                    ),
            ),

            // Input area - đảm bảo không có padding dư
            Container(
              decoration: BoxDecoration(
                color: backgroundColor,
                boxShadow: [
                  BoxShadow(
                    color: shadowColor,
                    blurRadius: 4,
                    offset: const Offset(0, -1),
                  ),
                ],
              ),
              padding: EdgeInsets.only(
                left: 16.0,
                right: 16.0,
                top: 8.0,
                bottom: MediaQuery.of(context).padding.bottom + 8.0,
              ),
              child: Container(
                height: 48,
                decoration: BoxDecoration(
                  color: isDarkMode ? const Color(0xFF1E1E1E) : Colors.white,
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(
                    color: isDarkMode
                        ? Colors.grey.shade800
                        : Colors.grey.shade300,
                    width: 1,
                  ),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(left: 16),
                        child: TextField(
                          controller: _messageController,
                          focusNode: _focusNode,
                          maxLines: 1,
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
                            focusedBorder: InputBorder.none,
                            enabledBorder: InputBorder.none,
                            errorBorder: InputBorder.none,
                            disabledBorder: InputBorder.none,
                            hintStyle: TextStyle(
                              color: isDarkMode
                                  ? Colors.grey.shade500
                                  : Colors.grey.shade400,
                              fontSize: 16,
                            ),
                            contentPadding:
                                const EdgeInsets.symmetric(vertical: 10),
                          ),
                          style: TextStyle(
                            fontSize: 16,
                            color: isDarkMode ? Colors.white : Colors.black87,
                          ),
                          textCapitalization: TextCapitalization.sentences,
                          keyboardType: TextInputType.text,
                          textInputAction: TextInputAction.send,
                          onSubmitted: (value) {
                            if (value.isNotEmpty) {
                              _sendMessage();
                            }
                          },
                        ),
                      ),
                    ),
                    Container(
                      margin: const EdgeInsets.only(right: 8),
                      child: IconButton(
                        icon: Icon(
                          Icons.send_rounded,
                          color: primaryColor,
                          size: 22,
                        ),
                        onPressed: () {
                          if (_messageController.text.trim().isNotEmpty) {
                            _sendMessage();
                          }
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Tạo nút header có animation đẹp
  Widget _buildHeaderButton({
    required IconData icon,
    required String tooltip,
    required Function()? onPressed,
    bool disabled = false,
  }) {
    return Tooltip(
      message: tooltip,
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 4),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(20),
            onTap: onPressed,
            child: Padding(
              padding: const EdgeInsets.all(8.0),
              child: Icon(
                icon,
                color: disabled
                    ? Colors.white.withOpacity(0.3)
                    : Colors.white.withOpacity(0.9),
                size: 22,
              ),
            ),
          ),
        ),
      ),
    );
  }

  // Định dạng ngày tháng để hiển thị header
  String _formatDate(DateTime date) {
    return '${date.year}-${date.month}-${date.day}';
  }

  // Định dạng header hiển thị ngày
  String _formatDateHeader(DateTime date) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final yesterday = DateTime(now.year, now.month, now.day - 1);
    final messageDate = DateTime(date.year, date.month, date.day);

    if (messageDate == today) {
      return 'Hôm nay';
    } else if (messageDate == yesterday) {
      return 'Hôm qua';
    } else {
      return '${date.day} tháng ${date.month}, ${date.year}';
    }
  }

  // Tạo widget hiển thị ngày tháng
  Widget _buildDateHeader(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: Theme.of(context).brightness == Brightness.dark
            ? Colors.white.withOpacity(0.1)
            : Colors.black.withOpacity(0.05),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 12,
          color: Theme.of(context).brightness == Brightness.dark
              ? Colors.white.withOpacity(0.7)
              : Colors.black54,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }
}

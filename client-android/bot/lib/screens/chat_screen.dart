import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../utils/app_routes.dart';
import '../utils/app_theme.dart';
import '../models/message.dart';
import '../widgets/chat_bubble.dart';
import '../providers/settings_provider.dart';
import '../services/auth_service.dart';
import 'login_screen.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final FocusNode _focusNode = FocusNode();
  final List<Message> _messages = [
    Message(
      text: 'Xin chào! Tôi là Copecute, tôi có thể giúp gì cho bạn?',
      isSentByUser: false,
      timestamp: DateTime.now().subtract(const Duration(minutes: 1)),
    ),
  ];

  // Messenger blue color
  static const Color messengerBlue = Color(0xFF0084FF);

  @override
  void initState() {
    super.initState();
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _sendMessage() {
    // Bỏ ký tự xuống dòng nếu có trước khi kiểm tra text rỗng
    final messageText = _messageController.text.trim();
    if (messageText.isEmpty) return;

    final userMessage = Message(
      text: messageText,
      isSentByUser: true,
      timestamp: DateTime.now(),
    );

    setState(() {
      _messages.add(userMessage);
      // Xóa text và đưa TextField về trạng thái sạch
      _messageController.clear();
    });

    // Scroll to bottom
    _scrollToBottom();

    // Simulate bot response after a short delay
    Future.delayed(const Duration(seconds: 1), () {
      final botResponse = Message(
        text:
            'Đây là phản hồi tự động từ Copecute. Trong tương lai, tôi sẽ được kết nối với API để trả lời thông minh hơn.',
        isSentByUser: false,
        timestamp: DateTime.now(),
      );

      setState(() {
        _messages.add(botResponse);
      });

      // Scroll to bottom again after bot response
      _scrollToBottom();
    });
  }

  void _scrollToBottom() {
    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    final settings = settingsProvider.settings;
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final authService = Provider.of<AuthService>(context);
    final user = authService.currentUser;

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
          IconButton(
            icon: const Icon(Icons.settings_outlined),
            onPressed: () {
              // Hiển thị thông tin người dùng
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Thông tin người dùng'),
                  content: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (user != null) ...[
                        ListTile(
                          leading: CircleAvatar(
                            backgroundImage: user.avatar != null
                                ? NetworkImage(user.avatar!)
                                : null,
                            child: user.avatar == null
                                ? Text(
                                    user.username.substring(0, 1).toUpperCase(),
                                    style: const TextStyle(fontSize: 24),
                                  )
                                : null,
                          ),
                          title: Text(user.fullName),
                          subtitle: Text(user.email),
                        ),
                        const Divider(),
                        ListTile(
                          leading: const Icon(Icons.message),
                          title: Text('Quota: ${user.quota}'),
                        ),
                      ],
                    ],
                  ),
                  actions: [
                    TextButton(
                      onPressed: () {
                        Navigator.of(context).pop();
                      },
                      child: const Text('Đóng'),
                    ),
                    TextButton(
                      onPressed: () async {
                        Navigator.of(context).pop();
                        await authService.signOut();
                        if (context.mounted) {
                          Navigator.of(context).pushReplacement(
                            MaterialPageRoute(
                              builder: (context) => const LoginScreen(),
                            ),
                          );
                        }
                      },
                      child: const Text(
                        'Đăng xuất',
                        style: TextStyle(color: Colors.red),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Chat messages
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: isDarkMode
                    ? const Color(0xFF121212)
                    : const Color(0xFFF2F2F2),
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(30),
                  topRight: Radius.circular(30),
                ),
              ),
              child: _messages.isEmpty
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
                  : ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.all(16),
                      itemCount: _messages.length,
                      itemBuilder: (context, index) {
                        final message = _messages[index];
                        final showAvatar = index == 0 ||
                            _messages[index - 1].isSentByUser !=
                                message.isSentByUser;

                        return Padding(
                          padding: const EdgeInsets.only(bottom: 4.0),
                          child: ChatBubble(
                            message: message,
                            showAvatar: showAvatar,
                            fontSize: settings.fontSize,
                          ),
                        );
                      },
                    ),
            ),
          ),

          // Input area
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 8.0),
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
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../models/message.dart';
import 'package:intl/intl.dart';
import '../utils/auth_service.dart';
import 'package:provider/provider.dart';

class ChatBubble extends StatelessWidget {
  final Message message;
  final bool showAvatar;
  final double fontSize;
  final Function()? onTeachBot;

  // Màu sắc chính
  static const Color messengerBlue = Color(0xFF2979FF);
  static const Color messengerGrey = Color(0xFFE4E6EB);
  static const Color darkModeGrey = Color(0xFF2A2F4F);

  const ChatBubble({
    super.key,
    required this.message,
    this.showAvatar = true,
    this.fontSize = 16,
    this.onTeachBot,
  });

  @override
  Widget build(BuildContext context) {
    final isUser = message.isSentByUser;
    final timeString = DateFormat('HH:mm', 'vi_VN').format(message.timestamp);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final authService = Provider.of<AuthService>(context);
    final user = authService.currentUser;

    // Gradient cho bong bóng tin nhắn người dùng
    final userGradient = LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: [
        const Color(0xFF2979FF),
        const Color(0xFF1E88E5),
      ],
    );

    // Gradient cho bong bóng tin nhắn bot (subtle)
    final botGradient = LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: isDarkMode
          ? [
              darkModeGrey,
              const Color(0xFF232842),
            ]
          : [
              const Color(0xFFEEF1F6),
              const Color(0xFFE2E7F0),
            ],
    );

    // Màu chữ dựa theo chế độ tối/sáng
    final botBubbleColor = isDarkMode ? darkModeGrey : messengerGrey;
    final botTextColor = isDarkMode ? Colors.white : Colors.black87;
    final timeColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;
    final userBubbleColor = messengerBlue;
    final double avatarSize = 32;

    // Hiệu ứng đổ bóng
    final bubbleShadow = [
      BoxShadow(
        color: isUser
            ? Colors.blue.withOpacity(0.15)
            : Colors.black.withOpacity(0.08),
        blurRadius: 8,
        spreadRadius: 0,
        offset: const Offset(0, 2),
      ),
    ];

    // Tạo border radius khác nhau cho tin nhắn người dùng và bot
    final borderRadius = isUser
        ? const BorderRadius.only(
            topLeft: Radius.circular(20),
            topRight: Radius.circular(20),
            bottomLeft: Radius.circular(20),
            bottomRight: Radius.circular(6),
          )
        : const BorderRadius.only(
            topLeft: Radius.circular(6),
            topRight: Radius.circular(20),
            bottomLeft: Radius.circular(20),
            bottomRight: Radius.circular(20),
          );

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisAlignment:
            isUser ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          if (!isUser && showAvatar)
            Container(
              margin: const EdgeInsets.only(right: 8),
              child: Container(
                height: 36,
                width: 36,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(
                    color: isDarkMode
                        ? Colors.white.withOpacity(0.1)
                        : Colors.white,
                    width: 2,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 5,
                      spreadRadius: 0,
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: Image.asset(
                    'assets/images/bot_avatar.png',
                    fit: BoxFit.cover,
                  ),
                ),
              ),
            )
          else if (!isUser && !showAvatar)
            const SizedBox(width: 44),
          Flexible(
            child: Column(
              crossAxisAlignment:
                  isUser ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                // Bong bóng tin nhắn
                Container(
                  constraints: BoxConstraints(
                    maxWidth: MediaQuery.of(context).size.width * 0.75,
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  decoration: BoxDecoration(
                    gradient: isUser ? userGradient : botGradient,
                    borderRadius: borderRadius,
                    boxShadow: bubbleShadow,
                  ),
                  child: GestureDetector(
                    onLongPress: () {
                      // Hiển thị menu sao chép
                      final RenderBox renderBox =
                          context.findRenderObject() as RenderBox;
                      final position = renderBox.localToGlobal(Offset.zero);
                      final size = renderBox.size;

                      showMenu(
                        context: context,
                        position: RelativeRect.fromLTRB(
                            isUser ? position.dx - 150 : position.dx,
                            position.dy,
                            position.dx + size.width,
                            position.dy + size.height),
                        items: [
                          PopupMenuItem(
                            child: Row(
                              children: [
                                Icon(Icons.copy,
                                    size: 18,
                                    color: isDarkMode
                                        ? Colors.white
                                        : Colors.black87),
                                const SizedBox(width: 8),
                                Text('Sao chép'),
                              ],
                            ),
                            onTap: () {
                              Clipboard.setData(
                                  ClipboardData(text: message.text));
                              // Delay để menu đóng trước khi hiển thị Snackbar
                              Future.delayed(const Duration(milliseconds: 200),
                                  () {
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content:
                                          const Text('Đã sao chép tin nhắn'),
                                      backgroundColor: const Color(0xFF3B82F6),
                                      behavior: SnackBarBehavior.floating,
                                      duration: const Duration(seconds: 1),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                    ),
                                  );
                                }
                              });
                            },
                          ),
                        ],
                      );
                    },
                    child: Column(
                      crossAxisAlignment: isUser
                          ? CrossAxisAlignment.end
                          : CrossAxisAlignment.start,
                      children: [
                        Text(
                          message.text,
                          style: TextStyle(
                            color: isUser ? Colors.white : botTextColor,
                            fontSize: fontSize,
                            height: 1.4,
                            fontWeight:
                                isUser ? FontWeight.w400 : FontWeight.w400,
                          ),
                        ),

                        // Chỉ hiển thị thời gian (không còn nút sao chép)
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(
                            timeString,
                            style: TextStyle(
                              fontSize: 10,
                              color: isUser
                                  ? Colors.white.withOpacity(0.7)
                                  : isDarkMode
                                      ? Colors.grey.shade300.withOpacity(0.7)
                                      : const Color(0xFF475569)
                                          .withOpacity(0.8),
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // Nút dạy bot nếu là tin nhắn mặc định từ bot
                if (!isUser && message.isDefaultResponse && onTeachBot != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4, left: 2),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: onTeachBot,
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? const Color(
                                    0xFF1E40AF) // Deeper blue for dark mode
                                : const Color(0xFFDBEAFE), // Light blue bg
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.2),
                                blurRadius: 3,
                                spreadRadius: 0,
                                offset: const Offset(0, 1),
                              ),
                            ],
                            border: Border.all(
                              color: isDarkMode
                                  ? const Color(0xFF3B82F6) // Blue-500
                                  : const Color(0xFF3B82F6), // Blue-500
                              width: 1,
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.lightbulb_outline,
                                size: 14,
                                color: isDarkMode
                                    ? Colors.yellow.shade300
                                    : const Color(0xFF1D4ED8), // Blue-700
                              ),
                              const SizedBox(width: 4),
                              Text(
                                'Dạy Bot',
                                style: TextStyle(
                                  color: isDarkMode
                                      ? Colors.white
                                      : const Color(0xFF1D4ED8), // Blue-700
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          if (isUser && showAvatar)
            Container(
              margin: const EdgeInsets.only(left: 8),
              height: 36,
              width: 36,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(18),
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    messengerBlue,
                    messengerBlue.withOpacity(0.8),
                  ],
                ),
                boxShadow: [
                  BoxShadow(
                    color: messengerBlue.withOpacity(0.3),
                    blurRadius: 5,
                    spreadRadius: 0,
                  ),
                ],
                border: Border.all(
                  color: isDarkMode
                      ? Colors.black.withOpacity(0.1)
                      : Colors.white.withOpacity(0.9),
                  width: 2,
                ),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: user?.avatar != null
                    ? Image.network(
                        user!.avatar!,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Center(
                          child: Text(
                            user.fullName.isNotEmpty
                                ? user.fullName.substring(0, 1).toUpperCase()
                                : 'U',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      )
                    : Center(
                        child: Text(
                          user?.fullName.isNotEmpty == true
                              ? user!.fullName.substring(0, 1).toUpperCase()
                              : 'U',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
              ),
            )
          else if (isUser && !showAvatar)
            const SizedBox(width: 44),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import '../models/message.dart';
import 'package:intl/intl.dart';
import '../utils/auth_service.dart';
import 'package:provider/provider.dart';

class ChatBubble extends StatelessWidget {
  final Message message;
  final bool showAvatar;
  final double fontSize;
  final Function()? onTeachBot;

  // Facebook Messenger colors
  static const Color messengerBlue = Color(0xFF0084FF);
  static const Color messengerGrey = Color(0xFFE4E6EB);
  static const Color darkModeGrey = Color(0xFF3E4042);

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
    final timeString = DateFormat('HH:mm').format(message.timestamp);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final authService = Provider.of<AuthService>(context);
    final user = authService.currentUser;

    // Colors based on dark/light mode
    final botBubbleColor = isDarkMode ? darkModeGrey : messengerGrey;
    final botTextColor = isDarkMode ? Colors.white : Colors.black87;
    final timeColor = Colors.grey.shade500;
    final userBubbleColor = messengerBlue;
    final double avatarSize = 32;

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
              child: CircleAvatar(
                backgroundColor: Colors.transparent,
                radius: 16,
                backgroundImage: AssetImage('assets/images/bot_avatar.png'),
              ),
            )
          else if (!isUser && !showAvatar)
            const SizedBox(width: 40),
          Flexible(
            child: Column(
              crossAxisAlignment:
                  isUser ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  decoration: BoxDecoration(
                    color: isUser
                        ? userBubbleColor.withOpacity(0.95)
                        : botBubbleColor.withOpacity(isDarkMode ? 0.95 : 0.9),
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.08),
                        blurRadius: 3,
                        offset: const Offset(0, 1),
                      ),
                    ],
                  ),
                  child: Text(
                    message.text,
                    style: TextStyle(
                      color: isUser ? Colors.white : botTextColor,
                      fontSize: fontSize,
                      height: 1.3,
                    ),
                  ),
                ),

                // Row cho thời gian và nút dạy bot (nếu cần)
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Padding(
                      padding: const EdgeInsets.only(top: 4, left: 4, right: 4),
                      child: Text(
                        timeString,
                        style: TextStyle(fontSize: 10, color: timeColor),
                      ),
                    ),

                    // Hiển thị nút dạy bot nếu là tin nhắn từ bot và là phản hồi mặc định
                    if (!isUser &&
                        message.isDefaultResponse &&
                        onTeachBot != null)
                      Padding(
                        padding: const EdgeInsets.only(left: 8, top: 4),
                        child: InkWell(
                          onTap: onTeachBot,
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: isDarkMode
                                  ? Colors.black12
                                  : Colors.white.withOpacity(0.7),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: messengerBlue.withOpacity(0.5),
                                width: 1,
                              ),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.lightbulb_outline,
                                  size: 14,
                                  color: messengerBlue,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  'Dạy Bot',
                                  style: TextStyle(
                                    color: messengerBlue,
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
          if (isUser && showAvatar)
            Container(
              margin: const EdgeInsets.only(left: 8),
              child: CircleAvatar(
                backgroundColor: messengerBlue,
                radius: 16,
                backgroundImage:
                    user?.avatar != null ? NetworkImage(user!.avatar!) : null,
                child: user?.avatar == null
                    ? (user?.fullName.isNotEmpty == true
                        ? Text(
                            user!.fullName.substring(0, 1).toUpperCase(),
                            style: const TextStyle(
                                color: Colors.white, fontSize: 16),
                          )
                        : const Icon(
                            Icons.person_rounded,
                            color: Colors.white,
                            size: 16,
                          ))
                    : null,
              ),
            )
          else if (isUser && !showAvatar)
            const SizedBox(width: 40),
        ],
      ),
    );
  }
}

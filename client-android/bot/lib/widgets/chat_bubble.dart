import 'package:flutter/material.dart';
import '../models/message.dart';
import 'package:intl/intl.dart';

class ChatBubble extends StatelessWidget {
  final Message message;
  final bool showAvatar;
  final double fontSize;

  // Facebook Messenger colors
  static const Color messengerBlue = Color(0xFF0084FF);
  static const Color messengerGrey = Color(0xFFE4E6EB);
  static const Color darkModeGrey = Color(0xFF3E4042);

  const ChatBubble({
    super.key,
    required this.message,
    this.showAvatar = true,
    this.fontSize = 16,
  });

  @override
  Widget build(BuildContext context) {
    final isUser = message.isSentByUser;
    final timeString = DateFormat('HH:mm').format(message.timestamp);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    // Colors based on dark/light mode
    final botBubbleColor = isDarkMode ? darkModeGrey : messengerGrey;
    final botTextColor = isDarkMode ? Colors.white : Colors.black87;
    final timeColor = Colors.grey.shade500;
    final userBubbleColor = messengerBlue;
    final double avatarSize = 32;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2.0),
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
                radius: avatarSize / 2,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(avatarSize / 2),
                  child: Image.asset(
                    'assets/images/bot_avatar.png',
                    width: avatarSize,
                    height: avatarSize,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) => Container(
                      width: avatarSize,
                      height: avatarSize,
                      decoration: BoxDecoration(
                        color: messengerBlue,
                        borderRadius: BorderRadius.circular(avatarSize / 2),
                      ),
                      child: Icon(
                        Icons.smart_toy_rounded,
                        color: Colors.white,
                        size: avatarSize * 0.6,
                      ),
                    ),
                  ),
                ),
              ),
            )
          else if (!isUser && !showAvatar)
            SizedBox(width: avatarSize + 8),
          Flexible(
            child: Column(
              crossAxisAlignment:
                  isUser ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: isUser ? userBubbleColor : botBubbleColor,
                    borderRadius: BorderRadius.circular(18),
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
                Padding(
                  padding: const EdgeInsets.only(top: 2, left: 4, right: 4),
                  child: Text(
                    timeString,
                    style: TextStyle(fontSize: 10, color: timeColor),
                  ),
                ),
              ],
            ),
          ),
          if (isUser && showAvatar)
            Container(
              margin: const EdgeInsets.only(left: 8),
              child: const CircleAvatar(
                backgroundColor: messengerBlue,
                radius: 16,
                child: Icon(
                  Icons.person_rounded,
                  color: Colors.white,
                  size: 16,
                ),
              ),
            )
          else if (isUser && !showAvatar)
            const SizedBox(width: 0),
        ],
      ),
    );
  }
}

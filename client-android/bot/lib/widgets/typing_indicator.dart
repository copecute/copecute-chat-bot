import 'package:flutter/material.dart';
import 'dart:math' as math;

class TypingIndicator extends StatefulWidget {
  const TypingIndicator({super.key});

  @override
  State<TypingIndicator> createState() => _TypingIndicatorState();
}

class _TypingIndicatorState extends State<TypingIndicator>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    // Màu sắc nâng cao
    final botGradient = LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: isDarkMode
          ? [
              const Color(0xFF2A2F4F),
              const Color(0xFF232842),
            ]
          : [
              const Color(0xFFEEF1F6),
              const Color(0xFFE2E7F0),
            ],
    );

    // Màu chấm nhảy
    final dotColors = [
      const Color(0xFF4FC3F7),
      const Color(0xFF2196F3),
      const Color(0xFF1976D2),
    ];

    // Bóng đổ
    final shadowColor =
        isDarkMode ? Colors.black54 : Colors.black.withOpacity(0.1);

    return Row(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        // Avatar bot
        Container(
          height: 36,
          width: 36,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: isDarkMode ? Colors.white.withOpacity(0.1) : Colors.white,
              width: 2,
            ),
            boxShadow: [
              BoxShadow(
                color: shadowColor,
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
        const SizedBox(width: 8),

        // Bong bóng "đang nhập..."
        Container(
          padding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 12,
          ),
          decoration: BoxDecoration(
            gradient: botGradient,
            borderRadius: const BorderRadius.only(
              topLeft: Radius.circular(6),
              topRight: Radius.circular(20),
              bottomLeft: Radius.circular(20),
              bottomRight: Radius.circular(20),
            ),
            boxShadow: [
              BoxShadow(
                color: shadowColor,
                blurRadius: 8,
                spreadRadius: 0,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: AnimatedBuilder(
            animation: _controller,
            builder: (context, child) {
              return Row(
                mainAxisSize: MainAxisSize.min,
                children: List.generate(3, (index) {
                  final delay = index * 0.2;
                  final position = (_controller.value + delay) % 1.0;

                  // Tạo hiệu ứng bounce giống như nhảy lên nhảy xuống
                  final bounce = math.sin(position * math.pi);
                  final scale = 0.6 + 0.4 * bounce;
                  final translateY = -2.0 * bounce;

                  // Tạo hiệu ứng mờ dần
                  final opacity = 0.4 + (0.6 * bounce);

                  return Transform.translate(
                    offset: Offset(0, translateY),
                    child: Transform.scale(
                      scale: scale,
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 2.5),
                        child: Container(
                          width: 8,
                          height: 8,
                          decoration: BoxDecoration(
                            color: dotColors[index].withOpacity(opacity),
                            borderRadius: BorderRadius.circular(4),
                            boxShadow: [
                              BoxShadow(
                                color: dotColors[index].withOpacity(0.3),
                                blurRadius: 4,
                                spreadRadius: 0,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                }),
              );
            },
          ),
        ),

        // Thông báo "đang nhập" cho người dùng
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 4),
          child: Text(
            'Đang nhập...',
            style: TextStyle(
              fontSize: 10,
              fontStyle: FontStyle.italic,
              color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600,
            ),
          ),
        ),
      ],
    );
  }
}

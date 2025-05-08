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
  final List<double> _heightValues = [5.0, 10.0, 5.0];

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
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
    final botBubbleColor =
        isDarkMode ? const Color(0xFF3E4042) : const Color(0xFFE4E6EB);

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        CircleAvatar(
          backgroundColor: Colors.transparent,
          radius: 16,
          backgroundImage: AssetImage('assets/images/bot_avatar.png'),
        ),
        const SizedBox(width: 8),
        Container(
          padding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 12,
          ),
          decoration: BoxDecoration(
            color: botBubbleColor,
            borderRadius: BorderRadius.circular(18),
          ),
          child: AnimatedBuilder(
            animation: _controller,
            builder: (context, child) {
              return Row(
                mainAxisSize: MainAxisSize.min,
                children: List.generate(3, (index) {
                  final delay = index * 0.2;
                  final position = (_controller.value + delay) % 1.0;
                  final height = 4.0 + 6.0 * math.sin(position * math.pi);

                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 2.5),
                    child: Container(
                      width: 6,
                      height: height,
                      decoration: BoxDecoration(
                        color: isDarkMode
                            ? Colors.white.withOpacity(0.8)
                            : Colors.black.withOpacity(0.75),
                        borderRadius: BorderRadius.circular(6),
                      ),
                    ),
                  );
                }),
              );
            },
          ),
        ),
      ],
    );
  }
}

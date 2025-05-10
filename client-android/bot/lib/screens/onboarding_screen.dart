import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:copecute/models/onboarding_content.dart';
import 'package:copecute/screens/login_screen.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  late PageController _pageController;
  int _pageIndex = 0;

  @override
  void initState() {
    super.initState();
    _pageController = PageController(initialPage: 0);
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  // Đánh dấu đã xem onboarding
  void _completeOnboarding() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('hasSeenOnboarding', true);
    if (mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => const LoginScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                onPageChanged: (index) {
                  setState(() {
                    _pageIndex = index;
                  });
                },
                itemCount: onboardingContents.length,
                itemBuilder: (context, index) => OnboardingContent(
                  image: onboardingContents[index].image,
                  title: onboardingContents[index].title,
                  description: onboardingContents[index].description,
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(20),
              child: Row(
                children: [
                  ...List.generate(
                    onboardingContents.length,
                    (index) => Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: DotIndicator(isActive: index == _pageIndex),
                    ),
                  ),
                  const Spacer(),
                  Container(
                    height: 60,
                    width: 60,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: const Color(0xFFFF6B6B),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFFFF6B6B).withOpacity(0.3),
                          blurRadius: 10,
                          offset: const Offset(0, 5),
                        ),
                      ],
                    ),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        borderRadius: BorderRadius.circular(30),
                        onTap: () {
                          if (_pageIndex == onboardingContents.length - 1) {
                            _completeOnboarding();
                          } else {
                            _pageController.nextPage(
                              duration: const Duration(milliseconds: 300),
                              curve: Curves.ease,
                            );
                          }
                        },
                        child: Center(
                          child: Icon(
                            _pageIndex == onboardingContents.length - 1
                                ? Icons.check
                                : Icons.arrow_forward,
                            color: Colors.white,
                            size: 28,
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.only(
                bottom: 16.0,
                left: 16.0,
                right: 16.0,
              ),
              child: TextButton(
                onPressed: _completeOnboarding,
                child: const Text(
                  'Bỏ qua',
                  style: TextStyle(
                    color: Colors.grey,
                    fontSize: 16,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class OnboardingContent extends StatelessWidget {
  final String image;
  final String title;
  final String description;

  const OnboardingContent({
    super.key,
    required this.image,
    required this.title,
    required this.description,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(20.0),
      child: Column(
        children: [
          const Spacer(),
          Image.asset(
            image,
            height: 250,
          ),
          const Spacer(),
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: Color(0xFF1E1E1E),
              fontSize: 24,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            description,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: Colors.grey,
              fontSize: 16,
            ),
          ),
          const Spacer(),
        ],
      ),
    );
  }
}

class DotIndicator extends StatelessWidget {
  const DotIndicator({
    super.key,
    this.isActive = false,
  });

  final bool isActive;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      height: 8,
      width: isActive ? 24 : 8,
      decoration: BoxDecoration(
        color: isActive ? const Color(0xFFFF6B6B) : Colors.grey.shade300,
        borderRadius: BorderRadius.circular(8),
      ),
    );
  }
}

class OnboardingContent {
  final String image;
  final String title;
  final String description;

  OnboardingContent({
    required this.image,
    required this.title,
    required this.description,
  });
}

final List<OnboardingContent> onboardingContents = [
  OnboardingContent(
    image: 'assets/images/onboarding1.png',
    title: 'Đăng ký trực tuyến',
    description:
        'Đăng ký tài khoản nhanh chóng và dễ dàng để bắt đầu trò chuyện với Copecute',
  ),
  OnboardingContent(
    image: 'assets/images/onboarding2.png',
    title: 'Bắt đầu ngay',
    description:
        'Trải nghiệm trò chuyện thông minh với trợ lý ảo được cá nhân hóa',
  ),
  OnboardingContent(
    image: 'assets/images/onboarding3.png',
    title: 'Thư giãn & Trò chuyện',
    description:
        'Copecute luôn sẵn sàng lắng nghe và trò chuyện với bạn mọi lúc mọi nơi',
  ),
];

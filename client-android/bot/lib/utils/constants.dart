class AppConstants {
  // URLs và API endpoints
  static const String baseUrl = 'https://copecute.minhgiang.pro';
  static const String apiUrl = '$baseUrl/api';

  // API endpoints cụ thể
  static const String loginEndpoint = '$apiUrl/auth/login.php';
  static const String registerEndpoint = '$apiUrl/auth/register.php';
  static const String googleLoginEndpoint = '$apiUrl/auth/google_login.php';
  static const String chatEndpoint = '$apiUrl/chat/chat.php';

  // Nhiều endpoints khác nhau cho dạy bot để thử nghiệm
  static const String teachEndpoint =
      '$apiUrl/chat/teach.php'; // Endpoint API chính
  static const String teachWebEndpoint =
      '$baseUrl/chat/teachbot_controller.php'; // Endpoint web
  static const String teachBackupEndpoint =
      '$apiUrl/teach_bot.php'; // Endpoint backup

  static const String historyEndpoint = '$apiUrl/chat/history.php';
  static const String clearHistoryEndpoint = '$apiUrl/chat/clear_messages.php';

  // Thông tin ứng dụng
  static const String appName = 'Copecute';
  static const String appVersion = '1.0.0';

  // Các khóa lưu trữ trong SharedPreferences
  static const String userPrefKey = 'user';
  static const String tokenPrefKey = 'token';
  static const String themePrefKey = 'theme';

  // URL dành cho môi trường phát triển (dev)
  static const String devBaseUrl = 'http://10.0.2.2/simsimi';
  static const String devApiUrl = '$devBaseUrl/api';

  // API Endpoint cho profile
  static String get profileEndpoint => '$baseUrl/api/profile/index.php';
  static String get profileUpdateEndpoint => '$baseUrl/api/profile/update.php';
  static String get changePasswordEndpoint =>
      '$baseUrl/api/profile/change_password.php';
}

class AppConstants {
  // URLs và API endpoints
  static const String baseUrl = 'https://copecute.minhgiang.pro';
  static const String apiUrl = '$baseUrl/api';

  // API endpoints cụ thể
  static const String loginEndpoint = '$apiUrl/login.php';
  static const String registerEndpoint = '$apiUrl/register.php';
  static const String googleLoginEndpoint = '$apiUrl/google_login.php';
  static const String chatEndpoint = '$apiUrl/chat.php';
  static const String teachEndpoint = '$apiUrl/teach.php';
  static const String historyEndpoint = '$apiUrl/history.php';

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
}

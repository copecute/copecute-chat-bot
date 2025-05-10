import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';
import 'package:copecute/utils/auth_service.dart';
import 'package:copecute/services/chat_service.dart';
import 'package:copecute/screens/login_screen.dart';
import 'package:copecute/screens/chat_screen.dart';
import 'package:copecute/screens/profile_screen.dart';
import 'package:copecute/screens/settings_screen.dart';
import 'package:copecute/providers/settings_provider.dart';
import 'package:copecute/utils/app_theme.dart';
import 'package:copecute/services/profile_service.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:copecute/screens/onboarding_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Kiểm tra xem người dùng đã xem onboarding chưa
  final prefs = await SharedPreferences.getInstance();
  final hasSeenOnboarding = prefs.getBool('hasSeenOnboarding') ?? false;

  runApp(MyApp(hasSeenOnboarding: hasSeenOnboarding));
}

class MyApp extends StatelessWidget {
  final bool hasSeenOnboarding;

  const MyApp({super.key, required this.hasSeenOnboarding});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (context) => AuthService()),
        ChangeNotifierProvider(create: (context) => SettingsProvider()),
        ChangeNotifierProvider(create: (context) => ChatService()),
        ChangeNotifierProvider(create: (context) => ProfileService()),
      ],
      child: Consumer2<AuthService, SettingsProvider>(
        builder: (context, authService, settingsProvider, _) {
          final settings = settingsProvider.settings;

          return MaterialApp(
            title: 'Copecute Chatbot',
            debugShowCheckedModeBanner: false,
            theme: AppTheme.lightTheme,
            darkTheme: AppTheme.darkTheme,
            themeMode: settings.getThemeMode(),
            locale: const Locale('vi', 'VN'),
            supportedLocales: const [
              Locale('vi', 'VN'),
              Locale('en', 'US'),
            ],
            localizationsDelegates: [
              GlobalMaterialLocalizations.delegate,
              GlobalWidgetsLocalizations.delegate,
              GlobalCupertinoLocalizations.delegate,
            ],
            home: hasSeenOnboarding
                ? const LoginScreen()
                : const OnboardingScreen(),
          );
        },
      ),
    );
  }
}

class LoadingScreen extends StatelessWidget {
  const LoadingScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: CircularProgressIndicator(),
      ),
    );
  }
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({Key? key}) : super(key: key);

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  static const Color messengerBlue = Color(0xFF0084FF);

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF1E1E1E) : Colors.white;

    return const Scaffold(
      body: ChatScreen(),
    );
  }
}

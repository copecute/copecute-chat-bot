import 'package:flutter/material.dart';

enum ThemePreference { system, light, dark }

class AppSettings {
  ThemePreference themePreference;
  double fontSize;
  bool enterToSend;
  bool notifications;
  String language;
  bool useCustomBackground;
  String? customBackgroundImagePath;
  double backgroundOpacity;

  static const double minFontSize = 14.0;
  static const double maxFontSize = 20.0;
  static const double defaultFontSize = 16.0;
  static const double defaultBackgroundOpacity = 0.5;

  AppSettings({
    this.themePreference = ThemePreference.system,
    this.fontSize = defaultFontSize,
    this.enterToSend = true,
    this.notifications = true,
    this.language = 'Tiếng Việt',
    this.useCustomBackground = true,
    this.customBackgroundImagePath,
    this.backgroundOpacity = defaultBackgroundOpacity,
  });

  bool isDarkMode(BuildContext context) {
    switch (themePreference) {
      case ThemePreference.light:
        return false;
      case ThemePreference.dark:
        return true;
      case ThemePreference.system:
      default:
        return MediaQuery.of(context).platformBrightness == Brightness.dark;
    }
  }

  ThemeMode getThemeMode() {
    switch (themePreference) {
      case ThemePreference.light:
        return ThemeMode.light;
      case ThemePreference.dark:
        return ThemeMode.dark;
      case ThemePreference.system:
      default:
        return ThemeMode.system;
    }
  }

  // Clone the settings with possible modifications
  AppSettings copyWith({
    ThemePreference? themePreference,
    double? fontSize,
    bool? enterToSend,
    bool? notifications,
    String? language,
    bool? useCustomBackground,
    String? customBackgroundImagePath,
    double? backgroundOpacity,
  }) {
    return AppSettings(
      themePreference: themePreference ?? this.themePreference,
      fontSize: fontSize ?? this.fontSize,
      enterToSend: enterToSend ?? this.enterToSend,
      notifications: notifications ?? this.notifications,
      language: language ?? this.language,
      useCustomBackground: useCustomBackground ?? this.useCustomBackground,
      customBackgroundImagePath:
          customBackgroundImagePath ?? this.customBackgroundImagePath,
      backgroundOpacity: backgroundOpacity ?? this.backgroundOpacity,
    );
  }
}

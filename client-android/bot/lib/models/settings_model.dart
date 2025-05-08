import 'package:flutter/material.dart';

class AppSettings {
  bool darkMode;
  double fontSize;
  bool enterToSend;
  bool notifications;
  String language;

  static const double minFontSize = 14.0;
  static const double maxFontSize = 20.0;
  static const double defaultFontSize = 16.0;

  AppSettings({
    this.darkMode = false,
    this.fontSize = defaultFontSize,
    this.enterToSend = true,
    this.notifications = true,
    this.language = 'Tiếng Việt',
  });

  ThemeMode getThemeMode() {
    return darkMode ? ThemeMode.dark : ThemeMode.light;
  }

  // Clone the settings with possible modifications
  AppSettings copyWith({
    bool? darkMode,
    double? fontSize,
    bool? enterToSend,
    bool? notifications,
    String? language,
  }) {
    return AppSettings(
      darkMode: darkMode ?? this.darkMode,
      fontSize: fontSize ?? this.fontSize,
      enterToSend: enterToSend ?? this.enterToSend,
      notifications: notifications ?? this.notifications,
      language: language ?? this.language,
    );
  }
}

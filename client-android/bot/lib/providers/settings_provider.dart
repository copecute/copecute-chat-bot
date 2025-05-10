import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:copecute/models/settings_model.dart';
import 'dart:io';

class SettingsProvider extends ChangeNotifier {
  AppSettings _settings = AppSettings();
  bool _isLoaded = false;

  AppSettings get settings => _settings;
  bool get isLoaded => _isLoaded;

  SettingsProvider() {
    loadSettings();
  }

  Future<void> loadSettings() async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Lấy theme preference
      final themeValue = prefs.getInt('themePreference') ?? 0;
      final ThemePreference themePreference =
          ThemePreference.values[themeValue];

      // Lấy các cài đặt khác
      final fontSize =
          prefs.getDouble('fontSize') ?? AppSettings.defaultFontSize;
      final enterToSend = prefs.getBool('enterToSend') ?? true;
      final notifications = prefs.getBool('notifications') ?? true;
      final language = prefs.getString('language') ?? 'Tiếng Việt';
      final useCustomBackground = prefs.getBool('useCustomBackground') ?? true;
      final customBackgroundImagePath =
          prefs.getString('customBackgroundImagePath');
      final backgroundOpacity = prefs.getDouble('backgroundOpacity') ??
          AppSettings.defaultBackgroundOpacity;

      _settings = AppSettings(
        themePreference: themePreference,
        fontSize: fontSize,
        enterToSend: enterToSend,
        notifications: notifications,
        language: language,
        useCustomBackground: useCustomBackground,
        customBackgroundImagePath: customBackgroundImagePath,
        backgroundOpacity: backgroundOpacity,
      );

      _isLoaded = true;
      notifyListeners();
    } catch (e) {
      debugPrint('Lỗi khi tải cài đặt: $e');
    }
  }

  Future<void> saveSettings() async {
    try {
      final prefs = await SharedPreferences.getInstance();

      await prefs.setInt('themePreference', _settings.themePreference.index);
      await prefs.setDouble('fontSize', _settings.fontSize);
      await prefs.setBool('enterToSend', _settings.enterToSend);
      await prefs.setBool('notifications', _settings.notifications);
      await prefs.setString('language', _settings.language);
      await prefs.setBool('useCustomBackground', _settings.useCustomBackground);

      if (_settings.customBackgroundImagePath != null) {
        await prefs.setString(
            'customBackgroundImagePath', _settings.customBackgroundImagePath!);
      } else {
        await prefs.remove('customBackgroundImagePath');
      }

      await prefs.setDouble('backgroundOpacity', _settings.backgroundOpacity);
    } catch (e) {
      debugPrint('Lỗi khi lưu cài đặt: $e');
    }
  }

  Future<void> setThemePreference(ThemePreference preference) async {
    _settings = _settings.copyWith(themePreference: preference);
    notifyListeners();
    await saveSettings();
  }

  Future<void> setFontSize(double size) async {
    _settings = _settings.copyWith(fontSize: size);
    notifyListeners();
    await saveSettings();
  }

  Future<void> toggleEnterToSend() async {
    _settings = _settings.copyWith(enterToSend: !_settings.enterToSend);
    notifyListeners();
    await saveSettings();
  }

  Future<void> toggleNotifications() async {
    _settings = _settings.copyWith(notifications: !_settings.notifications);
    notifyListeners();
    await saveSettings();
  }

  Future<void> toggleCustomBackground() async {
    _settings =
        _settings.copyWith(useCustomBackground: !_settings.useCustomBackground);
    notifyListeners();
    await saveSettings();
  }

  Future<void> setLanguage(String language) async {
    _settings = _settings.copyWith(language: language);
    notifyListeners();
    await saveSettings();
  }

  Future<void> setCustomBackgroundImage(String? imagePath) async {
    _settings = _settings.copyWith(customBackgroundImagePath: imagePath);
    notifyListeners();
    await saveSettings();
  }

  Future<void> setBackgroundOpacity(double opacity) async {
    _settings = _settings.copyWith(backgroundOpacity: opacity);
    notifyListeners();
    await saveSettings();
  }

  Future<void> resetBackground() async {
    // Kiểm tra và xóa file hình nền tùy chỉnh nếu tồn tại
    if (_settings.customBackgroundImagePath != null) {
      try {
        final file = File(_settings.customBackgroundImagePath!);
        if (await file.exists()) {
          await file.delete();
          debugPrint(
              'Đã xóa file hình nền cũ: ${_settings.customBackgroundImagePath}');
        }
      } catch (e) {
        debugPrint('Lỗi khi xóa file hình nền: $e');
      }
    }

    _settings = _settings.copyWith(
      customBackgroundImagePath: null,
      backgroundOpacity: AppSettings.defaultBackgroundOpacity,
      useCustomBackground: false, // Đặt về false để sử dụng hình nền mặc định
    );

    debugPrint(
        'Đã reset background: useCustomBackground=${_settings.useCustomBackground}, opacity=${_settings.backgroundOpacity}');
    notifyListeners();
    await saveSettings();
  }
}

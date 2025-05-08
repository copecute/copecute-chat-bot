import 'package:flutter/material.dart';
import '../models/settings_model.dart';

class SettingsProvider extends ChangeNotifier {
  AppSettings _settings = AppSettings();

  AppSettings get settings => _settings;

  void toggleDarkMode() {
    _settings = _settings.copyWith(darkMode: !_settings.darkMode);
    notifyListeners();
  }

  void setFontSize(double size) {
    _settings = _settings.copyWith(fontSize: size);
    notifyListeners();
  }

  void toggleEnterToSend() {
    _settings = _settings.copyWith(enterToSend: !_settings.enterToSend);
    notifyListeners();
  }

  void toggleNotifications() {
    _settings = _settings.copyWith(notifications: !_settings.notifications);
    notifyListeners();
  }

  void setLanguage(String language) {
    _settings = _settings.copyWith(language: language);
    notifyListeners();
  }
}

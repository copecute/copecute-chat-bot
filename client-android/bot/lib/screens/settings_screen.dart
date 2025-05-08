import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/settings_provider.dart';
import '../models/settings_model.dart';
import '../utils/app_theme.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    final settings = settingsProvider.settings;
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Cài đặt'),
        centerTitle: true,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const _SectionHeader(title: 'Tài khoản'),
          const _UserProfileTile(),
          const Divider(),
          const _SectionHeader(title: 'Giao diện'),
          _SettingsSwitchTile(
            title: 'Chế độ tối',
            subtitle: 'Bật chế độ tối giúp giảm ánh sáng màn hình',
            value: settings.darkMode,
            onChanged: (_) => settingsProvider.toggleDarkMode(),
            leadingIcon:
                isDarkMode ? Icons.dark_mode_rounded : Icons.light_mode_rounded,
          ),
          _FontSizeSettingTile(
            fontSize: settings.fontSize,
            onChanged: (value) => settingsProvider.setFontSize(value),
          ),
          _SettingsSwitchTile(
            title: 'Dùng Enter để gửi',
            subtitle: 'Cho phép dùng phím Enter để gửi tin nhắn',
            value: settings.enterToSend,
            onChanged: (_) => settingsProvider.toggleEnterToSend(),
            leadingIcon: Icons.keyboard_return_rounded,
          ),
          const Divider(),
          const _SectionHeader(title: 'Thông báo'),
          _SettingsSwitchTile(
            title: 'Thông báo',
            subtitle: 'Nhận thông báo khi có tin nhắn mới',
            value: settings.notifications,
            onChanged: (_) => settingsProvider.toggleNotifications(),
            leadingIcon: Icons.notifications_outlined,
          ),
          const Divider(),
          const _SectionHeader(title: 'Ngôn ngữ'),
          _LanguageSettingTile(
            language: settings.language,
            onTap: () => _showLanguageDialog(context, settings.language),
          ),
          const Divider(),
          const _SectionHeader(title: 'Thông tin ứng dụng'),
          _InformationTile(
            title: 'Phiên bản',
            value: '1.0.0',
            icon: Icons.info_outline_rounded,
          ),
          _NavigationTile(
            title: 'Điều khoản sử dụng',
            icon: Icons.description_outlined,
            onTap: () {
              // TODO: Navigate to terms of service
            },
          ),
          _NavigationTile(
            title: 'Chính sách bảo mật',
            icon: Icons.shield_outlined,
            onTap: () {
              // TODO: Navigate to privacy policy
            },
          ),
          const SizedBox(height: 24),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0),
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.errorColor,
                foregroundColor: Colors.white,
              ),
              onPressed: () {
                // TODO: Implement logout
              },
              icon: const Icon(Icons.logout_rounded),
              label: const Text('Đăng xuất'),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  void _showLanguageDialog(BuildContext context, String currentLanguage) {
    final settingsProvider =
        Provider.of<SettingsProvider>(context, listen: false);
    final languages = ['Tiếng Việt', 'English', 'Chinese', 'Japanese'];

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Chọn ngôn ngữ'),
          content: SizedBox(
            width: double.maxFinite,
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: languages.length,
              itemBuilder: (context, index) {
                return RadioListTile<String>(
                  title: Text(languages[index]),
                  value: languages[index],
                  groupValue: currentLanguage,
                  onChanged: (value) {
                    settingsProvider.setLanguage(value!);
                    Navigator.pop(context);
                  },
                );
              },
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Hủy'),
            ),
          ],
        );
      },
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;

  const _SectionHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: const EdgeInsets.only(top: 16, bottom: 8, left: 8),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: isDarkMode ? AppTheme.darkPrimaryColor : AppTheme.primaryColor,
        ),
      ),
    );
  }
}

class _UserProfileTile extends StatelessWidget {
  const _UserProfileTile();

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      margin: const EdgeInsets.symmetric(vertical: 8),
      child: Padding(
        padding: const EdgeInsets.all(8.0),
        child: ListTile(
          leading: CircleAvatar(
            backgroundColor: AppTheme.primaryColor,
            radius: 24,
            child:
                const Icon(Icons.person_rounded, color: Colors.white, size: 24),
          ),
          title: const Text(
            'Người dùng',
            style: TextStyle(fontWeight: FontWeight.bold),
          ),
          subtitle: const Text('user@example.com'),
          trailing: OutlinedButton(
            onPressed: () {
              // TODO: Navigate to profile edit screen
            },
            child: const Text('Chỉnh sửa'),
          ),
        ),
      ),
    );
  }
}

class _SettingsSwitchTile extends StatelessWidget {
  final String title;
  final String subtitle;
  final bool value;
  final Function(bool) onChanged;
  final IconData leadingIcon;

  const _SettingsSwitchTile({
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
    required this.leadingIcon,
  });

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.symmetric(vertical: 8),
      child: Padding(
        padding: const EdgeInsets.all(8.0),
        child: SwitchListTile(
          title: Text(
            title,
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
          subtitle: Text(subtitle),
          value: value,
          onChanged: onChanged,
          secondary: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: isDarkMode
                  ? AppTheme.darkPrimaryColor.withOpacity(0.1)
                  : AppTheme.primaryColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              leadingIcon,
              color: isDarkMode
                  ? AppTheme.darkPrimaryColor
                  : AppTheme.primaryColor,
            ),
          ),
        ),
      ),
    );
  }
}

class _FontSizeSettingTile extends StatelessWidget {
  final double fontSize;
  final Function(double) onChanged;

  const _FontSizeSettingTile({
    required this.fontSize,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.symmetric(vertical: 8),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: isDarkMode
                        ? AppTheme.darkPrimaryColor.withOpacity(0.1)
                        : AppTheme.primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    Icons.format_size_rounded,
                    color: isDarkMode
                        ? AppTheme.darkPrimaryColor
                        : AppTheme.primaryColor,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Cỡ chữ',
                        style: TextStyle(fontWeight: FontWeight.w500),
                      ),
                      Text('${fontSize.toInt()}px'),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Nhỏ', style: TextStyle(fontSize: 13)),
                Text(
                  'Vừa',
                  style: TextStyle(
                    fontSize: 16,
                    color: isDarkMode
                        ? AppTheme.darkPrimaryColor
                        : AppTheme.primaryColor,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const Text('Lớn', style: TextStyle(fontSize: 19)),
              ],
            ),
            Slider(
              value: fontSize,
              min: AppSettings.minFontSize,
              max: AppSettings.maxFontSize,
              divisions: 3,
              label: '${fontSize.toInt()}px',
              onChanged: onChanged,
            ),
          ],
        ),
      ),
    );
  }
}

class _LanguageSettingTile extends StatelessWidget {
  final String language;
  final VoidCallback onTap;

  const _LanguageSettingTile({
    required this.language,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.symmetric(vertical: 8),
      child: Padding(
        padding: const EdgeInsets.all(8.0),
        child: ListTile(
          title: const Text(
            'Ngôn ngữ hiển thị',
            style: TextStyle(fontWeight: FontWeight.w500),
          ),
          subtitle: Text(language),
          trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16),
          leading: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: isDarkMode
                  ? AppTheme.darkPrimaryColor.withOpacity(0.1)
                  : AppTheme.primaryColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              Icons.language_rounded,
              color: isDarkMode
                  ? AppTheme.darkPrimaryColor
                  : AppTheme.primaryColor,
            ),
          ),
          onTap: onTap,
        ),
      ),
    );
  }
}

class _InformationTile extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;

  const _InformationTile({
    required this.title,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.symmetric(vertical: 8),
      child: Padding(
        padding: const EdgeInsets.all(8.0),
        child: ListTile(
          title: Text(
            title,
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
          subtitle: Text(value),
          leading: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: isDarkMode
                  ? AppTheme.darkPrimaryColor.withOpacity(0.1)
                  : AppTheme.primaryColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              icon,
              color: isDarkMode
                  ? AppTheme.darkPrimaryColor
                  : AppTheme.primaryColor,
            ),
          ),
        ),
      ),
    );
  }
}

class _NavigationTile extends StatelessWidget {
  final String title;
  final IconData icon;
  final VoidCallback onTap;

  const _NavigationTile({
    required this.title,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.symmetric(vertical: 8),
      child: Padding(
        padding: const EdgeInsets.all(8.0),
        child: ListTile(
          title: Text(
            title,
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
          trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16),
          leading: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: isDarkMode
                  ? AppTheme.darkPrimaryColor.withOpacity(0.1)
                  : AppTheme.primaryColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              icon,
              color: isDarkMode
                  ? AppTheme.darkPrimaryColor
                  : AppTheme.primaryColor,
            ),
          ),
          onTap: onTap,
        ),
      ),
    );
  }
}

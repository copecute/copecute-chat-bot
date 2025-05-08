import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/settings_provider.dart';
import '../models/settings_model.dart';
import '../utils/app_theme.dart';
import '../services/image_service.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({Key? key}) : super(key: key);

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  // Màu sắc chính của ứng dụng
  static const Color messengerBlue = Color(0xFF0084FF);

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    final settings = settingsProvider.settings;
    final isDarkMode = settings.isDarkMode(context);

    final backgroundColor = isDarkMode ? const Color(0xFF121212) : Colors.white;
    final textColor = isDarkMode ? Colors.white : Colors.black87;
    final subtitleColor =
        isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;
    final dividerColor =
        isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200;
    final settingItemColor =
        isDarkMode ? const Color(0xFF1E1E1E) : Colors.grey.shade50;

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        backgroundColor: backgroundColor,
        title: const Text('Cài đặt'),
        elevation: 1,
      ),
      body: ListView(
        children: [
          // Chọn chế độ giao diện
          _buildSectionHeader('Giao diện', context),
          _buildCardSection(
            children: [
              _buildThemeOption(
                  ThemePreference.system,
                  'Theo hệ thống',
                  Icons.brightness_auto,
                  settings.themePreference == ThemePreference.system,
                  context),
              const Divider(),
              _buildThemeOption(
                  ThemePreference.light,
                  'Sáng',
                  Icons.brightness_low,
                  settings.themePreference == ThemePreference.light,
                  context),
              const Divider(),
              _buildThemeOption(ThemePreference.dark, 'Tối', Icons.brightness_3,
                  settings.themePreference == ThemePreference.dark, context),
            ],
          ),

          // Cài đặt nền chat
          _buildSectionHeader('Nền chat', context),
          _buildCardSection(
            children: [
              SwitchListTile(
                title: Text(
                  'Sử dụng nền tùy chỉnh',
                  style: TextStyle(color: textColor),
                ),
                subtitle: Text(
                  'Hiển thị hình nền cho đoạn chat',
                  style: TextStyle(color: subtitleColor, fontSize: 13),
                ),
                value: settings.useCustomBackground,
                activeColor: messengerBlue,
                onChanged: (value) async {
                  await settingsProvider.toggleCustomBackground();
                },
              ),

              // Hiện tùy chọn nếu bật nền tùy chỉnh
              if (settings.useCustomBackground) ...[
                const Divider(),

                // Upload hình nền
                ListTile(
                  title: Text(
                    'Tải lên hình nền tùy chỉnh',
                    style: TextStyle(color: textColor),
                  ),
                  subtitle: Text(
                    'Chọn hình ảnh từ thư viện của bạn',
                    style: TextStyle(color: subtitleColor, fontSize: 13),
                  ),
                  trailing: Icon(
                    Icons.upload_file,
                    color: messengerBlue,
                  ),
                  onTap: () async {
                    // Xử lý tải lên hình ảnh
                    final String? newImagePath = await ImageService.pickImage();
                    if (newImagePath != null) {
                      // Xóa hình ảnh cũ nếu có
                      if (settings.customBackgroundImagePath != null) {
                        await ImageService.deleteImageIfExists(
                            settings.customBackgroundImagePath);
                      }

                      // Lưu đường dẫn hình ảnh mới
                      await settingsProvider
                          .setCustomBackgroundImage(newImagePath);
                    }
                  },
                ),

                // Điều chỉnh độ trong suốt
                Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Độ trong suốt: ${(settings.backgroundOpacity * 100).toInt()}%',
                        style: TextStyle(
                          color: textColor,
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      Slider(
                        value: settings.backgroundOpacity,
                        min: 0.05,
                        max: 1.0,
                        divisions: 19,
                        activeColor: messengerBlue,
                        onChanged: (value) async {
                          await settingsProvider.setBackgroundOpacity(value);
                        },
                      ),
                    ],
                  ),
                ),

                // Nút đặt lại mặc định
                Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: ElevatedButton.icon(
                    onPressed: () async {
                      // Hiển thị dialog xác nhận
                      final bool confirm = await showDialog(
                            context: context,
                            builder: (context) => AlertDialog(
                              title: Text('Đặt lại hình nền',
                                  style: TextStyle(color: textColor)),
                              content: Text(
                                'Bạn có chắc chắn muốn đặt lại hình nền mặc định không? Hành động này sẽ xóa hình nền tùy chỉnh và sử dụng lại hình nền mặc định (sáng hoặc tối theo chế độ giao diện) với độ trong suốt 50%.',
                                style: TextStyle(color: textColor),
                              ),
                              actions: [
                                TextButton(
                                  onPressed: () =>
                                      Navigator.pop(context, false),
                                  child: const Text('Hủy'),
                                ),
                                ElevatedButton(
                                  onPressed: () => Navigator.pop(context, true),
                                  child: const Text('Đặt lại'),
                                ),
                              ],
                            ),
                          ) ??
                          false;

                      if (confirm) {
                        // Xóa hình ảnh tùy chỉnh nếu có
                        if (settings.customBackgroundImagePath != null) {
                          await ImageService.deleteImageIfExists(
                              settings.customBackgroundImagePath);
                        }

                        // Đặt lại cài đặt hình nền
                        await settingsProvider.resetBackground();

                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Đã đặt lại hình nền mặc định'),
                            backgroundColor: Colors.green,
                            duration: Duration(seconds: 2),
                          ),
                        );
                      }
                    },
                    icon: const Icon(Icons.refresh),
                    label: const Text('Đặt lại hình nền mặc định'),
                    style: ElevatedButton.styleFrom(
                      foregroundColor: Colors.white,
                      backgroundColor: messengerBlue,
                      padding: const EdgeInsets.symmetric(
                          vertical: 12, horizontal: 16),
                    ),
                  ),
                ),

                // Xem trước hình nền
                Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Xem trước',
                        style: TextStyle(
                          color: subtitleColor,
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        height: 120,
                        width: double.infinity,
                        decoration: BoxDecoration(
                          image: DecorationImage(
                            image: () {
                              // Debug
                              debugPrint(
                                  'Preview - useCustomBackground: ${settings.useCustomBackground}');
                              debugPrint(
                                  'Preview - customBackgroundImagePath: ${settings.customBackgroundImagePath}');

                              if (settings.customBackgroundImagePath != null) {
                                return FileImage(File(
                                        settings.customBackgroundImagePath!))
                                    as ImageProvider;
                              } else {
                                String assetPath = isDarkMode
                                    ? 'assets/images/bg-chat-dark.png'
                                    : 'assets/images/bg-chat-light.png';
                                debugPrint(
                                    'Preview - Using asset image: $assetPath');
                                return AssetImage(assetPath);
                              }
                            }(),
                            repeat: settings.customBackgroundImagePath != null
                                ? ImageRepeat.noRepeat
                                : ImageRepeat.repeat,
                            opacity: settings.backgroundOpacity,
                            fit: settings.customBackgroundImagePath != null
                                ? BoxFit.cover
                                : null,
                          ),
                          color: isDarkMode
                              ? const Color(0xFF121212)
                              : const Color(0xFFF2F2F2),
                        ),
                        child: Stack(
                          children: [
                            // Thêm một số tin nhắn mẫu để xem trước
                            Positioned(
                              left: 10,
                              top: 10,
                              child: Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: isDarkMode
                                      ? Colors.grey.shade800
                                      : Colors.white,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  'Xin chào!',
                                  style: TextStyle(
                                    color: textColor,
                                    fontSize: 12,
                                  ),
                                ),
                              ),
                            ),
                            Positioned(
                              right: 10,
                              bottom: 10,
                              child: Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: messengerBlue,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: const Text(
                                  'Chào bạn!',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 12,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),

          // Cài đặt tin nhắn
          _buildSectionHeader('Tin nhắn', context),
          _buildCardSection(
            children: [
              ListTile(
                title: Text(
                  'Cỡ chữ',
                  style: TextStyle(color: textColor),
                ),
                subtitle: Text(
                  'Điều chỉnh kích thước chữ trong tin nhắn',
                  style: TextStyle(color: subtitleColor, fontSize: 13),
                ),
                trailing: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'A',
                      style: TextStyle(
                        fontSize: 14,
                        color: textColor,
                      ),
                    ),
                    SizedBox(
                      width: 150,
                      child: Slider(
                        value: settings.fontSize,
                        min: AppSettings.minFontSize,
                        max: AppSettings.maxFontSize,
                        divisions: 6,
                        activeColor: messengerBlue,
                        onChanged: (value) async {
                          await settingsProvider.setFontSize(value);
                        },
                      ),
                    ),
                    Text(
                      'A',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: textColor,
                      ),
                    ),
                  ],
                ),
              ),
              const Divider(),
              SwitchListTile(
                title: Text(
                  'Phím Enter để gửi',
                  style: TextStyle(color: textColor),
                ),
                subtitle: Text(
                  'Sử dụng phím Enter để gửi tin nhắn thay vì xuống dòng',
                  style: TextStyle(color: subtitleColor, fontSize: 13),
                ),
                value: settings.enterToSend,
                activeColor: messengerBlue,
                onChanged: (value) async {
                  await settingsProvider.toggleEnterToSend();
                },
              ),
            ],
          ),

          // Thông báo
          _buildSectionHeader('Thông báo', context),
          _buildCardSection(
            children: [
              SwitchListTile(
                title: Text(
                  'Thông báo',
                  style: TextStyle(color: textColor),
                ),
                subtitle: Text(
                  'Nhận thông báo khi có tin nhắn mới',
                  style: TextStyle(color: subtitleColor, fontSize: 13),
                ),
                value: settings.notifications,
                activeColor: messengerBlue,
                onChanged: (value) async {
                  await settingsProvider.toggleNotifications();
                },
              ),
            ],
          ),

          // Ngôn ngữ
          _buildSectionHeader('Ngôn ngữ', context),
          _buildCardSection(
            children: [
              ListTile(
                title: Text(
                  'Ngôn ngữ ứng dụng',
                  style: TextStyle(color: textColor),
                ),
                subtitle: Text(
                  'Tiếng Việt',
                  style: TextStyle(color: subtitleColor, fontSize: 13),
                ),
                trailing: Icon(
                  Icons.chevron_right,
                  color: subtitleColor,
                ),
                onTap: () {
                  // TODO: Thực hiện thay đổi ngôn ngữ
                },
              ),
            ],
          ),

          // Thông tin ứng dụng
          _buildSectionHeader('Thông tin', context),
          _buildCardSection(
            children: [
              ListTile(
                title: Text(
                  'Phiên bản',
                  style: TextStyle(color: textColor),
                ),
                subtitle: Text(
                  '1.0.0',
                  style: TextStyle(color: subtitleColor, fontSize: 13),
                ),
              ),
              const Divider(),
              ListTile(
                title: Text(
                  'Điều khoản sử dụng',
                  style: TextStyle(color: textColor),
                ),
                trailing: Icon(
                  Icons.chevron_right,
                  color: subtitleColor,
                ),
                onTap: () {
                  // TODO: Mở điều khoản sử dụng
                },
              ),
              const Divider(),
              ListTile(
                title: Text(
                  'Chính sách bảo mật',
                  style: TextStyle(color: textColor),
                ),
                trailing: Icon(
                  Icons.chevron_right,
                  color: subtitleColor,
                ),
                onTap: () {
                  // TODO: Mở chính sách bảo mật
                },
              ),
            ],
          ),

          const SizedBox(height: 40),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title, BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: isDarkMode ? messengerBlue : const Color(0xFF0084FF),
        ),
      ),
    );
  }

  Widget _buildCardSection({required List<Widget> children}) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E1E) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 5,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Column(
        children: children,
      ),
    );
  }

  Widget _buildThemeOption(ThemePreference preference, String title,
      IconData icon, bool isSelected, BuildContext context) {
    final settingsProvider =
        Provider.of<SettingsProvider>(context, listen: false);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final textColor = isDarkMode ? Colors.white : Colors.black87;

    return ListTile(
      leading: Icon(
        icon,
        color: isSelected
            ? messengerBlue
            : (isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600),
      ),
      title: Text(
        title,
        style: TextStyle(
          color: textColor,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
        ),
      ),
      trailing: isSelected
          ? const Icon(
              Icons.check_circle,
              color: messengerBlue,
            )
          : null,
      onTap: () async {
        await settingsProvider.setThemePreference(preference);
      },
    );
  }
}

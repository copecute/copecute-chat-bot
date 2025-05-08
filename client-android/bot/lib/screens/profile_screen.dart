import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import '../models/user_model.dart';
import '../services/auth_service.dart';
import '../services/image_service.dart';
import '../utils/app_theme.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({Key? key}) : super(key: key);

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _isEditing = false;
  bool _isLoading = false;
  String? _localAvatarPath;
  final _formKey = GlobalKey<FormState>();
  final _displayNameController = TextEditingController();
  final _bioController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _initUserData();
    });
  }

  void _initUserData() {
    final authService = Provider.of<AuthService>(context, listen: false);
    final user = authService.currentUser;
    if (user != null) {
      _displayNameController.text = user.fullName;
      _bioController.text = user.bio ?? '';
    }
  }

  @override
  void dispose() {
    _displayNameController.dispose();
    _bioController.dispose();
    super.dispose();
  }

  Future<void> _selectAvatar() async {
    try {
      String? imagePath = await ImageService.pickImage();
      if (imagePath != null) {
        setState(() {
          _localAvatarPath = imagePath;
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Lỗi khi chọn ảnh: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
    });

    final authService = Provider.of<AuthService>(context, listen: false);

    try {
      // Chuẩn bị dữ liệu cần cập nhật
      final Map<String, dynamic> updateData = {
        'fullName': _displayNameController.text.trim(),
        'bio': _bioController.text.trim(),
      };

      // Nếu có ảnh mới, thêm vào dữ liệu cập nhật
      if (_localAvatarPath != null) {
        // Trong môi trường thực tế, bạn cần tải ảnh lên server và nhận URL trả về
        // updateData['avatar'] = uploadedAvatarUrl;
      }

      // Gọi API cập nhật thông tin người dùng
      // Giả sử chúng ta có phương thức updateUserProfile trong AuthService
      bool success = await authService.updateUserProfile(updateData);

      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Cập nhật thông tin thành công'),
            backgroundColor: Colors.green,
          ),
        );
        setState(() {
          _isEditing = false;
        });
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Không thể cập nhật thông tin'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Lỗi: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);
    final user = authService.currentUser;
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    // Màu sắc
    final primaryColor = AppTheme.messengerBlue;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : Colors.white;
    final cardColor = isDarkMode ? const Color(0xFF1E1E1E) : Colors.white;
    final textColor = isDarkMode ? Colors.white : Colors.black87;
    final subtitleColor =
        isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;

    if (user == null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Trang cá nhân'),
        ),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Text('Vui lòng đăng nhập để xem thông tin'),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: () {
                  Navigator.of(context).pushReplacement(
                    MaterialPageRoute(
                        builder: (context) => const LoginScreen()),
                  );
                },
                child: const Text('Đăng nhập'),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        backgroundColor: backgroundColor,
        title: Text(
          _isEditing ? 'Chỉnh sửa thông tin' : 'Trang cá nhân',
          style: TextStyle(color: textColor),
        ),
        actions: [
          if (!_isEditing)
            IconButton(
              icon: const Icon(Icons.edit),
              tooltip: 'Chỉnh sửa',
              onPressed: () {
                setState(() {
                  _isEditing = true;
                });
              },
            )
          else
            IconButton(
              icon: const Icon(Icons.close),
              tooltip: 'Hủy',
              onPressed: () {
                setState(() {
                  _isEditing = false;
                  _localAvatarPath = null;
                  _initUserData();
                });
              },
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Phần hồ sơ
                    Center(
                      child: Column(
                        children: [
                          // Ảnh đại diện
                          Stack(
                            children: [
                              Container(
                                width: 120,
                                height: 120,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: Colors.grey.shade200,
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.1),
                                      blurRadius: 10,
                                      spreadRadius: 1,
                                    ),
                                  ],
                                ),
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(60),
                                  child: _localAvatarPath != null
                                      ? Image.file(
                                          File(_localAvatarPath!),
                                          fit: BoxFit.cover,
                                        )
                                      : (user.avatar != null &&
                                              user.avatar!.isNotEmpty)
                                          ? Image.network(
                                              user.avatar!,
                                              fit: BoxFit.cover,
                                              errorBuilder: (context, error,
                                                      stackTrace) =>
                                                  Icon(
                                                Icons.person,
                                                size: 60,
                                                color: Colors.grey.shade400,
                                              ),
                                            )
                                          : Icon(
                                              Icons.person,
                                              size: 60,
                                              color: Colors.grey.shade400,
                                            ),
                                ),
                              ),
                              if (_isEditing)
                                Positioned(
                                  bottom: 0,
                                  right: 0,
                                  child: Container(
                                    decoration: BoxDecoration(
                                      color: primaryColor,
                                      shape: BoxShape.circle,
                                      border: Border.all(
                                        color: backgroundColor,
                                        width: 3,
                                      ),
                                    ),
                                    child: IconButton(
                                      icon: const Icon(
                                        Icons.camera_alt,
                                        color: Colors.white,
                                        size: 20,
                                      ),
                                      onPressed: _selectAvatar,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          // Tên hiển thị và thông tin cơ bản
                          if (!_isEditing) ...[
                            Text(
                              user.fullName,
                              style: TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: textColor,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              user.email,
                              style: TextStyle(
                                color: subtitleColor,
                                fontSize: 16,
                              ),
                            ),
                            if (user.bio != null && user.bio!.isNotEmpty) ...[
                              const SizedBox(height: 16),
                              Container(
                                width: double.infinity,
                                padding: const EdgeInsets.all(16),
                                decoration: BoxDecoration(
                                  color: cardColor,
                                  borderRadius: BorderRadius.circular(12),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.05),
                                      blurRadius: 10,
                                      spreadRadius: 1,
                                    ),
                                  ],
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Giới thiệu',
                                      style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        color: textColor,
                                        fontSize: 16,
                                      ),
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      user.bio!,
                                      style: TextStyle(
                                        color: textColor,
                                        fontSize: 16,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ] else ...[
                            // Form chỉnh sửa
                            Form(
                              key: _formKey,
                              child: Column(
                                children: [
                                  TextFormField(
                                    controller: _displayNameController,
                                    decoration: InputDecoration(
                                      labelText: 'Tên hiển thị',
                                      border: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                    validator: (value) {
                                      if (value == null || value.isEmpty) {
                                        return 'Vui lòng nhập tên hiển thị';
                                      }
                                      return null;
                                    },
                                  ),
                                  const SizedBox(height: 16),
                                  TextFormField(
                                    controller: _bioController,
                                    decoration: InputDecoration(
                                      labelText: 'Giới thiệu bản thân',
                                      border: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                    maxLines: 3,
                                  ),
                                  const SizedBox(height: 24),
                                  SizedBox(
                                    width: double.infinity,
                                    child: ElevatedButton(
                                      onPressed: _saveProfile,
                                      style: ElevatedButton.styleFrom(
                                        foregroundColor: Colors.white,
                                        backgroundColor: primaryColor,
                                        padding: const EdgeInsets.symmetric(
                                            vertical: 16),
                                        shape: RoundedRectangleBorder(
                                          borderRadius:
                                              BorderRadius.circular(12),
                                        ),
                                      ),
                                      child: const Text(
                                        'Lưu thông tin',
                                        style: TextStyle(fontSize: 16),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),

                    const SizedBox(height: 32),

                    // Thông tin tài khoản
                    if (!_isEditing) ...[
                      _buildSectionHeader('Thông tin tài khoản', context),
                      _buildCardSection(
                        children: [
                          _buildListTile(
                            icon: Icons.person,
                            title: 'Username',
                            subtitle: user.username,
                            context: context,
                          ),
                          const Divider(),
                          _buildListTile(
                            icon: Icons.email,
                            title: 'Email',
                            subtitle: user.email,
                            context: context,
                          ),
                          const Divider(),
                          _buildListTile(
                            icon: Icons.message,
                            title: 'Quota chat',
                            subtitle: '${user.quota} tin nhắn',
                            context: context,
                          ),
                        ],
                        context: context,
                      ),
                      const SizedBox(height: 16),
                      _buildSectionHeader('Bảo mật', context),
                      _buildCardSection(
                        children: [
                          _buildListTile(
                            icon: Icons.lock,
                            title: 'Đổi mật khẩu',
                            subtitle: 'Cập nhật mật khẩu của bạn',
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                              // Mở trang đổi mật khẩu
                            },
                            context: context,
                          ),
                          const Divider(),
                          _buildListTile(
                            icon: Icons.security,
                            title: 'Xác thực hai yếu tố',
                            subtitle: 'Chưa được kích hoạt',
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                              // Mở trang xác thực hai yếu tố
                            },
                            context: context,
                          ),
                        ],
                        context: context,
                      ),
                      const SizedBox(height: 16),
                      _buildSectionHeader('Cài đặt khác', context),
                      _buildCardSection(
                        children: [
                          _buildListTile(
                            icon: Icons.notifications,
                            title: 'Thông báo',
                            subtitle: 'Quản lý thông báo',
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                              // Mở trang cài đặt thông báo
                            },
                            context: context,
                          ),
                          const Divider(),
                          _buildListTile(
                            icon: Icons.language,
                            title: 'Ngôn ngữ',
                            subtitle: 'Tiếng Việt',
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                              // Mở trang chọn ngôn ngữ
                            },
                            context: context,
                          ),
                          const Divider(),
                          _buildListTile(
                            icon: Icons.logout,
                            title: 'Đăng xuất',
                            subtitle: 'Đăng xuất khỏi tài khoản',
                            onTap: () async {
                              // Hiển thị dialog xác nhận đăng xuất
                              bool confirm = await showDialog(
                                    context: context,
                                    builder: (context) => AlertDialog(
                                      title: const Text('Đăng xuất'),
                                      content: const Text(
                                          'Bạn có chắc chắn muốn đăng xuất?'),
                                      actions: [
                                        TextButton(
                                          onPressed: () =>
                                              Navigator.of(context).pop(false),
                                          child: const Text('Hủy'),
                                        ),
                                        ElevatedButton(
                                          onPressed: () =>
                                              Navigator.of(context).pop(true),
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: Colors.red,
                                          ),
                                          child: const Text('Đăng xuất'),
                                        ),
                                      ],
                                    ),
                                  ) ??
                                  false;

                              if (confirm && context.mounted) {
                                await authService.signOut();
                                if (context.mounted) {
                                  Navigator.of(context).pushReplacement(
                                    MaterialPageRoute(
                                      builder: (context) => const LoginScreen(),
                                    ),
                                  );
                                }
                              }
                            },
                            textColor: Colors.red,
                            context: context,
                          ),
                        ],
                        context: context,
                      ),
                    ],

                    const SizedBox(height: 40),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildSectionHeader(String title, BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = AppTheme.messengerBlue;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: isDarkMode ? primaryColor : primaryColor,
        ),
      ),
    );
  }

  Widget _buildCardSection({
    required List<Widget> children,
    required BuildContext context,
  }) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final cardColor = isDarkMode ? const Color(0xFF1E1E1E) : Colors.white;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.symmetric(vertical: 8),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            spreadRadius: 1,
          ),
        ],
      ),
      child: Column(
        children: children,
      ),
    );
  }

  Widget _buildListTile({
    required IconData icon,
    required String title,
    required String subtitle,
    Widget? trailing,
    VoidCallback? onTap,
    Color? textColor,
    required BuildContext context,
  }) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final titleColor =
        textColor ?? (isDarkMode ? Colors.white : Colors.black87);
    final subtitleColor =
        isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;

    return ListTile(
      leading: Icon(
        icon,
        color: titleColor,
      ),
      title: Text(
        title,
        style: TextStyle(
          color: titleColor,
          fontWeight: FontWeight.w500,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: TextStyle(
          color: subtitleColor,
          fontSize: 13,
        ),
      ),
      trailing: trailing,
      onTap: onTap,
    );
  }
}

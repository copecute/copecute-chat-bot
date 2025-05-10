import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:copecute/models/user_model.dart';
import 'package:copecute/utils/auth_service.dart';
import 'package:copecute/services/profile_service.dart';
import 'package:copecute/services/imgur_service.dart';
import 'package:copecute/utils/app_theme.dart';
import 'package:copecute/screens/login_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({Key? key}) : super(key: key);

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _isLoading = false;
  String? _localAvatarPath;
  final _profileFormKey = GlobalKey<FormState>();
  final _passwordFormKey = GlobalKey<FormState>();

  // Thông tin người dùng
  final _fullNameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  String _gender = 'other';
  DateTime? _birthday;

  // Đổi mật khẩu
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  @override
  void initState() {
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadUserProfile();
    });
  }

  Future<void> _loadUserProfile() async {
    setState(() => _isLoading = true);

    final authService = Provider.of<AuthService>(context, listen: false);
    final profileService = Provider.of<ProfileService>(context, listen: false);

    // Kiểm tra xem người dùng đã đăng nhập chưa
    if (!authService.isLoggedIn) {
      // Nếu chưa đăng nhập, chuyển về màn hình đăng nhập
      _navigateToLogin();
      return;
    }

    final token = authService.currentUser?.token;
    if (token != null) {
      await profileService.getProfile(token, context);

      // Kiểm tra xem người dùng có còn đăng nhập sau khi gọi API không
      if (!authService.isLoggedIn) {
        // Nếu đã bị đăng xuất (do lỗi 401), chuyển về màn hình đăng nhập
        _navigateToLogin();
        return;
      }

      _initUserData();
    }

    setState(() => _isLoading = false);
  }

  void _initUserData() {
    final profileService = Provider.of<ProfileService>(context, listen: false);
    final user = profileService.profileData ??
        Provider.of<AuthService>(context, listen: false).currentUser;

    if (user != null) {
      _fullNameController.text = user.fullName;
      _phoneController.text = user.phone ?? '';
      _addressController.text = user.address ?? '';
      _gender = user.gender;

      if (user.birthday != null && user.birthday!.isNotEmpty) {
        try {
          _birthday = DateTime.parse(user.birthday!);
        } catch (e) {
          debugPrint('Error parsing birthday: $e');
        }
      }
    }
  }

  @override
  void dispose() {
    _fullNameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  // Chọn và upload avatar
  Future<void> _selectAndUploadAvatar() async {
    try {
      final File? imageFile = await ImgurService.showImageSourceDialog(context);
      if (imageFile == null) return;

      setState(() {
        _localAvatarPath = imageFile.path;
        _isLoading = true;
      });

      // Upload ảnh lên Imgur
      final String? imageUrl = await ImgurService.uploadImage(imageFile);

      if (imageUrl != null) {
        // Cập nhật avatar
        final authService = Provider.of<AuthService>(context, listen: false);
        final profileService =
            Provider.of<ProfileService>(context, listen: false);

        final success = await profileService.updateProfile(
            authService.currentUser!.token, {'avatar': imageUrl}, context);

        if (success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Cập nhật ảnh đại diện thành công'),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                  'Lỗi: ${profileService.errorMessage ?? 'Không thể cập nhật ảnh đại diện'}'),
              backgroundColor: Colors.red,
            ),
          );
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Không thể tải ảnh lên, vui lòng thử lại sau'),
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
      setState(() => _isLoading = false);
    }
  }

  // Lưu thông tin hồ sơ
  Future<void> _saveProfile() async {
    if (!_profileFormKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final authService = Provider.of<AuthService>(context, listen: false);
    final profileService = Provider.of<ProfileService>(context, listen: false);

    // Kiểm tra xem người dùng đã đăng nhập chưa
    if (!authService.isLoggedIn) {
      // Nếu chưa đăng nhập, chuyển về màn hình đăng nhập
      _navigateToLogin();
      return;
    }

    try {
      // Chuẩn bị dữ liệu cập nhật
      final Map<String, dynamic> updateData = {
        'full_name': _fullNameController.text.trim(),
        'phone': _phoneController.text.trim(),
        'gender': _gender,
        'address': _addressController.text.trim(),
      };

      // Thêm ngày sinh nếu có
      if (_birthday != null) {
        updateData['birthday'] =
            DateFormat('yyyy-MM-dd', 'vi_VN').format(_birthday!);
      }

      // Gọi API cập nhật thông tin
      bool success = await profileService.updateProfile(
          authService.currentUser!.token, updateData, context);

      // Kiểm tra xem người dùng có còn đăng nhập sau khi gọi API không
      if (!authService.isLoggedIn) {
        // Nếu đã bị đăng xuất (do lỗi 401), chuyển về màn hình đăng nhập
        _navigateToLogin();
        return;
      }

      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Cập nhật thông tin thành công'),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
                'Lỗi: ${profileService.errorMessage ?? 'Không thể cập nhật thông tin'}'),
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
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  // Đổi mật khẩu
  Future<void> _changePassword() async {
    setState(() => _isLoading = true);

    final authService = Provider.of<AuthService>(context, listen: false);
    final profileService = Provider.of<ProfileService>(context, listen: false);

    // Kiểm tra xem người dùng đã đăng nhập chưa
    if (!authService.isLoggedIn) {
      // Nếu chưa đăng nhập, chuyển về màn hình đăng nhập
      _navigateToLogin();
      return;
    }

    try {
      // Gọi API đổi mật khẩu
      bool success = await profileService.changePassword(
          authService.currentUser!.token,
          _currentPasswordController.text,
          _newPasswordController.text,
          _confirmPasswordController.text,
          context);

      // Kiểm tra xem người dùng có còn đăng nhập sau khi gọi API không
      if (!authService.isLoggedIn) {
        // Nếu đã bị đăng xuất (do lỗi 401), chuyển về màn hình đăng nhập
        _navigateToLogin();
        return;
      }

      if (success) {
        // Xóa các trường nhập liệu
        _currentPasswordController.clear();
        _newPasswordController.clear();
        _confirmPasswordController.clear();

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Đổi mật khẩu thành công'),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
                'Lỗi: ${profileService.errorMessage ?? 'Không thể đổi mật khẩu'}'),
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
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  // Thêm phương thức để chuyển về màn hình đăng nhập
  void _navigateToLogin() {
    if (mounted) {
      // Sử dụng Navigator.pushAndRemoveUntil để xóa hết stack điều hướng
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (context) => const LoginScreen()),
        (route) => false,
      );
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // Kiểm tra trạng thái đăng nhập mỗi khi widget được xây dựng lại
    final authService = Provider.of<AuthService>(context, listen: false);
    if (!authService.isLoggedIn) {
      // Nếu chưa đăng nhập, sử dụng Future.microtask để tránh lỗi setState during build
      Future.microtask(() => _navigateToLogin());
    }
  }

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);
    final profileService = Provider.of<ProfileService>(context);

    // Nếu user đã bị đăng xuất (do lỗi 401 hoặc lý do khác), chuyển về màn hình đăng nhập
    if (!authService.isLoggedIn) {
      // Sử dụng Future.microtask để tránh gọi setState trong build
      Future.microtask(() => _navigateToLogin());

      // Hiển thị màn hình loading trong khi chờ chuyển hướng
      return Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    final user = profileService.profileData ?? authService.currentUser;
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    // Gradient chủ đạo
    final primaryGradient = LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: isDarkMode
          ? [const Color(0xFF1A237E), const Color(0xFF0D47A1)]
          : [const Color(0xFF42A5F5), const Color(0xFF2196F3)],
    );

    // Màu sắc chủ đạo
    final primaryColor =
        isDarkMode ? const Color(0xFF2979FF) : const Color(0xFF2196F3);
    final secondaryColor =
        isDarkMode ? const Color(0xFF64B5F6) : const Color(0xFF90CAF9);
    final backgroundColor =
        isDarkMode ? const Color(0xFF0A0E21) : const Color(0xFFF5F9FF);
    final cardColor = isDarkMode ? const Color(0xFF1A1D33) : Colors.white;
    final textColor = isDarkMode ? Colors.white : Colors.black87;
    final subtitleColor =
        isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;

    // Sắc thái sáng tối
    final lightShade = isDarkMode
        ? Colors.white.withOpacity(0.05)
        : Colors.black.withOpacity(0.05);
    final shadowColor = isDarkMode ? Colors.black54 : Colors.black12;

    // Hiển thị màn hình đăng nhập nếu chưa đăng nhập
    if (user == null) {
      return Scaffold(
        backgroundColor: backgroundColor,
        body: Center(
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 40),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: isDarkMode
                    ? [const Color(0xFF1A1D33), const Color(0xFF0A0E21)]
                    : [Colors.white, Colors.grey.shade100],
              ),
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: shadowColor,
                  blurRadius: 20,
                  spreadRadius: 2,
                )
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.account_circle,
                  size: 80,
                  color: primaryColor,
                ),
                const SizedBox(height: 24),
                Text(
                  'Vui lòng đăng nhập để xem thông tin',
                  style: TextStyle(
                    color: textColor,
                    fontSize: 18,
                    fontWeight: FontWeight.w500,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 32),
                InkWell(
                  onTap: _navigateToLogin,
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 32, vertical: 16),
                    decoration: BoxDecoration(
                      gradient: primaryGradient,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: primaryColor.withOpacity(0.4),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Text(
                      'Đăng nhập',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }

    // Màn hình chính (đã sửa lại header)
    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        backgroundColor: isDarkMode ? const Color(0xFF1A1D33) : Colors.white,
        title: const Text('Hồ sơ cá nhân'),
        elevation: 1,
      ),
      body: _isLoading
          ? Center(
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: shadowColor,
                      blurRadius: 12,
                      spreadRadius: 2,
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    SizedBox(
                      width: 60,
                      height: 60,
                      child: CircularProgressIndicator(
                        valueColor: AlwaysStoppedAnimation<Color>(primaryColor),
                        strokeWidth: 3,
                      ),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Đang tải thông tin...',
                      style: TextStyle(
                        fontWeight: FontWeight.w500,
                        fontSize: 16,
                        color: textColor,
                      ),
                    ),
                  ],
                ),
              ),
            )
          : SafeArea(
              child: _buildProfileContent(
                  context,
                  user,
                  backgroundColor,
                  cardColor,
                  textColor,
                  subtitleColor,
                  primaryColor,
                  primaryGradient,
                  shadowColor),
            ),
    );
  }

  // Nội dung trang hồ sơ (đã tách biệt và không còn trong CustomScrollView)
  Widget _buildProfileContent(
      BuildContext context,
      User user,
      Color backgroundColor,
      Color cardColor,
      Color textColor,
      Color subtitleColor,
      Color primaryColor,
      LinearGradient primaryGradient,
      Color shadowColor) {
    // Tạo GlobalKey mới cho form đổi mật khẩu
    final passwordFormKey = GlobalKey<FormState>();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Phần hồ sơ với ảnh đại diện và thông tin cơ bản
          Center(
            child: Column(
              children: [
                // Ảnh đại diện với hiệu ứng
                Stack(
                  children: [
                    Container(
                      width: 120,
                      height: 120,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [
                            primaryColor.withOpacity(0.2),
                            primaryColor.withOpacity(0.1),
                          ],
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: shadowColor,
                            blurRadius: 15,
                            spreadRadius: 2,
                          ),
                        ],
                        border: Border.all(
                          color: primaryColor.withOpacity(0.5),
                          width: 4,
                        ),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(60),
                        child: _localAvatarPath != null
                            ? Image.file(
                                File(_localAvatarPath!),
                                fit: BoxFit.cover,
                              )
                            : (user.avatar != null && user.avatar!.isNotEmpty)
                                ? Image.network(
                                    user.avatar!,
                                    fit: BoxFit.cover,
                                    errorBuilder:
                                        (context, error, stackTrace) =>
                                            Container(
                                      color: primaryColor.withOpacity(0.1),
                                      child: Icon(
                                        Icons.person,
                                        size: 60,
                                        color: primaryColor.withOpacity(0.7),
                                      ),
                                    ),
                                  )
                                : Container(
                                    color: primaryColor.withOpacity(0.1),
                                    child: Icon(
                                      Icons.person,
                                      size: 60,
                                      color: primaryColor.withOpacity(0.7),
                                    ),
                                  ),
                      ),
                    ),
                    Positioned(
                      bottom: 0,
                      right: 0,
                      child: Container(
                        decoration: BoxDecoration(
                          gradient: primaryGradient,
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: primaryColor.withOpacity(0.5),
                              blurRadius: 8,
                              spreadRadius: 0,
                            ),
                          ],
                          border: Border.all(
                            color: backgroundColor,
                            width: 3,
                          ),
                        ),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            borderRadius: BorderRadius.circular(20),
                            onTap: _selectAndUploadAvatar,
                            child: const Padding(
                              padding: EdgeInsets.all(8.0),
                              child: Icon(
                                Icons.camera_alt,
                                color: Colors.white,
                                size: 20,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Thông tin cơ bản với thiết kế đẹp
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        primaryColor.withOpacity(0.1),
                        primaryColor.withOpacity(0.05),
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: primaryColor.withOpacity(0.2),
                      width: 1,
                    ),
                  ),
                  child: Column(
                    children: [
                      Text(
                        user.username,
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
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          gradient: primaryGradient,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: primaryColor.withOpacity(0.4),
                              blurRadius: 4,
                              spreadRadius: 0,
                            ),
                          ],
                        ),
                        child: Text(
                          'Quota: ${user.quota} tin nhắn',
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Form thông tin cá nhân với thiết kế đẹp
          Container(
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: shadowColor,
                  blurRadius: 15,
                  spreadRadius: 1,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: Column(
              children: [
                // Header của form
                Container(
                  padding: const EdgeInsets.all(16.0),
                  decoration: BoxDecoration(
                    gradient: primaryGradient,
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(24),
                      topRight: Radius.circular(24),
                    ),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.person, color: Colors.white),
                      SizedBox(width: 12),
                      Text(
                        'Thông tin cá nhân',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
                // Nội dung form
                Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Form(
                    key: _profileFormKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Họ và tên
                        _buildTextField(
                          controller: _fullNameController,
                          label: 'Họ và tên',
                          icon: Icons.person_outline,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Vui lòng nhập họ và tên';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),

                        // Số điện thoại
                        _buildTextField(
                          controller: _phoneController,
                          label: 'Số điện thoại',
                          icon: Icons.phone_outlined,
                          keyboardType: TextInputType.phone,
                        ),
                        const SizedBox(height: 16),

                        // Giới tính
                        _buildDropdownField(
                          value: _gender,
                          label: 'Giới tính',
                          icon: Icons.people_outline,
                          items: const [
                            DropdownMenuItem(
                              value: 'male',
                              child: Text('Nam'),
                            ),
                            DropdownMenuItem(
                              value: 'female',
                              child: Text('Nữ'),
                            ),
                            DropdownMenuItem(
                              value: 'other',
                              child: Text('Khác'),
                            ),
                          ],
                          onChanged: (value) {
                            if (value != null) {
                              setState(() {
                                _gender = value;
                              });
                            }
                          },
                        ),
                        const SizedBox(height: 16),

                        // Ngày sinh
                        InkWell(
                          onTap: () async {
                            final DateTime? pickedDate = await showDatePicker(
                              context: context,
                              initialDate: _birthday ?? DateTime.now(),
                              firstDate: DateTime(1900),
                              lastDate: DateTime.now(),
                              builder: (context, child) {
                                return Theme(
                                  data: Theme.of(context).copyWith(
                                    colorScheme: ColorScheme.light(
                                      primary: primaryColor,
                                      onPrimary: Colors.white,
                                      surface: cardColor,
                                      onSurface: textColor,
                                    ),
                                  ),
                                  child: child!,
                                );
                              },
                              locale: const Locale('vi', 'VN'),
                            );
                            if (pickedDate != null) {
                              setState(() {
                                _birthday = pickedDate;
                              });
                            }
                          },
                          child: _buildDateField(
                            label: 'Ngày sinh',
                            icon: Icons.calendar_today_outlined,
                            value: _birthday == null
                                ? 'Chọn ngày sinh'
                                : DateFormat('dd/MM/yyyy', 'vi_VN')
                                    .format(_birthday!),
                          ),
                        ),
                        const SizedBox(height: 16),

                        // Địa chỉ
                        _buildTextField(
                          controller: _addressController,
                          label: 'Địa chỉ',
                          icon: Icons.home_outlined,
                          maxLines: 3,
                        ),
                        const SizedBox(height: 24),

                        // Nút lưu với thiết kế đẹp
                        Center(
                          child: InkWell(
                            onTap: _saveProfile,
                            borderRadius: BorderRadius.circular(16),
                            child: Container(
                              width: double.infinity,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              decoration: BoxDecoration(
                                gradient: primaryGradient,
                                borderRadius: BorderRadius.circular(16),
                                boxShadow: [
                                  BoxShadow(
                                    color: primaryColor.withOpacity(0.4),
                                    blurRadius: 8,
                                    offset: const Offset(0, 3),
                                  ),
                                ],
                              ),
                              child: const Center(
                                child: Text(
                                  'Lưu thông tin',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Form đổi mật khẩu với thiết kế đẹp
          Container(
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: shadowColor,
                  blurRadius: 15,
                  spreadRadius: 1,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: Column(
              children: [
                // Header của form
                Container(
                  padding: const EdgeInsets.all(16.0),
                  decoration: BoxDecoration(
                    gradient: primaryGradient,
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(24),
                      topRight: Radius.circular(24),
                    ),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.security, color: Colors.white),
                      SizedBox(width: 12),
                      Text(
                        'Đổi mật khẩu',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
                // Nội dung form
                Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Form(
                    // Sử dụng key mới thay vì _passwordFormKey
                    key: GlobalKey<FormState>(),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Mật khẩu hiện tại
                        _buildTextField(
                          controller: _currentPasswordController,
                          label: 'Mật khẩu hiện tại',
                          icon: Icons.lock_outline,
                          obscureText: true,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Vui lòng nhập mật khẩu hiện tại';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),

                        // Mật khẩu mới
                        _buildTextField(
                          controller: _newPasswordController,
                          label: 'Mật khẩu mới',
                          icon: Icons.vpn_key_outlined,
                          obscureText: true,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Vui lòng nhập mật khẩu mới';
                            }
                            if (value.length < 6) {
                              return 'Mật khẩu phải có ít nhất 6 ký tự';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),

                        // Xác nhận mật khẩu mới
                        _buildTextField(
                          controller: _confirmPasswordController,
                          label: 'Xác nhận mật khẩu mới',
                          icon: Icons.check_circle_outline,
                          obscureText: true,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Vui lòng xác nhận mật khẩu mới';
                            }
                            if (value != _newPasswordController.text) {
                              return 'Mật khẩu xác nhận không khớp';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 24),

                        // Nút đổi mật khẩu
                        Center(
                          child: InkWell(
                            onTap: _changePassword,
                            borderRadius: BorderRadius.circular(16),
                            child: Container(
                              width: double.infinity,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              decoration: BoxDecoration(
                                gradient: primaryGradient,
                                borderRadius: BorderRadius.circular(16),
                                boxShadow: [
                                  BoxShadow(
                                    color: primaryColor.withOpacity(0.4),
                                    blurRadius: 8,
                                    offset: const Offset(0, 3),
                                  ),
                                ],
                              ),
                              child: const Center(
                                child: Text(
                                  'Đổi mật khẩu',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Đăng xuất với thiết kế mới
          Container(
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: shadowColor,
                  blurRadius: 12,
                  spreadRadius: 1,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Material(
              color: Colors.transparent,
              borderRadius: BorderRadius.circular(24),
              child: InkWell(
                onTap: () async {
                  // Hiển thị dialog xác nhận đăng xuất
                  final bool confirm = await showDialog(
                        context: context,
                        builder: (context) => AlertDialog(
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(20),
                          ),
                          title: const Row(
                            children: [
                              Icon(Icons.logout, color: Colors.red),
                              SizedBox(width: 8),
                              Text('Đăng xuất'),
                            ],
                          ),
                          content:
                              const Text('Bạn có chắc chắn muốn đăng xuất?'),
                          actions: [
                            TextButton(
                              onPressed: () => Navigator.of(context).pop(false),
                              child: const Text('Hủy'),
                            ),
                            Container(
                              decoration: BoxDecoration(
                                color: Colors.red,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: TextButton(
                                onPressed: () =>
                                    Navigator.of(context).pop(true),
                                child: const Text(
                                  'Đăng xuất',
                                  style: TextStyle(color: Colors.white),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ) ??
                      false;

                  if (confirm && mounted) {
                    final authService =
                        Provider.of<AuthService>(context, listen: false);
                    await authService.signOut();

                    if (mounted) {
                      Navigator.of(context).pushReplacement(
                        MaterialPageRoute(
                            builder: (context) => const LoginScreen()),
                      );
                    }
                  }
                },
                borderRadius: BorderRadius.circular(24),
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.red.withOpacity(0.1),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.logout,
                          color: Colors.red,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Đăng xuất',
                              style: TextStyle(
                                color: Colors.red,
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Đăng xuất khỏi tài khoản hiện tại',
                              style: TextStyle(
                                color: subtitleColor,
                                fontSize: 14,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(
                        Icons.arrow_forward_ios,
                        color: Colors.red,
                        size: 16,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),

          // Thêm padding cuối cùng để tránh bị che khuất bởi thanh navigation
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  // Hàm helper tạo textfield hiện đại
  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    TextInputType? keyboardType,
    bool obscureText = false,
    int maxLines = 1,
    String? Function(String?)? validator,
  }) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor =
        isDarkMode ? const Color(0xFF2979FF) : const Color(0xFF2196F3);

    return TextFormField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: keyboardType,
      maxLines: maxLines,
      validator: validator,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: primaryColor),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(
            color: primaryColor.withOpacity(0.3),
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(
            color: primaryColor.withOpacity(0.3),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(
            color: primaryColor,
            width: 2,
          ),
        ),
        filled: true,
        fillColor: isDarkMode ? Colors.black12 : Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
      ),
    );
  }

  // Hàm helper tạo dropdown field hiện đại
  Widget _buildDropdownField({
    required String value,
    required String label,
    required IconData icon,
    required List<DropdownMenuItem<String>> items,
    required void Function(String?) onChanged,
  }) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor =
        isDarkMode ? const Color(0xFF2979FF) : const Color(0xFF2196F3);

    return DropdownButtonFormField<String>(
      value: value,
      items: items,
      onChanged: onChanged,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: primaryColor),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(
            color: primaryColor.withOpacity(0.3),
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(
            color: primaryColor.withOpacity(0.3),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(
            color: primaryColor,
            width: 2,
          ),
        ),
        filled: true,
        fillColor: isDarkMode ? Colors.black12 : Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
      ),
      icon: Icon(Icons.arrow_drop_down, color: primaryColor),
      dropdownColor: isDarkMode ? const Color(0xFF1A1D33) : Colors.white,
    );
  }

  // Hàm helper tạo date field hiện đại
  Widget _buildDateField({
    required String label,
    required IconData icon,
    required String value,
  }) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor =
        isDarkMode ? const Color(0xFF2979FF) : const Color(0xFF2196F3);

    return InputDecorator(
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: primaryColor),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(
            color: primaryColor.withOpacity(0.3),
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(
            color: primaryColor.withOpacity(0.3),
          ),
        ),
        filled: true,
        fillColor: isDarkMode ? Colors.black12 : Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(value),
          Icon(Icons.calendar_today_outlined, color: primaryColor, size: 16),
        ],
      ),
    );
  }
}

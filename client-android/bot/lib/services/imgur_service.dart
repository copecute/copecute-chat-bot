import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

class ImgurService {
  // Client ID Imgur API
  static const String clientId = 'b558035630d26ef';

  // API endpoint
  static const String apiUrl = 'https://api.imgur.com/3/image';

  // Chọn ảnh từ thư viện
  static Future<File?> pickImage() async {
    final ImagePicker picker = ImagePicker();

    // Chọn hình ảnh từ thư viện
    final XFile? image = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 80, // Giảm chất lượng để tối ưu kích thước
    );

    if (image == null) return null;

    return File(image.path);
  }

  // Chụp ảnh từ camera
  static Future<File?> takePhoto() async {
    final ImagePicker picker = ImagePicker();

    // Chụp ảnh mới từ camera
    final XFile? photo = await picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 80, // Giảm chất lượng để tối ưu kích thước
    );

    if (photo == null) return null;

    return File(photo.path);
  }

  // Upload ảnh lên Imgur
  static Future<String?> uploadImage(File imageFile) async {
    try {
      // Đọc file dưới dạng bytes
      final List<int> imageBytes = await imageFile.readAsBytes();

      // Encode thành base64
      final String base64Image = base64Encode(imageBytes);

      // Tạo request
      final response = await http.post(
        Uri.parse(apiUrl),
        headers: {
          'Authorization': 'Client-ID $clientId',
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
          'image': base64Image,
        },
      );

      // Xử lý response
      if (response.statusCode == 200) {
        final Map<String, dynamic> data = json.decode(response.body);
        if (data['success'] == true) {
          // Trả về URL của ảnh
          return data['data']['link'];
        }
      }

      debugPrint(
          'Imgur upload error: ${response.statusCode} - ${response.body}');
      return null;
    } catch (e) {
      debugPrint('Error uploading to Imgur: $e');
      return null;
    }
  }

  // Hiển thị dialog chọn ảnh
  static Future<File?> showImageSourceDialog(BuildContext context) async {
    final source = await showDialog<ImageSource>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Chọn nguồn ảnh'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Chọn từ thư viện'),
              onTap: () => Navigator.of(context).pop(ImageSource.gallery),
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt),
              title: const Text('Chụp ảnh mới'),
              onTap: () => Navigator.of(context).pop(ImageSource.camera),
            ),
          ],
        ),
      ),
    );

    if (source == null) return null;

    final ImagePicker picker = ImagePicker();
    final XFile? imageFile = await picker.pickImage(
      source: source,
      imageQuality: 80,
    );

    if (imageFile == null) return null;

    return File(imageFile.path);
  }
}

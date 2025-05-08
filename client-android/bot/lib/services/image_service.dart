import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as path;

class ImageService {
  static final ImagePicker _picker = ImagePicker();

  // Chọn hình ảnh từ thư viện
  static Future<String?> pickImage() async {
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 80,
      );

      if (image == null) return null;

      // Lưu hình ảnh vào thư mục ứng dụng để sử dụng lâu dài
      final String savedImagePath = await _saveImageToAppDirectory(image.path);
      return savedImagePath;
    } catch (e) {
      debugPrint('Lỗi khi chọn hình ảnh: $e');
      return null;
    }
  }

  // Lưu hình ảnh vào thư mục ứng dụng
  static Future<String> _saveImageToAppDirectory(String imagePath) async {
    final Directory appDocDir = await getApplicationDocumentsDirectory();
    final String appDocPath = appDocDir.path;

    // Tạo tên file mới với thời gian hiện tại để tránh trùng lặp
    final String fileName =
        'background_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final String destinationPath = path.join(appDocPath, fileName);

    // Sao chép file từ đường dẫn tạm thời vào thư mục ứng dụng
    final File sourceFile = File(imagePath);
    await sourceFile.copy(destinationPath);

    return destinationPath;
  }

  // Xóa hình ảnh cũ nếu cần
  static Future<void> deleteImageIfExists(String? imagePath) async {
    if (imagePath == null) return;

    try {
      final File file = File(imagePath);
      if (await file.exists()) {
        await file.delete();
      }
    } catch (e) {
      debugPrint('Lỗi khi xóa hình ảnh: $e');
    }
  }
}

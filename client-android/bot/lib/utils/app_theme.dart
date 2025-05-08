import 'package:flutter/material.dart';

class AppTheme {
  // Màu sắc chính của ứng dụng
  static const Color primaryColor = Color(0xFF4E55FF);
  static const Color secondaryColor = Color(0xFF00CFDE);
  static const Color backgroundColor = Color(0xFFF8F9FF);
  static const Color cardColor = Color(0xFFFFFFFF);
  static const Color textColor = Color(0xFF2D3142);
  static const Color subtitleColor = Color(0xFF9C9EB9);
  static const Color errorColor = Color(0xFFFF4D6D);
  static const Color successColor = Color(0xFF00C48C);

  // Dark mode colors
  static const Color darkPrimaryColor = Color(0xFF6C72FF);
  static const Color darkSecondaryColor = Color(0xFF00E3F3);
  static const Color darkBackgroundColor = Color(0xFF121212);
  static const Color darkCardColor = Color(0xFF1E1E1E);
  static const Color darkTextColor = Color(0xFFF2F2F2);
  static const Color darkSubtitleColor = Color(0xFFB4B4B4);

  static ThemeData lightTheme = ThemeData(
    useMaterial3: true,
    brightness: Brightness.light,
    colorScheme: ColorScheme.light(
      primary: primaryColor,
      secondary: secondaryColor,
      error: errorColor,
      background: backgroundColor,
      surface: cardColor,
    ),
    scaffoldBackgroundColor: backgroundColor,
    cardTheme: CardTheme(
      color: cardColor,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.transparent,
      elevation: 0,
      titleTextStyle: TextStyle(
        color: textColor,
        fontSize: 20,
        fontWeight: FontWeight.bold,
      ),
      iconTheme: IconThemeData(color: textColor),
    ),
    textTheme: const TextTheme(
      displayLarge: TextStyle(
        fontSize: 32,
        fontWeight: FontWeight.bold,
        color: textColor,
        letterSpacing: -0.5,
      ),
      displayMedium: TextStyle(
        fontSize: 24,
        fontWeight: FontWeight.bold,
        color: textColor,
      ),
      bodyLarge: TextStyle(
        fontSize: 16,
        color: textColor,
        height: 1.5,
      ),
      bodyMedium: TextStyle(
        fontSize: 14,
        color: subtitleColor,
        height: 1.5,
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ButtonStyle(
        backgroundColor: MaterialStateProperty.resolveWith<Color>((states) {
          if (states.contains(MaterialState.disabled)) {
            return primaryColor.withOpacity(0.5);
          }
          return primaryColor;
        }),
        foregroundColor: MaterialStateProperty.all<Color>(Colors.white),
        padding: MaterialStateProperty.all<EdgeInsets>(
          const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
        ),
        shape: MaterialStateProperty.all<RoundedRectangleBorder>(
          RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(30),
          ),
        ),
        elevation: MaterialStateProperty.all<double>(0),
        overlayColor: MaterialStateProperty.resolveWith<Color?>((states) {
          if (states.contains(MaterialState.pressed)) {
            return Colors.white.withOpacity(0.1);
          }
          return null;
        }),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: ButtonStyle(
        foregroundColor: MaterialStateProperty.all<Color>(primaryColor),
        textStyle: MaterialStateProperty.all<TextStyle>(
          const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: cardColor,
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: primaryColor, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: errorColor, width: 2),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: errorColor, width: 2),
      ),
      labelStyle: const TextStyle(
        color: subtitleColor,
        fontSize: 16,
      ),
      hintStyle: const TextStyle(
        color: subtitleColor,
        fontSize: 16,
      ),
      prefixIconColor: subtitleColor,
      suffixIconColor: subtitleColor,
      floatingLabelBehavior: FloatingLabelBehavior.never,
    ),
    checkboxTheme: CheckboxThemeData(
      fillColor: MaterialStateProperty.resolveWith<Color>((states) {
        if (states.contains(MaterialState.selected)) {
          return primaryColor;
        }
        return Colors.transparent;
      }),
      side: const BorderSide(color: subtitleColor, width: 1.5),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(4),
      ),
    ),
    dividerTheme: const DividerThemeData(
      color: Color(0xFFEEEFF5),
      thickness: 1,
    ),
    shadowColor: Colors.black.withOpacity(0.1),
  );

  static ThemeData darkTheme = ThemeData(
    useMaterial3: true,
    brightness: Brightness.dark,
    colorScheme: ColorScheme.dark(
      primary: darkPrimaryColor,
      secondary: darkSecondaryColor,
      error: errorColor,
      background: darkBackgroundColor,
      surface: darkCardColor,
    ),
    scaffoldBackgroundColor: darkBackgroundColor,
    cardTheme: CardTheme(
      color: darkCardColor,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.transparent,
      elevation: 0,
      titleTextStyle: TextStyle(
        color: darkTextColor,
        fontSize: 20,
        fontWeight: FontWeight.bold,
      ),
      iconTheme: IconThemeData(color: darkTextColor),
    ),
    textTheme: const TextTheme(
      displayLarge: TextStyle(
        fontSize: 32,
        fontWeight: FontWeight.bold,
        color: darkTextColor,
        letterSpacing: -0.5,
      ),
      displayMedium: TextStyle(
        fontSize: 24,
        fontWeight: FontWeight.bold,
        color: darkTextColor,
      ),
      bodyLarge: TextStyle(
        fontSize: 16,
        color: darkTextColor,
        height: 1.5,
      ),
      bodyMedium: TextStyle(
        fontSize: 14,
        color: darkSubtitleColor,
        height: 1.5,
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ButtonStyle(
        backgroundColor: MaterialStateProperty.resolveWith<Color>((states) {
          if (states.contains(MaterialState.disabled)) {
            return darkPrimaryColor.withOpacity(0.5);
          }
          return darkPrimaryColor;
        }),
        foregroundColor: MaterialStateProperty.all<Color>(Colors.white),
        padding: MaterialStateProperty.all<EdgeInsets>(
          const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
        ),
        shape: MaterialStateProperty.all<RoundedRectangleBorder>(
          RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(30),
          ),
        ),
        elevation: MaterialStateProperty.all<double>(0),
        overlayColor: MaterialStateProperty.resolveWith<Color?>((states) {
          if (states.contains(MaterialState.pressed)) {
            return Colors.white.withOpacity(0.1);
          }
          return null;
        }),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: ButtonStyle(
        foregroundColor: MaterialStateProperty.all<Color>(darkPrimaryColor),
        textStyle: MaterialStateProperty.all<TextStyle>(
          const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: darkCardColor,
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: darkPrimaryColor, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: errorColor, width: 2),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: errorColor, width: 2),
      ),
      labelStyle: const TextStyle(
        color: darkSubtitleColor,
        fontSize: 16,
      ),
      hintStyle: const TextStyle(
        color: darkSubtitleColor,
        fontSize: 16,
      ),
      prefixIconColor: darkSubtitleColor,
      suffixIconColor: darkSubtitleColor,
      floatingLabelBehavior: FloatingLabelBehavior.never,
    ),
    checkboxTheme: CheckboxThemeData(
      fillColor: MaterialStateProperty.resolveWith<Color>((states) {
        if (states.contains(MaterialState.selected)) {
          return darkPrimaryColor;
        }
        return Colors.transparent;
      }),
      side: const BorderSide(color: darkSubtitleColor, width: 1.5),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(4),
      ),
    ),
    dividerTheme: const DividerThemeData(
      color: Color(0xFF2C2C2C),
      thickness: 1,
    ),
    shadowColor: Colors.black.withOpacity(0.2),
  );
}

import 'package:flutter/material.dart';

class AppTheme {
  /// Sky blue palette
  static const brand = Color(0xFF0EA5E9); // sky-500
  static const brandDark = Color(0xFF0369A1); // sky-700
  static const brandSoft = Color(0xFF38BDF8); // sky-400
  static const muted = Color(0xFF64748B);
  static const bg = Color(0xFFF0F9FF); // sky-50

  static ThemeData light = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: brand,
      brightness: Brightness.light,
      primary: brand,
      secondary: brandSoft,
    ),
    scaffoldBackgroundColor: bg,
    appBarTheme: const AppBarTheme(
      backgroundColor: brand,
      foregroundColor: Colors.white,
      elevation: 0,
    ),
    navigationBarTheme: NavigationBarThemeData(
      indicatorColor: brand.withOpacity(0.2),
      backgroundColor: Colors.white,
    ),
    floatingActionButtonTheme: const FloatingActionButtonThemeData(
      backgroundColor: brand,
      foregroundColor: Colors.white,
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(backgroundColor: brand, foregroundColor: Colors.white),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: brand, width: 2),
      ),
    ),
    checkboxTheme: CheckboxThemeData(
      fillColor: MaterialStateProperty.resolveWith((s) => s.contains(MaterialState.selected) ? brand : null),
    ),
  );

  static ThemeData dark = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: brand,
      brightness: Brightness.dark,
      primary: brandSoft,
    ),
    scaffoldBackgroundColor: const Color(0xFF0C1929),
    appBarTheme: const AppBarTheme(
      backgroundColor: Color(0xFF0C4A6E),
      foregroundColor: Colors.white,
      elevation: 0,
    ),
    navigationBarTheme: const NavigationBarThemeData(
      backgroundColor: Color(0xFF0C1929),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(backgroundColor: brand, foregroundColor: Colors.white),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: const Color(0xFF1E293B),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
    ),
  );
}

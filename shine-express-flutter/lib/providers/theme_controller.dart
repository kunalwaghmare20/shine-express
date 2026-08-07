import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ThemeController extends ChangeNotifier {
  ThemeController() {
    _load();
  }

  ThemeMode _mode = ThemeMode.light;
  ThemeMode get mode => _mode;
  bool get isDark => _mode == ThemeMode.dark;

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    final stored = prefs.getString('theme_mode');
    if (stored == 'dark') {
      _mode = ThemeMode.dark;
    } else if (stored == 'system') {
      _mode = ThemeMode.system;
    } else {
      _mode = ThemeMode.light;
    }
    notifyListeners();
  }

  Future<void> setMode(ThemeMode mode) async {
    _mode = mode;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    final String value;
    if (mode == ThemeMode.dark) {
      value = 'dark';
    } else if (mode == ThemeMode.system) {
      value = 'system';
    } else {
      value = 'light';
    }
    await prefs.setString('theme_mode', value);
  }

  Future<void> setDark(bool dark) => setMode(dark ? ThemeMode.dark : ThemeMode.light);
}

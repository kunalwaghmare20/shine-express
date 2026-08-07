class AppConfig {
  /// Android emulator → 10.0.2.2 ; iOS simulator → localhost ; device → LAN IP
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8080',
  );

  static const supportPhone = '919673522737';
  static const supportWhatsapp = '919673522737';
}

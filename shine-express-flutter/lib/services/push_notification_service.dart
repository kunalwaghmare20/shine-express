import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import 'api_client.dart';

/// Registers FCM device tokens with the PHP API for push notifications.
class PushNotificationService {
  static Future<void> registerIfAvailable(ApiClient api) async {
    if (kIsWeb) return;

    try {
      if (Firebase.apps.isEmpty) {
        await Firebase.initializeApp();
      }

      final messaging = FirebaseMessaging.instance;
      if (Platform.isIOS) {
        await messaging.requestPermission(alert: true, badge: true, sound: true);
      }

      final token = await messaging.getToken();
      if (token != null) {
        await _registerToken(api, token);
      }

      messaging.onTokenRefresh.listen((token) => _registerToken(api, token));
    } catch (e) {
      if (kDebugMode) {
        debugPrint('Push notifications not configured: $e');
      }
    }
  }

  static Future<void> _registerToken(ApiClient api, String token) async {
    try {
      await api.post('/api/v1/devices', body: {
        'token': token,
        'platform': Platform.isIOS ? 'IOS' : 'ANDROID',
      });
    } catch (e) {
      if (kDebugMode) {
        debugPrint('Device token registration failed: $e');
      }
    }
  }
}

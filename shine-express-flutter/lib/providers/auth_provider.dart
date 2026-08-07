import 'package:flutter/foundation.dart';
import '../services/api_client.dart';

class AuthProvider extends ChangeNotifier {
  AuthProvider(this._api);

  final ApiClient _api;
  bool loading = true;
  Map<String, dynamic>? user;
  Map<String, dynamic>? employee;
  String? customerId;

  bool get isLoggedIn => user != null;
  bool get isCustomer => user?['role'] == 'CUSTOMER';
  bool get isStaff => user?['role'] == 'SERVICE_STAFF' || user?['role'] == 'BRANCH_MANAGER';

  Future<void> bootstrap() async {
    loading = true;
    notifyListeners();
    try {
      final token = await _api.getToken();
      if (token == null) {
        user = null;
      } else {
        await refreshMe();
      }
    } catch (_) {
      await _api.setToken(null);
      user = null;
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> refreshMe() async {
    final data = await _api.get('/api/v1/auth/me') as Map<String, dynamic>;
    user = Map<String, dynamic>.from(data['user'] as Map);
    employee = data['employee'] == null ? null : Map<String, dynamic>.from(data['employee'] as Map);
    customerId = data['customerId']?.toString();
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    final data = await _api.post('/api/v1/auth/login', body: {
      'email': email,
      'password': password,
      'deviceName': 'flutter',
    }) as Map<String, dynamic>;
    await _api.setToken(data['token'] as String);
    user = Map<String, dynamic>.from(data['user'] as Map);
    employee = data['employee'] == null ? null : Map<String, dynamic>.from(data['employee'] as Map);
    customerId = data['customerId']?.toString();
    notifyListeners();
  }

  Future<void> register(Map<String, dynamic> body) async {
    final data = await _api.post('/api/v1/auth/register', body: body) as Map<String, dynamic>;
    await _api.setToken(data['token'] as String);
    user = Map<String, dynamic>.from(data['user'] as Map);
    customerId = data['customerId']?.toString();
    employee = null;
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await _api.post('/api/v1/auth/logout');
    } catch (_) {}
    await _api.setToken(null);
    user = null;
    employee = null;
    customerId = null;
    notifyListeners();
  }
}

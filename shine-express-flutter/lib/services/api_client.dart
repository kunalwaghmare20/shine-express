import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';

class ApiClient {
  ApiClient();

  final _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );
  static const _tokenKey = 'api_token';
  static const _timeout = Duration(seconds: 12);

  Future<String?> getToken() async {
    try {
      return await _storage.read(key: _tokenKey).timeout(const Duration(seconds: 5));
    } catch (_) {
      return null;
    }
  }

  Future<void> setToken(String? token) async {
    try {
      if (token == null) {
        await _storage.delete(key: _tokenKey).timeout(const Duration(seconds: 5));
      } else {
        await _storage.write(key: _tokenKey, value: token).timeout(const Duration(seconds: 5));
      }
    } catch (_) {}
  }

  Future<dynamic> get(String path, {Map<String, String>? query}) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}$path').replace(queryParameters: query);
    final res = await http.get(uri, headers: await _headers()).timeout(_timeout);
    return _decode(res);
  }

  Future<dynamic> post(String path, {Map<String, dynamic>? body}) async {
    final res = await http
        .post(
          Uri.parse('${AppConfig.apiBaseUrl}$path'),
          headers: await _headers(json: true),
          body: body == null ? null : jsonEncode(body),
        )
        .timeout(_timeout);
    return _decode(res);
  }

  Future<dynamic> postMultipart(
    String path, {
    required Map<String, String> fields,
    required String fileField,
    required String filePath,
    required String filename,
  }) async {
    final req = http.MultipartRequest('POST', Uri.parse('${AppConfig.apiBaseUrl}$path'));
    final token = await getToken();
    if (token != null) req.headers['Authorization'] = 'Bearer $token';
    req.headers['Accept'] = 'application/json';
    req.fields.addAll(fields);
    req.files.add(await http.MultipartFile.fromPath(fileField, filePath, filename: filename));
    final streamed = await req.send().timeout(_timeout);
    final res = await http.Response.fromStream(streamed).timeout(_timeout);
    return _decode(res);
  }

  Future<Map<String, String>> _headers({bool json = false}) async {
    final headers = <String, String>{'Accept': 'application/json'};
    if (json) headers['Content-Type'] = 'application/json';
    final token = await getToken();
    if (token != null) headers['Authorization'] = 'Bearer $token';
    return headers;
  }

  dynamic _decode(http.Response res) {
    final map = jsonDecode(res.body.isEmpty ? '{}' : res.body) as Map<String, dynamic>;
    if (res.statusCode >= 400 || map['success'] == false) {
      throw ApiException(map['message']?.toString() ?? 'Request failed (${res.statusCode})', res.statusCode);
    }
    return map['data'];
  }
}

class ApiException implements Exception {
  ApiException(this.message, this.statusCode);
  final String message;
  final int statusCode;
  @override
  String toString() => message;
}

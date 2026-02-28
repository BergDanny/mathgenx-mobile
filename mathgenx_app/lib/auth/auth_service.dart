import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../core/api_config.dart';
import '../services/user_store.dart';

class AuthService {
  static String get _baseUrl => '${ApiConfig.baseUrl}/auth';
  static const String _tokenKey = 'auth_token';

  // ─── Token helpers ────────────────────────────────────────────────────────

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  // ─── API calls ────────────────────────────────────────────────────────────

  /// POST /api/v1/auth/login
  /// Saves token and pre-fetches the user profile into [UserStore].
  static Future<Map<String, dynamic>> login(
    String email,
    String password,
  ) async {
    final response = await http.post(
      Uri.parse('$_baseUrl/login'),
      headers: ApiConfig.headers,
      body: jsonEncode({'email': email, 'password': password}),
    );

    final data = jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode == 200) {
      final token =
          data['token'] as String? ?? data['data']?['token'] as String?;
      if (token != null) await saveToken(token);
      // Pre-fetch profile so all screens have it ready
      await UserStore.instance.fetchProfile();
      return data;
    } else {
      throw Exception(data['message'] ?? 'Login failed. Please try again.');
    }
  }

  /// POST /api/v1/auth/register
  /// Saves token and pre-fetches the user profile into [UserStore].
  static Future<Map<String, dynamic>> register(
    String name,
    String email,
    String password,
    String passwordConfirmation,
  ) async {
    final response = await http.post(
      Uri.parse('$_baseUrl/register'),
      headers: ApiConfig.headers,
      body: jsonEncode({
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );

    final data = jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode == 200 || response.statusCode == 201) {
      final token =
          data['token'] as String? ?? data['data']?['token'] as String?;
      if (token != null) await saveToken(token);
      await UserStore.instance.fetchProfile();
      return data;
    } else {
      if (response.statusCode == 422 && data['errors'] != null) {
        final errors = data['errors'] as Map<String, dynamic>;
        final firstError = (errors.values.first as List<dynamic>).first
            .toString();
        throw Exception(firstError);
      }
      throw Exception(
        data['message'] ?? 'Registration failed. Please try again.',
      );
    }
  }

  /// POST /api/v1/auth/logout — clears token and user store.
  static Future<void> logout() async {
    final token = await getToken();
    if (token == null) return;

    await http.post(
      Uri.parse('$_baseUrl/logout'),
      headers: ApiConfig.authHeaders(token),
    );

    await clearToken();
    UserStore.instance.clear();
  }
}

import 'dart:convert';
import 'package:http/http.dart' as http;
import '../auth/auth_service.dart';
import '../core/api_config.dart';
import '../models/user_profile.dart';

/// Singleton in-memory store for the authenticated user's profile.
/// Mirrors SPA behaviour: fetch once after login, read from anywhere.
class UserStore {
  UserStore._();
  static final UserStore instance = UserStore._();

  static String get _profileUrl => '${ApiConfig.baseUrl}/auth/profile';

  UserProfile? _profile;

  /// The cached profile. Null if not yet fetched or cleared.
  UserProfile? get profile => _profile;

  /// Fetch the profile from the API and cache it.
  /// Throws on network or auth errors.
  Future<UserProfile> fetchProfile() async {
    final token = await AuthService.getToken();
    if (token == null) throw Exception('No auth token found.');

    final response = await http.get(
      Uri.parse(_profileUrl),
      headers: ApiConfig.authHeaders(token),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body) as Map<String, dynamic>;
      if (json['success'] == true) {
        _profile = UserProfile.fromJson(json['data'] as Map<String, dynamic>);
        return _profile!;
      }
    }
    throw Exception('Failed to fetch profile (${response.statusCode}).');
  }

  /// Clear cached profile on logout.
  void clear() => _profile = null;
}

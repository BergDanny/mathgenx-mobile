import 'dart:convert';
import 'package:http/http.dart' as http;
import '../auth/auth_service.dart';
import '../core/api_config.dart';
import '../models/quiz_attempt.dart';

class AttemptsService {
  static String get _url => '${ApiConfig.baseUrl}/mathquest/attempts';

  /// Fetch all quiz attempts for the authenticated user.
  static Future<List<QuizAttempt>> fetchAttempts() async {
    final token = await AuthService.getToken();
    if (token == null) throw Exception('No auth token found.');

    final response = await http.get(
      Uri.parse(_url),
      headers: ApiConfig.authHeaders(token),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body) as Map<String, dynamic>;
      if (json['status'] == 'success') {
        final list = json['data'] as List<dynamic>;
        return list
            .map((e) => QuizAttempt.fromJson(e as Map<String, dynamic>))
            .toList();
      }
    }
    throw Exception('Failed to fetch attempts (${response.statusCode}).');
  }
}

import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../auth/auth_service.dart';
import '../core/api_config.dart';
import '../models/quiz_response.dart';

class QuizService {
  static String get _url => '${ApiConfig.baseUrl}/mathquest/quiz';

  static Future<QuizResponse> generateQuiz(Map<String, dynamic> payload) async {
    final token = await AuthService.getToken();
    if (token == null) throw Exception('No auth token found.');

    // Build query params — all values must be strings
    final queryParams = payload.map((k, v) => MapEntry(k, '$v'));
    final uri = Uri.parse(_url).replace(queryParameters: queryParams);

    debugPrint('── QuizService GET ───────────────────');
    debugPrint('URL : $uri');

    final response = await http.get(uri, headers: ApiConfig.authHeaders(token));

    debugPrint('Status  : ${response.statusCode}');
    debugPrint('Response: ${response.body}');

    if (response.statusCode == 200 || response.statusCode == 201) {
      final json = jsonDecode(response.body) as Map<String, dynamic>;
      if (json['status'] == 'success') {
        return QuizResponse.fromJson(json);
      }
      throw Exception(json['message'] ?? 'Quiz generation failed.');
    }

    // Show status + raw body so the error card displays the backend message
    String detail;
    try {
      final json = jsonDecode(response.body) as Map<String, dynamic>;
      detail = json['message'] ?? response.body;
    } catch (_) {
      detail = response.body;
    }
    throw Exception('${response.statusCode}: $detail');
  }

  static Future<Map<String, dynamic>> submitAttempt(
    Map<String, dynamic> attemptPayload,
  ) async {
    final token = await AuthService.getToken();
    if (token == null) throw Exception('No auth token found.');

    final url = '${ApiConfig.baseUrl}/mathquest/attempts';
    final body = jsonEncode(attemptPayload);

    debugPrint('── QuizService POST Attempt ──────────');
    debugPrint('URL  : $url');
    debugPrint('Body : $body');

    final response = await http.post(
      Uri.parse(url),
      headers: ApiConfig.authHeaders(token),
      body: body,
    );

    debugPrint('Status  : ${response.statusCode}');
    debugPrint('Response: ${response.body}');

    if (response.statusCode != 200 && response.statusCode != 201) {
      String detail;
      try {
        final json = jsonDecode(response.body) as Map<String, dynamic>;
        detail = json['message'] ?? response.body;
      } catch (_) {
        detail = response.body;
      }
      throw Exception('${response.statusCode}: $detail');
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return json['data'] as Map<String, dynamic>;
  }
}

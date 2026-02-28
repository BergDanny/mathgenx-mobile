import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;

class ApiConfig {
  static const String _host = 'mathgenx.test';

  static String get baseUrl {
    if (kIsWeb) {
      return 'http://$_host/api/v1';
    }
    if (Platform.isAndroid) {
      // 10.0.2.2 is the special alias to the host loopback interface (127.0.0.1) in Android emulator
      return 'http://10.0.2.2/api/v1';
    }
    // iOS simulator natively shares host machine network stack
    return 'http://$_host/api/v1';
  }

  static Map<String, String> get headers {
    final Map<String, String> headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    // Android emulator hits 10.0.2.2, but Valet needs the host header to route properly
    if (!kIsWeb && Platform.isAndroid) {
      headers['Host'] = _host;
    }
    return headers;
  }

  static Map<String, String> authHeaders(String token) {
    return {...headers, 'Authorization': 'Bearer $token'};
  }
}

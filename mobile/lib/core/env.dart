/// Environment configuration. Override at build/run time:
///   flutter run --dart-define=API_BASE_URL=https://staging.example.com
///   flutter build apk --dart-define=API_BASE_URL=https://api.example.com
class Env {
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    // Android emulator -> host loopback. For iOS sim / physical devices,
    // pass --dart-define=API_BASE_URL=http://<host-ip>:8899
    defaultValue: 'http://10.0.2.2:8899',
  );

  static const appEnv = String.fromEnvironment('APP_ENV', defaultValue: 'dev');

  static String get apiV1 => '$apiBaseUrl/api/v1';
}

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

/// Secure credential storage. Tokens live in the platform keychain/keystore
/// (flutter_secure_storage); only the non-sensitive install device ID and
/// cached read-only payloads live in shared_preferences.
class TokenStorage {
  static const _key = 'auth_token';
  static const _deviceKey = 'install_device_id';

  final FlutterSecureStorage _secure = const FlutterSecureStorage(
    aOptions: AndroidOptions(),
  );

  Future<String?> read() => _secure.read(key: _key);

  Future<void> write(String token) => _secure.write(key: _key, value: token);

  Future<void> clear() => _secure.delete(key: _key);

  /// Stable per-install device ID for push registration (see API `devices`).
  Future<String> deviceId() async {
    final prefs = await SharedPreferences.getInstance();
    var id = prefs.getString(_deviceKey);
    if (id == null) {
      id = const Uuid().v4();
      await prefs.setString(_deviceKey, id);
    }
    return id;
  }
}

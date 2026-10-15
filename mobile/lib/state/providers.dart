import 'dart:io' show Platform;

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api_client.dart';
import '../core/token_storage.dart';

/// Current authenticated user as returned by the API (`/me`).
class SessionUser {
  SessionUser(this.data);

  final Map<String, dynamic> data;

  int get id => data['id'] as int;
  String get name => data['name'] as String? ?? '';
  String get email => data['email'] as String? ?? '';
  List<String> get roles => List<String>.from(data['roles'] ?? const []);
  List<String> get permissions => List<String>.from(data['permissions'] ?? const []);
  bool get isStaff => data['is_staff'] == true;

  /// Client-side display hint ONLY — the API re-checks every request.
  bool can(String permission) => permissions.contains(permission);
}

/// null = signed out.
class AuthState {
  const AuthState.unknown() : status = AuthStatus.unknown, user = null;
  const AuthState.signedOut() : status = AuthStatus.signedOut, user = null;
  const AuthState.signedIn(this.user) : status = AuthStatus.signedIn;

  final AuthStatus status;
  final SessionUser? user;
}

enum AuthStatus { unknown, signedOut, signedIn }

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());

/// Notifier wires sign-out on 401 via callback.
final authProvider = NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(
    ref.read(tokenStorageProvider),
    onUnauthorized: () => ref.read(authProvider.notifier).expireSession(),
  );
});

class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() => const AuthState.unknown();

  ApiClient get _api => ref.read(apiClientProvider);
  TokenStorage get _storage => ref.read(tokenStorageProvider);

  /// Restore a persisted session at app start (splash screen calls this).
  Future<void> restore() async {
    final token = await _storage.read();
    if (token == null) {
      state = const AuthState.signedOut();
      return;
    }
    try {
      final res = await _api.get('/me');
      state = AuthState.signedIn(SessionUser(res['user'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      if (e.statusCode == 401 || e.isAccountBlocked) {
        await _storage.clear();
        state = const AuthState.signedOut();
      } else {
        // Network hiccup — stay signed in with the stored token.
        state = AuthState.signedIn(SessionUser(const {}));
      }
    }
  }

  Future<String?> login(String email, String password) async {
    try {
      final deviceId = await _storage.deviceId();
      final res = await _api.post('/auth/login',
          data: {'email': email, 'password': password, 'device_name': 'flutter-$deviceId'});
      await _storage.write(res['token'] as String);
      state = AuthState.signedIn(SessionUser(res['user'] as Map<String, dynamic>));
      await _registerDevice();
      return null;
    } on ApiException catch (e) {
      return e.message;
    }
  }

  Future<String?> register(Map<String, String> fields) async {
    try {
      final res = await _api.post('/auth/register', data: {
        ...fields,
        'device_name': 'flutter-${await _storage.deviceId()}',
      });
      await _storage.write(res['token'] as String);
      state = AuthState.signedIn(SessionUser(res['user'] as Map<String, dynamic>));
      await _registerDevice();
      return null;
    } on ApiException catch (e) {
      return e.errors?.values.first?.toString() ?? e.message;
    }
  }

  Future<String?> forgotPassword(String email) async {
    try {
      await _api.post('/auth/forgot-password', data: {'email': email});
      return null;
    } on ApiException catch (e) {
      return e.message;
    }
  }

  /// Register this install for push notifications (best effort).
  Future<void> _registerDevice() async {
    try {
      await _api.post('/devices', data: {
        'device_id': await _storage.deviceId(),
        'platform': _platform,
        'app_version': '1.0.0',
      });
    } catch (_) {/* push registration must never block sign-in */}
  }

  String get _platform {
    if (Platform.isIOS) return 'ios';
    if (Platform.isAndroid) return 'android';
    return 'web';
  }

  /// 401 from any call — token no longer valid.
  void expireSession() {
    _storage.clear();
    state = const AuthState.signedOut();
  }

  Future<void> logout() async {
    try {
      final deviceId = await _storage.deviceId();
      await _api.delete('/devices/$deviceId');
      await _api.post('/auth/logout');
    } catch (_) {/* best effort */}
    await _storage.clear();
    await ref.read(apiClientProvider).clearCache();
    state = const AuthState.signedOut();
  }
}

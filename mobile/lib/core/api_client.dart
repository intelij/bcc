import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'env.dart';
import 'token_storage.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  /// True when the server says the account is suspended/locked.
  bool get isAccountBlocked =>
      statusCode == 403 &&
      (errors?['error'] == 'account_suspended' || errors?['error'] == 'account_locked');

  @override
  String toString() => message;
}

/// Thin Dio wrapper: injects the Bearer token, normalises errors, and falls
/// back to cached GET responses when the network is unavailable.
/// Security note: the cache only ever stores GET payloads the user already
/// viewed, and is cleared on logout.
class ApiClient {
  ApiClient(this._storage, {this.onUnauthorized}) {
    _dio = Dio(BaseOptions(
      baseUrl: Env.apiV1,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json'},
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (e, handler) async {
        if (e.response?.statusCode == 401) {
          await _storage.clear();
          onUnauthorized?.call();
        }
        handler.next(e);
      },
    ));
  }

  final TokenStorage _storage;
  final void Function()? onUnauthorized;
  late final Dio _dio;

  Dio get dio => _dio;

  /// Result of a GET, flagging responses served from the offline cache.
  Future<({dynamic data, bool fromCache})> getCached(String path,
      {Map<String, dynamic>? query}) async {
    try {
      final res = await _dio.get(path, queryParameters: query);
      _cache(path, res.data);
      return (data: res.data, fromCache: false);
    } on DioException catch (e) {
      if (e.response == null) {
        final cached = await _readCache(path);
        if (cached != null) return (data: cached, fromCache: true);
      }
      throw _toException(e);
    }
  }

  Future<dynamic> get(String path, {Map<String, dynamic>? query}) async {
    try {
      final res = await _dio.get(path, queryParameters: query);
      return res.data;
    } on DioException catch (e) {
      throw _toException(e);
    }
  }

  Future<dynamic> post(String path, {dynamic data, Map<String, dynamic>? query}) async {
    try {
      final res = await _dio.post(path, data: data, queryParameters: query);
      return res.data;
    } on DioException catch (e) {
      throw _toException(e);
    }
  }

  Future<dynamic> put(String path, {dynamic data}) async {
    try {
      final res = await _dio.put(path, data: data);
      return res.data;
    } on DioException catch (e) {
      throw _toException(e);
    }
  }

  Future<dynamic> delete(String path) async {
    try {
      final res = await _dio.delete(path);
      return res.data;
    } on DioException catch (e) {
      throw _toException(e);
    }
  }

  /// Multipart upload (payment proofs, request photos).
  Future<dynamic> upload(String path, String field, String filePath,
      {void Function(int sent, int total)? onProgress}) async {
    try {
      final res = await _dio.post(
        path,
        data: FormData.fromMap({field: await MultipartFile.fromFile(filePath)}),
        onSendProgress: onProgress,
      );
      return res.data;
    } on DioException catch (e) {
      throw _toException(e);
    }
  }

  ApiException _toException(DioException e) {
    final body = e.response?.data;
    if (body is Map<String, dynamic>) {
      final fieldErrors = (body['errors'] as Map?)?.map(
        (k, v) => MapEntry(k.toString(), (v as List).join(', ')),
      );
      return ApiException(
        body['message']?.toString() ?? 'Request failed',
        statusCode: e.response?.statusCode,
        errors: {...?fieldErrors, if (body['error'] != null) 'error': body['error']},
      );
    }
    return ApiException(
      e.response == null
          ? 'No connection. Check your network and try again.'
          : 'Request failed (${e.response?.statusCode})',
      statusCode: e.response?.statusCode,
    );
  }

  // ---- offline read cache (non-sensitive GET payloads only) ----

  Future<void> _cache(String path, dynamic data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('cache:$path', jsonEncode(data));
  }

  Future<dynamic> _readCache(String path) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('cache:$path');
    return raw == null ? null : jsonDecode(raw);
  }

  Future<void> clearCache() async {
    final prefs = await SharedPreferences.getInstance();
    for (final key in prefs.getKeys().where((k) => k.startsWith('cache:'))) {
      await prefs.remove(key);
    }
  }
}

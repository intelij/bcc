import 'package:intl/intl.dart';

/// All API amounts are integer cents. Format consistently everywhere.
String fmtCents(int? cents, {String currency = 'USD'}) {
  if (cents == null) return '—';
  final sign = cents < 0 ? '-' : '';
  return '$sign$currency ${(cents.abs() / 100).toStringAsFixed(2)}';
}

String fmtDate(String? iso) {
  if (iso == null || iso.isEmpty) return '—';
  final dt = DateTime.tryParse(iso)?.toLocal();
  return dt == null ? iso : DateFormat('d MMM y').format(dt);
}

String fmtDateTime(String? iso) {
  if (iso == null || iso.isEmpty) return '—';
  final dt = DateTime.tryParse(iso)?.toLocal();
  return dt == null ? iso : DateFormat('d MMM y, HH:mm').format(dt);
}

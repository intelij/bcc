import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Standard loading / error / empty scaffolding for API-backed screens.
/// Wrap content in [RefreshIndicator] via [onRefresh] for pull-to-refresh.
class RemoteView extends StatelessWidget {
  const RemoteView({
    super.key,
    required this.value,
    required this.builder,
    this.onRefresh,
    this.fromCache = false,
  });

  final AsyncValue<dynamic> value;
  final Widget Function(dynamic data) builder;
  final Future<void> Function()? onRefresh;
  final bool fromCache;

  @override
  Widget build(BuildContext context) {
    return value.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => _ErrorState(
        message: e.toString(),
        onRetry: onRefresh,
      ),
      data: (data) {
        final content = builder(data);
        final wrapped = onRefresh == null
            ? content
            : RefreshIndicator(onRefresh: onRefresh!, child: content);
        if (!fromCache) return wrapped;
        return Column(children: [
          Container(
            width: double.infinity,
            color: const Color(0xFFFFF7E0),
            padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 16),
            child: const Text('Offline — showing saved data',
                style: TextStyle(fontSize: 12, color: Color(0xFF92400E))),
          ),
          Expanded(child: wrapped),
        ]);
      },
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, this.onRetry});

  final String message;
  final Future<void> Function()? onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.cloud_off, size: 48, color: Colors.grey),
          const SizedBox(height: 12),
          Text(message, textAlign: TextAlign.center),
          if (onRetry != null) ...[
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Retry'),
            ),
          ],
        ]),
      ),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.message, this.icon = Icons.inbox_outlined});

  final String message;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 48, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text(message, textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade600)),
        ]),
      ),
    );
  }
}

/// Coloured status pill shared by bills, payments and requests.
class StatusChip extends StatelessWidget {
  const StatusChip({super.key, required this.status});

  final String? status;

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = switch (status) {
      'paid' || 'successful' || 'resolved' || 'verified' || 'active' || 'restored' =>
        (const Color(0xFFDCFCE7), const Color(0xFF166534)),
      'overdue' || 'failed' || 'disconnected' || 'urgent' =>
        (const Color(0xFFFEE2E2), const Color(0xFF991B1B)),
      'pending' || 'processing' || 'submitted' || 'planned' || 'scheduled' =>
        (const Color(0xFFFEF3C7), const Color(0xFF92400E)),
      _ => (const Color(0xFFE2E8F0), const Color(0xFF475569)),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Text(
        (status ?? 'unknown').replaceAll('_', ' '),
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: fg),
      ),
    );
  }
}

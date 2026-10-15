import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_client.dart';
import '../../core/money.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final notificationsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).get('/notifications');
  return Map<String, dynamic>.from(res.data as Map);
});

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notifications = ref.watch(notificationsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton(
            onPressed: () async {
              try {
                await ref.read(apiClientProvider).post('/notifications/read-all');
                ref.invalidate(notificationsProvider);
              } on ApiException {/* keep UI quiet; pull-to-refresh retries */}
            },
            child: const Text('Mark all read', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
      body: RemoteView(
        value: notifications,
        onRefresh: () async => ref.invalidate(notificationsProvider),
        builder: (page) {
          final items = List<Map<String, dynamic>>.from(page['data'] ?? []);
          if (items.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 80),
              EmptyState(icon: Icons.notifications_none, message: 'No notifications.'),
            ]);
          }
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            itemBuilder: (_, i) {
              final n = items[i];
              final unread = n['read'] == false;
              return Card(
                color: unread ? const Color(0xFFEFF6FF) : null,
                child: ListTile(
                  leading: Icon(
                    unread ? Icons.circle : Icons.circle_outlined,
                    size: 12,
                    color: unread ? const Color(0xFF285D9A) : Colors.grey,
                  ),
                  title: Text(n['title'] ?? '',
                      style: TextStyle(
                          fontWeight: unread ? FontWeight.w600 : FontWeight.normal)),
                  subtitle: Text('${n['body'] ?? ''}\n${fmtDateTime(n['created_at'])}'),
                  isThreeLine: true,
                  onTap: unread
                      ? () async {
                          await ref
                              .read(apiClientProvider)
                              .post('/notifications/${n['id']}/read');
                          ref.invalidate(notificationsProvider);
                        }
                      : null,
                ),
              );
            },
          );
        },
      ),
    );
  }
}

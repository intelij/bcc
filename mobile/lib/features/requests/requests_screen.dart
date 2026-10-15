import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final requestsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/requests');
  return Map<String, dynamic>.from(res.data as Map);
});

class RequestsScreen extends ConsumerWidget {
  const RequestsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final requests = ref.watch(requestsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Service requests')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/requests/new'),
        icon: const Icon(Icons.add),
        label: const Text('Report'),
      ),
      body: RemoteView(
        value: requests,
        onRefresh: () async => ref.invalidate(requestsProvider),
        builder: (page) {
          final items = List<Map<String, dynamic>>.from(page['data'] ?? []);
          if (items.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 80),
              EmptyState(
                  icon: Icons.build_outlined,
                  message: 'No service requests. Report a leak, missed collection or road damage.'),
            ]);
          }
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            itemBuilder: (_, i) {
              final r = items[i];
              return Card(
                child: ListTile(
                  title: Text('${r['category_label']} · ${r['ticket_number']}'),
                  subtitle: Text(fmtDateTime(r['created_at'])),
                  trailing: StatusChip(status: r['status']),
                  onTap: () => context.push('/requests/${r['id']}'),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

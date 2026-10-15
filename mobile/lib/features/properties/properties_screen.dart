import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final propertiesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/properties');
  return List<Map<String, dynamic>>.from(res.data['data'] ?? []);
});

class PropertiesScreen extends ConsumerWidget {
  const PropertiesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final properties = ref.watch(propertiesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('My properties')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/properties/link'),
        icon: const Icon(Icons.link),
        label: const Text('Link'),
      ),
      body: RemoteView(
        value: properties,
        onRefresh: () async => ref.invalidate(propertiesProvider),
        builder: (items) {
          if (items.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 80),
              EmptyState(
                  icon: Icons.home_work_outlined,
                  message: 'No properties linked. Link one with your municipal account number.'),
            ]);
          }
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            itemBuilder: (_, i) {
              final p = items[i];
              final link = p['link'] as Map<String, dynamic>?;
              final account = p['account'] as Map<String, dynamic>?;
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      Expanded(
                        child: Text('${p['address']}, ${p['suburb']}',
                            style: const TextStyle(fontWeight: FontWeight.w600)),
                      ),
                      StatusChip(status: link?['status'] ?? p['status']),
                    ]),
                    const SizedBox(height: 4),
                    Text('${p['reference']} · ${link?['relationship'] ?? ''}',
                        style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                    if (account != null) ...[
                      const Divider(height: 20),
                      Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                        Text(account['account_number'] ?? '',
                            style: const TextStyle(fontSize: 13)),
                        Text(
                          fmtCents(account['outstanding_cents'] as int?,
                              currency: account['currency'] as String? ?? 'USD'),
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ]),
                    ],
                  ]),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

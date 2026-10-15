import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final billsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/bills');
  return Map<String, dynamic>.from(res.data as Map);
});

class BillsScreen extends ConsumerWidget {
  const BillsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bills = ref.watch(billsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Bills')),
      body: RemoteView(
        value: bills,
        onRefresh: () async => ref.invalidate(billsProvider),
        builder: (page) {
          final items = List<Map<String, dynamic>>.from(page['data'] ?? []);
          if (items.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 80),
              EmptyState(message: 'No bills on your account yet.'),
            ]);
          }
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            itemBuilder: (_, i) {
              final b = items[i];
              return Card(
                child: ListTile(
                  title: Text(b['invoice_number'] ?? ''),
                  subtitle: Text(
                      '${b['account_number'] ?? ''} · issued ${fmtDate(b['issue_date'])} · due ${fmtDate(b['due_date'])}'),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(fmtCents(b['outstanding_cents'] as int?),
                          style: const TextStyle(fontWeight: FontWeight.bold)),
                      StatusChip(status: b['status']),
                    ],
                  ),
                  onTap: () => context.push('/bills/${b['id']}'),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

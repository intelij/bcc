import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final paymentsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/payments');
  return Map<String, dynamic>.from(res.data as Map);
});

class PaymentsScreen extends ConsumerWidget {
  const PaymentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final payments = ref.watch(paymentsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Payments')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/pay'),
        icon: const Icon(Icons.add),
        label: const Text('Pay'),
      ),
      body: RemoteView(
        value: payments,
        onRefresh: () async => ref.invalidate(paymentsProvider),
        builder: (page) {
          final items = List<Map<String, dynamic>>.from(page['data'] ?? []);
          if (items.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 80),
              EmptyState(
                  icon: Icons.payments_outlined,
                  message: 'No payments yet. Tap Pay to make your first payment.'),
            ]);
          }
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            itemBuilder: (_, i) {
              final p = items[i];
              return Card(
                child: ListTile(
                  leading: Icon(
                    p['status'] == 'successful'
                        ? Icons.check_circle_outline
                        : p['status'] == 'failed'
                            ? Icons.error_outline
                            : Icons.schedule,
                    color: p['status'] == 'successful'
                        ? Colors.green
                        : p['status'] == 'failed'
                            ? Colors.red
                            : Colors.amber.shade700,
                  ),
                  title: Text(fmtCents(p['amount_cents'] as int?,
                      currency: p['currency'] as String? ?? 'USD')),
                  subtitle: Text(
                      '${p['account_number'] ?? ''} · ${p['provider'] ?? ''} · ${fmtDateTime(p['created_at'])}'),
                  trailing: StatusChip(status: p['status']),
                  onTap: () => context.push('/pay/status/${p['uuid']}'),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

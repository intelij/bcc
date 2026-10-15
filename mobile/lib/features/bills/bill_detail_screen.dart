import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../core/theme.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final billProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
  final res = await ref.read(apiClientProvider).get('/bills/$id');
  return Map<String, dynamic>.from(res['data'] as Map);
});

class BillDetailScreen extends ConsumerWidget {
  const BillDetailScreen({super.key, required this.id});

  final int id;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bill = ref.watch(billProvider(id));

    return Scaffold(
      appBar: AppBar(title: const Text('Bill')),
      body: RemoteView(
        value: bill,
        onRefresh: () async => ref.invalidate(billProvider(id)),
        builder: (b) {
          final items = List<Map<String, dynamic>>.from(b['items'] ?? []);
          final currency = 'USD';
          final payable = ['issued', 'partially_paid', 'overdue'].contains(b['status']);

          return ListView(padding: const EdgeInsets.all(16), children: [
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(b['invoice_number'] ?? '',
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  Text('${b['account_number'] ?? ''} · ${fmtDate(b['period_start'])} – ${fmtDate(b['period_end'])}',
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                ]),
              ),
              StatusChip(status: b['status']),
            ]),
            const SizedBox(height: 16),
            Card(
              child: Column(children: [
                for (final item in items)
                  ListTile(
                    dense: true,
                    title: Text(item['description'] ?? item['charge_type'] ?? ''),
                    subtitle: Text(item['charge_type'] ?? '',
                        style: const TextStyle(fontSize: 11)),
                    trailing: Text(fmtCents(item['amount_cents'] as int?, currency: currency)),
                  ),
                const Divider(height: 1),
                _row('Previous balance', b['previous_balance_cents'], currency),
                _row('Charges', b['charges_cents'], currency),
                if ((b['adjustments_cents'] ?? 0) != 0)
                  _row('Adjustments', b['adjustments_cents'], currency),
                _row('Payments received', b['payments_cents'], currency),
                _row('Total', b['total_cents'], currency, bold: true),
                _row('Paid', b['amount_paid_cents'], currency),
                _row('Outstanding', b['outstanding_cents'], currency, bold: true,
                    highlight: true),
              ]),
            ),
            const SizedBox(height: 8),
            Text('Due date: ${fmtDate(b['due_date'])}',
                style: TextStyle(color: Colors.grey.shade700)),
            const SizedBox(height: 20),
            if (payable && (b['outstanding_cents'] ?? 0) > 0)
              FilledButton.icon(
                onPressed: () => context.push('/pay', extra: b['account_id']),
                icon: const Icon(Icons.payments),
                label: const Text('Pay this bill'),
              ),
          ]);
        },
      ),
    );
  }

  Widget _row(String label, dynamic cents, String currency,
      {bool bold = false, bool highlight = false}) {
    return ListTile(
      dense: true,
      title: Text(label, style: TextStyle(fontWeight: bold ? FontWeight.bold : null)),
      trailing: Text(fmtCents(cents as int?, currency: currency),
          style: TextStyle(
              fontWeight: bold ? FontWeight.bold : null,
              color: highlight ? Brand.primary : null)),
    );
  }
}

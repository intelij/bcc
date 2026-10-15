import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../core/theme.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

/// Dashboard payload — GET /dashboard. Uses the cached GET fallback so the
/// summary stays viewable on flaky connections.
final dashboardProvider =
    FutureProvider.autoDispose<({Map<String, dynamic> data, bool fromCache})>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/dashboard');
  return (data: res.data as Map<String, dynamic>, fromCache: res.fromCache);
});

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dash = ref.watch(dashboardProvider);
    final user = ref.watch(authProvider).user;

    return Scaffold(
      appBar: AppBar(
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('Bulawayo City Council', style: TextStyle(fontSize: 15)),
          Text('Hello, ${user?.name.split(' ').first ?? 'resident'}',
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.normal)),
        ]),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () => context.push('/notifications'),
          ),
        ],
      ),
      body: RemoteView(
        value: dash,
        fromCache: dash.value?.fromCache ?? false,
        onRefresh: () async => ref.invalidate(dashboardProvider),
        builder: (data) {
          final account = data['account'] as Map<String, dynamic>?;
          final summary = data['summary'] as Map<String, dynamic>? ?? {};
          final properties = List<Map<String, dynamic>>.from(data['properties'] ?? []);
          final bills = List<Map<String, dynamic>>.from(data['bills'] ?? []);
          final interruptions =
              List<Map<String, dynamic>>.from(data['water_interruptions'] ?? []);
          final currency = account?['currency'] as String? ?? 'USD';

          if (properties.isEmpty) {
            return ListView(children: [
              const SizedBox(height: 48),
              const EmptyState(
                icon: Icons.home_work_outlined,
                message: 'No linked municipal accounts yet.\nLink a property to see bills and balances.',
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: FilledButton.icon(
                  onPressed: () => context.push('/properties/link'),
                  icon: const Icon(Icons.link),
                  label: const Text('Link a property'),
                ),
              ),
            ]);
          }

          return ListView(padding: const EdgeInsets.all(16), children: [
            if (data['active_disconnection'] == true)
              _banner('Disconnection notice active on your account',
                  Brand.danger, Icons.warning_amber),
            // ---- balances ----
            Card(
              color: Brand.primary,
              child: Padding(
                padding: const EdgeInsets.all(18),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  const Text('Outstanding balance',
                      style: TextStyle(color: Colors.white70, fontSize: 13)),
                  Text(fmtCents(summary['outstanding_cents'] as int?, currency: currency),
                      style: const TextStyle(
                          color: Colors.white, fontSize: 30, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  Row(children: [
                    _mini('Rates', summary['rates_cents'], currency),
                    _mini('Water', summary['water_cents'], currency),
                    _mini('Overdue', summary['overdue_cents'], currency,
                        warn: (summary['overdue_cents'] ?? 0) > 0),
                  ]),
                  if (summary['next_due_date'] != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 10),
                      child: Text('Next due: ${fmtDate(summary['next_due_date'])}',
                          style: const TextStyle(color: Colors.white70, fontSize: 12)),
                    ),
                ]),
              ),
            ),
            const SizedBox(height: 16),
            // ---- quick actions ----
            GridView.count(
              crossAxisCount: 3, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 8, crossAxisSpacing: 8, childAspectRatio: 1.1,
              children: [
                _action(context, Icons.payments, 'Pay', () => context.push('/pay')),
                _action(context, Icons.receipt_long, 'Bills', () => context.go('/bills')),
                _action(context, Icons.build, 'Report', () => context.push('/requests/new')),
                _action(context, Icons.water_drop, 'Water', () => context.push('/water')),
                _action(context, Icons.delete_outline, 'Refuse', () => context.push('/refuse')),
                _action(context, Icons.campaign, 'Updates', () => context.push('/updates')),
              ],
            ),
            const SizedBox(height: 16),
            if (interruptions.isNotEmpty) ...[
              _sectionTitle(context, 'Water alerts'),
              for (final i in interruptions)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.water_drop_outlined, color: Brand.secondary),
                  title: Text(i['title'] ?? '', style: const TextStyle(fontSize: 14)),
                  subtitle: Text(
                    '${i['area'] ?? ''}${i['expected_restoration_at'] != null ? ' · restores ~${fmtDateTime(i['expected_restoration_at'])}' : ''}',
                    style: const TextStyle(fontSize: 12),
                  ),
                  trailing: StatusChip(status: i['status']),
                ),
            ],
            _sectionTitle(context, 'Latest bills'),
            if (bills.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Text('No bills yet.', style: TextStyle(color: Colors.grey)),
              )
            else
              for (final b in bills.take(3))
                Card(
                  child: ListTile(
                    title: Text(b['invoice_number'] ?? ''),
                    subtitle: Text('Due ${fmtDate(b['due_date'])}'),
                    trailing: Column(mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end, children: [
                      Text(fmtCents(b['outstanding_cents'] as int?, currency: currency),
                          style: const TextStyle(fontWeight: FontWeight.bold)),
                      StatusChip(status: b['status']),
                    ]),
                    onTap: () => context.push('/bills/${b['id']}'),
                  ),
                ),
            if ((summary['last_payment'] as Map<String, dynamic>?)?['paid_at'] != null)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  'Last payment: ${fmtCents(summary['last_payment']['amount_cents'] as int?, currency: currency)} on ${fmtDate(summary['last_payment']['paid_at'])}',
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                ),
              ),
          ]);
        },
      ),
    );
  }

  Widget _mini(String label, dynamic cents, String currency, {bool warn = false}) {
    return Expanded(
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label, style: const TextStyle(color: Colors.white60, fontSize: 11)),
        Text(fmtCents(cents as int?, currency: currency),
            style: TextStyle(
                color: warn ? Brand.gold : Colors.white,
                fontWeight: FontWeight.w600, fontSize: 14)),
      ]),
    );
  }

  Widget _sectionTitle(BuildContext context, String text) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Text(text, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
      );

  Widget _action(BuildContext context, IconData icon, String label, VoidCallback onTap) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(icon, color: Brand.secondary),
          const SizedBox(height: 6),
          Text(label, style: const TextStyle(fontSize: 12)),
        ]),
      ),
    );
  }

  Widget _banner(String text, Color color, IconData icon) => Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: color)),
        child: Row(children: [
          Icon(icon, color: color),
          const SizedBox(width: 8),
          Expanded(child: Text(text, style: TextStyle(color: color, fontSize: 13))),
        ]),
      );
}

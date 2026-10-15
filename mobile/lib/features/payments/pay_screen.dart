import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:uuid/uuid.dart';

import '../../core/api_client.dart';
import '../../core/money.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final _payDataProvider =
    FutureProvider.autoDispose<({List<dynamic> accounts, List<dynamic> providers})>((ref) async {
  final api = ref.read(apiClientProvider);
  // Properties carry their account; providers come from the server so the
  // app never hardcodes gateway logic.
  final props = await api.get('/properties');
  final provs = await api.get('/payments/providers');
  return (
    accounts: List<dynamic>.from(props['data'] ?? []),
    providers: List<dynamic>.from(provs['data'] ?? []),
  );
});

/// Payment initiation. The screen generates ONE idempotency key per form
/// mount — re-tapping Pay (double-tap, retry after network loss) reuses the
/// same attempt server-side instead of creating duplicates.
class PayScreen extends ConsumerStatefulWidget {
  const PayScreen({super.key, this.accountId});

  final int? accountId;

  @override
  ConsumerState<PayScreen> createState() => _PayScreenState();
}

class _PayScreenState extends ConsumerState<PayScreen> {
  final _amount = TextEditingController();
  final _idempotencyKey = const Uuid().v4();
  int? _accountId;
  String? _provider;
  bool _busy = false;
  String? _error;

  Future<void> _pay() async {
    if (_accountId == null || _provider == null || _amount.text.trim().isEmpty) {
      setState(() => _error = 'Choose an account, provider and amount.');
      return;
    }
    final amount = double.tryParse(_amount.text.trim());
    if (amount == null || amount < 0.5) {
      setState(() => _error = 'Enter an amount of at least 0.50.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final res = await ref.read(apiClientProvider).post('/payments', data: {
        'municipal_account_id': _accountId,
        'amount': amount,
        'provider': _provider,
        'idempotency_key': _idempotencyKey,
      });
      if (!mounted) return;
      final uuid = res['data']['uuid'] as String;
      final initiation = res['initiation'] as Map<String, dynamic>?;

      // Redirect-type providers (e.g. the mock gateway) open a hosted page;
      // the authoritative result still arrives via the signed webhook.
      if (initiation?['type'] == 'redirect' && initiation?['redirect_url'] != null) {
        await launchUrl(Uri.parse(initiation!['redirect_url'] as String),
            mode: LaunchMode.externalApplication);
      }
      if (mounted) context.pushReplacement('/pay/status/$uuid');
    } on ApiException catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = e.message;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final data = ref.watch(_payDataProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Make a payment')),
      body: RemoteView(
        value: data,
        builder: (d) {
          // Only payable accounts (approved link + can_pay capability).
          final accounts = d.accounts
              .where((p) =>
                  p['link']?['status'] == 'approved' &&
                  p['link']?['capabilities']?['pay'] == true &&
                  p['account'] != null)
              .toList();
          _accountId ??= widget.accountId ?? (accounts.isNotEmpty
              ? accounts.first['account']['id'] as int?
              : null);
          _provider ??= d.providers.isNotEmpty
              ? d.providers.first['key'] as String?
              : null;

          if (accounts.isEmpty) {
            return const EmptyState(
                message: 'No payable accounts. Link a property first.');
          }

          return ListView(padding: const EdgeInsets.all(16), children: [
            if (_error != null)
              Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                    color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)),
                child: Text(_error!, style: const TextStyle(color: Color(0xFF991B1B))),
              ),
            DropdownButtonFormField<int>(
              initialValue: _accountId,
              decoration: const InputDecoration(labelText: 'Account'),
              items: [
                for (final p in accounts)
                  DropdownMenuItem(
                    value: p['account']['id'] as int,
                    child: Text(
                        '${p['account']['account_number']} — ${p['address']}\n(balance ${fmtCents(p['account']['outstanding_cents'] as int?, currency: p['account']['currency'] as String? ?? 'USD')})',
                        style: const TextStyle(fontSize: 13)),
                  ),
              ],
              onChanged: (v) => setState(() => _accountId = v),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _amount,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                  labelText: 'Amount', prefixText: 'USD ', hintText: '0.00'),
            ),
            const SizedBox(height: 16),
            const Text('Payment method',
                style: TextStyle(fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            RadioGroup<String>(
              groupValue: _provider,
              onChanged: (v) => setState(() => _provider = v),
              child: Column(children: [
                for (final p in d.providers)
                  Card(
                    child: RadioListTile<String>(
                      value: p['key'] as String,
                      title: Text(p['label'] as String? ?? p['key'] as String),
                    ),
                  ),
              ]),
            ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: _busy ? null : _pay,
              icon: const Icon(Icons.lock_outline),
              label: Text(_busy ? 'Processing…' : 'Pay'),
            ),
            const SizedBox(height: 8),
            Text(
              'Your payment is confirmed by the provider, not by this screen — you will see the verified status on the next page.',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
            ),
          ]);
        },
      ),
    );
  }
}

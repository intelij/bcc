import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../state/providers.dart';

final _prefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).get('/preferences');
  return Map<String, dynamic>.from(res as Map);
});

final _accountsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final res = await ref.read(apiClientProvider).get('/properties');
  return List<Map<String, dynamic>>.from(res['data'] ?? [])
      .where((p) =>
          p['account'] != null &&
          p['link']?['capabilities']?['view_statements'] == true)
      .toList();
});

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;

    return Scaffold(
      appBar: AppBar(title: const Text('Account')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        ListTile(
          leading: CircleAvatar(
            backgroundColor: Brand.primary.withValues(alpha: 0.1),
            child: Text(
                (user?.name.isNotEmpty == true ? user!.name[0] : '?').toUpperCase(),
                style: const TextStyle(color: Brand.primary)),
          ),
          title: Text(user?.name ?? 'Resident',
              style: const TextStyle(fontWeight: FontWeight.w600)),
          subtitle: Text(user?.email ?? ''),
        ),
        const SizedBox(height: 8),
        _nav(context, Icons.home_work_outlined, 'My properties', '/properties'),
        _nav(context, Icons.water_drop_outlined, 'Water services', '/water'),
        _nav(context, Icons.delete_outline, 'Refuse collection', '/refuse'),
        _nav(context, Icons.campaign_outlined, 'City updates', '/updates'),
        const Divider(height: 24),
        _StatementsTile(),
        const Divider(height: 24),
        _PreferencesTile(),
        const Divider(height: 24),
        ListTile(
          leading: const Icon(Icons.logout, color: Brand.danger),
          title: const Text('Sign out', style: TextStyle(color: Brand.danger)),
          onTap: () async {
            await ref.read(authProvider.notifier).logout();
          },
        ),
        Padding(
          padding: const EdgeInsets.all(16),
          child: Text('Bulawayo City Council digital services · fictional demo data',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
        ),
      ]),
    );
  }

  Widget _nav(BuildContext context, IconData icon, String label, String path) {
    return ListTile(
      leading: Icon(icon, color: Brand.secondary),
      title: Text(label),
      trailing: const Icon(Icons.chevron_right),
      onTap: () => context.push(path),
    );
  }
}

/// Download a statement PDF for any statement-visible account.
class _StatementsTile extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final accounts = ref.watch(_accountsProvider);

    return ExpansionTile(
      leading: const Icon(Icons.description_outlined, color: Brand.secondary),
      title: const Text('Statements'),
      subtitle: const Text('Download account statement PDFs',
          style: TextStyle(fontSize: 12)),
      children: [
        accounts.when(
          loading: () => const Padding(
              padding: EdgeInsets.all(12), child: CircularProgressIndicator()),
          error: (e, _) => Padding(
              padding: const EdgeInsets.all(12), child: Text('$e')),
          data: (props) => props.isEmpty
              ? const Padding(
                  padding: EdgeInsets.all(12),
                  child: Text('No accounts with statement access.'))
              : Column(children: [
                  for (final p in props)
                    ListTile(
                      dense: true,
                      title: Text(p['account']['account_number'] ?? ''),
                      subtitle: Text('${p['address']}, ${p['suburb']}',
                          style: const TextStyle(fontSize: 12)),
                      trailing: IconButton(
                        icon: const Icon(Icons.download, color: Brand.secondary),
                        onPressed: () => _download(context, ref, p['account']['id'] as int,
                            p['account']['account_number'] as String),
                      ),
                    ),
                ]),
        ),
      ],
    );
  }

  Future<void> _download(
      BuildContext context, WidgetRef ref, int accountId, String accountNumber) async {
    try {
      final dio = ref.read(apiClientProvider).dio;
      final res = await dio.get<List<int>>('/statements/$accountId/pdf',
          options: Options(responseType: ResponseType.bytes));
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/statement-$accountNumber.pdf');
      await file.writeAsBytes(res.data as List<int>);
      await OpenFilex.open(file.path);
    } on DioException catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text((e.response?.data is Map
                    ? e.response?.data['message']
                    : null) ??
                'Statement download failed.')));
      }
    }
  }
}

/// Per-event notification channel preferences (in_app/email/sms/whatsapp).
class _PreferencesTile extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final prefs = ref.watch(_prefsProvider);

    return ExpansionTile(
      leading: const Icon(Icons.tune, color: Brand.secondary),
      title: const Text('Notification preferences'),
      children: [
        prefs.when(
          loading: () => const Padding(
              padding: EdgeInsets.all(12), child: CircularProgressIndicator()),
          error: (e, _) => Padding(padding: const EdgeInsets.all(12), child: Text('$e')),
          data: (d) {
            final events = List<String>.from(d['events'] ?? []);
            final channels = List<String>.from(d['channels'] ?? []);
            final matrix = Map<String, dynamic>.from(d['data'] ?? {});
            return Column(children: [
              for (final event in events)
                ExpansionTile(
                  dense: true,
                  title: Text(event.replaceAll('_', ' ').toLowerCase(),
                      style: const TextStyle(fontSize: 13)),
                  children: [
                    for (final channel in channels)
                      SwitchListTile(
                        dense: true,
                        title: Text(channel.replaceAll('_', ' '),
                            style: const TextStyle(fontSize: 12)),
                        value: matrix[event]?[channel] == true,
                        onChanged: (v) async {
                          try {
                            await ref.read(apiClientProvider).put('/preferences',
                                data: {
                                  'preferences': {
                                    event: {channel: v}
                                  }
                                });
                            ref.invalidate(_prefsProvider);
                          } on ApiException {/* toggle stays; next refresh resyncs */}
                        },
                      ),
                  ],
                ),
            ]);
          },
        ),
      ],
    );
  }
}

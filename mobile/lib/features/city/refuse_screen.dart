import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/money.dart';
import '../../core/theme.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final refuseProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/refuse');
  return Map<String, dynamic>.from(res.data as Map);
});

const _days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

class RefuseScreen extends ConsumerWidget {
  const RefuseScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final refuse = ref.watch(refuseProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Refuse collection')),
      body: RemoteView(
        value: refuse,
        onRefresh: () async => ref.invalidate(refuseProvider),
        builder: (d) {
          final schedules = List<Map<String, dynamic>>.from(d['data'] ?? []);
          if (schedules.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 80),
              EmptyState(
                  icon: Icons.delete_outline,
                  message: 'No collection schedule for your area yet.'),
            ]);
          }
          return ListView(padding: const EdgeInsets.all(16), children: [
            for (final s in schedules)
              Card(
                child: ListTile(
                  leading: const Icon(Icons.delete_outline, color: Brand.secondary),
                  title: Text('${s['area']}${s['suburb'] != null ? ' — ${s['suburb']}' : ''}'),
                  subtitle: Text(
                    '${_days[s['collection_day'] as int? ?? 0]}s · ${s['frequency']}'
                    '${s['next_collection_date'] != null ? '\nNext: ${fmtDate(s['next_collection_date'])}' : ''}'
                    '${s['notes'] != null ? '\n${s['notes']}' : ''}',
                  ),
                  isThreeLine: s['next_collection_date'] != null || s['notes'] != null,
                  trailing: StatusChip(status: s['status']),
                ),
              ),
          ]);
        },
      ),
    );
  }
}

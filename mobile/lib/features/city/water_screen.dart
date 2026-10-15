import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/money.dart';
import '../../core/theme.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final waterProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/water');
  return Map<String, dynamic>.from(res.data as Map);
});

const _days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

class WaterScreen extends ConsumerWidget {
  const WaterScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final water = ref.watch(waterProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Water services')),
      body: RemoteView(
        value: water,
        onRefresh: () async => ref.invalidate(waterProvider),
        builder: (d) {
          final current = List<Map<String, dynamic>>.from(d['interruptions'] ?? []);
          final resolved = List<Map<String, dynamic>>.from(d['recently_resolved'] ?? []);
          final schedules = List<Map<String, dynamic>>.from(d['schedules'] ?? []);

          return ListView(padding: const EdgeInsets.all(16), children: [
            const Text('Current & planned interruptions',
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
            const SizedBox(height: 8),
            if (current.isEmpty)
              const Card(
                child: ListTile(
                  leading: Icon(Icons.check_circle_outline, color: Colors.green),
                  title: Text('No active interruptions in your area'),
                ),
              )
            else
              for (final i in current)
                Card(
                  child: ListTile(
                    leading: Icon(
                      i['type'] == 'emergency' ? Icons.error_outline : Icons.schedule,
                      color: i['type'] == 'emergency' ? Brand.danger : Brand.secondary,
                    ),
                    title: Text(i['title'] ?? ''),
                    subtitle: Text(
                      '${i['area'] ?? ''}${i['suburb'] != null ? ' · ${i['suburb']}' : ''}'
                      '\nFrom ${fmtDateTime(i['starts_at'])}'
                      '${i['expected_restoration_at'] != null ? ' · restores ~${fmtDateTime(i['expected_restoration_at'])}' : ''}'
                      '${i['reason'] != null ? '\n${i['reason']}' : ''}',
                    ),
                    isThreeLine: true,
                    trailing: StatusChip(status: i['status']),
                  ),
                ),
            const SizedBox(height: 20),
            const Text('Shedding schedule',
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
            const SizedBox(height: 8),
            if (schedules.isEmpty)
              Text('No scheduled water shedding for your area.',
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 13))
            else
              for (final s in schedules)
                ListTile(
                  dense: true,
                  leading: const Icon(Icons.water_drop_outlined, color: Brand.secondary),
                  title: Text('${s['area']}${s['suburb'] != null ? ' — ${s['suburb']}' : ''}'),
                  subtitle: Text(
                      '${_days[s['day_of_week'] as int? ?? 0]} · ${s['start_time']}–${s['end_time']}'),
                ),
            if (resolved.isNotEmpty) ...[
              const SizedBox(height: 20),
              const Text('Recently restored',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
              for (final i in resolved)
                ListTile(
                  dense: true,
                  leading: const Icon(Icons.check, color: Colors.green),
                  title: Text(i['title'] ?? ''),
                  subtitle: Text('Resolved ${fmtDateTime(i['resolved_at'])}'),
                ),
            ],
          ]);
        },
      ),
    );
  }
}

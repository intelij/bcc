import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/money.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final _announcementProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, String>((ref, slug) async {
  final res = await ref.read(apiClientProvider).get('/announcements/$slug');
  return Map<String, dynamic>.from(res['data'] as Map);
});

class AnnouncementScreen extends ConsumerWidget {
  const AnnouncementScreen({super.key, required this.slug});

  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final announcement = ref.watch(_announcementProvider(slug));

    return Scaffold(
      appBar: AppBar(title: const Text('Announcement')),
      body: RemoteView(
        value: announcement,
        builder: (a) => ListView(padding: const EdgeInsets.all(20), children: [
          Text(a['title'] ?? '',
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text('${a['category'] ?? ''} · ${fmtDate(a['published_at'])}',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
          const SizedBox(height: 16),
          Text(a['body'] ?? '', style: const TextStyle(fontSize: 15, height: 1.5)),
        ]),
      ),
    );
  }
}

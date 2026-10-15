import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

class _CategoryNotifier extends Notifier<String?> {
  @override
  String? build() => null;

  void select(String? category) => state = category;
}

final _categoryProvider =
    NotifierProvider<_CategoryNotifier, String?>(_CategoryNotifier.new);

final announcementsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final category = ref.watch(_categoryProvider);
  final res = await ref.read(apiClientProvider).getCached('/announcements',
      query: {'category': ?category});
  return Map<String, dynamic>.from(res.data as Map);
});

final _metaProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/meta');
  return Map<String, dynamic>.from(res.data as Map);
});

class AnnouncementsScreen extends ConsumerWidget {
  const AnnouncementsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final announcements = ref.watch(announcementsProvider);
    final meta = ref.watch(_metaProvider);
    final selected = ref.watch(_categoryProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('City updates')),
      body: Column(children: [
        meta.maybeWhen(
          data: (m) {
            final cats = Map<String, dynamic>.from(m['announcement_categories'] ?? {});
            return SizedBox(
              height: 48,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                children: [
                  Padding(
                    padding: const EdgeInsets.only(right: 6),
                    child: ChoiceChip(
                      label: const Text('All'),
                      selected: selected == null,
                      onSelected: (_) =>
                          ref.read(_categoryProvider.notifier).select(null),
                    ),
                  ),
                  for (final e in cats.entries)
                    Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: ChoiceChip(
                        label: Text(e.value.toString()),
                        selected: selected == e.key,
                        onSelected: (_) =>
                            ref.read(_categoryProvider.notifier).select(e.key),
                      ),
                    ),
                ],
              ),
            );
          },
          orElse: () => const SizedBox(height: 8),
        ),
        Expanded(
          child: RemoteView(
            value: announcements,
            onRefresh: () async => ref.invalidate(announcementsProvider),
            builder: (page) {
              final items = List<Map<String, dynamic>>.from(page['data'] ?? []);
              if (items.isEmpty) {
                return ListView(children: const [
                  SizedBox(height: 80),
                  EmptyState(icon: Icons.campaign_outlined, message: 'No announcements.'),
                ]);
              }
              return ListView.builder(
                padding: const EdgeInsets.all(12),
                itemCount: items.length,
                itemBuilder: (_, i) {
                  final a = items[i];
                  return Card(
                    child: ListTile(
                      title: Text(a['title'] ?? ''),
                      subtitle: Text(fmtDate(a['published_at'])),
                      trailing: a['priority'] == 'urgent' || a['priority'] == 'high'
                          ? const StatusChip(status: 'urgent')
                          : null,
                      onTap: () => context.push('/updates/${a['slug']}'),
                    ),
                  );
                },
              );
            },
          ),
        ),
      ]),
    );
  }
}

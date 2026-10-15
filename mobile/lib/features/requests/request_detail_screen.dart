import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_client.dart';
import '../../core/money.dart';
import '../../core/theme.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final _requestProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
  final res = await ref.read(apiClientProvider).get('/requests/$id');
  return Map<String, dynamic>.from(res['data'] as Map);
});

class RequestDetailScreen extends ConsumerStatefulWidget {
  const RequestDetailScreen({super.key, required this.id});

  final int id;

  @override
  ConsumerState<RequestDetailScreen> createState() => _RequestDetailScreenState();
}

class _RequestDetailScreenState extends ConsumerState<RequestDetailScreen> {
  final _comment = TextEditingController();
  bool _sending = false;

  Future<void> _send() async {
    if (_comment.text.trim().isEmpty) return;
    setState(() => _sending = true);
    try {
      await ref.read(apiClientProvider).post(
          '/requests/${widget.id}/comments',
          data: {'body': _comment.text.trim()});
      _comment.clear();
      ref.invalidate(_requestProvider(widget.id));
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    }
    if (mounted) setState(() => _sending = false);
  }

  @override
  Widget build(BuildContext context) {
    final request = ref.watch(_requestProvider(widget.id));

    return Scaffold(
      appBar: AppBar(title: const Text('Request')),
      body: RemoteView(
        value: request,
        onRefresh: () async => ref.invalidate(_requestProvider(widget.id)),
        builder: (r) {
          final comments = List<Map<String, dynamic>>.from(r['comments'] ?? []);
          return Column(children: [
            Expanded(
              child: ListView(padding: const EdgeInsets.all(16), children: [
                Row(children: [
                  Expanded(
                    child: Text('${r['category_label']} · ${r['ticket_number']}',
                        style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
                  ),
                  StatusChip(status: r['status']),
                ]),
                const SizedBox(height: 8),
                Text(r['description'] ?? ''),
                if (r['location'] != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Row(children: [
                      const Icon(Icons.place_outlined, size: 16, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text(r['location'], style: const TextStyle(fontSize: 13)),
                    ]),
                  ),
                Text('Submitted ${fmtDateTime(r['created_at'])}',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                const Divider(height: 32),
                const Text('Updates', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                if (comments.isEmpty)
                  Text('No updates yet.',
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 13))
                else
                  for (final c in comments)
                    Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: c['staff'] == true
                            ? Brand.sky.withValues(alpha: 0.12)
                            : Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('${c['author'] ?? ''}${c['staff'] == true ? ' (council)' : ''}',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                        const SizedBox(height: 2),
                        Text(c['body'] ?? ''),
                        Text(fmtDateTime(c['created_at']),
                            style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
                      ]),
                    ),
              ]),
            ),
            SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 4, 12, 8),
                child: Row(children: [
                  Expanded(
                    child: TextField(
                      controller: _comment,
                      decoration: const InputDecoration(
                          hintText: 'Add a comment…', isDense: true),
                    ),
                  ),
                  IconButton(
                    onPressed: _sending ? null : _send,
                    icon: const Icon(Icons.send, color: Brand.primary),
                  ),
                ]),
              ),
            ),
          ]);
        },
      ),
    );
  }
}

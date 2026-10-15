import 'dart:async';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_client.dart';
import '../../core/money.dart';
import '../../core/theme.dart';
import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final _paymentProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, String>((ref, uuid) async {
  final res = await ref.read(apiClientProvider).get('/payments/$uuid');
  return Map<String, dynamic>.from(res['data'] as Map);
});

/// Authoritative payment status — polled while pending/processing.
/// Bank-transfer payments can attach a proof document for finance review.
class PaymentStatusScreen extends ConsumerStatefulWidget {
  const PaymentStatusScreen({super.key, required this.uuid});

  final String uuid;

  @override
  ConsumerState<PaymentStatusScreen> createState() => _PaymentStatusScreenState();
}

class _PaymentStatusScreenState extends ConsumerState<PaymentStatusScreen> {
  Timer? _poller;
  double _uploadProgress = 0;

  @override
  void initState() {
    super.initState();
    // Poll while the payment is non-final; the webhook drives transitions.
    _poller = Timer.periodic(const Duration(seconds: 4), (_) {
      final p = ref.read(_paymentProvider(widget.uuid)).value;
      if (p == null || ['pending', 'processing'].contains(p['status'])) {
        ref.invalidate(_paymentProvider(widget.uuid));
      } else {
        _poller?.cancel();
      }
    });
  }

  @override
  void dispose() {
    _poller?.cancel();
    super.dispose();
  }

  Future<void> _uploadProof() async {
    final choice = await showModalBottomSheet<String>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          ListTile(
            leading: const Icon(Icons.photo_camera),
            title: const Text('Take a photo'),
            onTap: () => Navigator.pop(ctx, 'camera'),
          ),
          ListTile(
            leading: const Icon(Icons.photo_library),
            title: const Text('Choose from gallery'),
            onTap: () => Navigator.pop(ctx, 'gallery'),
          ),
          ListTile(
            leading: const Icon(Icons.insert_drive_file),
            title: const Text('Choose a PDF'),
            onTap: () => Navigator.pop(ctx, 'file'),
          ),
        ]),
      ),
    );
    if (choice == null) return;

    String? path;
    if (choice == 'file') {
      final files = await FilePicker.pickFiles(
          type: FileType.custom, allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png']);
      path = files.isNotEmpty ? files.first.path : null;
    } else {
      final x = await ImagePicker().pickImage(
          source: choice == 'camera' ? ImageSource.camera : ImageSource.gallery,
          imageQuality: 80);
      path = x?.path;
    }
    if (path == null || !mounted) return;

    try {
      await ref.read(apiClientProvider).upload(
            '/payments/${widget.uuid}/proof',
            'proof',
            path,
            onProgress: (sent, total) =>
                setState(() => _uploadProgress = total > 0 ? sent / total : 0),
          );
      if (mounted) {
        setState(() => _uploadProgress = 0);
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Proof uploaded — pending finance review.')));
        ref.invalidate(_paymentProvider(widget.uuid));
      }
    } on ApiException catch (e) {
      if (mounted) {
        setState(() => _uploadProgress = 0);
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final payment = ref.watch(_paymentProvider(widget.uuid));

    return Scaffold(
      appBar: AppBar(title: const Text('Payment status')),
      body: RemoteView(
        value: payment,
        onRefresh: () async => ref.invalidate(_paymentProvider(widget.uuid)),
        builder: (p) {
          final status = p['status'] as String? ?? 'pending';
          final instructions =
              (p['initiation'] as Map?)?['instructions'] as String?;

          return ListView(padding: const EdgeInsets.all(16), children: [
            Center(child: _statusIcon(status)),
            const SizedBox(height: 12),
            Center(
              child: Text(
                fmtCents(p['amount_cents'] as int?,
                    currency: p['currency'] as String? ?? 'USD'),
                style: const TextStyle(fontSize: 32, fontWeight: FontWeight.bold),
              ),
            ),
            Center(child: StatusChip(status: status)),
            const SizedBox(height: 20),
            _row('Account', p['account_number']),
            _row('Provider', p['provider']),
            _row('Reference', p['provider_reference'] ?? p['reference']),
            _row('Submitted', fmtDateTime(p['created_at'])),
            if (p['paid_at'] != null) _row('Paid', fmtDateTime(p['paid_at'])),
            if (p['failure_reason'] != null)
              _row('Reason', p['failure_reason']),
            if (instructions != null) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    const Text('Bank transfer instructions',
                        style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    Text(instructions, style: const TextStyle(fontSize: 13)),
                  ]),
                ),
              ),
            ],
            if (p['provider'] == 'bank_transfer' && status == 'pending') ...[
              const SizedBox(height: 16),
              if (_uploadProgress > 0)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: LinearProgressIndicator(value: _uploadProgress),
                ),
              OutlinedButton.icon(
                onPressed: _uploadProgress > 0 ? null : _uploadProof,
                icon: const Icon(Icons.upload_file),
                label: const Text('Upload proof of payment'),
              ),
            ],
            const SizedBox(height: 16),
            if (['pending', 'processing'].contains(status))
              const Center(
                child: Text('Waiting for provider confirmation…',
                    style: TextStyle(fontSize: 12, color: Colors.grey)),
              ),
          ]);
        },
      ),
    );
  }

  Widget _row(String label, dynamic value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(label, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
          Flexible(child: Text(value?.toString() ?? '—', textAlign: TextAlign.end)),
        ]),
      );

  Widget _statusIcon(String status) => switch (status) {
        'successful' => const Icon(Icons.check_circle, size: 64, color: Colors.green),
        'failed' || 'cancelled' => const Icon(Icons.cancel, size: 64, color: Colors.red),
        'refunded' => const Icon(Icons.undo, size: 64, color: Colors.orange),
        _ => const SizedBox(
            height: 64, width: 64,
            child: CircularProgressIndicator(color: Brand.secondary)),
      };
}

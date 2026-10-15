import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api_client.dart';
import '../../state/providers.dart';

/// Request a property↔user link. Stays `pending` until council staff verify
/// it — the API never grants access on submission alone.
class LinkPropertyScreen extends ConsumerStatefulWidget {
  const LinkPropertyScreen({super.key});

  @override
  ConsumerState<LinkPropertyScreen> createState() => _LinkPropertyScreenState();
}

class _LinkPropertyScreenState extends ConsumerState<LinkPropertyScreen> {
  final _account = TextEditingController();
  String _relationship = 'owner';
  bool _busy = false;
  String? _error;

  Future<void> _submit() async {
    if (_account.text.trim().isEmpty) {
      setState(() => _error = 'Enter your municipal account number.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(apiClientProvider).post('/properties/link', data: {
        'account_number': _account.text.trim(),
        'relationship': _relationship,
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
            content: Text('Link request submitted — pending council verification.')));
        context.pop();
      }
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
    return Scaffold(
      appBar: AppBar(title: const Text('Link a property')),
      body: ListView(padding: const EdgeInsets.all(20), children: [
        const Text(
          'Enter the municipal account number on your rates bill. The council will verify the link before access is granted.',
          style: TextStyle(fontSize: 13, color: Colors.grey),
        ),
        const SizedBox(height: 20),
        if (_error != null)
          Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
                color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)),
            child: Text(_error!, style: const TextStyle(color: Color(0xFF991B1B))),
          ),
        TextField(
          controller: _account,
          decoration: const InputDecoration(
              labelText: 'Account number', hintText: 'BYO-100001'),
          textCapitalization: TextCapitalization.characters,
        ),
        const SizedBox(height: 12),
        DropdownButtonFormField<String>(
          initialValue: _relationship,
          decoration: const InputDecoration(labelText: 'I am the'),
          items: const [
            DropdownMenuItem(value: 'owner', child: Text('Owner')),
            DropdownMenuItem(value: 'tenant', child: Text('Tenant')),
          ],
          onChanged: (v) => setState(() => _relationship = v!),
        ),
        const SizedBox(height: 24),
        FilledButton(
          onPressed: _busy ? null : _submit,
          child: Text(_busy ? 'Submitting…' : 'Submit for verification'),
        ),
      ]),
    );
  }
}

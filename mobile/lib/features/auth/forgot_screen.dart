import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../state/providers.dart';

class ForgotScreen extends ConsumerStatefulWidget {
  const ForgotScreen({super.key});

  @override
  ConsumerState<ForgotScreen> createState() => _ForgotScreenState();
}

class _ForgotScreenState extends ConsumerState<ForgotScreen> {
  final _email = TextEditingController();
  bool _busy = false;
  bool _sent = false;
  String? _error;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reset password')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: _sent
            ? const Center(
                child: Text('If that address is registered, a reset link has been sent.',
                    textAlign: TextAlign.center))
            : Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                if (_error != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Text(_error!, style: const TextStyle(color: Colors.red)),
                  ),
                TextField(
                  controller: _email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(labelText: 'Email'),
                ),
                const SizedBox(height: 20),
                FilledButton(
                  onPressed: _busy
                      ? null
                      : () async {
                          setState(() => _busy = true);
                          final error = await ref
                              .read(authProvider.notifier)
                              .forgotPassword(_email.text.trim());
                          if (mounted) {
                            setState(() {
                              _busy = false;
                              _sent = error == null;
                              _error = error;
                            });
                          }
                        },
                  child: Text(_busy ? 'Sending…' : 'Send reset link'),
                ),
              ]),
      ),
    );
  }
}

import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../state/providers.dart';
import '../../widgets/remote_view.dart';

final _metaProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ref.read(apiClientProvider).getCached('/meta');
  return Map<String, dynamic>.from(res.data as Map);
});

final _linkablePropertiesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final res = await ref.read(apiClientProvider).get('/properties');
  return List<Map<String, dynamic>>.from(res['data'] ?? [])
      .where((p) =>
          p['link']?['status'] == 'approved' &&
          p['link']?['capabilities']?['submit_requests'] == true)
      .toList();
});

class RequestCreateScreen extends ConsumerStatefulWidget {
  const RequestCreateScreen({super.key});

  @override
  ConsumerState<RequestCreateScreen> createState() => _RequestCreateScreenState();
}

class _RequestCreateScreenState extends ConsumerState<RequestCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _description = TextEditingController();
  final _location = TextEditingController();
  final List<XFile> _photos = [];
  String? _category;
  int? _propertyId;
  String _priority = 'normal';
  bool _busy = false;
  String? _error;

  Future<void> _addPhoto(ImageSource source) async {
    if (_photos.length >= 3) return;
    final x = await ImagePicker().pickImage(source: source, imageQuality: 75);
    if (x != null) setState(() => _photos.add(x));
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final form = FormData.fromMap({
        'category': _category,
        'description': _description.text.trim(),
        'priority': _priority,
        if (_propertyId != null) 'property_id': _propertyId,
        if (_location.text.trim().isNotEmpty) 'location_text': _location.text.trim(),
      });
      for (final photo in _photos) {
        form.files.add(MapEntry('photos[]', await MultipartFile.fromFile(photo.path)));
      }
      final res = await ref.read(apiClientProvider).dio.post('/requests', data: form);
      if (mounted) context.pushReplacement('/requests/${res.data['data']['id']}');
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = (e.response?.data as Map?)?['message']?.toString() ??
              'Submission failed. Try again.';
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final meta = ref.watch(_metaProvider);
    final properties = ref.watch(_linkablePropertiesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Report a problem')),
      body: Form(
        key: _formKey,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          if (_error != null)
            Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                  color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)),
              child: Text(_error!, style: const TextStyle(color: Color(0xFF991B1B))),
            ),
          RemoteView(
            value: meta,
            builder: (m) {
              final categories = Map<String, dynamic>.from(m['request_categories'] ?? {});
              return DropdownButtonFormField<String>(
                initialValue: _category,
                decoration: const InputDecoration(labelText: 'Category'),
                items: [
                  for (final e in categories.entries)
                    DropdownMenuItem(value: e.key, child: Text(e.value.toString())),
                ],
                onChanged: (v) => setState(() => _category = v),
                validator: (v) => v == null ? 'Choose a category' : null,
              );
            },
          ),
          const SizedBox(height: 12),
          properties.when(
            loading: () => const SizedBox.shrink(),
            error: (_, _) => const SizedBox.shrink(),
            data: (props) => props.isEmpty
                ? const SizedBox.shrink()
                : DropdownButtonFormField<int>(
                    initialValue: _propertyId,
                    decoration: const InputDecoration(
                        labelText: 'Property (optional)'),
                    items: [
                      for (final p in props)
                        DropdownMenuItem(
                            value: p['id'] as int,
                            child: Text('${p['address']}, ${p['suburb']}')),
                    ],
                    onChanged: (v) => setState(() => _propertyId = v),
                  ),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _description,
            maxLines: 4,
            decoration: const InputDecoration(
                labelText: 'Describe the problem', alignLabelWithHint: true),
            validator: (v) =>
                (v == null || v.trim().length < 10) ? 'Please describe the issue (min 10 chars)' : null,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _location,
            decoration: const InputDecoration(
                labelText: 'Location (street, landmark)', prefixIcon: Icon(Icons.place_outlined)),
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: _priority,
            decoration: const InputDecoration(labelText: 'Priority'),
            items: const [
              DropdownMenuItem(value: 'low', child: Text('Low')),
              DropdownMenuItem(value: 'normal', child: Text('Normal')),
              DropdownMenuItem(value: 'high', child: Text('High')),
              DropdownMenuItem(value: 'urgent', child: Text('Urgent')),
            ],
            onChanged: (v) => setState(() => _priority = v!),
          ),
          const SizedBox(height: 12),
          Row(children: [
            OutlinedButton.icon(
              onPressed: () => _addPhoto(ImageSource.camera),
              icon: const Icon(Icons.photo_camera),
              label: const Text('Photo'),
            ),
            const SizedBox(width: 8),
            OutlinedButton.icon(
              onPressed: () => _addPhoto(ImageSource.gallery),
              icon: const Icon(Icons.photo_library),
              label: const Text('Gallery'),
            ),
            const Spacer(),
            Text('${_photos.length}/3', style: const TextStyle(color: Colors.grey)),
          ]),
          if (_photos.isNotEmpty)
            SizedBox(
              height: 72,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.only(top: 8),
                children: [
                  for (final p in _photos)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: Stack(children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Image.file(File(p.path), width: 72, height: 72, fit: BoxFit.cover,
                              errorBuilder: (_, _, _) => Container(
                                  width: 72, height: 72, color: Colors.grey.shade200,
                                  child: const Icon(Icons.image))),
                        ),
                        Positioned(
                          top: 0, right: 0,
                          child: GestureDetector(
                            onTap: () => setState(() => _photos.remove(p)),
                            child: const CircleAvatar(
                                radius: 10, backgroundColor: Colors.black54,
                                child: Icon(Icons.close, size: 12, color: Colors.white)),
                          ),
                        ),
                      ]),
                    ),
                ],
              ),
            ),
          const SizedBox(height: 20),
          FilledButton(
            onPressed: _busy ? null : _submit,
            child: Text(_busy ? 'Submitting…' : 'Submit request'),
          ),
        ]),
      ),
    );
  }
}

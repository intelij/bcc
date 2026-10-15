import 'package:bcc_mobile/core/money.dart';
import 'package:bcc_mobile/widgets/remote_view.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

void main() {
  group('fmtCents', () {
    test('formats cents to currency', () {
      expect(fmtCents(12345), 'USD 123.45');
      expect(fmtCents(0), 'USD 0.00');
      expect(fmtCents(-500), '-USD 5.00');
      expect(fmtCents(null), '—');
    });
  });

  group('fmtDate', () {
    test('parses ISO dates', () {
      expect(fmtDate('2026-10-02'), '2 Oct 2026');
      expect(fmtDate(null), '—');
    });
  });

  testWidgets('StatusChip renders status label', (tester) async {
    await tester.pumpWidget(const ProviderScope(
      child: MaterialApp(home: Scaffold(body: StatusChip(status: 'successful'))),
    ));
    expect(find.text('successful'), findsOneWidget);
  });
}

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';

/// Bottom-navigation shell for the five primary resident sections.
class AppShell extends StatelessWidget {
  const AppShell({super.key, required this.child});

  final Widget child;

  static const _tabs = [
    (icon: Icons.home_outlined, selected: Icons.home, label: 'Home', path: '/'),
    (icon: Icons.receipt_long_outlined, selected: Icons.receipt_long, label: 'Bills', path: '/bills'),
    (icon: Icons.payments_outlined, selected: Icons.payments, label: 'Pay', path: '/payments'),
    (icon: Icons.build_outlined, selected: Icons.build, label: 'Requests', path: '/requests'),
    (icon: Icons.person_outline, selected: Icons.person, label: 'Account', path: '/profile'),
  ];

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    final index = _tabs.indexWhere((t) => t.path == '/'
        ? location == '/'
        : location.startsWith(t.path));

    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: index < 0 ? 0 : index,
        onDestinationSelected: (i) => context.go(_tabs[i].path),
        indicatorColor: Brand.gold.withValues(alpha: 0.3),
        destinations: [
          for (final t in _tabs)
            NavigationDestination(
              icon: Icon(t.icon),
              selectedIcon: Icon(t.selected, color: Brand.primary),
              label: t.label,
            ),
        ],
      ),
    );
  }
}

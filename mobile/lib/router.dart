import 'package:flutter/foundation.dart' show ValueNotifier;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'features/auth/forgot_screen.dart';
import 'features/auth/login_screen.dart';
import 'features/auth/register_screen.dart';
import 'features/auth/splash_screen.dart';
import 'features/bills/bill_detail_screen.dart';
import 'features/bills/bills_screen.dart';
import 'features/city/announcement_screen.dart';
import 'features/city/announcements_screen.dart';
import 'features/city/refuse_screen.dart';
import 'features/city/water_screen.dart';
import 'features/home/home_screen.dart';
import 'features/notifications/notifications_screen.dart';
import 'features/payments/pay_screen.dart';
import 'features/payments/payment_status_screen.dart';
import 'features/payments/payments_screen.dart';
import 'features/profile/profile_screen.dart';
import 'features/properties/link_property_screen.dart';
import 'features/properties/properties_screen.dart';
import 'features/requests/request_create_screen.dart';
import 'features/requests/request_detail_screen.dart';
import 'features/requests/requests_screen.dart';
import 'shell/app_shell.dart';
import 'state/providers.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final notifier = ValueNotifier(0);
  ref.onDispose(notifier.dispose);
  ref.listen(authProvider, (_, _) => notifier.value++);

  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: notifier,
    redirect: (context, state) {
      final auth = ref.read(authProvider);
      final loc = state.matchedLocation;
      final onAuthPage = ['/login', '/register', '/forgot'].contains(loc);

      switch (auth.status) {
        case AuthStatus.unknown:
          return loc == '/splash' ? null : '/splash';
        case AuthStatus.signedOut:
          return onAuthPage ? null : '/login';
        case AuthStatus.signedIn:
          return (loc == '/splash' || onAuthPage) ? '/' : null;
      }
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, _) => const SplashScreen()),
      GoRoute(path: '/login', builder: (_, _) => const LoginScreen()),
      GoRoute(path: '/register', builder: (_, _) => const RegisterScreen()),
      GoRoute(path: '/forgot', builder: (_, _) => const ForgotScreen()),
      ShellRoute(
        builder: (_, _, child) => AppShell(child: child),
        routes: [
          GoRoute(path: '/', builder: (_, _) => const HomeScreen()),
          GoRoute(path: '/bills', builder: (_, _) => const BillsScreen()),
          GoRoute(path: '/payments', builder: (_, _) => const PaymentsScreen()),
          GoRoute(path: '/requests', builder: (_, _) => const RequestsScreen()),
          GoRoute(path: '/notifications', builder: (_, _) => const NotificationsScreen()),
        ],
      ),
      GoRoute(path: '/bills/:id', builder: (_, s) => BillDetailScreen(id: int.parse(s.pathParameters['id']!))),
      GoRoute(path: '/pay', builder: (_, s) => PayScreen(accountId: s.extra as int?)),
      GoRoute(path: '/pay/status/:uuid', builder: (_, s) => PaymentStatusScreen(uuid: s.pathParameters['uuid']!)),
      GoRoute(path: '/requests/new', builder: (_, _) => const RequestCreateScreen()),
      GoRoute(path: '/requests/:id', builder: (_, s) => RequestDetailScreen(id: int.parse(s.pathParameters['id']!))),
      GoRoute(path: '/properties', builder: (_, _) => const PropertiesScreen()),
      GoRoute(path: '/properties/link', builder: (_, _) => const LinkPropertyScreen()),
      GoRoute(path: '/water', builder: (_, _) => const WaterScreen()),
      GoRoute(path: '/refuse', builder: (_, _) => const RefuseScreen()),
      GoRoute(path: '/updates', builder: (_, _) => const AnnouncementsScreen()),
      GoRoute(path: '/updates/:slug', builder: (_, s) => AnnouncementScreen(slug: s.pathParameters['slug']!)),
      GoRoute(path: '/profile', builder: (_, _) => const ProfileScreen()),
    ],
  );
});

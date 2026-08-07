import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../screens/auth/forgot_password_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/auth/otp_screen.dart';
import '../screens/auth/register_screen.dart';
import '../screens/customer/book_screen.dart';
import '../screens/customer/booking_detail_screen.dart';
import '../screens/customer/customer_shell.dart';
import '../screens/customer/home_screen.dart';
import '../screens/customer/history_screen.dart';
import '../screens/customer/loyalty_screen.dart';
import '../screens/customer/profile_screen.dart';
import '../screens/customer/service_detail_screen.dart';
import '../screens/customer/support_screen.dart';
import '../screens/staff/attendance_screen.dart';
import '../screens/staff/job_detail_screen.dart';
import '../screens/staff/jobs_screen.dart';
import '../screens/staff/leave_screen.dart';
import '../screens/staff/staff_dashboard_screen.dart';
import '../screens/staff/staff_profile_screen.dart';
import '../screens/staff/staff_shell.dart';

GoRouter createRouter(AuthProvider auth) {
  return GoRouter(
    refreshListenable: auth,
    initialLocation: '/login',
    redirect: (context, state) {
      final loc = state.matchedLocation;
      final loggingIn = loc.startsWith('/login') ||
          loc.startsWith('/register') ||
          loc.startsWith('/otp') ||
          loc.startsWith('/forgot');

      if (auth.loading) return null;
      if (!auth.isLoggedIn && !loggingIn) return '/login';
      if (auth.isLoggedIn && loggingIn) {
        return auth.isStaff ? '/staff' : '/home';
      }
      if (auth.isLoggedIn && auth.isStaff && (loc.startsWith('/home') || loc.startsWith('/book') || loc.startsWith('/history') || loc.startsWith('/loyalty') || loc.startsWith('/support') || loc.startsWith('/profile') || loc.startsWith('/service'))) {
        return '/staff';
      }
      if (auth.isLoggedIn && auth.isCustomer && loc.startsWith('/staff')) {
        return '/home';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/register', builder: (_, __) => const RegisterScreen()),
      GoRoute(path: '/otp', builder: (_, state) {
        final email = state.uri.queryParameters['email'] ?? '';
        final purpose = state.uri.queryParameters['purpose'] ?? 'REGISTER';
        return OtpScreen(email: email, purpose: purpose);
      }),
      GoRoute(path: '/forgot', builder: (_, __) => const ForgotPasswordScreen()),
      StatefulShellRoute.indexedStack(
        builder: (_, __, navigationShell) => CustomerShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/book', builder: (_, __) => const BookScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/history', builder: (_, __) => const HistoryScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/support', builder: (_, __) => const SupportScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
          ]),
        ],
      ),
      GoRoute(path: '/loyalty', builder: (_, __) => const LoyaltyScreen()),
      GoRoute(
        path: '/service/:id',
        builder: (_, state) => ServiceDetailScreen(id: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/booking/:id',
        builder: (_, state) => BookingDetailScreen(id: state.pathParameters['id']!),
      ),
      StatefulShellRoute.indexedStack(
        builder: (_, __, navigationShell) => StaffShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(path: '/staff', builder: (_, __) => const StaffDashboardScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/staff/jobs', builder: (_, __) => const JobsScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/staff/attendance', builder: (_, __) => const AttendanceScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/staff/leave', builder: (_, __) => const LeaveScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/staff/profile', builder: (_, __) => const StaffProfileScreen()),
          ]),
        ],
      ),
      GoRoute(
        path: '/staff/job/:id',
        builder: (_, state) => JobDetailScreen(id: state.pathParameters['id']!),
      ),
    ],
  );
}

extension AuthNav on BuildContext {
  AuthProvider get auth => read<AuthProvider>();
}

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../main.dart';
import '../../providers/auth_provider.dart';
import '../../providers/theme_controller.dart';
import '../../theme/app_theme.dart';

class StaffProfileScreen extends StatelessWidget {
  const StaffProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final themeCtrl = context.watch<ThemeController>();
    final u = auth.user;
    final e = auth.employee;

    return Scaffold(
      appBar: AppBar(title: const Text('Staff profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            '${u?['firstName'] ?? ''} ${u?['lastName'] ?? ''}',
            style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
          ),
          Text(u?['email']?.toString() ?? ''),
          Text('Role: ${u?['role']}'),
          if (e != null) ...[
            Text('Employee code: ${e['code']}'),
            Text('Available: ${e['isAvailable'] == true ? 'Yes' : 'No'}'),
          ],
          const Divider(height: 32),
          const Text('Appearance', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
          SwitchListTile(
            title: const Text('Dark mode'),
            subtitle: const Text('Use dark theme across the app'),
            secondary: Icon(themeCtrl.isDark ? Icons.dark_mode : Icons.light_mode, color: AppTheme.brand),
            value: themeCtrl.isDark,
            activeColor: AppTheme.brand,
            onChanged: (v) => themeCtrl.setDark(v),
          ),
          ListTile(title: const Text('API'), subtitle: Text(apiBaseUrl)),
          const SizedBox(height: 12),
          OutlinedButton(
            onPressed: () => auth.logout(),
            child: const Text('Sign out'),
          ),
        ],
      ),
    );
  }
}

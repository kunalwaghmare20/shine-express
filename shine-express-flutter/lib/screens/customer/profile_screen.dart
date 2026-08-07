import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../main.dart';
import '../../providers/auth_provider.dart';
import '../../providers/theme_controller.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late final first = TextEditingController();
  late final last = TextEditingController();
  late final phone = TextEditingController();
  String? message;

  @override
  void initState() {
    super.initState();
    final u = context.read<AuthProvider>().user;
    first.text = u?['firstName']?.toString() ?? '';
    last.text = u?['lastName']?.toString() ?? '';
    phone.text = u?['phone']?.toString() ?? '';
  }

  @override
  void dispose() {
    first.dispose();
    last.dispose();
    phone.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    try {
      await context.read<ApiClient>().post('/api/v1/auth/profile', body: {
        'firstName': first.text.trim(),
        'lastName': last.text.trim(),
        'phone': phone.text.trim(),
      });
      await context.read<AuthProvider>().refreshMe();
      setState(() => message = 'Profile updated');
    } catch (e) {
      setState(() => message = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final themeCtrl = context.watch<ThemeController>();

    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(auth.user?['email']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 12),
          TextField(controller: first, decoration: const InputDecoration(labelText: 'First name')),
          const SizedBox(height: 8),
          TextField(controller: last, decoration: const InputDecoration(labelText: 'Last name')),
          const SizedBox(height: 8),
          TextField(controller: phone, decoration: const InputDecoration(labelText: 'Phone')),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _save,
            style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
            child: const Text('Save'),
          ),
          if (message != null) Text(message!),
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
          OutlinedButton(onPressed: () => auth.logout(), child: const Text('Sign out')),
        ],
      ),
    );
  }
}

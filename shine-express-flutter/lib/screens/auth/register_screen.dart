import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final first = TextEditingController();
  final last = TextEditingController();
  final email = TextEditingController();
  final phone = TextEditingController();
  final password = TextEditingController();
  bool busy = false;
  String? error;

  Future<void> _submit() async {
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final api = context.read<ApiClient>();
      final otpRes = await api.post('/api/v1/auth/otp/send', body: {
        'email': email.text.trim(),
        'purpose': 'REGISTER',
      }) as Map<String, dynamic>;
      if (!mounted) return;
      final debugOtp = otpRes['debugOtp']?.toString();
      final verified = await context.push<bool>(
        '/otp?email=${Uri.encodeComponent(email.text.trim())}&purpose=REGISTER${debugOtp != null ? '&hint=$debugOtp' : ''}',
      );
      if (verified != true || !mounted) return;
      await context.read<AuthProvider>().register({
        'firstName': first.text.trim(),
        'lastName': last.text.trim(),
        'email': email.text.trim(),
        'phone': phone.text.trim(),
        'password': password.text,
        'deviceName': 'flutter',
      });
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sign up')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          TextField(controller: first, decoration: const InputDecoration(labelText: 'First name')),
          const SizedBox(height: 12),
          TextField(controller: last, decoration: const InputDecoration(labelText: 'Last name')),
          const SizedBox(height: 12),
          TextField(controller: email, decoration: const InputDecoration(labelText: 'Email'), keyboardType: TextInputType.emailAddress),
          const SizedBox(height: 12),
          TextField(controller: phone, decoration: const InputDecoration(labelText: 'Phone'), keyboardType: TextInputType.phone),
          const SizedBox(height: 12),
          TextField(controller: password, decoration: const InputDecoration(labelText: 'Password (min 6)'), obscureText: true),
          if (error != null) ...[
            const SizedBox(height: 12),
            Text(error!, style: const TextStyle(color: Colors.red)),
          ],
          const SizedBox(height: 20),
          FilledButton(
            onPressed: busy ? null : _submit,
            style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
            child: busy ? const CircularProgressIndicator(color: Colors.white) : const Text('Continue with OTP'),
          ),
        ],
      ),
    );
  }
}

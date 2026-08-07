import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final email = TextEditingController();
  final token = TextEditingController();
  final password = TextEditingController();
  bool step2 = false;
  bool busy = false;
  String? message;
  String? error;

  Future<void> _request() async {
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final data = await context.read<ApiClient>().post('/api/v1/auth/forgot-password', body: {
        'email': email.text.trim(),
      }) as Map<String, dynamic>?;
      setState(() {
        step2 = true;
        message = 'Check email for reset token (debug mode shows it below).';
        if (data != null && data['debugToken'] != null) {
          token.text = data['debugToken'].toString();
        }
      });
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      setState(() => busy = false);
    }
  }

  Future<void> _reset() async {
    setState(() {
      busy = true;
      error = null;
    });
    try {
      await context.read<ApiClient>().post('/api/v1/auth/reset-password', body: {
        'token': token.text.trim(),
        'password': password.text,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password updated — sign in')));
      Navigator.of(context).pop();
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Forgot password')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          if (!step2) ...[
            TextField(controller: email, decoration: const InputDecoration(labelText: 'Email'), keyboardType: TextInputType.emailAddress),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: busy ? null : _request,
              style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
              child: const Text('Send reset token'),
            ),
          ] else ...[
            if (message != null) Text(message!),
            const SizedBox(height: 12),
            TextField(controller: token, decoration: const InputDecoration(labelText: 'Reset token')),
            const SizedBox(height: 12),
            TextField(controller: password, decoration: const InputDecoration(labelText: 'New password'), obscureText: true),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: busy ? null : _reset,
              style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
              child: const Text('Update password'),
            ),
          ],
          if (error != null) ...[
            const SizedBox(height: 12),
            Text(error!, style: const TextStyle(color: Colors.red)),
          ],
        ],
      ),
    );
  }
}

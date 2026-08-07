import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class OtpScreen extends StatefulWidget {
  const OtpScreen({super.key, required this.email, required this.purpose});
  final String email;
  final String purpose;

  @override
  State<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends State<OtpScreen> {
  final otp = TextEditingController();
  bool busy = false;
  String? error;
  String? hint;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    hint ??= GoRouterState.of(context).uri.queryParameters['hint'];
  }

  Future<void> _verify() async {
    setState(() {
      busy = true;
      error = null;
    });
    try {
      await context.read<ApiClient>().post('/api/v1/auth/otp/verify', body: {
        'email': widget.email,
        'otp': otp.text.trim(),
        'purpose': widget.purpose,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('OTP verified')));
      context.pop(true);
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> _resend() async {
    try {
      final data = await context.read<ApiClient>().post('/api/v1/auth/otp/send', body: {
        'email': widget.email,
        'purpose': widget.purpose,
      }) as Map<String, dynamic>;
      setState(() => hint = data['debugOtp']?.toString());
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('OTP resent')));
      }
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Email OTP')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Code sent to ${widget.email}', style: const TextStyle(color: AppTheme.muted)),
            if (hint != null) ...[
              const SizedBox(height: 8),
              Text('Debug OTP: $hint', style: const TextStyle(fontWeight: FontWeight.bold)),
            ],
            const SizedBox(height: 16),
            TextField(
              controller: otp,
              decoration: const InputDecoration(labelText: '6-digit OTP'),
              keyboardType: TextInputType.number,
              maxLength: 6,
            ),
            if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: busy ? null : _verify,
              style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
              child: const Text('Verify'),
            ),
            TextButton(onPressed: _resend, child: const Text('Resend OTP')),
          ],
        ),
      ),
    );
  }
}

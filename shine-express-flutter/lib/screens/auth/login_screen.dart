import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../theme/app_theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final email = TextEditingController();
  final password = TextEditingController();
  bool busy = false;
  bool autofillDemo = false;
  String? error;

  static const _demoEmail = 'customer@shineexpress.com';
  static const _demoPassword = 'Customer@123';

  @override
  void dispose() {
    email.dispose();
    password.dispose();
    super.dispose();
  }

  void _onAutofillChanged(bool? checked) {
    final on = checked ?? false;
    setState(() {
      autofillDemo = on;
      if (on) {
        email.text = _demoEmail;
        password.text = _demoPassword;
      } else {
        email.clear();
        password.clear();
      }
    });
  }

  Future<void> _submit() async {
    setState(() {
      busy = true;
      error = null;
    });
    try {
      await context.read<AuthProvider>().login(email.text.trim(), password.text);
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppTheme.brandDark, AppTheme.brand, AppTheme.brandSoft],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Card(
                elevation: 8,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: AutofillGroup(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          'Shine Express',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: AppTheme.brandDark),
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'Customer & Staff',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: AppTheme.muted),
                        ),
                        const SizedBox(height: 24),
                        TextField(
                          controller: email,
                          decoration: const InputDecoration(labelText: 'Email'),
                          keyboardType: TextInputType.emailAddress,
                          autofillHints: const [],
                          enableSuggestions: false,
                          autocorrect: false,
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: password,
                          decoration: const InputDecoration(labelText: 'Password'),
                          obscureText: true,
                          autofillHints: const [],
                          enableSuggestions: false,
                          autocorrect: false,
                        ),
                        const SizedBox(height: 8),
                        CheckboxListTile(
                          contentPadding: EdgeInsets.zero,
                          controlAffinity: ListTileControlAffinity.leading,
                          value: autofillDemo,
                          onChanged: _onAutofillChanged,
                          title: const Text('Autofill demo login', style: TextStyle(fontSize: 14)),
                          subtitle: const Text(
                            'Fills customer@shineexpress.com / Customer@123',
                            style: TextStyle(fontSize: 12),
                          ),
                        ),
                        if (error != null) ...[
                          const SizedBox(height: 8),
                          Text(error!, style: const TextStyle(color: Colors.red)),
                        ],
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: busy ? null : _submit,
                          style: FilledButton.styleFrom(
                            backgroundColor: AppTheme.brand,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                          child: busy
                              ? const SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : const Text('Sign in'),
                        ),
                        TextButton(onPressed: () => context.push('/forgot'), child: const Text('Forgot password?')),
                        TextButton(onPressed: () => context.push('/register'), child: const Text('Create customer account')),
                        TextButton(
                          onPressed: () => context.push(
                            '/otp?email=${Uri.encodeComponent(email.text.trim())}&purpose=LOGIN',
                          ),
                          child: const Text('Verify email OTP'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

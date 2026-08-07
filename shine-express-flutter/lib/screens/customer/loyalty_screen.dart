import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class LoyaltyScreen extends StatefulWidget {
  const LoyaltyScreen({super.key});

  @override
  State<LoyaltyScreen> createState() => _LoyaltyScreenState();
}

class _LoyaltyScreenState extends State<LoyaltyScreen> {
  Map<String, dynamic>? data;
  String? error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/loyalty');
      setState(() => data = Map<String, dynamic>.from(res as Map));
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Loyalty & referrals')),
      body: data == null
          ? Center(child: error != null ? Text(error!) : const CircularProgressIndicator())
          : Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [AppTheme.brandDark, AppTheme.brand]),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Column(
                      children: [
                        const Text('Your points', style: TextStyle(color: Colors.white70)),
                        Text('${data!['points']}', style: const TextStyle(fontSize: 48, fontWeight: FontWeight.bold, color: Colors.white)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  ListTile(
                    title: const Text('Referral code'),
                    subtitle: Text(data!['referralCode']?.toString() ?? '', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700)),
                    trailing: IconButton(
                      icon: const Icon(Icons.copy),
                      onPressed: () {
                        Clipboard.setData(ClipboardData(text: data!['referralCode']?.toString() ?? ''));
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Copied')));
                      },
                    ),
                  ),
                  const Text('Share your code with friends. They get a welcome offer; you earn loyalty points when they book.'),
                ],
              ),
            ),
    );
  }
}

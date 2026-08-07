import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../config/app_config.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class SupportScreen extends StatefulWidget {
  const SupportScreen({super.key});

  @override
  State<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends State<SupportScreen> {
  List tickets = [];
  final subject = TextEditingController();
  final message = TextEditingController();
  String? error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/support/tickets');
      setState(() => tickets = res as List? ?? []);
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  Future<void> _create() async {
    try {
      await context.read<ApiClient>().post('/api/v1/support/tickets', body: {
        'subject': subject.text.trim(),
        'message': message.text.trim(),
      });
      subject.clear();
      message.clear();
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Complaint / ticket submitted')));
      }
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  Future<void> _call(String phone) async {
    final uri = Uri.parse('tel:$phone');
    if (await canLaunchUrl(uri)) await launchUrl(uri);
  }

  Future<void> _wa(String phone) async {
    final cleaned = phone.replaceAll(RegExp(r'[^\d+]'), '');
    final uri = Uri.parse('https://wa.me/${cleaned.replaceFirst('+', '')}');
    if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Support')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _call(AppConfig.supportPhone),
                  icon: const Icon(Icons.phone),
                  label: const Text('Call'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: FilledButton.icon(
                  onPressed: () => _wa(AppConfig.supportWhatsapp),
                  style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
                  icon: const Icon(Icons.chat),
                  label: const Text('WhatsApp'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          const Text('Raise a complaint', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          TextField(controller: subject, decoration: const InputDecoration(labelText: 'Subject')),
          const SizedBox(height: 8),
          TextField(controller: message, decoration: const InputDecoration(labelText: 'Message'), maxLines: 3),
          const SizedBox(height: 8),
          FilledButton(onPressed: _create, style: FilledButton.styleFrom(backgroundColor: AppTheme.brand), child: const Text('Submit')),
          if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),
          const SizedBox(height: 24),
          const Text('Your tickets', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          ...tickets.map((t) => Card(
                child: ListTile(
                  title: Text(t['subject']?.toString() ?? ''),
                  subtitle: Text('${t['status']} · ${t['createdAt']}\n${t['message']}'),
                  isThreeLine: true,
                ),
              )),
        ],
      ),
    );
  }
}

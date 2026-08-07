import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class ServiceDetailScreen extends StatefulWidget {
  const ServiceDetailScreen({super.key, required this.id});
  final String id;

  @override
  State<ServiceDetailScreen> createState() => _ServiceDetailScreenState();
}

class _ServiceDetailScreenState extends State<ServiceDetailScreen> {
  Map<String, dynamic>? data;
  String? error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/services/${widget.id}');
      setState(() => data = Map<String, dynamic>.from(res as Map));
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = data;
    return Scaffold(
      appBar: AppBar(title: Text(d?['name']?.toString() ?? 'Service')),
      floatingActionButton: d == null
          ? null
          : FloatingActionButton.extended(
              onPressed: () => context.go('/book'),
              backgroundColor: AppTheme.brand,
              label: const Text('Book now'),
              icon: const Icon(Icons.event_available),
            ),
      body: d == null
          ? Center(child: error != null ? Text(error!) : const CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(d['name']?.toString() ?? '', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                Text(d['categoryName']?.toString() ?? '', style: const TextStyle(color: AppTheme.muted)),
                const SizedBox(height: 8),
                Text('From ₹${d['basePrice']} · ~${d['duration']} min · ★ ${d['ratingAvg']}'),
                const SizedBox(height: 12),
                Text(d['description']?.toString() ?? ''),
                const SizedBox(height: 16),
                const Text('What\'s included', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                ...((d['items'] as List?) ?? []).map((i) => ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(i['name']?.toString() ?? ''),
                      subtitle: Text(i['description']?.toString() ?? ''),
                      trailing: Text('₹${i['price']}'),
                    )),
                if (((d['faqs'] as List?) ?? []).isNotEmpty) ...[
                  const SizedBox(height: 8),
                  const Text('FAQs', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                  ...((d['faqs'] as List?) ?? []).map((f) => ExpansionTile(
                        title: Text(f['question']?.toString() ?? ''),
                        children: [Padding(padding: const EdgeInsets.all(12), child: Text(f['answer']?.toString() ?? ''))],
                      )),
                ],
                if (((d['reviews'] as List?) ?? []).isNotEmpty) ...[
                  const SizedBox(height: 8),
                  const Text('Reviews', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                  ...((d['reviews'] as List?) ?? []).map((r) => ListTile(
                        title: Text('${r['firstName'] ?? 'Customer'} · ★ ${r['rating']}'),
                        subtitle: Text(r['comment']?.toString() ?? ''),
                      )),
                ],
                const SizedBox(height: 72),
              ],
            ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List items = [];
  String? error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/bookings');
      setState(() {
        items = res as List? ?? [];
        error = null;
      });
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My bookings')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: error != null
            ? ListView(children: [Padding(padding: const EdgeInsets.all(24), child: Text(error!))])
            : ListView.builder(
                itemCount: items.length,
                itemBuilder: (_, i) {
                  final b = items[i] as Map;
                  return Card(
                    margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    child: ListTile(
                      title: Text(b['serviceName']?.toString() ?? ''),
                      subtitle: Text('${b['bookingNumber']} · ${b['statusLabel'] ?? b['status']}\n${b['scheduledDate']} ${b['scheduledTime'] ?? ''}'),
                      isThreeLine: true,
                      trailing: Text('₹${b['totalAmount']}'),
                      onTap: () => context.push('/booking/${b['id']}'),
                    ),
                  );
                },
              ),
      ),
    );
  }
}

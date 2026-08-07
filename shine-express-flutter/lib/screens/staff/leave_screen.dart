import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class LeaveScreen extends StatefulWidget {
  const LeaveScreen({super.key});

  @override
  State<LeaveScreen> createState() => _LeaveScreenState();
}

class _LeaveScreenState extends State<LeaveScreen> {
  List leaves = [];
  final reason = TextEditingController();
  DateTime from = DateTime.now().add(const Duration(days: 1));
  DateTime to = DateTime.now().add(const Duration(days: 2));
  String? error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/staff/leaves');
      setState(() {
        leaves = res as List? ?? [];
        error = null;
      });
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  String _d(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _apply() async {
    try {
      await context.read<ApiClient>().post('/api/v1/staff/leaves', body: {
        'fromDate': _d(from),
        'toDate': _d(to),
        'reason': reason.text.trim(),
      });
      reason.clear();
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Leave applied')));
      }
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Leave')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ListTile(
            title: Text('From: ${_d(from)}'),
            trailing: const Icon(Icons.calendar_today),
            onTap: () async {
              final d = await showDatePicker(context: context, firstDate: DateTime.now(), lastDate: DateTime.now().add(const Duration(days: 365)), initialDate: from);
              if (d != null) setState(() => from = d);
            },
          ),
          ListTile(
            title: Text('To: ${_d(to)}'),
            trailing: const Icon(Icons.calendar_today),
            onTap: () async {
              final d = await showDatePicker(context: context, firstDate: from, lastDate: DateTime.now().add(const Duration(days: 365)), initialDate: to);
              if (d != null) setState(() => to = d);
            },
          ),
          TextField(controller: reason, decoration: const InputDecoration(labelText: 'Reason'), maxLines: 2),
          const SizedBox(height: 8),
          FilledButton(onPressed: _apply, style: FilledButton.styleFrom(backgroundColor: AppTheme.brand), child: const Text('Apply leave')),
          if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),
          const Divider(height: 32),
          const Text('History', style: TextStyle(fontWeight: FontWeight.w700)),
          ...leaves.map((l) => ListTile(
                title: Text('${l['fromDate'] ?? l['from_date']} → ${l['toDate'] ?? l['to_date']}'),
                subtitle: Text('${l['status']} · ${l['reason'] ?? ''}'),
              )),
        ],
      ),
    );
  }
}

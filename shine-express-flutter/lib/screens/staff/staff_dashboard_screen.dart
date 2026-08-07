import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/staff_tab_refresh.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class StaffDashboardScreen extends StatefulWidget {
  const StaffDashboardScreen({super.key});

  @override
  State<StaffDashboardScreen> createState() => _StaffDashboardScreenState();
}

class _StaffDashboardScreenState extends State<StaffDashboardScreen> {
  Map<String, dynamic>? data;
  String? error;
  Timer? _poll;
  StaffTabRefresh? _tabs;

  static const _todayTabIndex = 0;

  @override
  void initState() {
    super.initState();
    _load();
    _poll = Timer.periodic(const Duration(seconds: 20), (_) {
      if (!mounted) return;
      final tabs = _tabs;
      if (tabs == null || tabs.index == _todayTabIndex) {
        _load();
      }
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final tabs = context.read<StaffTabRefresh>();
    if (!identical(_tabs, tabs)) {
      _tabs?.removeListener(_onTab);
      _tabs = tabs;
      _tabs!.addListener(_onTab);
    }
  }

  void _onTab() {
    if (_tabs?.index == _todayTabIndex) {
      _load();
    }
  }

  @override
  void dispose() {
    _poll?.cancel();
    _tabs?.removeListener(_onTab);
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/staff/dashboard');
      if (!mounted) return;
      setState(() {
        data = Map<String, dynamic>.from(res as Map);
        error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => error = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = data;
    final att = d?['attendance'] as Map?;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Today'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _load),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),
            if (d != null) ...[
              Row(
                children: [
                  _StatCard('Today', '${d['todayJobs']}', Icons.today),
                  _StatCard('Upcoming', '${d['upcomingJobs']}', Icons.upcoming),
                  _StatCard('Done', '${d['completedJobs']}', Icons.check_circle),
                ],
              ),
              const SizedBox(height: 16),
              Card(
                child: ListTile(
                  title: const Text('Attendance today'),
                  subtitle: Text(att == null
                      ? 'Not checked in'
                      : '${att['status']} · in ${att['checkIn'] ?? '—'} · out ${att['checkOut'] ?? '—'}'),
                  trailing: TextButton(onPressed: () => context.go('/staff/attendance'), child: const Text('Manage')),
                ),
              ),
              FilledButton.icon(
                onPressed: () {
                  context.go('/staff/jobs');
                  context.read<StaffTabRefresh>().select(1);
                },
                style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
                icon: const Icon(Icons.work),
                label: const Text('View assigned jobs'),
              ),
            ] else if (error == null)
              const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator())),
          ],
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard(this.label, this.value, this.icon);
  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            children: [
              Icon(icon, color: AppTheme.brand),
              Text(value, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
              Text(label, style: const TextStyle(fontSize: 12, color: AppTheme.muted)),
            ],
          ),
        ),
      ),
    );
  }
}

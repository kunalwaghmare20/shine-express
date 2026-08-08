import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/staff_tab_refresh.dart';
import '../../services/api_client.dart';
import '../../services/staff_location_tracker.dart';

class JobsScreen extends StatefulWidget {
  const JobsScreen({super.key});

  @override
  State<JobsScreen> createState() => _JobsScreenState();
}

class _JobsScreenState extends State<JobsScreen> {
  List jobs = [];
  String? error;
  bool loading = false;
  Timer? _poll;
  StaffTabRefresh? _tabs;

  static const _jobsTabIndex = 1;

  @override
  void initState() {
    super.initState();
    _load();
    _poll = Timer.periodic(const Duration(seconds: 15), (_) {
      if (!mounted) return;
      // Only poll while Jobs tab is selected
      final tabs = _tabs;
      if (tabs == null || tabs.index == _jobsTabIndex) {
        _load(silent: true);
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
    if (_tabs?.index == _jobsTabIndex) {
      _load();
    }
  }

  @override
  void dispose() {
    _poll?.cancel();
    _tabs?.removeListener(_onTab);
    super.dispose();
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent && mounted) setState(() => loading = true);
    try {
      final res = await context.read<ApiClient>().get('/api/v1/jobs');
      if (!mounted) return;
      setState(() {
        jobs = res is Map ? (res['jobs'] as List? ?? []) : (res as List? ?? []);
        error = null;
        loading = false;
      });
      final onTheWay = jobs.any((j) => (j as Map)['status']?.toString() == 'ON_THE_WAY');
      await context.read<StaffLocationTracker>().setShouldTrack(onTheWay);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        error = e.toString();
        loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My jobs'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: loading ? null : () => _load(),
            icon: loading
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.refresh),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => _load(),
        child: error != null
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [Padding(padding: const EdgeInsets.all(24), child: Text(error!))],
              )
            : jobs.isEmpty
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: const [
                      SizedBox(height: 120),
                      Center(child: Text('No assigned jobs yet.\nPull down to refresh.', textAlign: TextAlign.center)),
                    ],
                  )
                : ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    itemCount: jobs.length,
                    itemBuilder: (_, i) {
                      final j = jobs[i] as Map;
                      final itemCount = j['itemCount'] as int? ?? 0;
                      final subtitle = StringBuffer()
                        ..write('${j['status']} · ${j['scheduledDate']} ${j['scheduledTime'] ?? ''}');
                      if (itemCount > 1) subtitle.write(' · $itemCount services');
                      if (j['isPrimary'] == true) subtitle.write(' · Primary');
                      return Card(
                        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        child: ListTile(
                          title: Text(j['serviceName']?.toString() ?? j['bookingNumber']?.toString() ?? ''),
                          subtitle: Text('${subtitle.toString()}\n${_addr(j)}'),
                          isThreeLine: true,
                          onTap: () async {
                            await context.push('/staff/job/${j['id']}');
                            if (mounted) _load();
                          },
                        ),
                      );
                    },
                  ),
      ),
    );
  }
}

String _addr(Map j) {
  final a = j['address'];
  if (a is Map) {
    return '${a['line1'] ?? ''}, ${a['city'] ?? ''}';
  }
  return a?.toString() ?? j['customerName']?.toString() ?? '';
}

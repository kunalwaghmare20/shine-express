import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class StaffEarningsScreen extends StatefulWidget {
  const StaffEarningsScreen({super.key});

  @override
  State<StaffEarningsScreen> createState() => _StaffEarningsScreenState();
}

class _StaffEarningsScreenState extends State<StaffEarningsScreen> {
  Map<String, dynamic>? data;
  String? error;
  bool loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final res = await context.read<ApiClient>().get('/api/v1/staff/earnings');
      if (!mounted) return;
      setState(() {
        data = Map<String, dynamic>.from(res as Map);
        loading = false;
      });
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
    final d = data;

    return Scaffold(
      appBar: AppBar(
        title: const Text('My earnings'),
        actions: [
          IconButton(onPressed: loading ? null : _load, icon: const Icon(Icons.refresh)),
        ],
      ),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : error != null
              ? Center(child: Text(error!))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _SummaryCard(
                        title: 'Today',
                        jobs: _period(d, 'today', 'completedJobs'),
                        earnings: _period(d, 'today', 'estimatedEarnings'),
                        revenue: _period(d, 'today', 'jobRevenue'),
                      ),
                      const SizedBox(height: 12),
                      _SummaryCard(
                        title: 'This week',
                        jobs: _period(d, 'week', 'completedJobs'),
                        earnings: _period(d, 'week', 'estimatedEarnings'),
                        revenue: _period(d, 'week', 'jobRevenue'),
                      ),
                      const SizedBox(height: 12),
                      _SummaryCard(
                        title: 'This month',
                        jobs: _period(d, 'month', 'completedJobs'),
                        earnings: _period(d, 'month', 'estimatedEarnings'),
                        revenue: _period(d, 'month', 'jobRevenue'),
                        highlight: true,
                        totalEstimate: d?['month'] is Map ? (d!['month'] as Map)['estimatedTotal'] : null,
                      ),
                      const SizedBox(height: 20),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Pay breakdown', style: TextStyle(fontWeight: FontWeight.w700)),
                              const SizedBox(height: 8),
                              _row('Base salary (monthly)', '₹${d?['baseSalary'] ?? 0}'),
                              _row('Per completed job bonus', '₹${d?['perJobBonus'] ?? 0}'),
                              _row('Daily base estimate', '₹${d?['dailyBaseEstimate'] ?? 0}'),
                              const SizedBox(height: 8),
                              Text(
                                'Bonuses are estimated from completed jobs × per-job rate. Confirm payouts with your branch manager.',
                                style: TextStyle(color: AppTheme.muted, fontSize: 12),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }

  num _period(Map<String, dynamic>? d, String key, String field) {
    final p = d?[key];
    if (p is Map) return p[field] ?? 0;
    return 0;
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [Text(label), Text(value, style: const TextStyle(fontWeight: FontWeight.w600))],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.title,
    required this.jobs,
    required this.earnings,
    required this.revenue,
    this.highlight = false,
    this.totalEstimate,
  });

  final String title;
  final num jobs;
  final num earnings;
  final num revenue;
  final bool highlight;
  final dynamic totalEstimate;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: highlight ? const Color(0xFFEFF6FF) : null,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(child: _metric('Jobs done', jobs.toString())),
                Expanded(child: _metric('Job revenue', '₹$revenue')),
                Expanded(child: _metric('Bonus est.', '₹$earnings')),
              ],
            ),
            if (totalEstimate != null) ...[
              const SizedBox(height: 10),
              Text(
                'Month total estimate (bonus + base): ₹$totalEstimate',
                style: const TextStyle(fontWeight: FontWeight.w600, color: AppTheme.brandDark),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _metric(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, color: AppTheme.muted)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
      ],
    );
  }
}

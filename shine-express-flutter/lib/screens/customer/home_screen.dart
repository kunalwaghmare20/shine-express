import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, dynamic>? data;
  String? error;
  final search = TextEditingController();
  List searchResults = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/home');
      setState(() {
        data = Map<String, dynamic>.from(res as Map);
        error = null;
      });
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  Future<void> _search(String q) async {
    if (q.trim().isEmpty) {
      setState(() => searchResults = []);
      return;
    }
    try {
      final res = await context.read<ApiClient>().get('/api/v1/search', query: {'q': q});
      setState(() => searchResults = (res as List?) ?? []);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final categories = (data?['categories'] as List?) ?? [];
    final featured = (data?['featuredServices'] as List?) ?? (data?['featured'] as List?) ?? [];
    final offers = (data?['offers'] as List?) ?? [];
    final upcoming = (data?['upcomingBookings'] as List?) ?? (data?['upcoming'] as List?) ?? [];
    final recent = (data?['recentBookings'] as List?) ?? (data?['recent'] as List?) ?? [];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Shine Express'),
        actions: [
          IconButton(icon: const Icon(Icons.card_giftcard), onPressed: () => context.push('/loyalty')),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: error != null && data == null
            ? ListView(children: [Padding(padding: const EdgeInsets.all(24), child: Text(error!))])
            : ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  TextField(
                    controller: search,
                    decoration: const InputDecoration(
                      prefixIcon: Icon(Icons.search),
                      hintText: 'Search services',
                    ),
                    onChanged: _search,
                  ),
                  if (searchResults.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    ...searchResults.map((s) => ListTile(
                          title: Text(s['name']?.toString() ?? ''),
                          subtitle: Text(s['categoryName']?.toString() ?? ''),
                          onTap: () => context.push('/service/${s['id']}'),
                        )),
                  ],
                  const SizedBox(height: 16),
                  const _SectionTitle('Categories'),
                  SizedBox(
                    height: 96,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: categories.length,
                      separatorBuilder: (_, __) => const SizedBox(width: 10),
                      itemBuilder: (_, i) {
                        final c = categories[i] as Map;
                        return InkWell(
                          onTap: () => context.go('/book'),
                          child: Container(
                            width: 110,
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: AppTheme.brand.withOpacity(0.2)),
                            ),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.cleaning_services, color: AppTheme.brand),
                                const SizedBox(height: 6),
                                Text(c['name']?.toString() ?? '', textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12)),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                  if (offers.isNotEmpty) ...[
                    const SizedBox(height: 20),
                    const _SectionTitle('Special offers'),
                    ...offers.map((o) {
                      final m = o as Map;
                      return Card(
                        child: ListTile(
                          leading: const Icon(Icons.local_offer, color: AppTheme.brand),
                          title: Text(m['title']?.toString() ?? ''),
                          subtitle: Text([m['description'], m['code']].where((e) => e != null && e.toString().isNotEmpty).join(' · ')),
                        ),
                      );
                    }),
                  ],
                  const SizedBox(height: 20),
                  const _SectionTitle('Featured services'),
                  ...featured.map((s) {
                    final m = s as Map;
                    return Card(
                      child: ListTile(
                        title: Text(m['name']?.toString() ?? ''),
                        subtitle: Text('${m['categoryName'] ?? ''} · from ₹${m['basePrice'] ?? 0}'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => context.push('/service/${m['id']}'),
                      ),
                    );
                  }),
                  if (upcoming.isNotEmpty) ...[
                    const SizedBox(height: 20),
                    const _SectionTitle('Upcoming bookings'),
                    ...upcoming.map((b) => _BookingTile(b as Map, onTap: () => context.push('/booking/${(b as Map)['id']}'))),
                  ],
                  if (recent.isNotEmpty) ...[
                    const SizedBox(height: 20),
                    const _SectionTitle('Recent bookings'),
                    ...recent.map((b) => _BookingTile(b as Map, onTap: () => context.push('/booking/${(b as Map)['id']}'))),
                  ],
                ],
              ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: Text(text, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
      );
}

class _BookingTile extends StatelessWidget {
  const _BookingTile(this.b, {required this.onTap});
  final Map b;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        title: Text(b['serviceName']?.toString() ?? b['bookingNumber']?.toString() ?? ''),
        subtitle: Text('${b['status']} · ${b['scheduledDate']} ${b['scheduledTime'] ?? ''}'),
        trailing: Text('₹${b['totalAmount'] ?? ''}'),
        onTap: onTap,
      ),
    );
  }
}

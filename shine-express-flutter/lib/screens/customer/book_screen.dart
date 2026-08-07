import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class BookScreen extends StatefulWidget {
  const BookScreen({super.key});

  @override
  State<BookScreen> createState() => _BookScreenState();
}

class _BookScreenState extends State<BookScreen> {
  List services = [];
  List addresses = [];
  List branches = [];
  String? serviceId;
  String? packageId;
  String? addressId;
  String? branchId;
  DateTime date = DateTime.now().add(const Duration(days: 1));
  TimeOfDay time = const TimeOfDay(hour: 10, minute: 0);
  final notes = TextEditingController();
  bool busy = false;
  String? error;
  Map<String, dynamic>? serviceDetail;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final api = context.read<ApiClient>();
    try {
      final catalog = await api.get('/api/v1/catalog') as Map;
      final addrs = await api.get('/api/v1/addresses');
      setState(() {
        services = (catalog['services'] as List?) ?? [];
        branches = (catalog['branches'] as List?) ?? [];
        addresses = addrs as List? ?? [];
        if (services.isNotEmpty && serviceId == null) {
          serviceId = services.first['id']?.toString();
          _loadService();
        }
        if (addresses.isNotEmpty && addressId == null) {
          addressId = addresses.first['id']?.toString();
        }
        if (branches.isNotEmpty && branchId == null) {
          branchId = branches.first['id']?.toString();
        }
      });
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  Future<void> _loadService() async {
    if (serviceId == null) return;
    try {
      final d = await context.read<ApiClient>().get('/api/v1/services/$serviceId');
      setState(() {
        serviceDetail = Map<String, dynamic>.from(d as Map);
        final pkgs = (serviceDetail?['items'] as List?) ?? (serviceDetail?['packages'] as List?) ?? [];
        packageId = pkgs.isNotEmpty ? pkgs.first['id']?.toString() : null;
      });
    } catch (_) {}
  }

  Future<void> _submit() async {
    if (serviceId == null || addressId == null) {
      setState(() => error = 'Select service and address');
      return;
    }
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final body = {
        'serviceId': serviceId,
        'addressId': addressId,
        if (branchId != null) 'branchId': branchId,
        'scheduledDate': '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}',
        'scheduledTime': '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}:00',
        'customerNotes': notes.text.trim(),
        if (packageId != null) 'serviceItemIds': [packageId],
      };
      final res = await context.read<ApiClient>().post('/api/v1/bookings', body: body) as Map;
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Booked ${res['bookingNumber'] ?? 'OK'}')));
      context.push('/booking/${res['id']}');
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> _addAddress() async {
    final label = TextEditingController(text: 'Home');
    final line1 = TextEditingController();
    final city = TextEditingController(text: 'City');
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('New address'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(controller: label, decoration: const InputDecoration(labelText: 'Label')),
            TextField(controller: line1, decoration: const InputDecoration(labelText: 'Address line')),
            TextField(controller: city, decoration: const InputDecoration(labelText: 'City')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Save')),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await context.read<ApiClient>().post('/api/v1/addresses', body: {
        'label': label.text.trim(),
        'line1': line1.text.trim(),
        'city': city.text.trim(),
        'latitude': 28.6139,
        'longitude': 77.2090,
      });
      await _load();
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final packages = (serviceDetail?['items'] as List?) ?? (serviceDetail?['packages'] as List?) ?? [];

    return Scaffold(
      appBar: AppBar(title: const Text('Book a service')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          DropdownButtonFormField<String>(
            value: serviceId,
            decoration: const InputDecoration(labelText: 'Service'),
            items: services
                .map((s) => DropdownMenuItem(value: s['id']?.toString(), child: Text(s['name']?.toString() ?? '')))
                .toList(),
            onChanged: (v) {
              setState(() => serviceId = v);
              _loadService();
            },
          ),
          if (packages.isNotEmpty) ...[
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: packageId,
              decoration: const InputDecoration(labelText: 'Package / item'),
              items: packages
                  .map((p) => DropdownMenuItem(
                        value: p['id']?.toString(),
                        child: Text('${p['name']} · ₹${p['price'] ?? p['basePrice'] ?? ''}'),
                      ))
                  .toList(),
              onChanged: (v) => setState(() => packageId = v),
            ),
          ],
          if (branches.isNotEmpty) ...[
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: branchId,
              decoration: const InputDecoration(labelText: 'Branch'),
              items: branches
                  .map((b) => DropdownMenuItem(value: b['id']?.toString(), child: Text(b['name']?.toString() ?? '')))
                  .toList(),
              onChanged: (v) => setState(() => branchId = v),
            ),
          ],
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: addressId,
                  decoration: const InputDecoration(labelText: 'Address'),
                  items: addresses
                      .map((a) => DropdownMenuItem(
                            value: a['id']?.toString(),
                            child: Text('${a['label'] ?? ''} — ${a['line1'] ?? a['addressLine1'] ?? ''}'),
                          ))
                      .toList(),
                  onChanged: (v) => setState(() => addressId = v),
                ),
              ),
              IconButton(onPressed: _addAddress, icon: const Icon(Icons.add_location_alt)),
            ],
          ),
          const SizedBox(height: 12),
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text('Date: ${date.toLocal().toString().split(' ').first}'),
            trailing: const Icon(Icons.calendar_today),
            onTap: () async {
              final d = await showDatePicker(context: context, firstDate: DateTime.now(), lastDate: DateTime.now().add(const Duration(days: 90)), initialDate: date);
              if (d != null) setState(() => date = d);
            },
          ),
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text('Time: ${time.format(context)}'),
            trailing: const Icon(Icons.access_time),
            onTap: () async {
              final t = await showTimePicker(context: context, initialTime: time);
              if (t != null) setState(() => time = t);
            },
          ),
          TextField(controller: notes, decoration: const InputDecoration(labelText: 'Special instructions'), maxLines: 3),
          if (error != null) ...[
            const SizedBox(height: 8),
            Text(error!, style: const TextStyle(color: Colors.red)),
          ],
          const SizedBox(height: 20),
          FilledButton(
            onPressed: busy ? null : _submit,
            style: FilledButton.styleFrom(backgroundColor: AppTheme.brand, padding: const EdgeInsets.symmetric(vertical: 14)),
            child: busy ? const CircularProgressIndicator(color: Colors.white) : const Text('Confirm booking'),
          ),
        ],
      ),
    );
  }
}

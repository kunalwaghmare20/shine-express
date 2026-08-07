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
  final Set<String> selectedServiceIds = {};
  final Set<String> selectedItemIds = {};
  String? addressId;
  String? branchId;
  DateTime date = DateTime.now().add(const Duration(days: 1));
  TimeOfDay time = const TimeOfDay(hour: 10, minute: 0);
  final notes = TextEditingController();
  bool busy = false;
  String? error;

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
        if (services.isNotEmpty && selectedServiceIds.isEmpty) {
          selectedServiceIds.add(services.first['id']?.toString() ?? '');
          selectedServiceIds.remove('');
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

  List get _visibleItems {
    final items = <dynamic>[];
    for (final s in services) {
      final id = s['id']?.toString();
      if (id == null || !selectedServiceIds.contains(id)) continue;
      final pkgs = (s['items'] as List?) ?? [];
      for (final p in pkgs) {
        items.add({...Map<String, dynamic>.from(p as Map), 'serviceName': s['name']});
      }
    }
    return items;
  }

  double get _estimate {
    var total = 0.0;
    final covered = <String>{};
    for (final item in _visibleItems) {
      final id = item['id']?.toString();
      if (id == null || !selectedItemIds.contains(id)) continue;
      total += (item['price'] as num?)?.toDouble() ?? 0;
      covered.add(item['serviceId']?.toString() ?? '');
    }
    for (final s in services) {
      final id = s['id']?.toString();
      if (id == null || !selectedServiceIds.contains(id)) continue;
      if (covered.contains(id)) continue;
      total += (s['basePrice'] as num?)?.toDouble() ?? 0;
    }
    return total;
  }

  void _toggleService(String id, bool selected) {
    setState(() {
      if (selected) {
        selectedServiceIds.add(id);
      } else {
        selectedServiceIds.remove(id);
        for (final s in services) {
          if (s['id']?.toString() != id) continue;
          for (final p in (s['items'] as List?) ?? []) {
            selectedItemIds.remove(p['id']?.toString());
          }
        }
      }
    });
  }

  Future<void> _submit() async {
    if (selectedServiceIds.isEmpty || addressId == null) {
      setState(() => error = 'Select at least one service and an address');
      return;
    }
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final body = {
        'serviceIds': selectedServiceIds.toList(),
        'serviceId': selectedServiceIds.first,
        'addressId': addressId,
        if (branchId != null) 'branchId': branchId,
        'scheduledDate':
            '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}',
        'scheduledTime':
            '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}:00',
        'customerNotes': notes.text.trim(),
        if (selectedItemIds.isNotEmpty) 'serviceItemIds': selectedItemIds.toList(),
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
    final packages = _visibleItems;

    return Scaffold(
      appBar: AppBar(title: const Text('Book a service')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('Services', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 4),
          Text('Select one or more', style: TextStyle(color: AppTheme.muted, fontSize: 13)),
          const SizedBox(height: 8),
          ...services.map((s) {
            final id = s['id']?.toString() ?? '';
            return CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              dense: true,
              value: selectedServiceIds.contains(id),
              title: Text(s['name']?.toString() ?? ''),
              subtitle: Text('${s['categoryName'] ?? ''} · ₹${s['basePrice'] ?? ''}'),
              onChanged: (v) => _toggleService(id, v == true),
            );
          }),
          if (packages.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text('Packages / items', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 4),
            Text('Optional — pick packages under selected services', style: TextStyle(color: AppTheme.muted, fontSize: 13)),
            ...packages.map((p) {
              final id = p['id']?.toString() ?? '';
              return CheckboxListTile(
                contentPadding: EdgeInsets.zero,
                dense: true,
                value: selectedItemIds.contains(id),
                title: Text(p['name']?.toString() ?? ''),
                subtitle: Text('${p['serviceName'] ?? ''} · ₹${p['price'] ?? ''}'),
                onChanged: (v) {
                  setState(() {
                    if (v == true) {
                      selectedItemIds.add(id);
                    } else {
                      selectedItemIds.remove(id);
                    }
                  });
                },
              );
            }),
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
              final d = await showDatePicker(
                context: context,
                firstDate: DateTime.now(),
                lastDate: DateTime.now().add(const Duration(days: 90)),
                initialDate: date,
              );
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
          const SizedBox(height: 8),
          Text('Estimate (before tax): ₹${_estimate.toStringAsFixed(0)}',
              style: const TextStyle(fontWeight: FontWeight.w600, color: AppTheme.brandDark)),
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

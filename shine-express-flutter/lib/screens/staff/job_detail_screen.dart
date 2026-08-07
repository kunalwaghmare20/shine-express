import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class JobDetailScreen extends StatefulWidget {
  const JobDetailScreen({super.key, required this.id});
  final String id;

  @override
  State<JobDetailScreen> createState() => _JobDetailScreenState();
}

class _JobDetailScreenState extends State<JobDetailScreen> {
  Map<String, dynamic>? job;
  String? error;
  bool busy = false;
  final note = TextEditingController();
  final rejectReason = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/jobs/${widget.id}');
      setState(() {
        job = Map<String, dynamic>.from(res as Map);
        error = null;
      });
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  Future<void> _action(String path, {Map<String, dynamic>? body}) async {
    setState(() => busy = true);
    try {
      await context.read<ApiClient>().post('/api/v1/jobs/${widget.id}/$path', body: body);
      await _load();
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      setState(() => busy = false);
    }
  }

  Future<void> _photo(String type) async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.camera, imageQuality: 70);
    if (file == null || !mounted) return;
    setState(() => busy = true);
    try {
      await context.read<ApiClient>().postMultipart(
            '/api/v1/jobs/${widget.id}/photos',
            fields: {'type': type},
            fileField: 'photo',
            filePath: file.path,
            filename: file.name,
          );
      await _load();
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      setState(() => busy = false);
    }
  }

  Future<void> _checklistToggle(Map item, bool done) async {
    final label = item['label']?.toString() ?? item['title']?.toString() ?? 'Item';
    final existing = List<Map<String, dynamic>>.from(
      ((job?['checklist'] as List?) ?? []).map((e) => Map<String, dynamic>.from(e as Map)),
    );
    final idx = existing.indexWhere((e) => (e['label']?.toString() ?? '') == label);
    if (idx >= 0) {
      existing[idx] = {'label': label, 'isDone': done};
    } else {
      existing.add({'label': label, 'isDone': done});
    }
    try {
      await context.read<ApiClient>().post('/api/v1/jobs/${widget.id}/checklist', body: {
        'items': existing.map((e) => {
              'label': e['label'],
              'isDone': e['isDone'] == true || e['isDone'] == 1,
            }).toList(),
      });
      await _load();
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final j = job;
    final status = j?['status']?.toString() ?? '';
    final checklist = (j?['checklist'] as List?) ?? [];
    final notes = (j?['notes'] as List?) ?? [];

    return Scaffold(
      appBar: AppBar(title: Text(j?['bookingNumber']?.toString() ?? 'Job')),
      body: j == null
          ? Center(child: error != null ? Text(error!) : const CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(j['serviceName']?.toString() ?? '', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                Chip(label: Text(status)),
                Text('Customer: ${j['customerName'] ?? '—'}'),
                Text('When: ${j['scheduledDate']} ${j['scheduledTime'] ?? ''}'),
                Text('Address: ${_jobAddr(j)}'),
                if (_jobLat(j) != null && _jobLng(j) != null)
                  Text('Map: ${_jobLat(j)}, ${_jobLng(j)}'),
                if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    if (status == 'ASSIGNED') ...[
                      FilledButton(
                        onPressed: busy ? null : () => _action('accept'),
                        style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
                        child: const Text('Accept'),
                      ),
                      OutlinedButton(
                        onPressed: busy
                            ? null
                            : () async {
                                final ok = await showDialog<bool>(
                                  context: context,
                                  builder: (ctx) => AlertDialog(
                                    title: const Text('Reject job'),
                                    content: TextField(controller: rejectReason, decoration: const InputDecoration(labelText: 'Reason')),
                                    actions: [
                                      TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
                                      FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Reject')),
                                    ],
                                  ),
                                );
                                if (ok == true) {
                                  await _action('reject', body: {'reason': rejectReason.text.trim()});
                                }
                              },
                        child: const Text('Reject'),
                      ),
                    ],
                    if (status == 'ACCEPTED' || status == 'ON_THE_WAY')
                      FilledButton(
                        onPressed: busy ? null : () => _action('start'),
                        style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
                        child: const Text('Start job'),
                      ),
                    if (status == 'STARTED')
                      FilledButton(
                        onPressed: busy ? null : () => _action('complete'),
                        style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
                        child: const Text('Complete job'),
                      ),
                    OutlinedButton(onPressed: busy ? null : () => _photo('BEFORE'), child: const Text('Before photo')),
                    OutlinedButton(onPressed: busy ? null : () => _photo('AFTER'), child: const Text('After photo')),
                  ],
                ),
                const Divider(height: 32),
                const Text('Checklist', style: TextStyle(fontWeight: FontWeight.w700)),
                if (checklist.isEmpty)
                  const Text('No checklist items yet — mark defaults below.')
                else
                  ...checklist.map((c) {
                    final m = c as Map;
                    return CheckboxListTile(
                      value: m['isDone'] == true || m['isDone'] == 1,
                      title: Text(m['label']?.toString() ?? ''),
                      onChanged: (v) => _checklistToggle(m, v ?? false),
                    );
                  }),
                ...['Arrive on site', 'Complete service', 'Collect payment'].map((label) => ListTile(
                      dense: true,
                      title: Text(label),
                      trailing: IconButton(
                        icon: const Icon(Icons.check),
                        onPressed: () => _checklistToggle({'itemKey': label, 'label': label}, true),
                      ),
                    )),
                const Divider(height: 32),
                const Text('Job notes', style: TextStyle(fontWeight: FontWeight.w700)),
                ...notes.map((n) => ListTile(title: Text((n as Map)['note']?.toString() ?? ''), subtitle: Text(n['createdAt']?.toString() ?? ''))),
                TextField(controller: note, decoration: const InputDecoration(labelText: 'Add note')),
                TextButton(
                  onPressed: busy
                      ? null
                      : () async {
                          await context.read<ApiClient>().post('/api/v1/jobs/${widget.id}/notes', body: {'note': note.text.trim()});
                          note.clear();
                          await _load();
                        },
                  child: const Text('Save note'),
                ),
              ],
            ),
    );
  }
}

String _jobAddr(Map j) {
  final a = j['address'];
  if (a is Map) return '${a['line1'] ?? ''}, ${a['city'] ?? ''} ${a['pincode'] ?? ''}';
  return a?.toString() ?? '—';
}

double? _jobLat(Map j) {
  final a = j['address'];
  if (a is Map && a['latitude'] != null) return double.tryParse(a['latitude'].toString());
  return double.tryParse(j['latitude']?.toString() ?? '');
}

double? _jobLng(Map j) {
  final a = j['address'];
  if (a is Map && a['longitude'] != null) return double.tryParse(a['longitude'].toString());
  return double.tryParse(j['longitude']?.toString() ?? '');
}

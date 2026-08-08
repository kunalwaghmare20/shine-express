import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../services/navigation_launcher.dart';
import '../../services/offline_job_sync_service.dart';
import '../../services/staff_location_tracker.dart';
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

  OfflineJobSyncService get _sync => context.read<OfflineJobSyncService>();
  StaffLocationTracker get _locationTracker => context.read<StaffLocationTracker>();

  @override
  void initState() {
    super.initState();
    _load();
    _sync.addListener(_onSyncChanged);
  }

  @override
  void dispose() {
    _sync.removeListener(_onSyncChanged);
    _locationTracker.setShouldTrack(false);
    note.dispose();
    rejectReason.dispose();
    super.dispose();
  }

  void _onSyncChanged() {
    if (!mounted || job == null) return;
    _load(showSpinner: false);
  }

  Future<void> _load({bool showSpinner = true}) async {
    if (showSpinner && job == null) {
      setState(() => error = null);
    }
    try {
      final res = await _sync.loadJob(widget.id);
      if (!mounted) return;
      setState(() {
        job = res;
        error = null;
      });
      await _locationTracker.setShouldTrack(res['status']?.toString() == 'ON_THE_WAY');
    } catch (e) {
      if (!mounted) return;
      setState(() => error = e.toString());
    }
  }

  Future<void> _action(String path, {Map<String, dynamic>? body}) async {
    setState(() => busy = true);
    try {
      await context.read<ApiClient>().post('/api/v1/jobs/${widget.id}/$path', body: body);
      await _load(showSpinner: false);
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
      await _sync.queuePhoto(widget.id, file.path, type, file.name);
      await _load(showSpinner: false);
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

    setState(() {
      job = Map<String, dynamic>.from(job ?? {})
        ..['checklist'] = existing.map((e) => {'label': e['label'], 'isDone': e['isDone'] == true || e['isDone'] == 1}).toList();
    });

    try {
      await _sync.saveChecklist(widget.id, existing);
      await _load(showSpinner: false);
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  Future<void> _saveNote() async {
    final text = note.text.trim();
    if (text.isEmpty) return;
    setState(() => busy = true);
    try {
      await _sync.addNote(widget.id, text);
      note.clear();
      await _load(showSpinner: false);
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      setState(() => busy = false);
    }
  }

  Future<void> _navigateToCustomer() async {
    final j = job;
    if (j == null) return;

    final target = navigationTargetFromJob(Map<String, dynamic>.from(j));
    if (!target.isNavigable) {
      setState(() => error = 'No address or GPS coordinates for this job');
      return;
    }

    setState(() => error = null);
    final ok = await NavigationLauncher.openTurnByTurn(target);
    if (!ok && mounted) {
      setState(() => error = 'Could not open maps. Install Google Maps or Apple Maps.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final j = job;
    final status = j?['status']?.toString() ?? '';
    final checklist = (j?['checklist'] as List?) ?? [];
    final notes = (j?['notes'] as List?) ?? [];
    final photos = (j?['photos'] as List?) ?? [];
    final items = (j?['items'] as List?) ?? [];
    final team = (j?['teamMembers'] as List?) ?? [];
    final isPrimary = j?['isPrimary'] == true;
    final navTarget = j == null ? null : navigationTargetFromJob(Map<String, dynamic>.from(j));
    final offline = j?['_offline'] == true;
    final pendingCount = j?['_pendingCount'] as int? ?? _sync.pendingCount;

    return Scaffold(
      appBar: AppBar(
        title: Text(j?['bookingNumber']?.toString() ?? 'Job'),
        actions: [
          if (pendingCount > 0)
            TextButton.icon(
              onPressed: _sync.isSyncing ? null : () => _sync.flushQueue(),
              icon: _sync.isSyncing
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.cloud_upload_outlined, size: 20),
              label: Text('Sync ($pendingCount)'),
            ),
        ],
      ),
      body: j == null
          ? Center(child: error != null ? Text(error!) : const CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (offline)
                  Card(
                    color: const Color(0xFFFFF7ED),
                    margin: const EdgeInsets.only(bottom: 12),
                    child: ListTile(
                      leading: Icon(Icons.cloud_off, color: Colors.orange.shade800),
                      title: Text(
                        pendingCount > 0 ? 'Offline — $pendingCount change(s) queued' : 'Offline — showing saved job data',
                        style: TextStyle(color: Colors.orange.shade900, fontWeight: FontWeight.w600),
                      ),
                      subtitle: const Text('Checklist, notes, and photos will sync when you reconnect.'),
                    ),
                  ),
                Text(j['serviceName']?.toString() ?? '', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                if (isPrimary)
                  const Padding(
                    padding: EdgeInsets.only(top: 4),
                    child: Chip(label: Text('You are primary contact'), visualDensity: VisualDensity.compact),
                  ),
                Chip(label: Text(status)),
                Text('Customer: ${j['customerName'] ?? '—'}'),
                Text('When: ${j['scheduledDate']} ${j['scheduledTime'] ?? ''}'),
                const SizedBox(height: 8),
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.location_on_outlined, color: AppTheme.brand, size: 22),
                            const SizedBox(width: 8),
                            Expanded(child: Text(_jobAddr(j), style: const TextStyle(height: 1.35))),
                          ],
                        ),
                        if (navTarget?.hasCoordinates == true) ...[
                          const SizedBox(height: 6),
                          Text(
                            'GPS: ${navTarget!.latitude!.toStringAsFixed(5)}, ${navTarget.longitude!.toStringAsFixed(5)}',
                            style: const TextStyle(fontSize: 12, color: AppTheme.muted),
                          ),
                        ],
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: busy || navTarget?.isNavigable != true ? null : _navigateToCustomer,
                            icon: const Icon(Icons.navigation),
                            label: const Text('Navigate to customer'),
                            style: FilledButton.styleFrom(
                              backgroundColor: AppTheme.brand,
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                if (items.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  const Text('Services in this job', style: TextStyle(fontWeight: FontWeight.w700)),
                  ...items.map((item) {
                    final m = Map<String, dynamic>.from(item as Map);
                    return ListTile(
                      contentPadding: EdgeInsets.zero,
                      dense: true,
                      title: Text(m['name']?.toString() ?? ''),
                      trailing: Text('₹${m['price'] ?? ''}'),
                    );
                  }),
                ],
                if (team.length > 1) ...[
                  const SizedBox(height: 12),
                  const Text('Team on this job', style: TextStyle(fontWeight: FontWeight.w700)),
                  ...team.map((member) {
                    final m = Map<String, dynamic>.from(member as Map);
                    if (m['rejected'] == true) return const SizedBox.shrink();
                    return ListTile(
                      contentPadding: EdgeInsets.zero,
                      dense: true,
                      leading: const Icon(Icons.badge_outlined, size: 20),
                      title: Text(m['name']?.toString() ?? ''),
                      subtitle: m['isPrimary'] == true ? const Text('Primary') : null,
                    );
                  }),
                ],
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
                if (photos.isNotEmpty) ...[
                  const Divider(height: 32),
                  const Text('Photos', style: TextStyle(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: photos.map((p) => _PhotoThumb(photo: Map<String, dynamic>.from(p as Map))).toList(),
                  ),
                ],
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
                ...notes.map((n) {
                  final m = Map<String, dynamic>.from(n as Map);
                  return ListTile(
                    title: Text(m['note']?.toString() ?? ''),
                    subtitle: Text(m['createdAt']?.toString() ?? ''),
                    trailing: m['pending'] == true ? const Icon(Icons.schedule, size: 18, color: AppTheme.muted) : null,
                  );
                }),
                TextField(controller: note, decoration: const InputDecoration(labelText: 'Add note')),
                TextButton(onPressed: busy ? null : _saveNote, child: const Text('Save note')),
              ],
            ),
    );
  }
}

class _PhotoThumb extends StatelessWidget {
  const _PhotoThumb({required this.photo});
  final Map<String, dynamic> photo;

  @override
  Widget build(BuildContext context) {
    final url = photo['url']?.toString() ?? '';
    final type = photo['type']?.toString() ?? '';
    final isLocal = photo['local'] == true || (!url.startsWith('http') && File(url).existsSync());

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: SizedBox(
            width: 96,
            height: 96,
            child: isLocal
                ? Image.file(File(url), fit: BoxFit.cover)
                : Image.network(url, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const Icon(Icons.broken_image)),
          ),
        ),
        const SizedBox(height: 4),
        Text(type, style: const TextStyle(fontSize: 11, color: AppTheme.muted)),
        if (photo['pending'] == true) const Text('Pending sync', style: TextStyle(fontSize: 10, color: Colors.orange)),
      ],
    );
  }
}

String _jobAddr(Map j) {
  final a = j['address'];
  if (a is Map) return '${a['line1'] ?? ''}, ${a['city'] ?? ''} ${a['pincode'] ?? ''}'.trim();
  return a?.toString() ?? '—';
}

import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:path_provider/path_provider.dart';

import 'api_client.dart';

/// Queues checklist, notes, and photo uploads when staff have no connectivity.
class OfflineJobSyncService extends ChangeNotifier {
  OfflineJobSyncService(this._api);

  final ApiClient _api;
  static const _cacheBoxName = 'job_cache';
  static const _queueBoxName = 'sync_queue';

  Box<String>? _cacheBox;
  Box<String>? _queueBox;
  StreamSubscription<ConnectivityResult>? _connectivitySub;

  bool _initialized = false;
  bool _online = true;
  bool _syncing = false;

  bool get isOnline => _online;
  bool get isSyncing => _syncing;
  int get pendingCount => _queueBox?.length ?? 0;

  Future<void> init() async {
    if (_initialized) return;
    if (!Hive.isBoxOpen(_cacheBoxName)) {
      await Hive.openBox<String>(_cacheBoxName);
    }
    if (!Hive.isBoxOpen(_queueBoxName)) {
      await Hive.openBox<String>(_queueBoxName);
    }
    _cacheBox = Hive.box<String>(_cacheBoxName);
    _queueBox = Hive.box<String>(_queueBoxName);
    _online = await _hasNetwork();
    _initialized = true;

    _connectivitySub = Connectivity().onConnectivityChanged.listen((_) async {
      final wasOffline = !_online;
      _online = await _hasNetwork();
      if (wasOffline && _online) {
        await flushQueue();
      }
      notifyListeners();
    });

    if (_online && pendingCount > 0) {
      unawaited(flushQueue());
    }
    notifyListeners();
  }

  @override
  void dispose() {
    _connectivitySub?.cancel();
    super.dispose();
  }

  Future<bool> _hasNetwork() async {
    final result = await Connectivity().checkConnectivity();
    return result != ConnectivityResult.none;
  }

  Future<Map<String, dynamic>> loadJob(String jobId) async {
    await init();
    try {
      final res = await _api.get('/api/v1/jobs/$jobId');
      final job = Map<String, dynamic>.from(res as Map);
      await _cacheBox!.put(jobId, jsonEncode(job));
      _online = true;
      notifyListeners();
      return _mergePending(jobId, job);
    } catch (_) {
      _online = await _hasNetwork();
      final cached = _cacheBox!.get(jobId);
      if (cached != null) {
        notifyListeners();
        return _mergePending(jobId, Map<String, dynamic>.from(jsonDecode(cached) as Map));
      }
      rethrow;
    }
  }

  Future<void> saveChecklist(String jobId, List<Map<String, dynamic>> items) async {
    await init();
    final payload = items
        .map((e) => {
              'label': e['label']?.toString() ?? '',
              'isDone': e['isDone'] == true || e['isDone'] == 1,
            })
        .toList();

    _updateCachedChecklist(jobId, payload);
    notifyListeners();

    if (_online) {
      try {
        await _api.post('/api/v1/jobs/$jobId/checklist', body: {'items': payload});
        await _removeQueuedType(jobId, 'checklist');
        notifyListeners();
        return;
      } catch (_) {
        _online = await _hasNetwork();
      }
    }

    await _enqueue(_QueueItem(
      id: 'checklist_$jobId',
      jobId: jobId,
      type: 'checklist',
      payload: {'items': payload},
    ));
    notifyListeners();
  }

  Future<void> addNote(String jobId, String text) async {
    await init();
    final trimmed = text.trim();
    if (trimmed.isEmpty) return;

    _appendCachedNote(jobId, trimmed);
    notifyListeners();

    if (_online) {
      try {
        await _api.post('/api/v1/jobs/$jobId/notes', body: {'note': trimmed});
        notifyListeners();
        return;
      } catch (_) {
        _online = await _hasNetwork();
      }
    }

    await _enqueue(_QueueItem(
      id: 'note_${DateTime.now().millisecondsSinceEpoch}',
      jobId: jobId,
      type: 'note',
      payload: {'note': trimmed},
    ));
    notifyListeners();
  }

  Future<void> queuePhoto(String jobId, String sourcePath, String type, String filename) async {
    await init();
    final localPath = await _persistPhoto(jobId, sourcePath);
    _appendCachedPhoto(jobId, localPath, type);
    notifyListeners();

    if (_online) {
      try {
        await _api.postMultipart(
          '/api/v1/jobs/$jobId/photos',
          fields: {'type': type},
          fileField: 'photo',
          filePath: localPath,
          filename: filename,
        );
        await _removeQueuedPhoto(jobId, localPath);
        try {
          await File(localPath).delete();
        } catch (_) {}
        notifyListeners();
        return;
      } catch (_) {
        _online = await _hasNetwork();
      }
    }

    await _enqueue(_QueueItem(
      id: 'photo_${DateTime.now().millisecondsSinceEpoch}',
      jobId: jobId,
      type: 'photo',
      payload: {'type': type, 'filename': filename},
      localPhotoPath: localPath,
    ));
    notifyListeners();
  }

  Future<void> flushQueue() async {
    await init();
    if (_syncing || !_online || pendingCount == 0) return;

    _syncing = true;
    notifyListeners();

    try {
      final items = _allQueueItems()..sort((a, b) => a.createdAt.compareTo(b.createdAt));
      final latestChecklist = <String, _QueueItem>{};
      final ordered = <_QueueItem>[];

      for (final item in items) {
        if (item.type == 'checklist') {
          latestChecklist[item.jobId] = item;
        } else {
          ordered.add(item);
        }
      }
      ordered.addAll(latestChecklist.values);

      for (final item in ordered) {
        if (!_online) break;
        try {
          await _flushItem(item);
          await _queueBox!.delete(item.id);
        } catch (_) {
          _online = await _hasNetwork();
          break;
        }
      }
    } finally {
      _syncing = false;
      notifyListeners();
    }
  }

  Future<void> _flushItem(_QueueItem item) async {
    switch (item.type) {
      case 'checklist':
        await _api.post('/api/v1/jobs/${item.jobId}/checklist', body: item.payload);
        return;
      case 'note':
        await _api.post('/api/v1/jobs/${item.jobId}/notes', body: item.payload);
        return;
      case 'photo':
        final path = item.localPhotoPath;
        if (path == null || !File(path).existsSync()) {
          return;
        }
        await _api.postMultipart(
          '/api/v1/jobs/${item.jobId}/photos',
          fields: {'type': item.payload['type']?.toString() ?? 'BEFORE'},
          fileField: 'photo',
          filePath: path,
          filename: item.payload['filename']?.toString() ?? 'photo.jpg',
        );
        try {
          await File(path).delete();
        } catch (_) {}
        return;
    }
  }

  Map<String, dynamic> _mergePending(String jobId, Map<String, dynamic> job) {
    final merged = Map<String, dynamic>.from(job);
    final items = _allQueueItems().where((e) => e.jobId == jobId).toList()
      ..sort((a, b) => a.createdAt.compareTo(b.createdAt));

    for (final item in items) {
      if (item.type == 'checklist') {
        merged['checklist'] = item.payload['items'];
      } else if (item.type == 'note') {
        final notes = List<Map<String, dynamic>>.from((merged['notes'] as List?)?.map((e) => Map<String, dynamic>.from(e as Map)) ?? []);
        notes.insert(0, {
          'id': 'pending_${item.id}',
          'note': item.payload['note'],
          'createdAt': item.createdAt.toIso8601String(),
          'pending': true,
        });
        merged['notes'] = notes;
      } else if (item.type == 'photo' && item.localPhotoPath != null) {
        final photos = List<Map<String, dynamic>>.from((merged['photos'] as List?)?.map((e) => Map<String, dynamic>.from(e as Map)) ?? []);
        photos.add({
          'id': 'pending_${item.id}',
          'url': item.localPhotoPath,
          'type': item.payload['type'],
          'local': true,
          'pending': true,
        });
        merged['photos'] = photos;
      }
    }

    merged['_offline'] = !_online || items.isNotEmpty;
    merged['_pendingCount'] = items.length;
    return merged;
  }

  void _updateCachedChecklist(String jobId, List<Map<String, dynamic>> items) {
    final job = _readCache(jobId);
    if (job == null) return;
    job['checklist'] = items
        .map((e) => {
              'label': e['label'],
              'isDone': e['isDone'] == true || e['isDone'] == 1,
            })
        .toList();
    _writeCache(jobId, job);
  }

  void _appendCachedNote(String jobId, String text) {
    final job = _readCache(jobId);
    if (job == null) return;
    final notes = List<Map<String, dynamic>>.from((job['notes'] as List?)?.map((e) => Map<String, dynamic>.from(e as Map)) ?? []);
    notes.insert(0, {
      'id': 'local_${DateTime.now().millisecondsSinceEpoch}',
      'note': text,
      'createdAt': DateTime.now().toIso8601String(),
      'pending': true,
    });
    job['notes'] = notes;
    _writeCache(jobId, job);
  }

  void _appendCachedPhoto(String jobId, String localPath, String type) {
    final job = _readCache(jobId);
    if (job == null) return;
    final photos = List<Map<String, dynamic>>.from((job['photos'] as List?)?.map((e) => Map<String, dynamic>.from(e as Map)) ?? []);
    photos.add({
      'id': 'local_${DateTime.now().millisecondsSinceEpoch}',
      'url': localPath,
      'type': type,
      'local': true,
      'pending': true,
    });
    job['photos'] = photos;
    _writeCache(jobId, job);
  }

  Map<String, dynamic>? _readCache(String jobId) {
    final raw = _cacheBox?.get(jobId);
    if (raw == null) return null;
    return Map<String, dynamic>.from(jsonDecode(raw) as Map);
  }

  void _writeCache(String jobId, Map<String, dynamic> job) {
    _cacheBox?.put(jobId, jsonEncode(job));
  }

  Future<String> _persistPhoto(String jobId, String sourcePath) async {
    final dir = await getApplicationDocumentsDirectory();
    final pendingDir = Directory('${dir.path}/pending_photos/$jobId');
    if (!await pendingDir.exists()) {
      await pendingDir.create(recursive: true);
    }
    final dest = '${pendingDir.path}/${DateTime.now().millisecondsSinceEpoch}.jpg';
    await File(sourcePath).copy(dest);
    return dest;
  }

  Future<void> _enqueue(_QueueItem item) async {
    await _queueBox!.put(item.id, jsonEncode(item.toJson()));
    if (item.type == 'checklist') {
      await _removeQueuedType(item.jobId, 'checklist', exceptId: item.id);
    }
  }

  Future<void> _removeQueuedType(String jobId, String type, {String? exceptId}) async {
    for (final key in _queueBox!.keys.cast<String>()) {
      if (exceptId != null && key == exceptId) continue;
      final item = _decodeQueueItem(_queueBox!.get(key));
      if (item != null && item.jobId == jobId && item.type == type) {
        await _queueBox!.delete(key);
      }
    }
  }

  Future<void> _removeQueuedPhoto(String jobId, String localPath) async {
    for (final key in _queueBox!.keys.cast<String>()) {
      final item = _decodeQueueItem(_queueBox!.get(key));
      if (item != null && item.jobId == jobId && item.type == 'photo' && item.localPhotoPath == localPath) {
        await _queueBox!.delete(key);
      }
    }
  }

  List<_QueueItem> _allQueueItems() {
    return _queueBox!.keys
        .map((k) => _decodeQueueItem(_queueBox!.get(k)))
        .whereType<_QueueItem>()
        .toList();
  }

  _QueueItem? _decodeQueueItem(String? raw) {
    if (raw == null) return null;
    try {
      return _QueueItem.fromJson(Map<String, dynamic>.from(jsonDecode(raw) as Map));
    } catch (_) {
      return null;
    }
  }
}

class _QueueItem {
  _QueueItem({
    required this.id,
    required this.jobId,
    required this.type,
    required this.payload,
    this.localPhotoPath,
    DateTime? createdAt,
  }) : createdAt = createdAt ?? DateTime.now();

  final String id;
  final String jobId;
  final String type;
  final Map<String, dynamic> payload;
  final String? localPhotoPath;
  final DateTime createdAt;

  Map<String, dynamic> toJson() => {
        'id': id,
        'jobId': jobId,
        'type': type,
        'payload': payload,
        'localPhotoPath': localPhotoPath,
        'createdAt': createdAt.toIso8601String(),
      };

  factory _QueueItem.fromJson(Map<String, dynamic> json) => _QueueItem(
        id: json['id']?.toString() ?? '',
        jobId: json['jobId']?.toString() ?? '',
        type: json['type']?.toString() ?? '',
        payload: Map<String, dynamic>.from(json['payload'] as Map? ?? {}),
        localPhotoPath: json['localPhotoPath']?.toString(),
        createdAt: DateTime.tryParse(json['createdAt']?.toString() ?? '') ?? DateTime.now(),
      );
}

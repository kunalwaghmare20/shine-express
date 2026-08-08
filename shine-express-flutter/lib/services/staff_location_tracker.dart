import 'dart:async';

import 'package:geolocator/geolocator.dart';

import 'api_client.dart';

/// Sends periodic GPS updates while staff has an ON_THE_WAY job.
class StaffLocationTracker {
  StaffLocationTracker(this._api);

  final ApiClient _api;
  Timer? _timer;
  bool _active = false;

  bool get isActive => _active;

  Future<void> setShouldTrack(bool shouldTrack) async {
    if (shouldTrack) {
      if (_active) return;
      _active = true;
      await _sendLocation();
      _timer = Timer.periodic(const Duration(seconds: 45), (_) => _sendLocation());
      return;
    }

    _active = false;
    _timer?.cancel();
    _timer = null;
  }

  void dispose() {
    _timer?.cancel();
    _timer = null;
    _active = false;
  }

  Future<void> _sendLocation() async {
    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
        return;
      }

      final pos = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      await _api.post('/api/v1/staff/location', body: {
        'latitude': pos.latitude,
        'longitude': pos.longitude,
      });
    } catch (_) {}
  }
}

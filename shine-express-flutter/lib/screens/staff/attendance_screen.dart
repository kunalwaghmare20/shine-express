import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  String? message;
  bool busy = false;

  Future<Position?> _pos() async {
    var perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
    }
    if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) {
      return null;
    }
    return Geolocator.getCurrentPosition();
  }

  Future<void> _check(String path) async {
    setState(() => busy = true);
    try {
      final p = await _pos();
      await context.read<ApiClient>().post('/api/v1/staff/attendance/$path', body: {
        if (p != null) 'latitude': p.latitude,
        if (p != null) 'longitude': p.longitude,
      });
      setState(() => message = path == 'check-in' ? 'Checked in' : 'Checked out');
    } catch (e) {
      setState(() => message = e.toString());
    } finally {
      setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Attendance')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text('Mark attendance with optional GPS location.'),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: busy ? null : () => _check('check-in'),
              style: FilledButton.styleFrom(backgroundColor: AppTheme.brand, padding: const EdgeInsets.symmetric(vertical: 16)),
              child: const Text('Check in'),
            ),
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: busy ? null : () => _check('check-out'),
              style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
              child: const Text('Check out'),
            ),
            if (message != null) ...[
              const SizedBox(height: 16),
              Text(message!),
            ],
          ],
        ),
      ),
    );
  }
}

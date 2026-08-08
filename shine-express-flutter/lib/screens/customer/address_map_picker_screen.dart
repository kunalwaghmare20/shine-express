import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

import '../../services/nominatim_service.dart';
import '../../theme/app_theme.dart';

class AddressPickResult {
  const AddressPickResult({
    required this.label,
    required this.line1,
    required this.city,
    required this.latitude,
    required this.longitude,
    this.state,
    this.pincode,
  });

  final String label;
  final String line1;
  final String city;
  final String? state;
  final String? pincode;
  final double latitude;
  final double longitude;
}

class AddressMapPickerScreen extends StatefulWidget {
  const AddressMapPickerScreen({super.key});

  @override
  State<AddressMapPickerScreen> createState() => _AddressMapPickerScreenState();
}

class _AddressMapPickerScreenState extends State<AddressMapPickerScreen> {
  static const _defaultCenter = LatLng(28.6139, 77.2090);

  final _mapController = MapController();
  final _label = TextEditingController(text: 'Home');
  final _line1 = TextEditingController();
  final _city = TextEditingController();
  final _pincode = TextEditingController();

  LatLng _pin = _defaultCenter;
  bool _loadingLocation = true;
  bool _loadingAddress = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initLocation();
  }

  @override
  void dispose() {
    _label.dispose();
    _line1.dispose();
    _city.dispose();
    _pincode.dispose();
    super.dispose();
  }

  Future<void> _initLocation() async {
    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
        await _setPin(_defaultCenter);
        return;
      }
      final pos = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      await _setPin(LatLng(pos.latitude, pos.longitude));
    } catch (_) {
      await _setPin(_defaultCenter);
    } finally {
      if (mounted) setState(() => _loadingLocation = false);
    }
  }

  Future<void> _setPin(LatLng point) async {
    setState(() {
      _pin = point;
      _loadingAddress = true;
      _error = null;
    });
    _mapController.move(point, _mapController.camera.zoom);

    try {
      final addr = await NominatimService.reverseGeocode(point.latitude, point.longitude);
      if (!mounted) return;
      if (addr != null) {
        _line1.text = addr.line1;
        _city.text = addr.city;
        _pincode.text = addr.pincode ?? '';
      }
    } catch (e) {
      if (mounted) _error = 'Could not resolve address: $e';
    } finally {
      if (mounted) setState(() => _loadingAddress = false);
    }
  }

  Future<void> _useMyLocation() async {
    setState(() {
      _loadingLocation = true;
      _error = null;
    });
    try {
      final pos = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      await _setPin(LatLng(pos.latitude, pos.longitude));
    } catch (e) {
      if (mounted) setState(() => _error = 'Location unavailable: $e');
    } finally {
      if (mounted) setState(() => _loadingLocation = false);
    }
  }

  void _confirm() {
    final line = _line1.text.trim();
    final city = _city.text.trim();
    if (line.isEmpty || city.isEmpty) {
      setState(() => _error = 'Address line and city are required');
      return;
    }
    Navigator.pop(
      context,
      AddressPickResult(
        label: _label.text.trim().isEmpty ? 'Home' : _label.text.trim(),
        line1: line,
        city: city,
        pincode: _pincode.text.trim().isEmpty ? null : _pincode.text.trim(),
        latitude: _pin.latitude,
        longitude: _pin.longitude,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pick location'),
        actions: [
          TextButton(
            onPressed: _loadingLocation ? null : _useMyLocation,
            child: const Text('My location'),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: Stack(
              children: [
                FlutterMap(
                  mapController: _mapController,
                  options: MapOptions(
                    initialCenter: _pin,
                    initialZoom: 15,
                    onTap: (_, point) => _setPin(point),
                  ),
                  children: [
                    TileLayer(
                      urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                      userAgentPackageName: 'com.shineexpress.shine_express_app',
                    ),
                    MarkerLayer(
                      markers: [
                        Marker(
                          point: _pin,
                          width: 40,
                          height: 40,
                          child: Icon(Icons.location_on, color: AppTheme.brand, size: 40),
                        ),
                      ],
                    ),
                  ],
                ),
                if (_loadingLocation || _loadingAddress)
                  const Align(
                    alignment: Alignment.topCenter,
                    child: Padding(
                      padding: EdgeInsets.all(12),
                      child: Card(
                        child: Padding(
                          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)),
                              SizedBox(width: 10),
                              Text('Updating location…'),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          Material(
            elevation: 8,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('Tap the map to move the pin', style: TextStyle(color: AppTheme.muted, fontSize: 13)),
                  const SizedBox(height: 8),
                  TextField(controller: _label, decoration: const InputDecoration(labelText: 'Label (Home, Office…)')),
                  TextField(controller: _line1, decoration: const InputDecoration(labelText: 'Address line')),
                  TextField(controller: _city, decoration: const InputDecoration(labelText: 'City')),
                  TextField(
                    controller: _pincode,
                    decoration: const InputDecoration(labelText: 'Pincode (optional)'),
                    keyboardType: TextInputType.number,
                  ),
                  Text(
                    'Lat ${_pin.latitude.toStringAsFixed(5)}, Lng ${_pin.longitude.toStringAsFixed(5)}',
                    style: TextStyle(color: AppTheme.muted, fontSize: 12),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 6),
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                  ],
                  const SizedBox(height: 10),
                  FilledButton(
                    onPressed: _confirm,
                    style: FilledButton.styleFrom(
                      backgroundColor: AppTheme.brand,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    child: const Text('Save address'),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

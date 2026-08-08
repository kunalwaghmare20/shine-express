import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../theme/app_theme.dart';

class StaffTrackingMap extends StatelessWidget {
  const StaffTrackingMap({
    super.key,
    required this.customerLat,
    required this.customerLng,
    this.staffLat,
    this.staffLng,
    this.staffName,
    this.updatedAt,
  });

  final double customerLat;
  final double customerLng;
  final double? staffLat;
  final double? staffLng;
  final String? staffName;
  final String? updatedAt;

  @override
  Widget build(BuildContext context) {
    final customer = LatLng(customerLat, customerLng);
    final staff = staffLat != null && staffLng != null ? LatLng(staffLat!, staffLng!) : null;
    final points = staff != null ? [customer, staff] : [customer];
    final bounds = LatLngBounds.fromPoints(points);
    final center = staff != null
        ? LatLng((customer.latitude + staff.latitude) / 2, (customer.longitude + staff.longitude) / 2)
        : customer;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Live tracking', style: Theme.of(context).textTheme.titleMedium),
        if (staffName != null) ...[
          const SizedBox(height: 4),
          Text(
            staff != null ? '$staffName is on the way' : 'Waiting for $staffName location…',
            style: const TextStyle(color: AppTheme.muted, fontSize: 13),
          ),
        ],
        if (updatedAt != null && staff != null) ...[
          const SizedBox(height: 2),
          Text('Updated $updatedAt', style: const TextStyle(color: AppTheme.muted, fontSize: 12)),
        ],
        const SizedBox(height: 8),
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: SizedBox(
            height: 220,
            child: FlutterMap(
              options: MapOptions(
                initialCenter: center,
                initialZoom: staff != null ? 13.5 : 15,
                initialCameraFit: staff != null ? CameraFit.bounds(bounds: bounds, padding: const EdgeInsets.all(48)) : null,
                interactionOptions: const InteractionOptions(flags: InteractiveFlag.all & ~InteractiveFlag.rotate),
              ),
              children: [
                TileLayer(
                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                  userAgentPackageName: 'com.shineexpress.shine_express_app',
                ),
                MarkerLayer(
                  markers: [
                    Marker(
                      point: customer,
                      width: 36,
                      height: 36,
                      child: const Icon(Icons.home, color: AppTheme.brandDark, size: 32),
                    ),
                    if (staff != null)
                      Marker(
                        point: staff,
                        width: 36,
                        height: 36,
                        child: const Icon(Icons.directions_car, color: Colors.green, size: 32),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 4),
        Row(
          children: [
            const Icon(Icons.home, size: 14, color: AppTheme.brandDark),
            const SizedBox(width: 4),
            const Text('Your address', style: TextStyle(fontSize: 12, color: AppTheme.muted)),
            if (staff != null) ...[
              const SizedBox(width: 12),
              const Icon(Icons.directions_car, size: 14, color: Colors.green),
              const SizedBox(width: 4),
              const Text('Staff', style: TextStyle(fontSize: 12, color: AppTheme.muted)),
            ],
          ],
        ),
      ],
    );
  }
}

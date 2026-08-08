import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:url_launcher/url_launcher.dart';

/// Destination for one-tap turn-by-turn navigation (no paid Maps API).
class NavigationTarget {
  const NavigationTarget({
    this.latitude,
    this.longitude,
    this.addressLine,
    this.label,
  });

  final double? latitude;
  final double? longitude;
  final String? addressLine;
  final String? label;

  bool get hasCoordinates => latitude != null && longitude != null;

  bool get hasAddress => addressLine != null && addressLine!.trim().isNotEmpty;

  bool get isNavigable => hasCoordinates || hasAddress;
}

/// Opens the device's native maps app for turn-by-turn navigation.
class NavigationLauncher {
  /// Tries platform-preferred URIs first, then universal fallbacks.
  static Future<bool> openTurnByTurn(NavigationTarget target) async {
    if (!target.isNavigable) return false;

    final uris = _uriChain(target);
    for (final uri in uris) {
      try {
        final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
        if (launched) return true;
      } catch (_) {
        // Try next scheme in the chain.
      }
    }
    return false;
  }

  static List<Uri> _uriChain(NavigationTarget target) {
    if (!kIsWeb && Platform.isAndroid) {
      return _androidUris(target);
    }
    if (!kIsWeb && Platform.isIOS) {
      return _iosUris(target);
    }
    return _webFallbackUris(target);
  }

  static List<Uri> _androidUris(NavigationTarget target) {
    if (target.hasCoordinates) {
      final lat = target.latitude!;
      final lng = target.longitude!;
      return [
        Uri.parse('google.navigation:q=$lat,$lng'),
        Uri.parse('geo:$lat,$lng?q=$lat,$lng'),
        Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$lat,$lng'),
      ];
    }

    final encoded = Uri.encodeComponent(target.addressLine!.trim());
    return [
      Uri.parse('google.navigation:q=$encoded'),
      Uri.parse('geo:0,0?q=$encoded'),
      Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$encoded'),
    ];
  }

  static List<Uri> _iosUris(NavigationTarget target) {
    if (target.hasCoordinates) {
      final lat = target.latitude!;
      final lng = target.longitude!;
      return [
        Uri.parse('https://maps.apple.com/?daddr=$lat,$lng&dirflg=d'),
        Uri.parse('comgooglemaps://?daddr=$lat,$lng&directionsmode=driving'),
        Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$lat,$lng'),
      ];
    }

    final encoded = Uri.encodeComponent(target.addressLine!.trim());
    return [
      Uri.parse('https://maps.apple.com/?daddr=$encoded&dirflg=d'),
      Uri.parse('comgooglemaps://?daddr=$encoded&directionsmode=driving'),
      Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$encoded'),
    ];
  }

  static List<Uri> _webFallbackUris(NavigationTarget target) {
    if (target.hasCoordinates) {
      final lat = target.latitude!;
      final lng = target.longitude!;
      return [Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$lat,$lng')];
    }
    final encoded = Uri.encodeComponent(target.addressLine!.trim());
    return [Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$encoded')];
  }
}

/// Build a [NavigationTarget] from a job/booking API payload.
NavigationTarget navigationTargetFromJob(Map<String, dynamic> job) {
  final address = job['address'];
  String? line;
  double? lat;
  double? lng;

  if (address is Map) {
    final map = Map<String, dynamic>.from(address);
    final parts = [
      map['line1']?.toString(),
      map['city']?.toString(),
      map['pincode']?.toString(),
    ].where((p) => p != null && p.trim().isNotEmpty).join(', ');
    line = parts.isEmpty ? null : parts;
    lat = _parseCoord(map['latitude']);
    lng = _parseCoord(map['longitude']);
  }

  lat ??= _parseCoord(job['latitude']);
  lng ??= _parseCoord(job['longitude']);

  return NavigationTarget(
    latitude: lat,
    longitude: lng,
    addressLine: line,
    label: job['customerName']?.toString() ?? 'Customer',
  );
}

double? _parseCoord(dynamic value) {
  if (value == null) return null;
  return double.tryParse(value.toString());
}

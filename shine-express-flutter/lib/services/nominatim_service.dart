import 'dart:convert';

import 'package:http/http.dart' as http;

/// Reverse geocoding via OpenStreetMap Nominatim (free, no API key).
class NominatimService {
  static const _userAgent = 'ShineExpressApp/1.0 (contact@shineexpress.local)';

  static Future<NominatimAddress?> reverseGeocode(double latitude, double longitude) async {
    final uri = Uri.https('nominatim.openstreetmap.org', '/reverse', {
      'format': 'json',
      'lat': latitude.toString(),
      'lon': longitude.toString(),
      'addressdetails': '1',
      'zoom': '18',
    });

    final res = await http.get(uri, headers: {'User-Agent': _userAgent});
    if (res.statusCode != 200) return null;

    final data = jsonDecode(res.body);
    if (data is! Map<String, dynamic>) return null;

    final addr = data['address'];
    if (addr is! Map<String, dynamic>) {
      final display = data['display_name']?.toString();
      if (display == null || display.isEmpty) return null;
      return NominatimAddress(line1: display, city: '', state: null, pincode: null);
    }

    final parts = <String>[];
    for (final key in ['house_number', 'road', 'suburb', 'neighbourhood', 'quarter']) {
      final v = addr[key]?.toString().trim();
      if (v != null && v.isNotEmpty) parts.add(v);
    }

    final city = _firstNonEmpty(addr, ['city', 'town', 'village', 'county', 'state_district']);
    final state = addr['state']?.toString();
    final pincode = addr['postcode']?.toString();

    return NominatimAddress(
      line1: parts.isNotEmpty ? parts.join(', ') : (data['display_name']?.toString() ?? ''),
      city: city,
      state: state,
      pincode: pincode,
    );
  }

  static String _firstNonEmpty(Map<String, dynamic> addr, List<String> keys) {
    for (final key in keys) {
      final v = addr[key]?.toString().trim();
      if (v != null && v.isNotEmpty) return v;
    }
    return '';
  }
}

class NominatimAddress {
  const NominatimAddress({
    required this.line1,
    required this.city,
    this.state,
    this.pincode,
  });

  final String line1;
  final String city;
  final String? state;
  final String? pincode;
}

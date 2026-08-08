import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../services/upi_payment_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/staff_tracking_map.dart';

class BookingDetailScreen extends StatefulWidget {
  const BookingDetailScreen({super.key, required this.id});
  final String id;

  @override
  State<BookingDetailScreen> createState() => _BookingDetailScreenState();
}

class _BookingDetailScreenState extends State<BookingDetailScreen> {
  Map<String, dynamic>? booking;
  Map<String, dynamic>? staffTracking;
  String? error;
  double rating = 5;
  final comment = TextEditingController();
  bool busy = false;
  Timer? _trackingPoll;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _trackingPoll?.cancel();
    comment.dispose();
    super.dispose();
  }

  void _configureTrackingPoll() {
    _trackingPoll?.cancel();
    final status = booking?['status']?.toString() ?? '';
    final trackable = status == 'ON_THE_WAY' || status == 'ACCEPTED' || status == 'STARTED';
    if (!trackable) return;

    _trackingPoll = Timer.periodic(const Duration(seconds: 20), (_) => _refreshTracking());
  }

  Future<void> _refreshTracking() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/bookings/${widget.id}/tracking');
      if (!mounted) return;
      setState(() => staffTracking = Map<String, dynamic>.from(res as Map));
    } catch (_) {}
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/bookings/${widget.id}');
      setState(() {
        booking = Map<String, dynamic>.from(res as Map);
        staffTracking = booking?['staffTracking'] is Map
            ? Map<String, dynamic>.from(booking!['staffTracking'] as Map)
            : staffTracking;
        error = null;
      });
      _configureTrackingPoll();
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  Future<void> _payUpi() async {
    final b = booking;
    if (b == null) return;

    final upi = Map<String, dynamic>.from((b['upi'] as Map?) ?? {});
    final upiId = upi['upiId']?.toString() ?? '';
    if (upiId.isEmpty) {
      setState(() => error = 'UPI payments are not configured on the server');
      return;
    }

    final amount = UpiPaymentService.formatAmount(b['totalAmount']);
    final merchant = upi['merchantName']?.toString() ?? 'Shine Express';
    final ref = b['bookingNumber']?.toString() ?? widget.id;
    final note = 'Shine Express ${b['bookingNumber']}';

    setState(() => busy = true);
    try {
      final result = await UpiPaymentService.payWithConfirmation(
        context,
        upiId: upiId,
        payeeName: merchant,
        amount: amount,
        transactionRef: ref,
        note: note,
      );

      if (result == null) return;

      final txnId = result['transactionId'] ?? '';
      if (!mounted) return;
      await context.read<ApiClient>().post('/api/v1/bookings/${widget.id}/pay-upi', body: {
        'transactionRef': ref,
        if (txnId.isNotEmpty) 'transactionId': txnId,
        'status': result['status'] ?? 'SUCCESS',
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('UPI payment recorded. Thank you!')),
        );
      }
      await _load();
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> _complete() async {
    setState(() => busy = true);
    try {
      await context.read<ApiClient>().post('/api/v1/bookings/${widget.id}/complete', body: {
        'rating': rating.round(),
        'comment': comment.text.trim(),
      });
      await _load();
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      setState(() => busy = false);
    }
  }

  Future<void> _review() async {
    setState(() => busy = true);
    try {
      await context.read<ApiClient>().post('/api/v1/bookings/${widget.id}/review', body: {
        'rating': rating.round(),
        'comment': comment.text.trim(),
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Review saved')));
      }
      await _load();
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final b = booking;
    final payment = b?['payment'] is Map ? Map<String, dynamic>.from(b!['payment'] as Map) : null;
    final isPaid = payment != null;
    final canPayUpi = b?['canPayUpi'] == true;
    final tracking = staffTracking;
    final address = b?['address'] is Map ? Map<String, dynamic>.from(b!['address'] as Map) : null;
    final showTracking = tracking?['active'] == true &&
        address?['latitude'] != null &&
        address?['longitude'] != null;

    return Scaffold(
      appBar: AppBar(title: Text(b?['bookingNumber']?.toString() ?? 'Booking')),
      body: b == null
          ? Center(child: error != null ? Text(error!) : const CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(b['serviceName']?.toString() ?? '', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Chip(label: Text(b['statusLabel']?.toString() ?? b['status']?.toString() ?? '')),
                const SizedBox(height: 8),
                Text('When: ${b['scheduledDate']} ${b['scheduledTime'] ?? ''}'),
                Text(
                  isPaid
                      ? 'Paid: ₹${payment['amount']} via ${payment['method']}'
                      : 'Total: ₹${b['totalAmount']} (UPI or cash on completion)',
                ),
                if (b['address'] != null) Text('Address: ${_formatAddress(b['address'])}'),
                if (showTracking) ...[
                  const SizedBox(height: 16),
                  StaffTrackingMap(
                    customerLat: (address!['latitude'] as num).toDouble(),
                    customerLng: (address['longitude'] as num).toDouble(),
                    staffLat: tracking?['available'] == true ? (tracking!['latitude'] as num?)?.toDouble() : null,
                    staffLng: tracking?['available'] == true ? (tracking!['longitude'] as num?)?.toDouble() : null,
                    staffName: tracking?['staffName']?.toString(),
                    updatedAt: tracking?['updatedAt']?.toString(),
                  ),
                ],
                if (b['customerNotes'] != null) Text('Notes: ${b['customerNotes']}'),
                if (b['items'] is List && (b['items'] as List).isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const Text('Selected services', style: TextStyle(fontWeight: FontWeight.w600)),
                  ...(b['items'] as List).map((item) {
                    final m = Map<String, dynamic>.from(item as Map);
                    return ListTile(
                      contentPadding: EdgeInsets.zero,
                      dense: true,
                      title: Text(m['name']?.toString() ?? ''),
                      trailing: Text('₹${m['price'] ?? ''}'),
                    );
                  }),
                ],
                if (b['assignedStaff'] is List && (b['assignedStaff'] as List).isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const Text('Assigned team', style: TextStyle(fontWeight: FontWeight.w600)),
                  ...(b['assignedStaff'] as List).map((member) {
                    final m = Map<String, dynamic>.from(member as Map);
                    final primary = m['isPrimary'] == true;
                    return ListTile(
                      contentPadding: EdgeInsets.zero,
                      dense: true,
                      leading: const Icon(Icons.person_outline),
                      title: Text(m['name']?.toString() ?? ''),
                      subtitle: primary ? const Text('Primary contact') : null,
                      trailing: Text(m['employeeCode']?.toString() ?? ''),
                    );
                  }),
                ],
                if (canPayUpi) ...[
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: busy ? null : _payUpi,
                      icon: const Icon(Icons.account_balance_wallet_outlined),
                      label: Text('Pay ₹${b['totalAmount']} via UPI'),
                      style: FilledButton.styleFrom(
                        backgroundColor: AppTheme.brand,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                    ),
                  ),
                  const Padding(
                    padding: EdgeInsets.only(top: 6),
                    child: Text(
                      'Opens GPay, PhonePe, Paytm, or BHIM — no payment gateway fee.',
                      style: TextStyle(fontSize: 12, color: AppTheme.muted),
                    ),
                  ),
                ],
                if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),
                const Divider(height: 32),
                const Text('Rate this service', style: TextStyle(fontWeight: FontWeight.w600)),
                RatingBar.builder(
                  initialRating: rating,
                  minRating: 1,
                  itemCount: 5,
                  itemBuilder: (_, __) => const Icon(Icons.star, color: Colors.amber),
                  onRatingUpdate: (v) => rating = v,
                ),
                TextField(controller: comment, decoration: const InputDecoration(labelText: 'Comment'), maxLines: 2),
                const SizedBox(height: 12),
                if (b['status'] == 'STARTED')
                  FilledButton(
                    onPressed: busy ? null : _complete,
                    style: FilledButton.styleFrom(backgroundColor: AppTheme.brand),
                    child: Text(isPaid ? 'Mark complete' : 'Mark complete & pay cash'),
                  ),
                if (b['status'] == 'COMPLETED')
                  OutlinedButton(onPressed: busy ? null : _review, child: const Text('Submit review')),
              ],
            ),
    );
  }

  String _formatAddress(dynamic address) {
    if (address is String) return address;
    if (address is Map) {
      final m = Map<String, dynamic>.from(address);
      return '${m['label'] ?? ''} — ${m['line1'] ?? ''}, ${m['city'] ?? ''}';
    }
    return address?.toString() ?? '';
  }
}

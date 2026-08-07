import 'package:flutter/material.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:provider/provider.dart';
import '../../services/api_client.dart';
import '../../theme/app_theme.dart';

class BookingDetailScreen extends StatefulWidget {
  const BookingDetailScreen({super.key, required this.id});
  final String id;

  @override
  State<BookingDetailScreen> createState() => _BookingDetailScreenState();
}

class _BookingDetailScreenState extends State<BookingDetailScreen> {
  Map<String, dynamic>? booking;
  String? error;
  double rating = 5;
  final comment = TextEditingController();
  bool busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await context.read<ApiClient>().get('/api/v1/bookings/${widget.id}');
      setState(() {
        booking = Map<String, dynamic>.from(res as Map);
        error = null;
      });
    } catch (e) {
      setState(() => error = e.toString());
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
                Text('Total: ₹${b['totalAmount']} (cash on completion)'),
                if (b['address'] != null) Text('Address: ${_formatAddress(b['address'])}'),
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
                    child: const Text('Mark complete & pay cash'),
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

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

/// UPI deep-link payments (zero gateway fee — uses installed UPI apps).
class UpiPaymentService {
  static String formatAmount(dynamic amount) {
    final value = (amount is num) ? amount.toDouble() : double.tryParse('$amount') ?? 0;
    return value.toStringAsFixed(2);
  }

  static Uri buildUpiUri({
    required String upiId,
    required String payeeName,
    required String amount,
    required String transactionRef,
    required String note,
  }) {
    return Uri.parse(
      'upi://pay?pa=${Uri.encodeComponent(upiId)}'
      '&pn=${Uri.encodeComponent(payeeName)}'
      '&am=$amount'
      '&cu=INR'
      '&tr=${Uri.encodeComponent(transactionRef)}'
      '&tn=${Uri.encodeComponent(note)}',
    );
  }

  static Future<bool> launchPayment({
    required String upiId,
    required String payeeName,
    required String amount,
    required String transactionRef,
    required String note,
  }) async {
    final uri = buildUpiUri(
      upiId: upiId,
      payeeName: payeeName,
      amount: amount,
      transactionRef: transactionRef,
      note: note,
    );
    try {
      return await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      return false;
    }
  }

  /// Opens UPI app, then asks customer to confirm payment and optional txn id.
  static Future<Map<String, String>?> confirmAfterLaunch(BuildContext context) async {
    final txnController = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirm UPI payment'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text('After paying in your UPI app, confirm below.'),
            const SizedBox(height: 12),
            TextField(
              controller: txnController,
              decoration: const InputDecoration(
                labelText: 'UPI transaction ID (optional)',
                hintText: 'From GPay / PhonePe receipt',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Payment done')),
        ],
      ),
    );

    if (confirmed != true) return null;

    return {
      'transactionId': txnController.text.trim(),
      'status': 'SUCCESS',
    };
  }

  /// Full flow: launch UPI intent + confirmation dialog.
  static Future<Map<String, String>?> payWithConfirmation(
    BuildContext context, {
    required String upiId,
    required String payeeName,
    required String amount,
    required String transactionRef,
    required String note,
  }) async {
    final launched = await launchPayment(
      upiId: upiId,
      payeeName: payeeName,
      amount: amount,
      transactionRef: transactionRef,
      note: note,
    );
    if (!context.mounted) return null;

    if (!launched) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open UPI. Install GPay, PhonePe, or Paytm.')),
      );
      return null;
    }

    return confirmAfterLaunch(context);
  }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

final class PaymentService extends BaseService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array{enabled:bool,upiId:string,merchantName:string} */
    public function publicConfig(): array
    {
        $upiId = trim((string) env_file('UPI_VPA', ''));
        return [
            'enabled' => $upiId !== '' && filter_var(env_file('UPI_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
            'upiId' => $upiId,
            'merchantName' => (string) env_file('UPI_MERCHANT_NAME', env_file('APP_NAME', 'Shine Express')),
        ];
    }

    /** @return array<string, mixed>|null */
    public function bookingPayment(string $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, method, status, amount, transaction_id AS transactionId, paid_at AS paidAt
             FROM payments WHERE booking_id = ? AND status = 'COMPLETED'
             ORDER BY paid_at DESC LIMIT 1"
        );
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        $row['amount'] = (float) $row['amount'];
        return $row;
    }

    /**
     * Record a UPI payment reported by the customer app after UPI intent return.
     *
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    public function recordUpiPayment(
        string $bookingId,
        string $customerId,
        string $transactionRef,
        ?string $transactionId,
        array $meta = []
    ): array {
        if ($transactionRef === '') {
            throw new RuntimeException('Transaction reference is required');
        }

        $booking = $this->fetchOne(
            'SELECT * FROM bookings WHERE id = ? AND customer_id = ?',
            [$bookingId, $customerId]
        );
        if (!$booking) {
            throw new RuntimeException('Booking not found');
        }

        $allowed = ['STARTED', 'COMPLETED', 'CONFIRMED', 'ASSIGNED', 'ACCEPTED', 'ON_THE_WAY'];
        if (!in_array($booking['status'], $allowed, true)) {
            throw new RuntimeException('UPI payment is not available for this booking status');
        }

        $existing = $this->bookingPayment($bookingId);
        if ($existing !== null) {
            throw new RuntimeException('This booking is already paid');
        }

        $dup = $this->fetchOne(
            "SELECT id FROM payments WHERE transaction_id = ? AND status = 'COMPLETED' LIMIT 1",
            [$transactionId ?: $transactionRef]
        );
        if ($dup) {
            throw new RuntimeException('This transaction reference was already used');
        }

        $paymentId = generate_id();
        $txn = $transactionId ?: $transactionRef;
        $this->db->prepare(
            "INSERT INTO payments (id, booking_id, customer_id, amount, method, status, transaction_id, gateway_response, paid_at)
             VALUES (?,?,?,?,'UPI','COMPLETED',?,?,NOW(3))"
        )->execute([
            $paymentId,
            $bookingId,
            $customerId,
            $booking['total_amount'],
            $txn,
            json_encode(array_merge(['transactionRef' => $transactionRef], $meta)),
        ]);

        (new NotificationService())->paymentReceived($bookingId, (float) $booking['total_amount'], 'UPI');

        return [
            'id' => $paymentId,
            'method' => 'UPI',
            'status' => 'COMPLETED',
            'amount' => (float) $booking['total_amount'],
            'transactionId' => $txn,
        ];
    }

    /** @param list<mixed> $params */
    private function fetchOne(string $sql, array $params): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}

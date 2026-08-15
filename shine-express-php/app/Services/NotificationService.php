<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\BookingStatus;
use PDO;

final class NotificationService extends BaseService
{
    private PDO $db;
    private FcmService $fcm;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->fcm = new FcmService();
    }

    /** @return array{attempted:int,sent:int,failed:int,skipped_reason:?string} */
    public function notify(string $userId, string $title, string $body, string $type = 'GENERAL', array $metadata = []): array
    {
        $this->db->prepare(
            'INSERT INTO notifications (id, user_id, title, body, type, channel, metadata, sent_at)
             VALUES (?,?,?,?,?,"IN_APP",?,NOW(3))'
        )->execute([
            generate_id(),
            $userId,
            $title,
            $body,
            $type,
            json_encode($metadata),
        ]);

        $data = array_map('strval', array_merge(['type' => $type], $metadata));
        return $this->fcm->sendToUser($userId, $title, $body, $data);
    }

    public function bookingCreated(string $bookingId): void
    {
        $b = $this->bookingContext($bookingId);
        if (!$b) {
            return;
        }
        $summary = $this->serviceSummary($bookingId, $b['service_name']);
        $this->notify(
            $b['customer_user_id'],
            'Booking created',
            "Your booking {$b['booking_number']} for {$summary} on {$b['scheduled_date']} at {$b['scheduled_time']} is pending confirmation.",
            'BOOKING_CREATED',
            ['bookingId' => $bookingId]
        );
    }

    public function bookingStatusChanged(string $bookingId, string $status): void
    {
        $b = $this->bookingContext($bookingId);
        if (!$b) {
            return;
        }

        $summary = $this->serviceSummary($bookingId, $b['service_name']);

        $map = [
            BookingStatus::CONFIRMED => ['BOOKING_CONFIRMED', 'Booking confirmed', "Booking {$b['booking_number']} has been confirmed."],
            BookingStatus::ASSIGNED => ['BOOKING_ASSIGNED', 'Staff assigned', "Staff has been assigned to booking {$b['booking_number']}."],
            BookingStatus::ON_THE_WAY => ['BOOKING_STARTED', 'Team on the way', "Our team is on the way for {$summary} ({$b['booking_number']})."],
            BookingStatus::STARTED => ['BOOKING_STARTED', 'Service started', "Our team has started your {$summary} service ({$b['booking_number']})."],
            BookingStatus::COMPLETED => ['BOOKING_COMPLETED', 'Service completed', "Your booking {$b['booking_number']} is complete. Thank you for choosing Shine Express."],
            BookingStatus::CANCELLED => ['BOOKING_CANCELLED', 'Booking cancelled', "Booking {$b['booking_number']} has been cancelled."],
        ];

        if (!isset($map[$status])) {
            return;
        }
        [$type, $title, $body] = $map[$status];
        $this->notify($b['customer_user_id'], $title, $body, $type, ['bookingId' => $bookingId]);

        (new BookingAlertService())->onStatusChange($bookingId, $status);
    }

    public function bookingAssigned(string $bookingId): void
    {
        $b = $this->bookingContext($bookingId);
        if (!$b) {
            return;
        }

        $this->notify(
            $b['customer_user_id'],
            'Staff assigned',
            "Staff has been assigned to booking {$b['booking_number']}.",
            'BOOKING_ASSIGNED',
            ['bookingId' => $bookingId]
        );

        $summary = $this->serviceSummary($bookingId, $b['service_name']);

        $stmt = $this->db->prepare(
            'SELECT e.user_id FROM booking_assignments ba JOIN employees e ON e.id = ba.employee_id
             WHERE ba.booking_id = ? AND ba.rejected_at IS NULL'
        );
        $stmt->execute([$bookingId]);
        foreach ($stmt->fetchAll() as $row) {
            $this->notify(
                $row['user_id'],
                'New job assigned',
                "You have been assigned to {$b['booking_number']} — {$summary} on {$b['scheduled_date']} at {$b['scheduled_time']}.",
                'BOOKING_ASSIGNED',
                ['bookingId' => $bookingId]
            );
        }

        (new BookingAlertService())->onAssigned($bookingId);
    }

    public function paymentReceived(string $bookingId, float $amount, string $method): void
    {
        $b = $this->bookingContext($bookingId);
        if (!$b) {
            return;
        }
        $formatted = money_format_inr($amount);
        $this->notify(
            $b['customer_user_id'],
            'Payment received',
            "We received your {$method} payment of {$formatted} for booking {$b['booking_number']}.",
            'PAYMENT_RECEIVED',
            ['bookingId' => $bookingId, 'method' => $method]
        );
    }

    private function serviceSummary(string $bookingId, string $fallback): string
    {
        $stmt = $this->db->prepare('SELECT name FROM booking_items WHERE booking_id = ? ORDER BY name');
        $stmt->execute([$bookingId]);
        $names = array_values(array_unique(array_column($stmt->fetchAll(), 'name')));
        if (count($names) > 1) {
            return implode(', ', $names);
        }
        if (count($names) === 1) {
            return $names[0];
        }
        return $fallback;
    }

    /** @return array<string, mixed>|null */
    private function bookingContext(string $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, s.name AS service_name, c.user_id AS customer_user_id
             FROM bookings b
             JOIN services s ON s.id = b.service_id
             JOIN customers c ON c.id = b.customer_id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}

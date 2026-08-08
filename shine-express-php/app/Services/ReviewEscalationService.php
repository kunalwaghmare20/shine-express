<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ReviewEscalationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function escalateIfLowRating(string $bookingId, int $rating, ?string $comment = null): bool
    {
        if ($rating > 2) {
            return false;
        }

        $booking = $this->fetchBooking($bookingId);
        if ($booking === null) {
            return false;
        }

        $this->db->prepare('UPDATE bookings SET requires_followup = 1 WHERE id = ?')->execute([$bookingId]);

        $customer = trim((string) ($booking['customer_name'] ?? 'Customer'));
        $number = (string) $booking['booking_number'];
        $stars = (string) $rating;
        $body = "Booking {$number} ({$customer}) received {$stars} star(s)";
        if ($comment !== null && trim($comment) !== '') {
            $body .= ': "' . mb_substr(trim($comment), 0, 200) . '"';
        }
        $body .= '. Immediate customer callback recommended.';

        $notify = new NotificationService();
        foreach ($this->adminRecipients((string) $booking['branch_id']) as $userId) {
            $notify->notify(
                $userId,
                'Urgent: low rating — follow up required',
                $body,
                'GENERAL',
                ['bookingId' => $bookingId, 'rating' => (string) $rating, 'requiresFollowup' => '1']
            );
        }

        return true;
    }

    /** @return list<string> */
    private function adminRecipients(string $branchId): array
    {
        $ids = [];

        $super = $this->db->query(
            "SELECT id FROM users WHERE role = 'SUPER_ADMIN' AND deleted_at IS NULL AND is_active = 1"
        );
        foreach ($super->fetchAll() as $row) {
            $ids[$row['id']] = true;
        }

        $stmt = $this->db->prepare(
            "SELECT u.id FROM users u
             JOIN employees e ON e.user_id = u.id AND e.deleted_at IS NULL
             WHERE u.role = 'BRANCH_MANAGER' AND e.branch_id = ? AND u.deleted_at IS NULL AND u.is_active = 1"
        );
        $stmt->execute([$branchId]);
        foreach ($stmt->fetchAll() as $row) {
            $ids[$row['id']] = true;
        }

        return array_keys($ids);
    }

    /** @return array<string, mixed>|null */
    private function fetchBooking(string $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT b.id, b.booking_number, b.branch_id,
                    CONCAT(u.first_name, " ", u.last_name) AS customer_name
             FROM bookings b
             JOIN customers c ON c.id = b.customer_id
             JOIN users u ON u.id = c.user_id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}

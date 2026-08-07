<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\BookingStatus;
use PDO;
use RuntimeException;

final class BookingService extends BaseService
{
    private const TAX_RATE = 18.0;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @param array{service_id?:string,service_ids?:list<string>,address_id:string,branch_id:string,scheduled_date:string,scheduled_time:string,customer_notes?:?string,service_item_ids?:list<string>,customer_id?:string} $input */
    public function create(array $input, string $actorUserId, ?string $forcedCustomerId = null): string
    {
        $customerId = $forcedCustomerId ?? ($input['customer_id'] ?? null);
        if (!$customerId) {
            throw new RuntimeException('Customer is required');
        }

        $serviceIds = array_values(array_unique(array_filter(
            array_map('strval', $input['service_ids'] ?? []),
            fn ($id) => $id !== ''
        )));
        if ($serviceIds === [] && !empty($input['service_id'])) {
            $serviceIds = [(string) $input['service_id']];
        }
        if ($serviceIds === []) {
            throw new RuntimeException('Select at least one service');
        }

        $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT * FROM services WHERE is_active = 1 AND id IN ({$placeholders})"
        );
        $stmt->execute($serviceIds);
        $services = $stmt->fetchAll();
        if (count($services) !== count($serviceIds)) {
            throw new RuntimeException('One or more services are not available');
        }
        $servicesById = [];
        foreach ($services as $svc) {
            $servicesById[$svc['id']] = $svc;
        }
        // Preserve selection order
        $orderedServices = [];
        foreach ($serviceIds as $sid) {
            $orderedServices[] = $servicesById[$sid];
        }
        $primaryService = $orderedServices[0];

        $address = $this->fetchOne(
            'SELECT * FROM addresses WHERE id = ? AND customer_id = ?',
            [$input['address_id'], $customerId]
        );
        if (!$address) {
            throw new RuntimeException('Address not found');
        }

        $branch = $this->fetchOne('SELECT * FROM branches WHERE id = ? AND is_active = 1', [$input['branch_id']]);
        if (!$branch) {
            throw new RuntimeException('Branch not found');
        }

        $itemIds = array_values(array_unique(array_filter(
            array_map('strval', $input['service_item_ids'] ?? []),
            fn ($id) => $id !== ''
        )));
        $lines = [];
        $coveredServiceIds = [];

        if ($itemIds !== []) {
            $itemPlaceholders = implode(',', array_fill(0, count($itemIds), '?'));
            $svcPlaceholders = implode(',', array_fill(0, count($serviceIds), '?'));
            $itemStmt = $this->db->prepare(
                "SELECT * FROM service_items
                 WHERE is_active = 1
                   AND id IN ({$itemPlaceholders})
                   AND service_id IN ({$svcPlaceholders})"
            );
            $itemStmt->execute(array_merge($itemIds, $serviceIds));
            $selected = $itemStmt->fetchAll();
            foreach ($selected as $item) {
                $coveredServiceIds[$item['service_id']] = true;
                $lines[] = [
                    'service_item_id' => $item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'price' => (float) $item['price'],
                    'quantity' => 1,
                    'duration' => (int) ($item['duration'] ?? 0),
                ];
            }
        }

        // Services with no selected packages still add a base-price line
        foreach ($orderedServices as $service) {
            if (isset($coveredServiceIds[$service['id']])) {
                continue;
            }
            $lines[] = [
                'service_item_id' => null,
                'name' => $service['name'],
                'description' => $service['description'],
                'price' => (float) $service['base_price'],
                'quantity' => 1,
                'duration' => (int) $service['duration'],
            ];
        }

        $subtotal = array_reduce($lines, fn ($s, $l) => $s + $l['price'] * $l['quantity'], 0.0);
        $taxAmount = round(($subtotal * self::TAX_RATE) / 100, 2);
        $total = $subtotal + $taxAmount;
        $duration = array_reduce($lines, fn ($s, $l) => $s + ($l['duration'] ?: 0), 0)
            ?: (int) $primaryService['duration'];

        $bookingId = generate_id();
        $number = 'SE-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $this->db->prepare(
            'INSERT INTO bookings (id, booking_number, customer_id, branch_id, service_id, address_id, status, scheduled_date, scheduled_time, estimated_duration, customer_notes, subtotal, tax_rate, tax_amount, discount, total_amount)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?)'
        )->execute([
            $bookingId, $number, $customerId, $input['branch_id'], $primaryService['id'], $input['address_id'],
            BookingStatus::PENDING, $input['scheduled_date'], $input['scheduled_time'], $duration,
            $input['customer_notes'] ?? null, $subtotal, self::TAX_RATE, $taxAmount, $total,
        ]);

        $insItem = $this->db->prepare(
            'INSERT INTO booking_items (id, booking_id, service_item_id, name, description, price, quantity) VALUES (?,?,?,?,?,?,?)'
        );
        foreach ($lines as $line) {
            $insItem->execute([
                generate_id(), $bookingId, $line['service_item_id'], $line['name'], $line['description'],
                $line['price'], $line['quantity'],
            ]);
        }

        $this->recordHistory($bookingId, null, BookingStatus::PENDING, $actorUserId, 'Booking created');

        $notify = new NotificationService();
        $notify->bookingCreated($bookingId);

        return $bookingId;
    }

    public function updateStatus(string $bookingId, string $to, string $actorUserId, ?string $notes = null, ?string $cancelReason = null): void
    {
        $booking = $this->fetchOne('SELECT * FROM bookings WHERE id = ?', [$bookingId]);
        if (!$booking) {
            throw new RuntimeException('Booking not found');
        }

        $from = $booking['status'];
        if (!BookingStatus::canTransition($from, $to)) {
            throw new RuntimeException("Invalid status transition: {$from} → {$to}");
        }

        $fields = ['status = ?'];
        $params = [$to];

        if ($to === BookingStatus::ACCEPTED) {
            $fields[] = 'accepted_at = NOW(3)';
        }
        if ($to === BookingStatus::STARTED) {
            $fields[] = 'started_at = NOW(3)';
        }
        if ($to === BookingStatus::COMPLETED) {
            $fields[] = 'completed_at = NOW(3)';
        }
        if ($to === BookingStatus::ASSIGNED) {
            $fields[] = 'assigned_at = NOW(3)';
        }
        if ($to === BookingStatus::CANCELLED) {
            $fields[] = 'cancelled_at = NOW(3)';
            $fields[] = 'cancellation_reason = ?';
            $params[] = $cancelReason ?? $notes;
        }

        $params[] = $bookingId;
        $this->db->prepare('UPDATE bookings SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
        $this->recordHistory($bookingId, $from, $to, $actorUserId, $notes);

        if ($to === BookingStatus::COMPLETED) {
            $this->ensureCashPayment($booking);
        }

        (new NotificationService())->bookingStatusChanged($bookingId, $to);
    }

    /** @param list<string> $employeeIds */
    public function assignStaff(string $bookingId, array $employeeIds, string $actorUserId, ?string $primaryId = null): void
    {
        $booking = $this->fetchOne('SELECT * FROM bookings WHERE id = ?', [$bookingId]);
        if (!$booking) {
            throw new RuntimeException('Booking not found');
        }

        $allowed = [BookingStatus::PENDING, BookingStatus::CONFIRMED, BookingStatus::ASSIGNED, BookingStatus::REJECTED];
        if (!in_array($booking['status'], $allowed, true)) {
            throw new RuntimeException('Staff can only be assigned in pending/confirmed/assigned/rejected state');
        }

        if ($employeeIds === []) {
            throw new RuntimeException('Select at least one staff member');
        }

        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE id IN ({$placeholders}) AND deleted_at IS NULL");
        $stmt->execute($employeeIds);
        $staff = $stmt->fetchAll();
        if (count($staff) !== count($employeeIds)) {
            throw new RuntimeException('One or more employees not found');
        }
        foreach ($staff as $emp) {
            if ($emp['branch_id'] !== $booking['branch_id']) {
                throw new RuntimeException('All assigned staff must belong to the booking branch');
            }
        }

        $this->db->prepare('DELETE FROM booking_assignments WHERE booking_id = ?')->execute([$bookingId]);
        $primary = $primaryId ?? $employeeIds[0];
        $ins = $this->db->prepare(
            'INSERT INTO booking_assignments (id, booking_id, employee_id, assigned_by_id, is_primary) VALUES (?,?,?,?,?)'
        );
        foreach ($employeeIds as $eid) {
            $ins->execute([generate_id(), $bookingId, $eid, $actorUserId, $eid === $primary ? 1 : 0]);
        }

        $from = $booking['status'];
        if ($from !== BookingStatus::ASSIGNED) {
            if ($from === BookingStatus::PENDING) {
                $this->db->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([BookingStatus::CONFIRMED, $bookingId]);
                $this->recordHistory($bookingId, BookingStatus::PENDING, BookingStatus::CONFIRMED, $actorUserId, 'Auto-confirmed before assignment');
                $from = BookingStatus::CONFIRMED;
            }
            if (!BookingStatus::canTransition($from, BookingStatus::ASSIGNED)) {
                throw new RuntimeException("Cannot assign from {$from}");
            }
            $this->db->prepare('UPDATE bookings SET status = ?, assigned_at = NOW(3) WHERE id = ?')
                ->execute([BookingStatus::ASSIGNED, $bookingId]);
            $this->recordHistory($bookingId, $from, BookingStatus::ASSIGNED, $actorUserId, 'Staff assigned');
        }

        (new NotificationService())->bookingAssigned($bookingId);
    }

    /** @param array<string, mixed> $booking */
    private function ensureCashPayment(array $booking): void
    {
        $existing = $this->fetchOne(
            "SELECT id FROM payments WHERE booking_id = ? AND status = 'COMPLETED' LIMIT 1",
            [$booking['id']]
        );
        if ($existing) {
            return;
        }

        $this->db->prepare(
            "INSERT INTO payments (id, booking_id, customer_id, amount, method, status, paid_at)
             VALUES (?,?,?,?,'CASH','COMPLETED',NOW(3))"
        )->execute([generate_id(), $booking['id'], $booking['customer_id'], $booking['total_amount']]);
    }

    private function recordHistory(string $bookingId, ?string $from, string $to, ?string $userId, ?string $notes): void
    {
        $this->db->prepare(
            'INSERT INTO booking_status_history (id, booking_id, from_status, to_status, changed_by_id, notes) VALUES (?,?,?,?,?,?)'
        )->execute([generate_id(), $bookingId, $from, $to, $userId, $notes]);
    }

    /** @param list<mixed> $params @return array<string, mixed>|null */
    private function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}

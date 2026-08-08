<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\ApiResponse;
use App\Core\Database;
use App\Core\Request;
use App\Helpers\BookingStatus;
use App\Services\BookingService;
use RuntimeException;

final class ApiEmployeeJobController
{
    /** Jobs assigned to this employee */
    public function index(): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }

        $stmt = Database::connection()->prepare(
            'SELECT b.id, b.booking_number AS bookingNumber, b.status, b.scheduled_date AS scheduledDate,
                    b.scheduled_time AS scheduledTime, b.total_amount AS totalAmount,
                    s.name AS serviceName,
                    CONCAT(u.first_name, " ", u.last_name) AS customerName,
                    u.phone AS customerPhone,
                    a.line1, a.city, a.pincode, a.latitude, a.longitude,
                    ba.is_primary AS isPrimary
             FROM booking_assignments ba
             JOIN bookings b ON b.id = ba.booking_id
             JOIN services s ON s.id = b.service_id
             JOIN customers c ON c.id = b.customer_id
             JOIN users u ON u.id = c.user_id
             JOIN addresses a ON a.id = b.address_id
             WHERE ba.employee_id = ? AND ba.rejected_at IS NULL
             ORDER BY b.scheduled_date DESC, b.scheduled_time DESC'
        );
        $stmt->execute([$emp['id']]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $items = $this->bookingItems($r['id']);
            $itemNames = array_values(array_unique(array_map(fn ($i) => $i['name'], $items)));
            $r['totalAmount'] = (float) $r['totalAmount'];
            $r['statusLabel'] = BookingStatus::label($r['status']);
            $r['isPrimary'] = (bool) $r['isPrimary'];
            $r['serviceName'] = $this->serviceLabel($r['serviceName'], $items);
            $r['serviceNames'] = $itemNames;
            $r['itemCount'] = count($items);
            $r['address'] = [
                'line1' => $r['line1'],
                'city' => $r['city'],
                'pincode' => $r['pincode'],
                'latitude' => $r['latitude'],
                'longitude' => $r['longitude'],
            ];
            unset($r['line1'], $r['city'], $r['pincode'], $r['latitude'], $r['longitude']);
        }
        ApiResponse::success($rows);
    }

    public function show(string $id): void
    {
        ApiResponse::success($this->loadJob($id));
    }

    /** Pick / accept a job to serve */
    public function accept(string $id): void
    {
        $this->assertAssigned($id);
        $booking = $this->raw($id);
        $to = match ($booking['status']) {
            BookingStatus::ASSIGNED => BookingStatus::ACCEPTED,
            BookingStatus::ACCEPTED => BookingStatus::ON_THE_WAY,
            BookingStatus::ON_THE_WAY => BookingStatus::STARTED,
            default => null,
        };
        if ($to === null) {
            ApiResponse::error('Cannot progress this job from current status', 400);
        }
        try {
            (new BookingService())->updateStatus($id, $to, (string) ApiAuth::id(), 'Updated via employee app');
            ApiResponse::success($this->loadJob($id), 200, 'Job updated to ' . BookingStatus::label($to));
        } catch (RuntimeException $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function start(string $id): void
    {
        $this->assertAssigned($id);
        try {
            $booking = $this->raw($id);
            if ($booking['status'] === BookingStatus::ASSIGNED) {
                (new BookingService())->updateStatus($id, BookingStatus::ACCEPTED, (string) ApiAuth::id());
                (new BookingService())->updateStatus($id, BookingStatus::ON_THE_WAY, (string) ApiAuth::id());
            } elseif ($booking['status'] === BookingStatus::ACCEPTED) {
                (new BookingService())->updateStatus($id, BookingStatus::ON_THE_WAY, (string) ApiAuth::id());
            }
            (new BookingService())->updateStatus($id, BookingStatus::STARTED, (string) ApiAuth::id(), 'Started via employee app');
            ApiResponse::success($this->loadJob($id), 200, 'Job started');
        } catch (RuntimeException $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function complete(string $id): void
    {
        $emp = $this->assertAssigned($id);
        $booking = $this->raw($id);
        if ($booking['status'] !== BookingStatus::STARTED) {
            ApiResponse::error('Start the job before completing it', 400);
        }

        try {
            (new BookingService())->updateStatus($id, BookingStatus::COMPLETED, (string) ApiAuth::id(), 'Completed via employee app');
        } catch (RuntimeException $e) {
            ApiResponse::error($e->getMessage(), 400);
        }

        ApiResponse::success($this->loadJob($id), 200, 'Job completed');
    }

    public function uploadPhoto(string $id): void
    {
        $emp = $this->assertAssigned($id);
        $type = strtoupper((string) Request::input('type', 'AFTER'));
        if (!in_array($type, ['BEFORE', 'AFTER'], true)) {
            ApiResponse::error('type must be BEFORE or AFTER', 422);
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            ApiResponse::error('Photo upload required', 422);
        }

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            ApiResponse::error('Invalid image type', 422);
        }

        $dir = PUBLIC_PATH . '/uploads/bookings/' . $id;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            ApiResponse::error('Could not create upload directory', 500);
        }

        $filename = $type . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            ApiResponse::error('Upload failed', 500);
        }

        $publicUrl = url('uploads/bookings/' . $id . '/' . $filename);

        Database::connection()->prepare(
            'INSERT INTO photos (id, booking_id, employee_id, url, type, caption) VALUES (?,?,?,?,?,?)'
        )->execute([
            generate_id(), $id, $emp['id'], $publicUrl, $type, Request::input('caption'),
        ]);

        ApiResponse::success(['url' => $publicUrl, 'type' => $type], 201, 'Photo uploaded');
    }

    /** @return array<string, mixed> */
    private function assertAssigned(string $id): array
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM booking_assignments
             WHERE booking_id = ? AND employee_id = ? AND rejected_at IS NULL'
        );
        $stmt->execute([$id, $emp['id']]);
        if (!$stmt->fetch()) {
            ApiResponse::error('Job not assigned to you', 403);
        }
        return $emp;
    }

    /** @return list<array{name:string,price:float,quantity:int}> */
    private function bookingItems(string $bookingId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT name, price, quantity FROM booking_items WHERE booking_id = ? ORDER BY name'
        );
        $stmt->execute([$bookingId]);
        return array_map(fn ($i) => [
            'name' => $i['name'],
            'price' => (float) $i['price'],
            'quantity' => (int) $i['quantity'],
        ], $stmt->fetchAll());
    }

    /** @return list<array{employeeId:string,name:string,employeeCode:string,isPrimary:bool,rejected:bool}> */
    private function assignmentTeam(string $bookingId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT e.id AS employeeId, e.employee_code AS employeeCode, ba.is_primary AS isPrimary,
                    ba.rejected_at AS rejectedAt,
                    CONCAT(u.first_name, " ", u.last_name) AS name
             FROM booking_assignments ba
             JOIN employees e ON e.id = ba.employee_id
             JOIN users u ON u.id = e.user_id
             WHERE ba.booking_id = ?
             ORDER BY ba.is_primary DESC, u.first_name'
        );
        $stmt->execute([$bookingId]);
        return array_map(fn ($a) => [
            'employeeId' => $a['employeeId'],
            'name' => $a['name'],
            'employeeCode' => $a['employeeCode'],
            'isPrimary' => (bool) $a['isPrimary'],
            'rejected' => $a['rejectedAt'] !== null,
        ], $stmt->fetchAll());
    }

    /** @param list<array{name:string}> $items */
    private function serviceLabel(string $fallback, array $items): string
    {
        $names = array_values(array_unique(array_map(fn ($i) => $i['name'], $items)));
        if (count($names) > 1) {
            return implode(', ', $names);
        }
        if (count($names) === 1) {
            return $names[0];
        }
        return $fallback;
    }

    /** @return array<string, mixed> */
    private function raw(string $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row === false) {
            ApiResponse::error('Booking not found', 404);
        }
        return $row;
    }

    /** @return array<string, mixed> */
    private function loadJob(string $id): array
    {
        $emp = $this->assertAssigned($id);
        $stmt = Database::connection()->prepare(
            'SELECT b.*, s.name AS service_name,
                    CONCAT(u.first_name, " ", u.last_name) AS customer_name, u.phone AS customer_phone,
                    a.line1, a.city, a.pincode, a.latitude, a.longitude
             FROM bookings b
             JOIN services s ON s.id = b.service_id
             JOIN customers c ON c.id = b.customer_id
             JOIN users u ON u.id = c.user_id
             JOIN addresses a ON a.id = b.address_id
             WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        $b = $stmt->fetch();

        $photos = Database::connection()->prepare(
            'SELECT id, url, type, caption, created_at AS createdAt FROM photos WHERE booking_id = ? ORDER BY created_at'
        );
        $photos->execute([$id]);

        $notes = Database::connection()->prepare(
            'SELECT id, note, created_at AS createdAt FROM job_notes WHERE booking_id = ? ORDER BY created_at DESC'
        );
        $notes->execute([$id]);

        $checklist = Database::connection()->prepare(
            'SELECT id, label, is_done AS isDone FROM job_checklist_items WHERE booking_id = ? ORDER BY updated_at'
        );
        $checklist->execute([$id]);
        $checkRows = $checklist->fetchAll();
        foreach ($checkRows as &$c) {
            $c['isDone'] = (bool) $c['isDone'];
        }

        $items = $this->bookingItems($id);
        $team = $this->assignmentTeam($id);
        $isPrimary = false;
        foreach ($team as $member) {
            if ($member['employeeId'] === $emp['id'] && $member['isPrimary']) {
                $isPrimary = true;
                break;
            }
        }

        return [
            'id' => $b['id'],
            'bookingNumber' => $b['booking_number'],
            'status' => $b['status'],
            'statusLabel' => BookingStatus::label($b['status']),
            'scheduledDate' => $b['scheduled_date'],
            'scheduledTime' => $b['scheduled_time'],
            'totalAmount' => (float) $b['total_amount'],
            'serviceName' => $this->serviceLabel($b['service_name'], $items),
            'serviceNames' => array_values(array_unique(array_map(fn ($i) => $i['name'], $items))),
            'items' => $items,
            'teamMembers' => $team,
            'isPrimary' => $isPrimary,
            'customerName' => $b['customer_name'],
            'customerPhone' => $b['customer_phone'],
            'customerNotes' => $b['customer_notes'],
            'address' => [
                'line1' => $b['line1'],
                'city' => $b['city'],
                'pincode' => $b['pincode'],
                'latitude' => $b['latitude'],
                'longitude' => $b['longitude'],
            ],
            'photos' => $photos->fetchAll(),
            'notes' => $notes->fetchAll(),
            'checklist' => $checkRows,
            'nextAction' => match ($b['status']) {
                BookingStatus::ASSIGNED => 'accept',
                BookingStatus::ACCEPTED => 'on_the_way',
                BookingStatus::ON_THE_WAY => 'start',
                BookingStatus::STARTED => 'complete',
                default => null,
            },
        ];
    }
}

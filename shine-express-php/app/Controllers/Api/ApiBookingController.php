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

final class ApiBookingController
{
    public function index(): void
    {
        $customerId = ApiAuth::customerId();
        $stmt = Database::connection()->prepare(
            'SELECT b.id, b.booking_number AS bookingNumber, b.status, b.scheduled_date AS scheduledDate,
                    b.scheduled_time AS scheduledTime, b.total_amount AS totalAmount, b.created_at AS createdAt,
                    s.name AS serviceName, s.id AS serviceId
             FROM bookings b
             JOIN services s ON s.id = b.service_id
             WHERE b.customer_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$customerId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['totalAmount'] = (float) $r['totalAmount'];
            $r['statusLabel'] = BookingStatus::label($r['status']);
        }
        ApiResponse::success($rows);
    }

    public function show(string $id): void
    {
        $booking = $this->loadForCustomer($id);
        ApiResponse::success($booking);
    }

    public function store(): void
    {
        $customerId = ApiAuth::customerId();
        $itemIds = Request::input('serviceItemIds', Request::input('service_item_ids', []));
        if (!is_array($itemIds)) {
            $itemIds = [];
        }
        $serviceIds = Request::input('serviceIds', Request::input('service_ids', []));
        if (!is_array($serviceIds)) {
            $serviceIds = $serviceIds ? [$serviceIds] : [];
        }
        $single = Request::input('serviceId', Request::input('service_id'));
        if ($serviceIds === [] && $single) {
            $serviceIds = [(string) $single];
        }

        try {
            $id = (new BookingService())->create([
                'service_ids' => array_values(array_map('strval', $serviceIds)),
                'service_id' => (string) ($serviceIds[0] ?? $single),
                'address_id' => (string) Request::input('addressId', Request::input('address_id')),
                'branch_id' => (string) Request::input('branchId', Request::input('branch_id')),
                'scheduled_date' => (string) Request::input('scheduledDate', Request::input('scheduled_date')),
                'scheduled_time' => (string) Request::input('scheduledTime', Request::input('scheduled_time')),
                'customer_notes' => Request::input('customerNotes', Request::input('customer_notes')),
                'service_item_ids' => array_values($itemIds),
            ], (string) ApiAuth::id(), $customerId);

            ApiResponse::success($this->loadForCustomer($id), 201, 'Booking created');
        } catch (RuntimeException $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    /** Customer marks service complete + optional review */
    public function complete(string $id): void
    {
        $booking = $this->rawBookingForCustomer($id);
        if ($booking['status'] !== BookingStatus::STARTED) {
            ApiResponse::error('You can only complete a booking that has started', 400);
        }

        try {
            (new BookingService())->updateStatus(
                $id,
                BookingStatus::COMPLETED,
                (string) ApiAuth::id(),
                'Completed by customer via app'
            );
        } catch (RuntimeException $e) {
            ApiResponse::error($e->getMessage(), 400);
        }

        $rating = (int) Request::input('rating', 0);
        $comment = Request::input('comment');
        if ($rating >= 1 && $rating <= 5) {
            $this->upsertReview($id, $booking['customer_id'], $rating, $comment);
        }

        ApiResponse::success($this->loadForCustomer($id), 200, 'Booking completed');
    }

    public function review(string $id): void
    {
        $booking = $this->rawBookingForCustomer($id);
        if ($booking['status'] !== BookingStatus::COMPLETED) {
            ApiResponse::error('Reviews are only allowed for completed bookings', 400);
        }

        $rating = (int) Request::input('rating');
        if ($rating < 1 || $rating > 5) {
            ApiResponse::error('Rating must be 1-5', 422);
        }

        $this->upsertReview($id, $booking['customer_id'], $rating, Request::input('comment'));
        ApiResponse::success(null, 200, 'Review saved');
    }

    private function upsertReview(string $bookingId, string $customerId, int $rating, mixed $comment): void
    {
        $db = Database::connection();
        $existing = $db->prepare('SELECT id FROM reviews WHERE booking_id = ?');
        $existing->execute([$bookingId]);
        $row = $existing->fetch();
        if ($row) {
            $db->prepare('UPDATE reviews SET rating=?, comment=? WHERE id=?')
                ->execute([$rating, $comment, $row['id']]);
            return;
        }
        $db->prepare(
            'INSERT INTO reviews (id, booking_id, customer_id, rating, comment) VALUES (?,?,?,?,?)'
        )->execute([generate_id(), $bookingId, $customerId, $rating, $comment]);
    }

    /** @return array<string, mixed> */
    private function rawBookingForCustomer(string $id): array
    {
        $customerId = ApiAuth::customerId();
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE id = ? AND customer_id = ?');
        $stmt->execute([$id, $customerId]);
        $row = $stmt->fetch();
        if ($row === false) {
            ApiResponse::error('Booking not found', 404);
        }
        return $row;
    }

    /** @return array<string, mixed> */
    private function loadForCustomer(string $id): array
    {
        $customerId = ApiAuth::customerId();
        $stmt = Database::connection()->prepare(
            'SELECT b.*, s.name AS service_name, a.label AS address_label, a.line1, a.city, a.pincode, a.latitude, a.longitude
             FROM bookings b
             JOIN services s ON s.id = b.service_id
             JOIN addresses a ON a.id = b.address_id
             WHERE b.id = ? AND b.customer_id = ?'
        );
        $stmt->execute([$id, $customerId]);
        $b = $stmt->fetch();
        if ($b === false) {
            ApiResponse::error('Booking not found', 404);
        }

        $items = Database::connection()->prepare('SELECT name, price, quantity FROM booking_items WHERE booking_id = ?');
        $items->execute([$id]);
        $itemRows = $items->fetchAll();

        $review = Database::connection()->prepare('SELECT rating, comment FROM reviews WHERE booking_id = ?');
        $review->execute([$id]);
        $rev = $review->fetch();

        $itemNames = array_values(array_unique(array_map(fn ($i) => $i['name'], $itemRows)));

        return [
            'id' => $b['id'],
            'bookingNumber' => $b['booking_number'],
            'status' => $b['status'],
            'statusLabel' => BookingStatus::label($b['status']),
            'scheduledDate' => $b['scheduled_date'],
            'scheduledTime' => $b['scheduled_time'],
            'totalAmount' => (float) $b['total_amount'],
            'serviceName' => count($itemNames) > 1 ? implode(', ', $itemNames) : $b['service_name'],
            'serviceNames' => $itemNames,
            'customerNotes' => $b['customer_notes'],
            'address' => [
                'label' => $b['address_label'],
                'line1' => $b['line1'],
                'city' => $b['city'],
                'pincode' => $b['pincode'],
                'latitude' => $b['latitude'],
                'longitude' => $b['longitude'],
            ],
            'items' => array_map(fn ($i) => [
                'name' => $i['name'],
                'price' => (float) $i['price'],
                'quantity' => (int) $i['quantity'],
            ], $itemRows),
            'review' => $rev ? ['rating' => (int) $rev['rating'], 'comment' => $rev['comment']] : null,
            'canComplete' => $b['status'] === BookingStatus::STARTED,
            'canReview' => $b['status'] === BookingStatus::COMPLETED && !$rev,
        ];
    }
}

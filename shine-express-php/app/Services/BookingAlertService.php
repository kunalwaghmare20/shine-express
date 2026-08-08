<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\BookingStatus;

/**
 * WhatsApp alerts for booking lifecycle (Meta Cloud API / log provider).
 */
final class BookingAlertService
{
    private WhatsAppService $whatsapp;

    public function __construct()
    {
        $this->whatsapp = new WhatsAppService();
    }

    public function onStatusChange(string $bookingId, string $status): void
    {
        if (!$this->whatsapp->enabled()) {
            return;
        }

        $ctx = $this->context($bookingId);
        if ($ctx === null || empty($ctx['customer_phone'])) {
            return;
        }

        $message = match ($status) {
            BookingStatus::CONFIRMED => $this->message(
                $ctx,
                'Your booking *{number}* is *confirmed* for {date}. We will assign staff shortly.'
            ),
            BookingStatus::ASSIGNED => $this->message(
                $ctx,
                'Staff has been assigned to your booking *{number}* scheduled for {date}.'
            ),
            BookingStatus::ON_THE_WAY => $this->message(
                $ctx,
                'Our team is *on the way* for booking *{number}*. Please keep your phone handy.'
            ),
            BookingStatus::COMPLETED => $this->message(
                $ctx,
                'Your booking *{number}* is *completed*. Thank you for choosing Shine Express!'
            ),
            default => null,
        };

        if ($message !== null) {
            $this->whatsapp->send((string) $ctx['customer_phone'], $message, $bookingId);
        }
    }

    public function onAssigned(string $bookingId): void
    {
        $this->onStatusChange($bookingId, BookingStatus::ASSIGNED);
    }

    /** @param array<string, mixed> $ctx */
    private function message(array $ctx, string $template): string
    {
        $name = trim((string) ($ctx['customer_first_name'] ?? 'Customer'));
        if ($name === '') {
            $name = 'there';
        }
        $date = trim((string) $ctx['scheduled_date'] . ' at ' . (string) $ctx['scheduled_time']);
        $service = $this->serviceSummary((string) $ctx['id'], (string) ($ctx['service_name'] ?? 'service'));

        return str_replace(
            ['{name}', '{number}', '{date}', '{service}'],
            [$name, (string) $ctx['booking_number'], $date, $service],
            "Hello {$name}, " . $template . "\n\nService: {$service}\n— Shine Express"
        );
    }

    private function serviceSummary(string $bookingId, string $fallback): string
    {
        $stmt = Database::connection()->prepare('SELECT name FROM booking_items WHERE booking_id = ? ORDER BY name');
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
    private function context(string $bookingId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.id, b.booking_number, b.scheduled_date, b.scheduled_time, b.status,
                    s.name AS service_name, u.phone AS customer_phone, u.first_name AS customer_first_name
             FROM bookings b
             JOIN services s ON s.id = b.service_id
             JOIN customers c ON c.id = b.customer_id
             JOIN users u ON u.id = c.user_id
             WHERE b.id = ?'
        );
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}

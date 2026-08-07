<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\BookingStatus;
use PDO;

/**
 * Rebook WhatsApp reminders — per service `reminder_days` after completion.
 *
 * Example: Pest Control with reminder_days=30 → 30 days after COMPLETED,
 * customer gets a WhatsApp asking them to book their next appointment.
 */
final class ServiceReminderService
{
    private PDO $db;
    private WhatsAppService $whatsapp;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->whatsapp = new WhatsAppService();
        $this->notifications = new NotificationService();
    }

    public function adminWhatsApp(): string
    {
        return (string) env_file('SUPPORT_WHATSAPP', '919673522737');
    }

    /**
     * @return array{checked:int,sent:int,failed:int,skipped:int,details:list<array<string,mixed>>}
     */
    public function sendDueReminders(?\DateTimeInterface $today = null): array
    {
        $today = $today ?? new \DateTimeImmutable('today');
        $todayStr = $today->format('Y-m-d');
        $result = ['checked' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'details' => []];

        foreach ($this->dueBookings($todayStr) as $row) {
            $result['checked']++;
            $out = $this->sendForBooking($row);
            $result['details'][] = $out;
            if ($out['status'] === 'SENT') {
                $result['sent']++;
            } elseif ($out['status'] === 'SKIPPED') {
                $result['skipped']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Completed bookings where completion date + service.reminder_days = today.
     *
     * @return list<array<string, mixed>>
     */
    public function dueBookings(string $today): array
    {
        $sql = "SELECT b.id, b.booking_number, b.scheduled_date, b.scheduled_time, b.status,
                       b.completed_at, b.whatsapp_reminder_sent_at,
                       s.name AS service_name, s.reminder_days,
                       c.user_id AS customer_user_id,
                       u.phone AS customer_phone,
                       u.first_name AS customer_first_name,
                       u.last_name AS customer_last_name
                FROM bookings b
                JOIN services s ON s.id = b.service_id
                JOIN customers c ON c.id = b.customer_id
                JOIN users u ON u.id = c.user_id
                WHERE b.status = ?
                  AND s.reminder_days > 0
                  AND b.whatsapp_reminder_sent_at IS NULL
                  AND DATE(COALESCE(b.completed_at, b.scheduled_date)) = DATE_SUB(?, INTERVAL s.reminder_days DAY)
                ORDER BY s.name, b.completed_at";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([BookingStatus::COMPLETED, $today]);
        return $stmt->fetchAll();
    }

    /**
     * Upcoming due list for admin UI (next N days of reminder windows).
     *
     * @return list<array<string, mixed>>
     */
    public function previewDue(?\DateTimeInterface $today = null, int $lookAheadDays = 0): array
    {
        $today = $today ?? new \DateTimeImmutable('today');
        return $this->dueBookings($today->format('Y-m-d'));
    }

    /**
     * @param array<string, mixed> $booking
     * @return array<string, mixed>
     */
    public function sendForBooking(array $booking): array
    {
        $id = (string) $booking['id'];
        $phone = (string) ($booking['customer_phone'] ?? '');
        if ($phone === '') {
            return ['bookingId' => $id, 'status' => 'SKIPPED', 'reason' => 'No customer phone'];
        }

        if (!empty($booking['whatsapp_reminder_sent_at'])) {
            return ['bookingId' => $id, 'status' => 'SKIPPED', 'reason' => 'Already reminded'];
        }

        $days = (int) ($booking['reminder_days'] ?? 0);
        if ($days <= 0) {
            return ['bookingId' => $id, 'status' => 'SKIPPED', 'reason' => 'Reminder disabled for service'];
        }

        $message = $this->buildMessage($booking);
        $wa = $this->whatsapp->send($phone, $message, $id);

        if ($wa['ok']) {
            $this->db->prepare('UPDATE bookings SET whatsapp_reminder_sent_at = NOW(3) WHERE id = ?')
                ->execute([$id]);

            $this->notifications->notify(
                (string) $booking['customer_user_id'],
                'Time to rebook',
                $this->inAppBody($booking),
                'SERVICE_REBOOK_REMINDER',
                [
                    'bookingId' => $id,
                    'reminderDays' => $days,
                    'channel' => 'WHATSAPP',
                    'adminWhatsApp' => $this->adminWhatsApp(),
                ]
            );

            return ['bookingId' => $id, 'status' => 'SENT', 'phone' => $phone, 'reminderDays' => $days];
        }

        return [
            'bookingId' => $id,
            'status' => 'FAILED',
            'phone' => $phone,
            'reason' => $wa['response'] ?? $wa['status'],
        ];
    }

    /** @param array<string, mixed> $b */
    private function buildMessage(array $b): string
    {
        $name = trim(($b['customer_first_name'] ?? '') . ' ' . ($b['customer_last_name'] ?? ''));
        if ($name === '') {
            $name = 'Customer';
        }

        $adminWa = $this->adminWhatsApp();
        $waLink = 'https://wa.me/' . preg_replace('/\D+/', '', $adminWa);

        $template = (string) env_file(
            'WHATSAPP_REBOOK_MESSAGE',
            "Hello {name},\n\nThank you for choosing Shine Express for your *{service}* service (booking {booking}).\n\nIt has been {days} days since your last service — now is a great time to book your *next appointment* so your space stays fresh and protected.\n\nReply on WhatsApp or message us at {admin_whatsapp} to schedule:\n{wa_link}\n\n— Shine Express"
        );

        if (trim((string) env_file('WHATSAPP_TEMPLATE_NAME', '')) !== '') {
            return implode(' | ', [
                $name,
                (string) $b['service_name'],
                (string) $b['booking_number'],
                (string) ($b['reminder_days'] ?? ''),
                $adminWa,
            ]);
        }

        return strtr($template, [
            '{name}' => $name,
            '{service}' => (string) $b['service_name'],
            '{booking}' => (string) $b['booking_number'],
            '{days}' => (string) ($b['reminder_days'] ?? ''),
            '{date}' => (string) ($b['scheduled_date'] ?? ''),
            '{admin_whatsapp}' => $adminWa,
            '{wa_link}' => $waLink,
        ]);
    }

    /** @param array<string, mixed> $b */
    private function inAppBody(array $b): string
    {
        return "It's time to book your next {$b['service_name']} appointment. Message us on WhatsApp {$this->adminWhatsApp()} to schedule.";
    }
}

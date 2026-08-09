<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Super admin broadcast: custom message to all or selected customers.
 */
final class WhatsAppBroadcastService
{
    private PDO $db;
    private WhatsAppService $whatsapp;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->whatsapp = new WhatsAppService();
    }

    public function adminWhatsApp(): string
    {
        return (string) env_file('SUPPORT_WHATSAPP', '919673522737');
    }

    public function defaultTemplate(): string
    {
        $fromEnv = trim((string) env_file('WHATSAPP_BROADCAST_DEFAULT', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        return "Hello {first_name},\n\nThank you for being a Shine Express customer. We wanted to share a quick update with you.\n\nReply on WhatsApp at {admin_whatsapp} to book your next service.\n\n— Shine Express";
    }

    /** @return list<string> */
    public function placeholders(): array
    {
        return ['{first_name}', '{name}', '{email}', '{phone}', '{admin_whatsapp}'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listCustomers(?string $search = null): array
    {
        $sql = 'SELECT c.id, u.first_name, u.last_name, u.email, u.phone, u.is_active
                FROM customers c
                JOIN users u ON u.id = c.user_id
                WHERE c.deleted_at IS NULL AND u.deleted_at IS NULL';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = '%' . trim($search) . '%';
            $params = [$like, $like, $like, $like];
        }
        $sql .= ' ORDER BY u.first_name, u.last_name LIMIT 500';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function personalize(string $template, array $customer): string
    {
        $first = trim((string) ($customer['first_name'] ?? ''));
        $last = trim((string) ($customer['last_name'] ?? ''));
        $name = trim($first . ' ' . $last);
        if ($name === '') {
            $name = 'Customer';
        }
        if ($first === '') {
            $first = explode(' ', $name)[0] ?: 'there';
        }

        $replacements = [
            '{first_name}' => $first,
            '{name}' => $name,
            '{email}' => (string) ($customer['email'] ?? ''),
            '{phone}' => (string) ($customer['phone'] ?? ''),
            '{admin_whatsapp}' => $this->adminWhatsApp(),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * @param list<string> $customerIds
     * @return array{
     *   recipient_count:int,
     *   sendable_count:int,
     *   skipped_count:int,
     *   samples:list<array{customer:string,phone:string,message:string}>
     * }
     */
    public function buildPreview(string $template, string $audience, array $customerIds = []): array
    {
        $customers = $this->resolveRecipients($audience, $customerIds);
        $sendable = 0;
        $skipped = 0;
        $samples = [];

        foreach ($customers as $customer) {
            $phone = trim((string) ($customer['phone'] ?? ''));
            $label = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $canSend = $phone !== '' && !empty($customer['is_active']);

            if ($canSend) {
                ++$sendable;
                if (count($samples) < 5) {
                    $samples[] = [
                        'customer' => $label,
                        'phone' => $phone,
                        'message' => $this->personalize($template, $customer),
                    ];
                }
            } else {
                ++$skipped;
            }
        }

        return [
            'recipient_count' => count($customers),
            'sendable_count' => $sendable,
            'skipped_count' => $skipped,
            'samples' => $samples,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function listSavedTemplates(): array
    {
        try {
            return $this->db->query(
                'SELECT id, name, body, updated_at AS updatedAt FROM whatsapp_broadcast_templates ORDER BY updated_at DESC'
            )->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public function getSavedTemplate(string $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT id, name, body FROM whatsapp_broadcast_templates WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row === false ? null : $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function saveTemplate(string $name, string $body, ?string $userId): string
    {
        $name = trim($name);
        $body = trim($body);
        if ($name === '' || $body === '') {
            throw new \InvalidArgumentException('Template name and message are required');
        }

        $existing = $this->db->prepare('SELECT id FROM whatsapp_broadcast_templates WHERE name = ? LIMIT 1');
        $existing->execute([$name]);
        $row = $existing->fetch();

        if ($row) {
            $this->db->prepare(
                'UPDATE whatsapp_broadcast_templates SET body = ?, created_by = ?, updated_at = NOW(3) WHERE id = ?'
            )->execute([$body, $userId, $row['id']]);
            return (string) $row['id'];
        }

        $id = generate_id();
        $this->db->prepare(
            'INSERT INTO whatsapp_broadcast_templates (id, name, body, created_by) VALUES (?,?,?,?)'
        )->execute([$id, $name, $body, $userId]);

        return $id;
    }

    public function deleteTemplate(string $id): void
    {
        $this->db->prepare('DELETE FROM whatsapp_broadcast_templates WHERE id = ?')->execute([$id]);
    }

    /**
     * @param list<string> $customerIds
     * @return array{total:int,sent:int,failed:int,skipped:int,results:list<array<string,mixed>>}
     */
    public function send(string $template, string $audience, array $customerIds = []): array
    {
        $customers = $this->resolveRecipients($audience, $customerIds);
        $results = [];
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            $phone = trim((string) ($customer['phone'] ?? ''));
            $label = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));

            if ($phone === '') {
                ++$skipped;
                $results[] = [
                    'customer' => $label,
                    'phone' => '',
                    'status' => 'SKIPPED',
                    'detail' => 'No phone number',
                ];
                continue;
            }

            if (empty($customer['is_active'])) {
                ++$skipped;
                $results[] = [
                    'customer' => $label,
                    'phone' => $phone,
                    'status' => 'SKIPPED',
                    'detail' => 'Inactive account',
                ];
                continue;
            }

            $message = $this->personalize($template, $customer);
            $outcome = $this->whatsapp->send($phone, $message);

            if ($outcome['ok']) {
                ++$sent;
                $status = 'SENT';
            } else {
                ++$failed;
                $status = 'FAILED';
            }

            $results[] = [
                'customer' => $label,
                'phone' => $phone,
                'status' => $status,
                'detail' => $outcome['response'] ?? $outcome['status'] ?? '',
            ];

            if ($this->whatsapp->provider() === 'cloud') {
                usleep(300000);
            }
        }

        return [
            'total' => count($customers),
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'results' => $results,
        ];
    }

    /**
     * @param list<string> $customerIds
     * @return list<array<string, mixed>>
     */
    private function resolveRecipients(string $audience, array $customerIds): array
    {
        if ($audience === 'all') {
            return $this->listCustomers();
        }

        $ids = array_values(array_unique(array_filter(array_map('strval', $customerIds))));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT c.id, u.first_name, u.last_name, u.email, u.phone, u.is_active
             FROM customers c
             JOIN users u ON u.id = c.user_id
             WHERE c.deleted_at IS NULL AND u.deleted_at IS NULL AND c.id IN ({$placeholders})"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }
}

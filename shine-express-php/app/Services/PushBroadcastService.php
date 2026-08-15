<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Super admin broadcast: custom push notification to all or selected customers.
 */
final class PushBroadcastService
{
    private PDO $db;
    private NotificationService $notifications;
    private FcmService $fcm;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->notifications = new NotificationService();
        $this->fcm = new FcmService();
    }

    /** @return array{tokens:int,users:int} */
    public function deviceTokenStats(): array
    {
        try {
            $tokens = (int) $this->db->query('SELECT COUNT(*) FROM device_tokens')->fetchColumn();
            $users = (int) $this->db->query('SELECT COUNT(DISTINCT user_id) FROM device_tokens')->fetchColumn();
            return ['tokens' => $tokens, 'users' => $users];
        } catch (\Throwable $e) {
            return ['tokens' => 0, 'users' => 0];
        }
    }

    public function fcmEnabled(): bool
    {
        return $this->fcm->enabled();
    }

    /** @return array{enabled:bool,reason:?string} */
    public function fcmSetupStatus(): array
    {
        return $this->fcm->setupStatus();
    }

    /** @return array{title:string,body:string} */
    public function defaultTemplate(): array
    {
        $title = trim((string) env_file('PUSH_BROADCAST_DEFAULT_TITLE', ''));
        $body = trim((string) env_file('PUSH_BROADCAST_DEFAULT_BODY', ''));

        if ($title === '') {
            $title = 'Update from Shine Express';
        }
        if ($body === '') {
            $body = "Hello {first_name},\n\nThank you for being a Shine Express customer. We wanted to share a quick update with you.\n\nOpen the app to book your next service.\n\n— Shine Express";
        }

        return ['title' => $title, 'body' => $body];
    }

    /** @return list<string> */
    public function placeholders(): array
    {
        return ['{first_name}', '{name}', '{email}', '{phone}'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listCustomers(?string $search = null): array
    {
        $sql = 'SELECT c.id, c.user_id, u.first_name, u.last_name, u.email, u.phone, u.is_active,
                       (SELECT COUNT(*) FROM device_tokens dt WHERE dt.user_id = c.user_id) AS device_count
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
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * @param list<string> $customerIds
     * @return array{
     *   recipient_count:int,
     *   sendable_count:int,
     *   skipped_count:int,
     *   samples:list<array{customer:string,devices:int,title:string,body:string}>
     * }
     */
    public function buildPreview(string $title, string $body, string $audience, array $customerIds = []): array
    {
        $customers = $this->resolveRecipients($audience, $customerIds);
        $sendable = 0;
        $skipped = 0;
        $samples = [];

        foreach ($customers as $customer) {
            $label = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $canSend = $this->canSendToCustomer($customer);

            if ($canSend) {
                ++$sendable;
                if (count($samples) < 5) {
                    $samples[] = [
                        'customer' => $label,
                        'devices' => (int) ($customer['device_count'] ?? 0),
                        'title' => $this->personalize($title, $customer),
                        'body' => $this->personalize($body, $customer),
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
                'SELECT id, name, title, body, updated_at AS updatedAt FROM push_broadcast_templates ORDER BY updated_at DESC'
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
                'SELECT id, name, title, body FROM push_broadcast_templates WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row === false ? null : $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function saveTemplate(string $name, string $title, string $body, ?string $userId): string
    {
        $name = trim($name);
        $title = trim($title);
        $body = trim($body);
        if ($name === '' || $title === '' || $body === '') {
            throw new \InvalidArgumentException('Template name, title, and message are required');
        }

        $existing = $this->db->prepare('SELECT id FROM push_broadcast_templates WHERE name = ? LIMIT 1');
        $existing->execute([$name]);
        $row = $existing->fetch();

        if ($row) {
            $this->db->prepare(
                'UPDATE push_broadcast_templates SET title = ?, body = ?, created_by = ?, updated_at = NOW(3) WHERE id = ?'
            )->execute([$title, $body, $userId, $row['id']]);
            return (string) $row['id'];
        }

        $id = generate_id();
        $this->db->prepare(
            'INSERT INTO push_broadcast_templates (id, name, title, body, created_by) VALUES (?,?,?,?,?)'
        )->execute([$id, $name, $title, $body, $userId]);

        return $id;
    }

    public function deleteTemplate(string $id): void
    {
        $this->db->prepare('DELETE FROM push_broadcast_templates WHERE id = ?')->execute([$id]);
    }

    /**
     * @param list<string> $customerIds
     * @return array{total:int,in_app:int,push:int,failed:int,skipped:int,results:list<array<string,mixed>>}
     */
    public function send(string $title, string $body, string $audience, array $customerIds = []): array
    {
        $customers = $this->resolveRecipients($audience, $customerIds);
        $results = [];
        $inApp = 0;
        $push = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            $label = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $userId = (string) ($customer['user_id'] ?? '');
            $devices = (int) ($customer['device_count'] ?? 0);

            if (!$this->canSendToCustomer($customer)) {
                ++$skipped;
                $detail = empty($customer['is_active'])
                    ? 'Inactive account'
                    : 'No registered device (customer must log in to the app)';
                $results[] = [
                    'customer' => $label,
                    'devices' => $devices,
                    'status' => 'SKIPPED',
                    'detail' => $detail,
                ];
                continue;
            }

            $personalTitle = $this->personalize($title, $customer);
            $personalBody = $this->personalize($body, $customer);

            try {
                $fcmResult = $this->notifications->notify(
                    $userId,
                    $personalTitle,
                    $personalBody,
                    'PUSH_BROADCAST',
                    ['channel' => 'PUSH']
                );
                ++$inApp;

                $devicesSent = (int) ($fcmResult['sent'] ?? 0);
                $devicesFailed = (int) ($fcmResult['failed'] ?? 0);
                $push += $devicesSent;

                if ($devicesSent > 0) {
                    $status = 'PUSH_SENT';
                    $detail = "{$devicesSent}/{$devices} device push(es) delivered";
                } elseif (($fcmResult['skipped_reason'] ?? null) !== null) {
                    $status = 'IN_APP_ONLY';
                    $detail = 'In-app saved · push skipped: ' . $fcmResult['skipped_reason'];
                } elseif ($devicesFailed > 0) {
                    $status = 'PUSH_FAILED';
                    $detail = "In-app saved · {$devicesFailed} push attempt(s) failed — see storage/logs/fcm.log";
                    ++$failed;
                } else {
                    $status = 'IN_APP_ONLY';
                    $detail = 'In-app saved · no push attempted';
                }
            } catch (\Throwable $e) {
                ++$failed;
                $status = 'FAILED';
                $detail = mb_substr($e->getMessage(), 0, 120);
            }

            $results[] = [
                'customer' => $label,
                'devices' => $devices,
                'status' => $status,
                'detail' => $detail,
            ];
        }

        return [
            'total' => count($customers),
            'in_app' => $inApp,
            'push' => $push,
            'sent' => $inApp,
            'failed' => $failed,
            'skipped' => $skipped,
            'results' => $results,
        ];
    }

    /** @param array<string, mixed> $customer */
    private function canSendToCustomer(array $customer): bool
    {
        return !empty($customer['is_active'])
            && (int) ($customer['device_count'] ?? 0) > 0
            && trim((string) ($customer['user_id'] ?? '')) !== '';
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
            "SELECT c.id, c.user_id, u.first_name, u.last_name, u.email, u.phone, u.is_active,
                    (SELECT COUNT(*) FROM device_tokens dt WHERE dt.user_id = c.user_id) AS device_count
             FROM customers c
             JOIN users u ON u.id = c.user_id
             WHERE c.deleted_at IS NULL AND u.deleted_at IS NULL AND c.id IN ({$placeholders})"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }
}

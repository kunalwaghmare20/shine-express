<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class ApiAuth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?string
    {
        return self::$user['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::$user['role'] ?? null;
    }

    public static function attemptFromHeader(): bool
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return false;
        }

        $token = $m[1];
        $stmt = Database::connection()->prepare(
            'SELECT u.* FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token = ? AND u.deleted_at IS NULL AND u.is_active = 1
               AND (t.expires_at IS NULL OR t.expires_at > NOW())
             LIMIT 1'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if ($user === false) {
            return false;
        }

        Database::connection()->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE token = ?')->execute([$token]);
        self::$user = $user;
        return true;
    }

    public static function issueToken(string $userId, ?string $deviceName = null): string
    {
        $token = bin2hex(random_bytes(32));
        Database::connection()->prepare(
            'INSERT INTO api_tokens (id, user_id, token, device_name, last_used_at, expires_at)
             VALUES (?,?,?,?,NOW(),DATE_ADD(NOW(), INTERVAL 180 DAY))'
        )->execute([generate_id(), $userId, $token, $deviceName]);
        return $token;
    }

    public static function revokeToken(?string $token = null): void
    {
        if ($token) {
            Database::connection()->prepare('DELETE FROM api_tokens WHERE token = ?')->execute([$token]);
            return;
        }
        if (self::$user) {
            Database::connection()->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([self::$user['id']]);
        }
    }

    public static function customerId(): ?string
    {
        $uid = self::id();
        if (!$uid) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT id FROM customers WHERE user_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        return $row['id'] ?? null;
    }

    public static function employee(): ?array
    {
        $uid = self::id();
        if (!$uid) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT * FROM employees WHERE user_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<string, mixed> */
    public static function publicUser(array $user): array
    {
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'role' => $user['role'],
            'avatarUrl' => $user['avatar_url'] ?? null,
        ];
    }
}

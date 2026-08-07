<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use PDO;

final class Auth
{
    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        $id = Session::get('user_id');
        if (!$id) {
            return null;
        }

        static $cached = null;
        static $cachedId = null;
        if ($cachedId === $id && is_array($cached)) {
            return $cached;
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL AND is_active = 1 LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if ($user === false) {
            self::logout();
            return null;
        }

        $cached = $user;
        $cachedId = $id;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?string
    {
        $user = self::user();
        return $user['id'] ?? null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE email = :email AND deleted_at IS NULL AND is_active = 1 LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if ($user === false || empty($user['password_hash'])) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);

        $upd = Database::connection()->prepare('UPDATE users SET last_login_at = NOW(3) WHERE id = :id');
        $upd->execute(['id' => $user['id']]);

        return true;
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        Session::forget('user_role');
    }

    public static function hasPermission(string $permission): bool
    {
        $role = self::role();
        if ($role === null) {
            return false;
        }
        $config = require APP_PATH . '/Config/roles.php';
        $map = $config['role_permissions'][$role] ?? [];
        if ($map === '*') {
            return true;
        }
        return in_array($permission, $map, true);
    }

    public static function homePath(): string
    {
        $config = require APP_PATH . '/Config/roles.php';
        $role = self::role() ?? 'CUSTOMER';
        return $config['role_home'][$role] ?? '/';
    }

    /** Customer profile id for current user, if any. */
    public static function customerId(): ?string
    {
        $uid = self::id();
        if (!$uid) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT id FROM customers WHERE user_id = :uid AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['uid' => $uid]);
        $row = $stmt->fetch();
        return $row['id'] ?? null;
    }

    /** Employee profile for current user. */
    public static function employee(): ?array
    {
        $uid = self::id();
        if (!$uid) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT * FROM employees WHERE user_id = :uid AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['uid' => $uid]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function branchId(): ?string
    {
        $emp = self::employee();
        return $emp['branch_id'] ?? null;
    }
}

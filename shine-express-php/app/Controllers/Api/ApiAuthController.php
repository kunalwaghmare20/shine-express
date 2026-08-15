<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\ApiResponse;
use App\Core\Database;
use App\Core\Request;

final class ApiAuthController
{
    public function register(): void
    {
        $first = trim((string) Request::input('firstName', Request::input('first_name')));
        $last = trim((string) Request::input('lastName', Request::input('last_name')));
        $email = trim((string) Request::input('email'));
        $phone = trim((string) Request::input('phone', ''));
        $password = (string) Request::input('password');

        if ($first === '' || $last === '' || $email === '' || strlen($password) < 6) {
            ApiResponse::error('Invalid registration data', 422);
        }

        $db = Database::connection();
        $exists = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            ApiResponse::error('Email already registered', 422);
        }

        $userId = generate_id();
        $customerId = generate_id();
        $db->beginTransaction();
        try {
            $db->prepare(
                'INSERT INTO users (id, email, password_hash, phone, first_name, last_name, role, is_active)
                 VALUES (?,?,?,?,?,?,"CUSTOMER",1)'
            )->execute([$userId, $email, password_hash($password, PASSWORD_DEFAULT), $phone ?: null, $first, $last]);
            $db->prepare('INSERT INTO customers (id, user_id) VALUES (?,?)')->execute([$customerId, $userId]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            ApiResponse::error('Registration failed', 500);
        }

        $token = ApiAuth::issueToken($userId, Request::input('deviceName'));
        $user = $db->prepare('SELECT * FROM users WHERE id = ?');
        $user->execute([$userId]);
        ApiResponse::success([
            'token' => $token,
            'user' => ApiAuth::publicUser($user->fetch()),
            'customerId' => $customerId,
        ], 201, 'Registered');
    }

    public function login(): void
    {
        $email = trim((string) Request::input('email'));
        $password = (string) Request::input('password');

        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user === false || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            ApiResponse::error('Invalid credentials', 401);
        }

        if (!in_array($user['role'], ['CUSTOMER', 'SERVICE_STAFF', 'BRANCH_MANAGER'], true)) {
            ApiResponse::error('This account cannot use the mobile app', 403);
        }

        Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
        $token = ApiAuth::issueToken($user['id'], Request::input('deviceName'));

        $payload = [
            'token' => $token,
            'user' => ApiAuth::publicUser($user),
        ];

        if ($user['role'] === 'CUSTOMER') {
            $c = Database::connection()->prepare('SELECT id FROM customers WHERE user_id = ? LIMIT 1');
            $c->execute([$user['id']]);
            $payload['customerId'] = $c->fetchColumn() ?: null;
        } else {
            $e = Database::connection()->prepare('SELECT * FROM employees WHERE user_id = ? AND deleted_at IS NULL LIMIT 1');
            $e->execute([$user['id']]);
            $emp = $e->fetch();
            $payload['employee'] = $emp ? [
                'id' => $emp['id'],
                'code' => $emp['employee_code'],
                'branchId' => $emp['branch_id'],
                'isAvailable' => (bool) $emp['is_available'],
            ] : null;
        }

        ApiResponse::success($payload, 200, 'Logged in');
    }

    public function me(): void
    {
        $user = ApiAuth::user();
        $payload = ['user' => ApiAuth::publicUser($user)];
        if (ApiAuth::role() === 'CUSTOMER') {
            $payload['customerId'] = ApiAuth::customerId();
        } else {
            $emp = ApiAuth::employee();
            $payload['employee'] = $emp ? [
                'id' => $emp['id'],
                'code' => $emp['employee_code'],
                'branchId' => $emp['branch_id'],
                'isAvailable' => (bool) $emp['is_available'],
                'skills' => json_decode((string) ($emp['skills'] ?? '[]'), true) ?: [],
            ] : null;
        }
        ApiResponse::success($payload);
    }

    public function logout(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            ApiAuth::revokeToken($m[1]);
        }
        ApiResponse::success(null, 200, 'Logged out');
    }

    public function updateProfile(): void
    {
        $user = ApiAuth::user();
        $first = trim((string) Request::input('firstName', $user['first_name']));
        $last = trim((string) Request::input('lastName', $user['last_name']));
        $phone = trim((string) Request::input('phone', $user['phone'] ?? ''));

        Database::connection()->prepare(
            'UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?'
        )->execute([$first, $last, $phone ?: null, $user['id']]);

        if (ApiAuth::role() !== 'CUSTOMER') {
            $emp = ApiAuth::employee();
            if ($emp && Request::input('isAvailable') !== null) {
                Database::connection()->prepare('UPDATE employees SET is_available=? WHERE id=?')
                    ->execute([Request::input('isAvailable') ? 1 : 0, $emp['id']]);
            }
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        ApiResponse::success(['user' => ApiAuth::publicUser($stmt->fetch())], 200, 'Profile updated');
    }

    public function changePassword(): void
    {
        $user = ApiAuth::user();
        $current = (string) Request::input('currentPassword');
        $password = (string) Request::input('newPassword');
        $confirm = (string) Request::input('confirmPassword');

        if ($current === '' || $password === '') {
            ApiResponse::error('Current and new password are required', 422);
        }
        if (strlen($password) < 6) {
            ApiResponse::error('New password must be at least 6 characters', 422);
        }
        if ($password !== $confirm) {
            ApiResponse::error('New passwords do not match', 422);
        }
        if (empty($user['password_hash']) || !password_verify($current, (string) $user['password_hash'])) {
            ApiResponse::error('Current password is incorrect', 401);
        }

        Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);

        ApiResponse::success(null, 200, 'Password updated');
    }
}

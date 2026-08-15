<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\ApiResponse;
use App\Core\Database;
use App\Core\Request;

final class ApiAddressController
{
    public function index(): void
    {
        $customerId = ApiAuth::customerId();
        $stmt = Database::connection()->prepare(
            'SELECT id, label, line1, line2, city, state, pincode, country, latitude, longitude, is_default AS isDefault
             FROM addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC'
        );
        $stmt->execute([$customerId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['isDefault'] = (bool) $r['isDefault'];
        }
        ApiResponse::success($rows);
    }

    public function store(): void
    {
        $customerId = ApiAuth::customerId();
        $line1 = trim((string) Request::input('line1'));
        $city = trim((string) Request::input('city'));
        if ($line1 === '' || $city === '') {
            ApiResponse::error('Building and city are required', 422);
        }

        $state = trim((string) Request::input('state', 'Maharashtra'));
        if ($state === '') {
            $state = 'Maharashtra';
        }
        $pincode = trim((string) Request::input('pincode', ''));
        if ($pincode === '') {
            $pincode = '000000';
        }

        $id = generate_id();
        $isDefault = Request::input('isDefault') ? 1 : 0;

        $db = Database::connection();
        if ($isDefault) {
            $db->prepare('UPDATE addresses SET is_default = 0 WHERE customer_id = ?')->execute([$customerId]);
        }

        $db->prepare(
            'INSERT INTO addresses (id, customer_id, label, line1, line2, city, state, pincode, country, latitude, longitude, is_default)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $id,
            $customerId,
            Request::input('label', 'Home'),
            $line1,
            Request::input('line2'),
            $city,
            $state,
            $pincode,
            Request::input('country', 'India'),
            Request::input('latitude'),
            Request::input('longitude'),
            $isDefault,
        ]);

        ApiResponse::success(['id' => $id], 201, 'Address added');
    }

    public function update(string $id): void
    {
        $customerId = ApiAuth::customerId();
        $db = Database::connection();
        $check = $db->prepare('SELECT id FROM addresses WHERE id = ? AND customer_id = ?');
        $check->execute([$id, $customerId]);
        if (!$check->fetch()) {
            ApiResponse::error('Address not found', 404);
        }

        $line1 = trim((string) Request::input('line1'));
        $city = trim((string) Request::input('city'));
        if ($line1 === '' || $city === '') {
            ApiResponse::error('Building and city are required', 422);
        }

        $state = trim((string) Request::input('state', 'Maharashtra'));
        if ($state === '') {
            $state = 'Maharashtra';
        }
        $pincode = trim((string) Request::input('pincode', ''));
        if ($pincode === '') {
            $pincode = '000000';
        }

        $isDefault = Request::input('isDefault') ? 1 : 0;
        if ($isDefault) {
            $db->prepare('UPDATE addresses SET is_default = 0 WHERE customer_id = ?')->execute([$customerId]);
        }

        $db->prepare(
            'UPDATE addresses SET label=?, line1=?, line2=?, city=?, state=?, pincode=?, country=?, latitude=?, longitude=?, is_default=?
             WHERE id=? AND customer_id=?'
        )->execute([
            Request::input('label', 'Home'),
            $line1,
            Request::input('line2'),
            $city,
            $state,
            $pincode,
            Request::input('country', 'India'),
            Request::input('latitude'),
            Request::input('longitude'),
            $isDefault,
            $id,
            $customerId,
        ]);

        ApiResponse::success(['id' => $id], 200, 'Address updated');
    }

    public function destroy(string $id): void
    {
        $customerId = ApiAuth::customerId();
        Database::connection()->prepare('DELETE FROM addresses WHERE id = ? AND customer_id = ?')
            ->execute([$id, $customerId]);
        ApiResponse::success(null, 200, 'Address deleted');
    }
}

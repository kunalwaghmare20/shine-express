<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiResponse;
use App\Core\Database;

final class ApiCatalogController
{
    public function services(): void
    {
        $services = Database::connection()->query(
            'SELECT s.id, s.name, s.slug, s.description, s.base_price AS basePrice, s.duration,
                    c.id AS categoryId, c.name AS categoryName
             FROM services s
             JOIN service_categories c ON c.id = s.category_id
             WHERE s.is_active = 1
             ORDER BY c.sort_order, s.sort_order'
        )->fetchAll();

        $items = Database::connection()->query(
            'SELECT id, service_id AS serviceId, name, description, price, duration
             FROM service_items WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();

        $byService = [];
        foreach ($items as $item) {
            $byService[$item['serviceId']][] = $item;
        }
        foreach ($services as &$s) {
            $s['basePrice'] = (float) $s['basePrice'];
            $s['duration'] = (int) $s['duration'];
            $s['items'] = $byService[$s['id']] ?? [];
            foreach ($s['items'] as &$it) {
                $it['price'] = (float) $it['price'];
            }
        }

        $branches = Database::connection()->query(
            'SELECT id, name, code, city, address FROM branches WHERE is_active = 1 ORDER BY name'
        )->fetchAll();

        ApiResponse::success([
            'services' => $services,
            'branches' => $branches,
        ]);
    }
}

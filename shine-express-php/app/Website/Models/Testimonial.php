<?php

declare(strict_types=1);

namespace App\Website\Models;

use App\Core\Model;

final class Testimonial extends Model
{
    protected string $table = 'cms_testimonials';

    /** @return list<array<string, mixed>> */
    public function listed(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM cms_testimonials';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, created_at';
        try {
            $rows = $this->db()->query($sql)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as &$row) {
            $row['photo_url'] = public_file_url((string) ($row['photo_path'] ?? ''));
        }
        unset($row);
        return $rows;
    }
}

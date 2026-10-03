<?php

declare(strict_types=1);

namespace App\Website\Models;

use App\Core\Model;

final class GalleryItem extends Model
{
    protected string $table = 'cms_gallery';

    /** @return list<array<string, mixed>> */
    public function listed(bool $homeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_gallery';
        if ($homeOnly) {
            $sql .= ' WHERE show_on_home = 1';
        }
        $sql .= ' ORDER BY sort_order, created_at';
        try {
            $rows = $this->db()->query($sql)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as &$row) {
            $row['url'] = public_file_url((string) ($row['image_path'] ?? ''));
        }
        unset($row);
        return $rows;
    }
}

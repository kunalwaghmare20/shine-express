<?php

declare(strict_types=1);

namespace App\Website\Models;

use App\Core\Model;

final class Page extends Model
{
    protected string $table = 'cms_pages';

    public function tableReady(): bool
    {
        try {
            $this->db()->query('SELECT 1 FROM cms_pages LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->db()->prepare('SELECT * FROM cms_pages WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row === false ? null : $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return list<array<string, mixed>> */
    public function ordered(): array
    {
        try {
            return $this->db()->query('SELECT * FROM cms_pages ORDER BY slug')->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}

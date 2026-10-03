<?php

declare(strict_types=1);

namespace App\Website\Models;

use App\Core\Model;

final class Enquiry extends Model
{
    protected string $table = 'website_enquiries';

    public function add(string $name, string $phone, string $email, string $message): void
    {
        $this->create([
            'id' => generate_id(),
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'message' => $message,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        try {
            return $this->db()->query(
                'SELECT * FROM website_enquiries ORDER BY created_at DESC LIMIT ' . $limit
            )->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}

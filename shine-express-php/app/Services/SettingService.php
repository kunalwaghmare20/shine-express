<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Key/value app settings stored in MySQL, with .env as fallback.
 */
final class SettingService
{
    private static ?self $instance = null;

    /** @var array<string, string>|null */
    private ?array $cache = null;

    private bool $tableMissing = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::instance()->value($key, $default);
    }

    public function value(string $key, mixed $default = null): mixed
    {
        $rows = $this->allFromDatabase();
        if (array_key_exists($key, $rows)) {
            return $rows[$key];
        }

        return env_file($key, $default);
    }

    public function source(string $key): string
    {
        $rows = $this->allFromDatabase();
        return array_key_exists($key, $rows) ? 'database' : 'env';
    }

    public function tableReady(): bool
    {
        $this->allFromDatabase();
        return !$this->tableMissing;
    }

    /**
     * @param array<string, string> $pairs
     */
    public function setMany(array $pairs, ?string $userId = null): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_by) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
        );
        foreach ($pairs as $key => $value) {
            $stmt->execute([$key, $value, $userId]);
        }
        $this->cache = null;
    }

    /** @return array<string, string> */
    private function allFromDatabase(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        if ($this->tableMissing) {
            return $this->cache = [];
        }

        try {
            $stmt = Database::connection()->query(
                'SELECT setting_key, setting_value FROM app_settings'
            );
            $this->cache = [];
            foreach ($stmt->fetchAll() as $row) {
                $this->cache[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        } catch (\Throwable $e) {
            $this->tableMissing = true;
            $this->cache = [];
        }

        return $this->cache;
    }
}

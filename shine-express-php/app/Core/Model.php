<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    protected function db(): PDO
    {
        return Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return list<array<string, mixed>> */
    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM `{$this->table}` ORDER BY `{$orderBy}` {$direction}";
        return $this->db()->query($sql)->fetchAll();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string
    {
        $columns = array_keys($data);
        $fields = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
        $placeholders = implode(', ', array_map(fn ($c) => ':' . $c, $columns));

        $sql = "INSERT INTO `{$this->table}` ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($data);

        return (string) ($data[$this->primaryKey] ?? $this->db()->lastInsertId());
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "`{$column}` = :{$column}";
        }

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets)
            . " WHERE `{$this->primaryKey}` = :__pk";
        $stmt = $this->db()->prepare($sql);
        $data['__pk'] = $id;
        return $stmt->execute($data);
    }

    public function delete(string $id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        $stmt = $this->db()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}

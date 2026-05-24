<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected PDO    $db;
    protected string $table  = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    /**
     * Run a raw SELECT and return all rows.
     */
    protected function query(string $sql, array $bindings = []): array
    {
        $stmt = $this->prepare($sql, $bindings);
        return $stmt->fetchAll();
    }

    /**
     * Run a raw SELECT and return the first row or null.
     */
    protected function queryOne(string $sql, array $bindings = []): ?array
    {
        $stmt = $this->prepare($sql, $bindings);
        $row  = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Run INSERT / UPDATE / DELETE and return affected rows.
     */
    protected function execute(string $sql, array $bindings = []): int
    {
        $stmt = $this->prepare($sql, $bindings);
        return $stmt->rowCount();
    }

    /**
     * Run INSERT and return the last insert ID.
     */
    protected function insert(string $sql, array $bindings = []): int
    {
        $this->prepare($sql, $bindings);
        return (int)$this->db->lastInsertId();
    }

    private function prepare(string $sql, array $bindings): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    // ── Convenience CRUD ──────────────────────────────────────────────────────

    public function find(int $id): ?array
    {
        return $this->queryOne(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            [$id]
        );
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->queryOne(
            "SELECT * FROM `{$this->table}` WHERE `uuid` = ? LIMIT 1",
            [$uuid]
        );
    }

    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        return $this->query($sql);
    }

    public function count(): int
    {
        $row = $this->queryOne("SELECT COUNT(*) AS cnt FROM `{$this->table}`");
        return (int)($row['cnt'] ?? 0);
    }
}

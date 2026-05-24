<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    // ── Lookup ────────────────────────────────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        return $this->queryOne(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    public function findByUuidWithRole(string $uuid): ?array
    {
        return $this->queryOne(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.uuid = ? LIMIT 1',
            [$uuid]
        );
    }

    public function findWithRole(int $id): ?array
    {
        return $this->queryOne(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1',
            [$id]
        );
    }

    // ── Login lockout ─────────────────────────────────────────────────────────

    public function incrementLoginAttempts(int $id): void
    {
        $this->execute(
            'UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?',
            [$id]
        );
    }

    public function lockAccount(int $id, int $minutes): void
    {
        $until = date('Y-m-d H:i:s', time() + $minutes * 60);
        $this->execute(
            'UPDATE users SET locked_until = ?, login_attempts = login_attempts + 1 WHERE id = ?',
            [$until, $id]
        );
    }

    public function resetLoginAttempts(int $id): void
    {
        $this->execute(
            'UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?',
            [$id]
        );
    }

    public function updateLastLogin(int $id): void
    {
        $this->execute(
            'UPDATE users SET last_login_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        return $this->insert(
            'INSERT INTO users
             (uuid, role_id, first_name, last_name, email, password_hash, is_active, email_verified_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['uuid'],
                $data['role_id'],
                $data['first_name'],
                $data['last_name'],
                strtolower(trim($data['email'])),
                $data['password_hash'],
                $data['is_active'] ?? 1,
                $data['email_verified_at'] ?? null,
            ]
        );
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(int $id, array $data): void
    {
        $sets   = [];
        $values = [];
        $allowed = ['first_name','last_name','email','role_id','is_active','avatar','password_hash'];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]   = "`$col` = ?";
                $values[] = $data[$col];
            }
        }
        if (empty($sets)) return;
        $values[] = $id;
        $this->execute('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $values);
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    // ── Counts ────────────────────────────────────────────────────────────────

    public function countByRole(): array
    {
        $rows   = $this->query(
            'SELECT r.name, COUNT(u.id) AS cnt
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id
             GROUP BY r.id, r.name'
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['name']] = (int)$row['cnt'];
        }
        return $result;
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function paginate(int $page = 1, int $perPage = 20, string $search = '', string $roleFilter = ''): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($roleFilter !== '') {
            $where[]  = 'r.name = ?';
            $params[] = $roleFilter;
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $total = (int)($this->queryOne(
            "SELECT COUNT(*) AS cnt FROM users u JOIN roles r ON r.id = u.role_id WHERE $whereStr",
            $params
        )['cnt'] ?? 0);

        $rows = $this->query(
            "SELECT u.*, r.name AS role_name, r.display_name AS role_display
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE $whereStr
             ORDER BY u.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return compact('rows', 'total', 'page', 'perPage');
    }
}

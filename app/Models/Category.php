<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected string $table = 'categories';

    public function allWithParent(): array
    {
        return $this->query(
            'SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             ORDER BY COALESCE(c.parent_id, c.id), c.id'
        );
    }

    public function roots(): array
    {
        return $this->query(
            'SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name'
        );
    }

    public function children(int $parentId): array
    {
        return $this->query(
            'SELECT * FROM categories WHERE parent_id = ? ORDER BY name',
            [$parentId]
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->queryOne('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    public function create(string $name, string $slug, ?int $parentId): int
    {
        return $this->insert(
            'INSERT INTO categories (name, slug, parent_id) VALUES (?, ?, ?)',
            [$name, $slug, $parentId]
        );
    }

    public function update(int $id, string $name, string $slug, ?int $parentId): void
    {
        $this->execute(
            'UPDATE categories SET name = ?, slug = ?, parent_id = ? WHERE id = ?',
            [$name, $slug, $parentId, $id]
        );
    }

    public function delete(int $id): void
    {
        // Nullify children first
        $this->execute('UPDATE categories SET parent_id = NULL WHERE parent_id = ?', [$id]);
        $this->execute('DELETE FROM categories WHERE id = ?', [$id]);
    }

    /** Flat list formatted for dropdowns */
    public function forDropdown(): array
    {
        $all    = $this->allWithParent();
        $result = [];
        // parents first
        foreach ($all as $row) {
            if (!$row['parent_id']) {
                $result[] = ['id' => $row['id'], 'label' => $row['name']];
            }
        }
        // children indented
        foreach ($all as $row) {
            if ($row['parent_id']) {
                $result[] = ['id' => $row['id'], 'label' => '— ' . $row['name']];
            }
        }
        return $result;
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Section extends Model
{
    protected string $table = 'sections';

    public function forCourse(int $courseId): array
    {
        return $this->query(
            'SELECT * FROM sections WHERE course_id = ? ORDER BY sort_order, id',
            [$courseId]
        );
    }

    public function create(array $d): int
    {
        return $this->insert(
            'INSERT INTO sections (uuid, course_id, title, description, drip_days, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $d['uuid'],
                (int)$d['course_id'],
                $d['title'],
                $d['description'] ?? null,
                $d['drip_days'] !== '' ? (int)$d['drip_days'] : null,
                (int)($d['sort_order'] ?? 0),
            ]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            'UPDATE sections SET title=?, description=?, drip_days=?, sort_order=?, updated_at=NOW()
             WHERE id=?',
            [
                $d['title'],
                $d['description'] ?? null,
                ($d['drip_days'] ?? '') !== '' ? (int)$d['drip_days'] : null,
                (int)($d['sort_order'] ?? 0),
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM sections WHERE id = ?', [$id]);
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            $this->execute(
                'UPDATE sections SET sort_order = ? WHERE id = ?',
                [$order, (int)$id]
            );
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Lesson extends Model
{
    protected string $table = 'lessons';

    public function forSection(int $sectionId): array
    {
        return $this->query(
            'SELECT * FROM lessons WHERE section_id = ? ORDER BY sort_order, id',
            [$sectionId]
        );
    }

    public function create(array $d): int
    {
        return $this->insert(
            'INSERT INTO lessons
             (uuid, section_id, course_id, title, type, video_type, content,
              file_path, thumbnail, duration_sec, drip_days, is_previewable,
              is_mandatory, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $d['uuid'],
                (int)$d['section_id'],
                (int)$d['course_id'],
                $d['title'],
                $d['type'],
                $d['video_type'] ?? null,
                $d['content'] ?? null,
                $d['file_path'] ?? null,
                $d['thumbnail'] ?? null,
                ($d['duration_sec'] ?? '') !== '' ? (int)$d['duration_sec'] : null,
                ($d['drip_days'] ?? '') !== '' ? (int)$d['drip_days'] : null,
                (int)($d['is_previewable'] ?? 0),
                (int)($d['is_mandatory'] ?? 1),
                (int)($d['sort_order'] ?? 0),
            ]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            'UPDATE lessons SET
               title=?, type=?, video_type=?, content=?, file_path=?, thumbnail=?,
               duration_sec=?, drip_days=?, is_previewable=?, is_mandatory=?,
               sort_order=?, updated_at=NOW()
             WHERE id=?',
            [
                $d['title'],
                $d['type'],
                $d['video_type'] ?? null,
                $d['content'] ?? null,
                $d['file_path'] ?? null,
                $d['thumbnail'] ?? null,
                ($d['duration_sec'] ?? '') !== '' ? (int)$d['duration_sec'] : null,
                ($d['drip_days'] ?? '') !== '' ? (int)$d['drip_days'] : null,
                (int)($d['is_previewable'] ?? 0),
                (int)($d['is_mandatory'] ?? 1),
                (int)($d['sort_order'] ?? 0),
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM lessons WHERE id = ?', [$id]);
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            $this->execute(
                'UPDATE lessons SET sort_order = ? WHERE id = ?',
                [$order, (int)$id]
            );
        }
    }
}

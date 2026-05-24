<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Course extends Model
{
    protected string $table = 'courses';

    // ── Fetch ─────────────────────────────────────────────────────────────────

    public function findByUuidFull(string $uuid): ?array
    {
        return $this->queryOne(
            'SELECT c.*, cat.name AS category_name, u.first_name, u.last_name
             FROM courses c
             LEFT JOIN categories cat ON cat.id = c.category_id
             LEFT JOIN users u ON u.id = c.created_by
             WHERE c.uuid = ? LIMIT 1',
            [$uuid]
        );
    }

    public function paginate(int $page = 1, int $perPage = 20, string $search = '', string $status = '', int $categoryId = 0): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(c.title LIKE ? OR c.short_description LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status !== '') {
            $where[]  = 'c.status = ?';
            $params[] = $status;
        }
        if ($categoryId > 0) {
            $where[]  = 'c.category_id = ?';
            $params[] = $categoryId;
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $total = (int)($this->queryOne(
            "SELECT COUNT(*) AS cnt FROM courses c WHERE $whereStr",
            $params
        )['cnt'] ?? 0);

        $rows = $this->query(
            "SELECT c.*, cat.name AS category_name,
                    (SELECT COUNT(*) FROM sections s WHERE s.course_id = c.id) AS section_count,
                    (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lesson_count,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS enrollment_count,
                    ROUND((SELECT AVG(r.rating) FROM course_reviews r WHERE r.course_id = c.id AND r.is_approved = 1), 1) AS avg_rating
             FROM courses c
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE $whereStr
             ORDER BY c.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return compact('rows', 'total', 'page', 'perPage');
    }

    public function countByStatus(): array
    {
        $rows = $this->query(
            'SELECT status, COUNT(*) AS cnt FROM courses GROUP BY status'
        );
        $result = ['draft' => 0, 'published' => 0, 'archived' => 0];
        foreach ($rows as $r) {
            $result[$r['status']] = (int)$r['cnt'];
        }
        return $result;
    }

    // ── Create / Update / Delete ──────────────────────────────────────────────

    public function create(array $d): int
    {
        return $this->insert(
            'INSERT INTO courses
             (uuid, title, slug, description, short_description, thumbnail, preview_video,
              category_id, level, language, is_rtl, status, visibility, password,
              enrollment_type, pass_percentage, certificate_enabled, forum_enabled,
              forum_enrolled_only, drip_enabled, end_date, grade_points, duration_hours,
              sort_order, created_by, published_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $d['uuid'], $d['title'], $d['slug'], $d['description'] ?? null,
                $d['short_description'] ?? null, $d['thumbnail'] ?? null,
                $d['preview_video'] ?? null, $d['category_id'] ?? null,
                $d['level'] ?? 'beginner', $d['language'] ?? 'English',
                (int)($d['is_rtl'] ?? 0), $d['status'] ?? 'draft',
                $d['visibility'] ?? 'public', $d['password'] ?? null,
                $d['enrollment_type'] ?? 'admin_only',
                (int)($d['pass_percentage'] ?? 80),
                (int)($d['certificate_enabled'] ?? 1),
                (int)($d['forum_enabled'] ?? 0),
                (int)($d['forum_enrolled_only'] ?? 1),
                (int)($d['drip_enabled'] ?? 0),
                $d['end_date'] ?? null,
                (int)($d['grade_points'] ?? 0),
                $d['duration_hours'] ?? null,
                (int)($d['sort_order'] ?? 0),
                (int)$d['created_by'],
                $d['status'] === 'published' ? date('Y-m-d H:i:s') : null,
            ]
        );
    }

    public function update(string $uuid, array $d): void
    {
        $publishedAt = null;
        if (($d['status'] ?? '') === 'published') {
            // preserve existing published_at if already set
            $existing    = $this->findByUuidFull($uuid);
            $publishedAt = $existing['published_at'] ?? date('Y-m-d H:i:s');
        }

        $this->execute(
            'UPDATE courses SET
               title=?, slug=?, description=?, short_description=?, thumbnail=?,
               preview_video=?, category_id=?, level=?, language=?, is_rtl=?,
               status=?, visibility=?, password=?, enrollment_type=?,
               pass_percentage=?, certificate_enabled=?, forum_enabled=?,
               forum_enrolled_only=?, drip_enabled=?, end_date=?,
               grade_points=?, duration_hours=?, sort_order=?, published_at=?,
               updated_at=NOW()
             WHERE uuid=?',
            [
                $d['title'], $d['slug'], $d['description'] ?? null,
                $d['short_description'] ?? null, $d['thumbnail'] ?? null,
                $d['preview_video'] ?? null, $d['category_id'] ?? null,
                $d['level'] ?? 'beginner', $d['language'] ?? 'English',
                (int)($d['is_rtl'] ?? 0), $d['status'] ?? 'draft',
                $d['visibility'] ?? 'public',
                ($d['visibility'] === 'password' ? ($d['password'] ?? null) : null),
                $d['enrollment_type'] ?? 'admin_only',
                (int)($d['pass_percentage'] ?? 80),
                (int)($d['certificate_enabled'] ?? 1),
                (int)($d['forum_enabled'] ?? 0),
                (int)($d['forum_enrolled_only'] ?? 1),
                (int)($d['drip_enabled'] ?? 0),
                ($d['end_date'] ?? '') !== '' ? $d['end_date'] : null,
                (int)($d['grade_points'] ?? 0),
                ($d['duration_hours'] ?? '') !== '' ? $d['duration_hours'] : null,
                (int)($d['sort_order'] ?? 0),
                $publishedAt,
                $uuid,
            ]
        );
    }

    public function delete(string $uuid): void
    {
        $this->execute('DELETE FROM courses WHERE uuid = ?', [$uuid]);
    }

    // ── Sections & Lessons ────────────────────────────────────────────────────

    public function sectionsWithLessons(int $courseId): array
    {
        $sections = $this->query(
            'SELECT * FROM sections WHERE course_id = ? ORDER BY sort_order, id',
            [$courseId]
        );
        foreach ($sections as &$section) {
            $section['lessons'] = $this->query(
                'SELECT * FROM lessons WHERE section_id = ? ORDER BY sort_order, id',
                [$section['id']]
            );
        }
        return $sections;
    }

    // ── JSON Export / Import ──────────────────────────────────────────────────

    public function exportJson(string $uuid): array
    {
        $course = $this->findByUuidFull($uuid);
        if (!$course) return [];

        $sections = $this->sectionsWithLessons((int)$course['id']);

        // Strip user-specific and system fields
        unset($course['id'], $course['created_by'], $course['created_at'],
              $course['updated_at'], $course['published_at'], $course['first_name'],
              $course['last_name'], $course['category_name']);

        foreach ($sections as &$s) {
            unset($s['id'], $s['course_id'], $s['created_at'], $s['updated_at']);
            foreach ($s['lessons'] as &$l) {
                unset($l['id'], $l['course_id'], $l['section_id'],
                      $l['created_at'], $l['updated_at']);
            }
        }

        return [
            'lmsadvisor_export' => true,
            'version'           => '1.0',
            'exported_at'       => date('c'),
            'course'            => $course,
            'sections'          => $sections,
        ];
    }
}

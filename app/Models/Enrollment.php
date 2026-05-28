<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Enrollment extends Model
{
    protected string $table = 'enrollments';

    // ── Lookup ────────────────────────────────────────────────────────────────

    public function findEnrollment(int $courseId, int $userId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM enrollments WHERE course_id = ? AND user_id = ? LIMIT 1',
            [$courseId, $userId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->queryOne(
            'SELECT e.*, c.title AS course_title, c.uuid AS course_uuid,
                    u.first_name, u.last_name, u.email
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             JOIN users u   ON u.id = e.user_id
             WHERE e.id = ? LIMIT 1',
            [$id]
        );
    }

    // ── For course (admin enrolled tab) ──────────────────────────────────────

    public function forCourse(int $courseId, string $search = '', string $status = ''): array
    {
        $where  = ['e.course_id = ?'];
        $params = [$courseId];

        if ($search !== '') {
            $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status !== '') {
            $where[]  = 'e.status = ?';
            $params[] = $status;
        }

        return $this->query(
            'SELECT e.*,
                    u.first_name, u.last_name, u.email,
                    u.uuid AS user_uuid,
                    r.display_name AS role_display,
                    (SELECT ROUND(
                        COUNT(CASE WHEN lp.status = "completed" THEN 1 END) * 100.0
                        / NULLIF(COUNT(l.id), 0)
                     , 0)
                     FROM lessons l
                     LEFT JOIN lesson_progress lp
                       ON lp.lesson_id = l.id AND lp.enrollment_id = e.id
                     WHERE l.course_id = e.course_id
                    ) AS progress_pct
             FROM enrollments e
             JOIN users u ON u.id = e.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY e.enrolled_at DESC',
            $params
        );
    }

    // ── For user (student view) ────────────────────────────────────────────

    public function forUser(int $userId): array
    {
        return $this->query(
            'SELECT e.*, c.title, c.uuid AS course_uuid, c.thumbnail,
                    c.level, c.duration_hours, c.certificate_enabled,
                    cat.name AS category_name,
                    (SELECT ROUND(
                        COUNT(CASE WHEN lp.status = "completed" THEN 1 END) * 100.0
                        / NULLIF(COUNT(l.id), 0)
                     , 0)
                     FROM lessons l
                     LEFT JOIN lesson_progress lp
                       ON lp.lesson_id = l.id AND lp.enrollment_id = e.id
                     WHERE l.course_id = e.course_id
                    ) AS progress_pct
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE e.user_id = ?
             ORDER BY e.enrolled_at DESC',
            [$userId]
        );
    }

    // ── Paginated admin list ──────────────────────────────────────────────────

    public function paginate(int $page = 1, int $perPage = 25, string $search = '', string $status = '', int $courseId = 0): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.title LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status !== '') {
            $where[]  = 'e.status = ?';
            $params[] = $status;
        }
        if ($courseId > 0) {
            $where[]  = 'e.course_id = ?';
            $params[] = $courseId;
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $total = (int)($this->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM enrollments e
             JOIN users u ON u.id = e.user_id
             JOIN courses c ON c.id = e.course_id
             WHERE $whereStr",
            $params
        )['cnt'] ?? 0);

        $rows = $this->query(
            "SELECT e.*, c.title AS course_title, c.uuid AS course_uuid,
                    u.first_name, u.last_name, u.email, u.uuid AS user_uuid
             FROM enrollments e
             JOIN users u ON u.id = e.user_id
             JOIN courses c ON c.id = e.course_id
             WHERE $whereStr
             ORDER BY e.enrolled_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return compact('rows', 'total', 'page', 'perPage');
    }

    // ── Enroll / Remove ───────────────────────────────────────────────────────

    public function enroll(int $courseId, int $userId, int $enrolledBy, ?string $expiresAt = null): int
    {
        return $this->insert(
            'INSERT INTO enrollments (uuid, course_id, user_id, enrolled_by, status, expires_at)
             VALUES (?, ?, ?, ?, "active", ?)',
            [\App\Helpers\Uuid::v4(), $courseId, $userId, $enrolledBy, $expiresAt]
        );
    }

    public function remove(int $id): void
    {
        $this->execute('DELETE FROM enrollments WHERE id = ?', [$id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
        $this->execute(
            'UPDATE enrollments SET status = ?, completed_at = ? WHERE id = ?',
            [$status, $completedAt, $id]
        );
    }

    // ── Counts ────────────────────────────────────────────────────────────────

    public function countByStatus(): array
    {
        $rows = $this->query(
            'SELECT status, COUNT(*) AS cnt FROM enrollments GROUP BY status'
        );
        $result = ['active' => 0, 'completed' => 0, 'suspended' => 0, 'expired' => 0];
        foreach ($rows as $r) {
            $result[$r['status']] = (int)$r['cnt'];
        }
        return $result;
    }

    public function totalEnrolled(): int
    {
        return (int)($this->queryOne('SELECT COUNT(*) AS cnt FROM enrollments')['cnt'] ?? 0);
    }

    // ── Progress ──────────────────────────────────────────────────────────────

    public function markLessonProgress(int $enrollmentId, int $lessonId, string $status, int $pct = 0): void
    {
        // Get user_id from enrollment first
        $enrollment = $this->queryOne(
            'SELECT user_id FROM enrollments WHERE id = ? LIMIT 1',
            [$enrollmentId]
        );
        if (!$enrollment) return;

        $userId = (int)$enrollment['user_id'];

        // Check if a progress row already exists
        $existing = $this->queryOne(
            'SELECT id FROM lesson_progress WHERE enrollment_id = ? AND lesson_id = ? LIMIT 1',
            [$enrollmentId, $lessonId]
        );

        if ($existing) {
            // Update existing row
            $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
            $this->execute(
                'UPDATE lesson_progress
                 SET status = ?, progress_pct = ?, completed_at = COALESCE(?, completed_at)
                 WHERE enrollment_id = ? AND lesson_id = ?',
                [$status, $pct, $completedAt, $enrollmentId, $lessonId]
            );
        } else {
            // Insert new row
            $this->insert(
                'INSERT INTO lesson_progress
                 (enrollment_id, lesson_id, user_id, status, progress_pct, started_at, completed_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?)',
                [
                    $enrollmentId,
                    $lessonId,
                    $userId,
                    $status,
                    $pct,
                    $status === 'completed' ? date('Y-m-d H:i:s') : null,
                ]
            );
        }
    }
}

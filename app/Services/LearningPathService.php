<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Helpers\Uuid;

class LearningPathService
{
    /** Get all paths with course count and user enrollment status. */
    public static function all(?int $userId = null): array
    {
        $pdo   = Database::getInstance();
        $stmt  = $pdo->prepare(
            'SELECT lp.*,
                    COUNT(DISTINCT lpc.course_id)   AS course_count,
                    lpe.status                      AS enrollment_status,
                    lpe.enrolled_at                 AS enrolled_at
             FROM learning_paths lp
             LEFT JOIN learning_path_courses lpc ON lpc.path_id = lp.id
             LEFT JOIN learning_path_enrollments lpe
                    ON lpe.path_id = lp.id AND lpe.user_id = ?
             WHERE lp.is_published = 1
             GROUP BY lp.id
             ORDER BY lp.sort_order, lp.created_at DESC'
        );
        $stmt->execute([$userId ?? 0]);
        return $stmt->fetchAll();
    }

    /** Get path with courses and per-course enrollment status for a user. */
    public static function findWithCourses(int $pathId, ?int $userId = null): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT lp.*, lpe.status AS enrollment_status
             FROM learning_paths lp
             LEFT JOIN learning_path_enrollments lpe
                    ON lpe.path_id=lp.id AND lpe.user_id=?
             WHERE lp.id=? LIMIT 1'
        );
        $stmt->execute([$userId ?? 0, $pathId]);
        $path = $stmt->fetch();
        if (!$path) return null;

        $courses = $pdo->prepare(
            'SELECT c.*, lpc.sort_order, lpc.is_required,
                    e.status AS enrollment_status, e.progress_pct,
                    cat.name AS category_name
             FROM learning_path_courses lpc
             JOIN courses c ON c.id=lpc.course_id
             LEFT JOIN categories cat ON cat.id=c.category_id
             LEFT JOIN enrollments e ON e.course_id=c.id AND e.user_id=?
             WHERE lpc.path_id=?
             ORDER BY lpc.sort_order'
        );
        $courses->execute([$userId ?? 0, $pathId]);
        $path['courses'] = $courses->fetchAll();

        return $path;
    }

    /** Enroll user in a path + all its courses. */
    public static function enroll(int $pathId, int $userId): void
    {
        $pdo = Database::getInstance();

        // Enroll in path
        $pdo->prepare(
            'INSERT IGNORE INTO learning_path_enrollments (path_id, user_id) VALUES (?,?)'
        )->execute([$pathId, $userId]);

        // Enroll in all courses in path
        $courses = $pdo->prepare(
            'SELECT course_id FROM learning_path_courses WHERE path_id=?'
        );
        $courses->execute([$pathId]);
        foreach ($courses->fetchAll() as $row) {
            EnrollmentService::enroll($row['course_id'], $userId);
        }
    }

    /** Check if prerequisite courses are all completed before allowing access. */
    public static function prerequisitesMet(int $courseId, int $userId): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT cp.prerequisite_course_id
             FROM course_prerequisites cp WHERE cp.course_id=?'
        );
        $stmt->execute([$courseId]);
        $prereqs = $stmt->fetchAll();

        foreach ($prereqs as $p) {
            $check = $pdo->prepare(
                'SELECT id FROM enrollments
                 WHERE course_id=? AND user_id=? AND status=\'completed\' LIMIT 1'
            );
            $check->execute([$p['prerequisite_course_id'], $userId]);
            if (!$check->fetch()) return false;
        }
        return true;
    }

    /** Calculate path completion % for a user. */
    public static function progressPct(int $pathId, int $userId): int
    {
        $pdo  = Database::getInstance();
        $total = $pdo->prepare(
            'SELECT COUNT(*) FROM learning_path_courses WHERE path_id=?'
        );
        $total->execute([$pathId]); $total = (int)$total->fetchColumn();
        if (!$total) return 0;

        $done = $pdo->prepare(
            'SELECT COUNT(*) FROM learning_path_courses lpc
             JOIN enrollments e ON e.course_id=lpc.course_id
             WHERE lpc.path_id=? AND e.user_id=? AND e.status=\'completed\''
        );
        $done->execute([$pathId, $userId]); $done = (int)$done->fetchColumn();

        $pct = (int)round($done / $total * 100);

        // Mark path complete if 100%
        if ($pct === 100) {
            $pdo->prepare(
                'UPDATE learning_path_enrollments
                 SET status=\'completed\', completed_at=NOW()
                 WHERE path_id=? AND user_id=? AND status=\'active\''
            )->execute([$pathId, $userId]);
        }
        return $pct;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO learning_paths (uuid, title, slug, description, thumbnail, is_published, created_by)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([
            Uuid::v4(),
            $data['title'],
            self::slugify($data['title']),
            $data['description'] ?? null,
            $data['thumbnail']   ?? null,
            $data['is_published'] ? 1 : 0,
            $data['created_by'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function syncCourses(int $pathId, array $courseIds): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare('DELETE FROM learning_path_courses WHERE path_id=?')->execute([$pathId]);
        foreach ($courseIds as $i => $courseId) {
            $pdo->prepare(
                'INSERT INTO learning_path_courses (path_id, course_id, sort_order)
                 VALUES (?,?,?)'
            )->execute([$pathId, (int)$courseId, $i]);
        }
    }

    private static function slugify(string $text): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text));
        return trim($slug, '-') . '-' . substr(md5($text . time()), 0, 6);
    }
}

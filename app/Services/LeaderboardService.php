<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class LeaderboardService
{
    /**
     * Award grade points when a course enrollment is completed.
     */
    public static function awardFromEnrollment(int $enrollmentId, int $userId, int $courseId): void
    {
        $pdo = Database::getInstance();

        // Get course grade points
        $stmt = $pdo->prepare('SELECT grade_points, title FROM courses WHERE id = ? LIMIT 1');
        $stmt->execute([$courseId]);
        $course = $stmt->fetch();

        if (!$course || (int)$course['grade_points'] === 0) return;

        // Avoid duplicate awards for same enrollment
        $check = $pdo->prepare(
            'SELECT id FROM grade_points WHERE user_id = ? AND course_id = ?
             AND reason = "course_completion" LIMIT 1'
        );
        $check->execute([$userId, $courseId]);
        if ($check->fetch()) return;

        $pdo->prepare(
            'INSERT INTO grade_points (user_id, course_id, points, reason)
             VALUES (?, ?, ?, "course_completion")'
        )->execute([$userId, $courseId, (int)$course['grade_points']]);
    }

    /**
     * Award arbitrary points to a user.
     */
    public static function award(int $userId, ?int $courseId, int $points, string $reason = ''): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO grade_points (user_id, course_id, points, reason) VALUES (?, ?, ?, ?)'
        )->execute([$userId, $courseId, $points, $reason]);
    }

    /**
     * Get total points for a user.
     */
    public static function totalPoints(int $userId): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(points),0) AS total FROM grade_points WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetch()['total'];
    }

    /**
     * Get leaderboard — top N users by total points.
     */
    public static function topN(int $n = 20): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.uuid, u.first_name, u.last_name, u.avatar,
                    r.name AS role_name,
                    COALESCE(SUM(gp.points), 0) AS total_points,
                    COUNT(DISTINCT e.course_id) AS courses_completed
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN grade_points gp ON gp.user_id = u.id
             LEFT JOIN enrollments e ON e.user_id = u.id AND e.status = "completed"
             WHERE u.is_active = 1
             GROUP BY u.id
             HAVING total_points > 0
             ORDER BY total_points DESC
             LIMIT ' . (int)$n
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

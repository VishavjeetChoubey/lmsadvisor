<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * RecommendationService — generates personalised next-course suggestions.
 *
 * Scoring algorithm:
 *   1. Same category as completed courses         (+30)
 *   2. Same level or next level up                (+20)
 *   3. Completed by students who finished same courses (+25)
 *   4. High rating (≥4.0)                         (+15)
 *   5. Not already enrolled                       (required)
 *   6. Published courses only                     (required)
 */
class RecommendationService
{
    public static function generateForUser(int $userId, int $limit = 6): array
    {
        $pdo = Database::getInstance();

        // Courses already enrolled in
        $enrolledStmt = $pdo->prepare(
            'SELECT course_id FROM enrollments WHERE user_id=?'
        );
        $enrolledStmt->execute([$userId]);
        $enrolledIds = $enrolledStmt->fetchAll(\PDO::FETCH_COLUMN) ?: [0];

        // Completed course categories + levels
        $completedStmt = $pdo->prepare(
            "SELECT c.category_id, c.level, c.id AS course_id
             FROM enrollments e JOIN courses c ON c.id=e.course_id
             WHERE e.user_id=? AND e.status='completed'"
        );
        $completedStmt->execute([$userId]);
        $completed = $completedStmt->fetchAll();

        $categories  = array_unique(array_filter(array_column($completed, 'category_id')));
        $levels      = array_unique(array_column($completed, 'level'));
        $completedCourseIds = array_column($completed, 'course_id') ?: [0];

        // Level progression
        $levelUp = ['beginner' => 'intermediate', 'intermediate' => 'advanced', 'advanced' => 'advanced'];
        $nextLevels = array_map(fn($l) => $levelUp[$l] ?? $l, $levels);
        $allLevels  = array_unique(array_merge($levels, $nextLevels));

        // Get all published courses not enrolled
        $excl = implode(',', array_fill(0, count($enrolledIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT c.id, c.uuid, c.title, c.short_description, c.level, c.category_id,
                    c.thumbnail, c.duration_hours, c.grade_points,
                    cat.name AS category_name,
                    COALESCE(r.avg_rating, 0) AS avg_rating,
                    (SELECT COUNT(*) FROM enrollments e2 WHERE e2.course_id=c.id) AS enroll_count
             FROM courses c
             LEFT JOIN categories cat ON cat.id=c.category_id
             LEFT JOIN (SELECT course_id, AVG(rating) AS avg_rating FROM course_reviews GROUP BY course_id) r
               ON r.course_id=c.id
             WHERE c.status='published' AND c.id NOT IN ({$excl})
             ORDER BY enroll_count DESC"
        );
        $stmt->execute($enrolledIds);
        $candidates = $stmt->fetchAll();

        // ── Score each candidate ──────────────────────────────────────────────
        $scored = [];
        foreach ($candidates as $course) {
            $score  = 0;
            $reason = [];

            // Same category as completed
            if ($categories && in_array($course['category_id'], $categories)) {
                $score += 30;
                $reason[] = 'matches your interests';
            }

            // Level match or next level
            if ($allLevels && in_array($course['level'], $allLevels)) {
                $score += 20;
                $reason[] = 'right difficulty level';
            }

            // Peer learning — others who completed same courses also enrolled here
            if ($completedCourseIds !== [0]) {
                $peerExcl = implode(',', array_fill(0, count($completedCourseIds), '?'));
                $peerStmt = $pdo->prepare(
                    "SELECT COUNT(DISTINCT e.user_id) FROM enrollments e
                     WHERE e.course_id=? AND e.user_id IN (
                         SELECT DISTINCT user_id FROM enrollments WHERE course_id IN ({$peerExcl})
                     ) AND e.user_id != ?"
                );
                $peerStmt->execute(array_merge(
                    [$course['id']],
                    $completedCourseIds,
                    [$userId]
                ));
                $peers = (int)$peerStmt->fetchColumn();
                if ($peers > 0) {
                    $score += min(25, $peers * 5);
                    $reason[] = "{$peers} similar students enrolled";
                }
            }

            // High rating
            if ($course['avg_rating'] >= 4.0) {
                $score += 15;
                $reason[] = number_format($course['avg_rating'], 1) . '★ rating';
            }

            // Popular
            if ($course['enroll_count'] > 10) {
                $score += 10;
            }

            if ($score > 0) {
                $course['recommendation_score'] = $score;
                $course['recommendation_reason'] = implode(' · ', $reason) ?: 'Popular course';
                $scored[] = $course;
            }
        }

        // Sort by score, take top N
        usort($scored, fn($a, $b) => $b['recommendation_score'] <=> $a['recommendation_score']);
        $top = array_slice($scored, 0, $limit);

        // Upsert into course_recommendations
        foreach ($top as $rec) {
            $pdo->prepare(
                'INSERT INTO course_recommendations (user_id, course_id, score, reason)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE score=VALUES(score), reason=VALUES(reason), generated_at=NOW(), dismissed=0'
            )->execute([$userId, $rec['id'], $rec['recommendation_score'], $rec['recommendation_reason']]);
        }

        return $top;
    }

    public static function getForUser(int $userId, int $limit = 6): array
    {
        // Regenerate on every call (fast enough for <200 courses)
        return self::generateForUser($userId, $limit);
    }

    public static function dismiss(int $userId, int $courseId): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare('UPDATE course_recommendations SET dismissed=1 WHERE user_id=? AND course_id=?')
            ->execute([$userId, $courseId]);
    }
}

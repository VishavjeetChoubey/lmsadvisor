<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * LessonGatingService — determines if a student can access a lesson.
 *
 * Gating rules (checked in order):
 *   1. Drip schedule  — lesson.drip_days days after enrollment
 *   2. Available from — specific datetime gate
 *   3. Prerequisite   — previous lesson must be completed
 *   4. Min time       — must have spent N seconds on previous lesson
 *   5. No gate        — always accessible
 */
class LessonGatingService
{
    public static function canAccess(int $lessonId, int $enrollmentId): array
    {
        $pdo = Database::getInstance();

        $lesson = $pdo->prepare(
            'SELECT l.*, e.enrolled_at
             FROM lessons l
             JOIN enrollments e ON e.id=?
             WHERE l.id=? LIMIT 1'
        );
        $lesson->execute([$enrollmentId, $lessonId]);
        $l = $lesson->fetch();

        if (!$l) return ['allowed' => false, 'reason' => 'Lesson not found.'];

        // Rule 1: Drip schedule
        if (!empty($l['drip_days']) && (int)$l['drip_days'] > 0) {
            $unlockTs = strtotime($l['enrolled_at']) + ((int)$l['drip_days'] * 86400);
            if (time() < $unlockTs) {
                return [
                    'allowed'    => false,
                    'reason'     => 'This lesson unlocks on ' . date('d M Y', $unlockTs),
                    'unlock_at'  => date('c', $unlockTs),
                    'type'       => 'drip',
                ];
            }
        }

        // Rule 2: Available from date
        if (!empty($l['available_from']) && strtotime($l['available_from']) > time()) {
            return [
                'allowed'   => false,
                'reason'    => 'Available from ' . date('d M Y H:i', strtotime($l['available_from'])),
                'unlock_at' => date('c', strtotime($l['available_from'])),
                'type'      => 'date',
            ];
        }

        // Rule 3 & 4: Prerequisite lesson
        if (!empty($l['unlock_after_lesson'])) {
            $prevProgress = $pdo->prepare(
                'SELECT status, time_spent_sec FROM lesson_progress
                 WHERE lesson_id=? AND enrollment_id=? LIMIT 1'
            );
            $prevProgress->execute([(int)$l['unlock_after_lesson'], $enrollmentId]);
            $prev = $prevProgress->fetch();

            if (!$prev || $prev['status'] !== 'completed') {
                $prevLesson = $pdo->prepare('SELECT title FROM lessons WHERE id=? LIMIT 1');
                $prevLesson->execute([(int)$l['unlock_after_lesson']]);
                $prevTitle = $prevLesson->fetchColumn();
                return [
                    'allowed' => false,
                    'reason'  => 'Complete "' . $prevTitle . '" first.',
                    'type'    => 'prerequisite',
                ];
            }

            // Rule 4: Minimum time on previous lesson
            if (!empty($l['min_time_sec']) && (int)$l['min_time_sec'] > 0) {
                $spent = (int)($prev['time_spent_sec'] ?? 0);
                if ($spent < (int)$l['min_time_sec']) {
                    $remaining = (int)$l['min_time_sec'] - $spent;
                    return [
                        'allowed' => false,
                        'reason'  => 'Spend at least ' . ceil($remaining/60) . ' more minute(s) on the previous lesson.',
                        'type'    => 'min_time',
                    ];
                }
            }
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /** Get lock status for all lessons in an enrollment */
    public static function statusForEnrollment(int $enrollmentId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT l.id, l.is_locked, l.drip_days, l.unlock_after_lesson,
                    l.min_time_sec, l.available_from
             FROM lessons l
             JOIN enrollments e ON e.id=?
             WHERE l.course_id = e.course_id
             ORDER BY l.sort_order'
        );
        $stmt->execute([$enrollmentId]);
        $lessons = $stmt->fetchAll();

        $result = [];
        foreach ($lessons as $l) {
            $result[$l['id']] = self::canAccess($l['id'], $enrollmentId);
        }
        return $result;
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class CalendarService
{
    /**
     * Create calendar events when a user is enrolled in a course.
     * Events: enrollment date + course end date (if set) + webinars.
     */
    public static function createEnrollmentEvents(int $enrollmentId, int $courseId, int $userId): void
    {
        $pdo = Database::getInstance();

        // Get course details
        $course = $pdo->prepare('SELECT title, end_date FROM courses WHERE id = ? LIMIT 1');
        $course->execute([$courseId]);
        $c = $course->fetch();
        if (!$c) return;

        // 1. Enrollment event
        self::insertEvent([
            'enrollment_id' => $enrollmentId,
            'user_id'       => $userId,
            'course_id'     => $courseId,
            'title'         => 'Enrolled: ' . $c['title'],
            'event_type'    => 'enrollment',
            'event_date'    => date('Y-m-d'),
        ]);

        // 2. Course deadline (if end_date set)
        if ($c['end_date']) {
            self::insertEvent([
                'enrollment_id' => $enrollmentId,
                'user_id'       => $userId,
                'course_id'     => $courseId,
                'title'         => 'Deadline: ' . $c['title'],
                'event_type'    => 'due_date',
                'event_date'    => $c['end_date'],
            ]);
        }

        // 3. Webinar sessions for this course
        $webinars = $pdo->prepare(
            'SELECT id, title, scheduled_at FROM webinar_sessions
             WHERE course_id = ? AND status = "scheduled" AND scheduled_at >= NOW()'
        );
        $webinars->execute([$courseId]);
        foreach ($webinars->fetchAll() as $w) {
            self::insertEvent([
                'enrollment_id' => $enrollmentId,
                'user_id'       => $userId,
                'course_id'     => $courseId,
                'title'         => 'Webinar: ' . $w['title'],
                'event_type'    => 'webinar',
                'event_date'    => date('Y-m-d', strtotime($w['scheduled_at'])),
                'notes'         => 'Session ID: ' . $w['id'],
            ]);
        }
    }

    /**
     * Remove all calendar events for an enrollment (called on unenroll).
     */
    public static function removeEnrollmentEvents(int $enrollmentId): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare('DELETE FROM course_calendar_events WHERE enrollment_id = ?')
            ->execute([$enrollmentId]);
    }

    /**
     * Get all calendar events for a user, optionally filtered by month.
     */
    public static function forUser(int $userId, ?int $year = null, ?int $month = null): array
    {
        $pdo    = Database::getInstance();
        $where  = 'user_id = ?';
        $params = [$userId];

        if ($year && $month) {
            $where   .= ' AND YEAR(event_date) = ? AND MONTH(event_date) = ?';
            $params[] = $year;
            $params[] = $month;
        }

        $stmt = $pdo->prepare(
            "SELECT cce.*, c.uuid AS course_uuid, c.title AS course_title
             FROM course_calendar_events cce
             JOIN courses c ON c.id = cce.course_id
             WHERE $where ORDER BY cce.event_date ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Return events as FullCalendar-compatible JSON array.
     */
    public static function toFullCalendarEvents(array $events): array
    {
        $colorMap = [
            'enrollment' => '#1a56db',
            'due_date'   => '#e02424',
            'completion' => '#0e9f6e',
            'webinar'    => '#7c3aed',
        ];

        return array_map(fn($e) => [
            'id'    => $e['id'],
            'title' => $e['title'],
            'start' => $e['event_date'],
            'color' => $colorMap[$e['event_type']] ?? '#64748b',
            'extendedProps' => [
                'type'         => $e['event_type'],
                'course_uuid'  => $e['course_uuid'],
                'course_title' => $e['course_title'],
                'notes'        => $e['notes'] ?? '',
            ],
        ], $events);
    }

    private static function insertEvent(array $d): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO course_calendar_events
             (enrollment_id, user_id, course_id, title, event_type, event_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $d['enrollment_id'],
            $d['user_id'],
            $d['course_id'],
            $d['title'],
            $d['event_type'],
            $d['event_date'],
            $d['notes'] ?? null,
        ]);
    }
}

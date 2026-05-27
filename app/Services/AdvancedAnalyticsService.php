<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * Advanced Learner Analytics — Phase 20
 * Provides LMS-specific insights beyond page-view tracking.
 */
class AdvancedAnalyticsService
{
    // ── Course Completion Funnel ─────────────────────────────────────────────

    /** Where students drop off in a course (section-by-section) */
    public static function completionFunnel(int $courseId): array
    {
        $pdo  = Database::getInstance();
        // Total enrolled
        $total = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id=?');
        $total->execute([$courseId]);
        $total = max(1, (int)$total->fetchColumn());

        // Per-section completion
        $stmt = $pdo->prepare(
            'SELECT s.id, s.title, s.sort_order,
                    COUNT(DISTINCT l.id)                         AS total_lessons,
                    COUNT(DISTINCT CASE WHEN lp.status=\'completed\' THEN lp.user_id END) AS completions
             FROM sections s
             JOIN lessons l ON l.section_id=s.id
             LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id
             WHERE s.course_id=?
             GROUP BY s.id ORDER BY s.sort_order'
        );
        $stmt->execute([$courseId]);
        $sections = $stmt->fetchAll();

        foreach ($sections as &$sec) {
            $sec['completion_pct'] = round($sec['completions'] / $total * 100);
            $sec['drop_off']       = max(0, 100 - $sec['completion_pct']);
        }
        return ['sections' => $sections, 'total_enrolled' => $total];
    }

    // ── Quiz Performance ─────────────────────────────────────────────────────

    /** Hardest questions — lowest pass rate */
    public static function quizHardestQuestions(int $courseId, int $limit = 10): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT q.question, q.id,
                    COUNT(DISTINCT ar.attempt_id)                               AS attempts,
                    SUM(CASE WHEN ar.is_correct=1 THEN 1 ELSE 0 END)            AS correct,
                    ROUND(SUM(CASE WHEN ar.is_correct=1 THEN 1 ELSE 0 END) / COUNT(DISTINCT ar.attempt_id) * 100) AS pass_pct
             FROM questions q
             JOIN quizzes qz ON qz.id=q.quiz_id
             JOIN lessons l ON l.id=qz.lesson_id
             JOIN attempt_responses ar ON ar.question_id=q.id
             WHERE l.course_id=?
             GROUP BY q.id HAVING attempts > 0
             ORDER BY pass_pct ASC LIMIT ?'
        );
        $stmt->execute([$courseId, $limit]);
        return $stmt->fetchAll();
    }

    // ── Learner Engagement Score ─────────────────────────────────────────────

    /**
     * Engagement = (logins in period) × 0.3 + (lessons completed) × 0.5
     *            + (quiz attempts) × 0.2  — normalised to 100
     */
    public static function engagementScores(int $days = 30): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));

        $stmt = $pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email,
                    COALESCE(s.logins,0)  AS logins,
                    COALESCE(lp.lessons,0) AS lessons_done,
                    COALESCE(qa.quizzes,0) AS quiz_attempts,
                    COALESCE(e.courses,0)  AS enrolled_courses
             FROM users u
             JOIN roles r ON r.id=u.role_id AND r.name=\'student\'
             LEFT JOIN (
                 SELECT user_id, COUNT(*) AS logins
                 FROM sessions WHERE created_at>=? GROUP BY user_id
             ) s ON s.user_id=u.id
             LEFT JOIN (
                 SELECT user_id, COUNT(*) AS lessons
                 FROM lesson_progress WHERE completed_at>=? AND status=\'completed\' GROUP BY user_id
             ) lp ON lp.user_id=u.id
             LEFT JOIN (
                 SELECT user_id, COUNT(*) AS quizzes
                 FROM quiz_attempts WHERE started_at>=? GROUP BY user_id
             ) qa ON qa.user_id=u.id
             LEFT JOIN (
                 SELECT user_id, COUNT(*) AS courses
                 FROM enrollments WHERE status=\'active\' GROUP BY user_id
             ) e ON e.user_id=u.id
             WHERE u.is_active=1
             ORDER BY (COALESCE(s.logins,0)*0.3 + COALESCE(lp.lessons,0)*0.5 + COALESCE(qa.quizzes,0)*0.2) DESC
             LIMIT 100'
        );
        $stmt->execute([$from, $from, $from]);
        $rows = $stmt->fetchAll();

        // Normalise scores
        $maxRaw = max(1, array_reduce($rows, fn($c, $r) =>
            max($c, $r['logins']*0.3 + $r['lessons_done']*0.5 + $r['quiz_attempts']*0.2), 0));

        foreach ($rows as &$row) {
            $raw              = $row['logins']*0.3 + $row['lessons_done']*0.5 + $row['quiz_attempts']*0.2;
            $row['score']     = round($raw / $maxRaw * 100);
            $row['raw_score'] = round($raw, 1);
        }
        return $rows;
    }

    // ── At-Risk Students ────────────────────────────────────────────────────

    /** Students enrolled but not logged in for N+ days */
    public static function atRisk(int $inactiveDays = 14): array
    {
        $pdo      = Database::getInstance();
        $cutoff   = date('Y-m-d H:i:s', strtotime("-{$inactiveDays} days"));
        $stmt     = $pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email,
                    u.last_login_at,
                    COUNT(DISTINCT e.course_id) AS active_courses,
                    DATEDIFF(NOW(), u.last_login_at) AS days_inactive
             FROM users u
             JOIN roles r ON r.id=u.role_id AND r.name=\'student\'
             JOIN enrollments e ON e.user_id=u.id AND e.status=\'active\'
             WHERE u.is_active=1
               AND (u.last_login_at IS NULL OR u.last_login_at < ?)
             GROUP BY u.id
             ORDER BY days_inactive DESC LIMIT 50'
        );
        $stmt->execute([$cutoff]);
        return $stmt->fetchAll();
    }

    // ── Course Stats (for instructor dashboard) ──────────────────────────────

    public static function courseStats(int $courseId): array
    {
        $pdo = Database::getInstance();

        $enrolled = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id=?');
        $enrolled->execute([$courseId]); $enrolled = (int)$enrolled->fetchColumn();

        $completed = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id=? AND status=\'completed\'');
        $completed->execute([$courseId]); $completed = (int)$completed->fetchColumn();

        $avgPct = $pdo->prepare('SELECT ROUND(AVG(progress_pct)) FROM enrollments WHERE course_id=?');
        $avgPct->execute([$courseId]); $avgPct = (int)$avgPct->fetchColumn();

        // Avg quiz score
        $avgQuiz = $pdo->prepare(
            'SELECT ROUND(AVG(qa.score)) FROM quiz_attempts qa
             JOIN quizzes qz ON qz.id=qa.quiz_id
             JOIN lessons l ON l.id=qz.lesson_id
             WHERE l.course_id=? AND qa.score IS NOT NULL'
        );
        $avgQuiz->execute([$courseId]); $avgQuiz = (int)$avgQuiz->fetchColumn();

        // Avg rating
        $avgRating = $pdo->prepare('SELECT ROUND(AVG(rating),1), COUNT(*) FROM course_reviews WHERE course_id=? AND is_approved=1');
        $avgRating->execute([$courseId]); [$avgRat, $ratingCount] = $avgRating->fetch(\PDO::FETCH_NUM);

        // Enrollments over time (last 30 days)
        $trend = $pdo->prepare(
            'SELECT DATE(enrolled_at) AS d, COUNT(*) AS c
             FROM enrollments WHERE course_id=? AND enrolled_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)
             GROUP BY DATE(enrolled_at) ORDER BY d'
        );
        $trend->execute([$courseId]);

        return [
            'enrolled'     => $enrolled,
            'completed'    => $completed,
            'completion_pct'=> $enrolled > 0 ? round($completed/$enrolled*100) : 0,
            'avg_progress' => $avgPct,
            'avg_quiz_score'=> $avgQuiz,
            'avg_rating'   => (float)($avgRat ?? 0),
            'rating_count' => (int)($ratingCount ?? 0),
            'enroll_trend' => $trend->fetchAll(),
        ];
    }

    // ── Grade Book ───────────────────────────────────────────────────────────

    public static function gradeBook(int $courseId): array
    {
        $pdo = Database::getInstance();
        // Students
        $students = $pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email,
                    e.id AS enrollment_id, e.progress_pct, e.status AS enrollment_status
             FROM enrollments e JOIN users u ON u.id=e.user_id
             WHERE e.course_id=? ORDER BY u.first_name'
        );
        $students->execute([$courseId]);
        $students = $students->fetchAll();

        // Quiz scores per student
        $quizScores = $pdo->prepare(
            'SELECT qa.user_id, qz.title, MAX(qa.score) AS best_score,
                    qz.pass_percentage, MAX(qa.passed) AS passed
             FROM quiz_attempts qa
             JOIN quizzes qz ON qz.id=qa.quiz_id
             JOIN lessons l ON l.id=qz.lesson_id
             WHERE l.course_id=?
             GROUP BY qa.user_id, qz.id'
        );
        $quizScores->execute([$courseId]);
        $scores = [];
        foreach ($quizScores->fetchAll() as $row) {
            $scores[$row['user_id']][] = $row;
        }

        // Assignment scores
        $asgScores = $pdo->prepare(
            'SELECT s.user_id, a.title, MAX(s.score) AS best_score, a.max_score,
                    a.pass_score, MAX(CASE WHEN s.status=\'pass\' THEN 1 ELSE 0 END) AS passed
             FROM assignment_submissions s
             JOIN assignments a ON a.id=s.assignment_id
             JOIN lessons l ON l.id=a.lesson_id
             WHERE l.course_id=?
             GROUP BY s.user_id, a.id'
        );
        $asgScores->execute([$courseId]);
        $asgScoresMap = [];
        foreach ($asgScores->fetchAll() as $row) {
            $asgScoresMap[$row['user_id']][] = $row;
        }

        return [
            'students'     => $students,
            'quiz_scores'  => $scores,
            'asgn_scores'  => $asgScoresMap,
        ];
    }

    // ── Average time per lesson ──────────────────────────────────────────────

    public static function avgLessonTime(int $courseId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT l.id, l.title, l.type,
                    COUNT(DISTINCT lp.user_id)  AS learners,
                    ROUND(AVG(
                        TIMESTAMPDIFF(SECOND, lp.started_at, lp.completed_at)
                    )) AS avg_seconds
             FROM lessons l
             LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id AND lp.status=\'completed\'
             WHERE l.course_id=?
             GROUP BY l.id ORDER BY l.sort_order'
        );
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }
}

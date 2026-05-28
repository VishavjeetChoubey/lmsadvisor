<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * ReportingService — executive-level analytics for the platform.
 * Revenue, cohort retention, student LTV, course performance.
 */
class ReportingService
{
    /** Platform summary KPIs */
    public static function kpis(): array
    {
        $pdo = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $m30 = date('Y-m-d H:i:s', strtotime('-30 days'));
        $m7  = date('Y-m-d H:i:s', strtotime('-7 days'));

        return [
            'total_users'          => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id=(SELECT id FROM roles WHERE name='student')")->fetchColumn(),
            'new_users_30d'        => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE created_at>='$m30'")->fetchColumn(),
            'total_enrollments'    => (int)$pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn(),
            'new_enrollments_30d'  => (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE enrolled_at>='$m30'")->fetchColumn(),
            'total_completions'    => (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE status='completed'")->fetchColumn(),
            'completions_30d'      => (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE status='completed' AND completed_at>='$m30'")->fetchColumn(),
            'completion_rate'      => self::completionRate(),
            'avg_progress'         => self::avgProgress(),
            'active_learners_7d'   => (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM lesson_progress WHERE last_accessed>='$m7'")->fetchColumn(),
            'total_courses'        => (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE status='published'")->fetchColumn(),
            'certificates_issued'  => (int)$pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn(),
        ];
    }

    /** Enrollment trend — daily for last 60 days */
    public static function enrollmentTrend(int $days = 60): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            "SELECT DATE(enrolled_at) AS date, COUNT(*) AS count
             FROM enrollments WHERE enrolled_at >= ?
             GROUP BY DATE(enrolled_at) ORDER BY date ASC"
        );
        $stmt->execute([$from]);
        return $stmt->fetchAll();
    }

    /** Completion trend — daily for last 60 days */
    public static function completionTrend(int $days = 60): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            "SELECT DATE(completed_at) AS date, COUNT(*) AS count
             FROM enrollments WHERE completed_at >= ? AND status='completed'
             GROUP BY DATE(completed_at) ORDER BY date ASC"
        );
        $stmt->execute([$from]);
        return $stmt->fetchAll();
    }

    /** Cohort retention — % of each monthly cohort still active after N months */
    public static function cohortRetention(int $cohorts = 6): array
    {
        $pdo    = Database::getInstance();
        $result = [];

        for ($i = $cohorts - 1; $i >= 0; $i--) {
            $cohortStart = date('Y-m-01', strtotime("-{$i} months"));
            $cohortEnd   = date('Y-m-t',  strtotime("-{$i} months"));
            $label       = date('M Y',    strtotime("-{$i} months"));

            $cohortStmt = $pdo->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM enrollments WHERE enrolled_at BETWEEN ? AND ?"
            );
            $cohortStmt->execute([$cohortStart, $cohortEnd . ' 23:59:59']);
            $cohortSize = (int)$cohortStmt->fetchColumn();

            if (!$cohortSize) continue;

            $row = ['cohort' => $label, 'size' => $cohortSize, 'retention' => [100]];

            // Check retention at 1,2,3 months after cohort
            for ($m = 1; $m <= 3; $m++) {
                $checkDate = date('Y-m-d H:i:s', strtotime($cohortEnd) + ($m * 30 * 86400));
                $activeStmt = $pdo->prepare(
                    "SELECT COUNT(DISTINCT e.user_id) FROM enrollments e
                     JOIN lesson_progress lp ON lp.enrollment_id=e.id
                     WHERE e.enrolled_at BETWEEN ? AND ?
                     AND lp.last_accessed >= ?"
                );
                $activeStmt->execute([$cohortStart, $cohortEnd . ' 23:59:59', $checkDate]);
                $active = (int)$activeStmt->fetchColumn();
                $row['retention'][] = $cohortSize > 0 ? round($active / $cohortSize * 100, 1) : 0;
            }
            $result[] = $row;
        }
        return $result;
    }

    /** Top courses by enrollment + completion rate */
    public static function topCourses(int $limit = 10): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query(
            "SELECT c.uuid, c.title, c.level,
                    COUNT(e.id) AS enrollments,
                    SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END) AS completions,
                    ROUND(SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END)/COUNT(e.id)*100,1) AS completion_rate,
                    COALESCE(AVG(r.rating),0) AS avg_rating,
                    COUNT(DISTINCT cert.id) AS certificates
             FROM courses c
             LEFT JOIN enrollments e ON e.course_id=c.id
             LEFT JOIN reviews r ON r.course_id=c.id
             LEFT JOIN certificates cert ON cert.course_id=c.id
             WHERE c.status='published'
             GROUP BY c.id
             ORDER BY enrollments DESC
             LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }

    /** Student LTV — average lessons completed per student + grade points */
    public static function studentLtv(): array
    {
        $pdo = Database::getInstance();
        return [
            'avg_courses_per_student' => (float)($pdo->query(
                "SELECT AVG(cnt) FROM (SELECT COUNT(*) AS cnt FROM enrollments GROUP BY user_id) t"
            )->fetchColumn() ?? 0),
            'avg_completion_per_student' => (float)($pdo->query(
                "SELECT AVG(cnt) FROM (SELECT COUNT(*) AS cnt FROM enrollments WHERE status='completed' GROUP BY user_id) t"
            )->fetchColumn() ?? 0),
            'avg_grade_points' => (float)($pdo->query(
                "SELECT AVG(pts) FROM (SELECT SUM(c.grade_points) AS pts FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.status='completed' GROUP BY e.user_id) t"
            )->fetchColumn() ?? 0),
            'power_learners' => (int)$pdo->query(
                "SELECT COUNT(*) FROM (SELECT user_id, COUNT(*) AS cnt FROM enrollments WHERE status='completed' GROUP BY user_id HAVING cnt>=3) t"
            )->fetchColumn(),
        ];
    }

    /** Category breakdown */
    public static function categoryBreakdown(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query(
            "SELECT cat.name AS category, COUNT(DISTINCT c.id) AS courses,
                    COUNT(e.id) AS enrollments,
                    ROUND(AVG(CASE WHEN e.status='completed' THEN 1 ELSE 0 END)*100,1) AS completion_rate
             FROM categories cat
             JOIN courses c ON c.category_id=cat.id
             LEFT JOIN enrollments e ON e.course_id=c.id
             WHERE c.status='published'
             GROUP BY cat.id
             ORDER BY enrollments DESC"
        );
        return $stmt->fetchAll();
    }

    private static function completionRate(): float
    {
        $pdo   = Database::getInstance();
        $total = (int)$pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
        if (!$total) return 0.0;
        $done  = (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE status='completed'")->fetchColumn();
        return round($done / $total * 100, 1);
    }

    private static function avgProgress(): float
    {
        $pdo = Database::getInstance();
        return (float)($pdo->query(
            "SELECT AVG(pct) FROM (
                SELECT e.id,
                       ROUND(COUNT(CASE WHEN lp.status='completed' THEN 1 END)*100/NULLIF(COUNT(l.id),0)) AS pct
                FROM enrollments e
                JOIN courses c ON c.id=e.course_id
                JOIN lessons l ON l.course_id=c.id
                LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id AND lp.enrollment_id=e.id
                GROUP BY e.id
             ) t"
        )->fetchColumn() ?? 0.0);
    }
}

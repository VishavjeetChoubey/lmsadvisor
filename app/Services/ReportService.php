<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class ReportService
{
    private static function pdo(): \PDO
    {
        return Database::getInstance();
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  OVERVIEW / DASHBOARD METRICS
    // ══════════════════════════════════════════════════════════════════════════

    public static function overviewStats(): array
    {
        $pdo = self::pdo();

        return [
            'total_users'       => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'total_courses'     => (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
            'total_enrollments' => (int)$pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn(),
            'completed'         => (int)$pdo->query('SELECT COUNT(*) FROM enrollments WHERE status="completed"')->fetchColumn(),
            'active_enrollments'=> (int)$pdo->query('SELECT COUNT(*) FROM enrollments WHERE status="active"')->fetchColumn(),
            'total_lessons'     => (int)$pdo->query('SELECT COUNT(*) FROM lessons')->fetchColumn(),
            'total_quizzes'     => (int)$pdo->query('SELECT COUNT(*) FROM quizzes')->fetchColumn(),
            'total_reviews'     => (int)$pdo->query('SELECT COUNT(*) FROM course_reviews WHERE is_approved=1')->fetchColumn(),
            'avg_rating'        => (float)($pdo->query('SELECT ROUND(AVG(rating),1) FROM course_reviews WHERE is_approved=1')->fetchColumn() ?: 0),
            'total_points'      => (int)$pdo->query('SELECT COALESCE(SUM(points),0) FROM grade_points')->fetchColumn(),
            'forum_threads'     => (int)$pdo->query('SELECT COUNT(*) FROM forum_threads')->fetchColumn(),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  TIME-SERIES (last N days)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Daily enrollments for the last $days days.
     * Returns [['date'=>'2025-01-01','count'=>3], ...]
     */
    public static function enrollmentsTrend(int $days = 30): array
    {
        $pdo  = self::pdo();
        $stmt = $pdo->prepare(
            'SELECT DATE(enrolled_at) AS date, COUNT(*) AS count
             FROM enrollments
             WHERE enrolled_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(enrolled_at)
             ORDER BY date ASC'
        );
        $stmt->execute([$days]);
        return self::fillDateRange($stmt->fetchAll(), $days);
    }

    /**
     * Daily new users for the last $days days.
     */
    public static function usersTrend(int $days = 30): array
    {
        $pdo  = self::pdo();
        $stmt = $pdo->prepare(
            'SELECT DATE(created_at) AS date, COUNT(*) AS count
             FROM users
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC'
        );
        $stmt->execute([$days]);
        return self::fillDateRange($stmt->fetchAll(), $days);
    }

    /**
     * Daily quiz attempts for the last $days days.
     */
    public static function quizAttemptsTrend(int $days = 30): array
    {
        $pdo  = self::pdo();
        $stmt = $pdo->prepare(
            'SELECT DATE(started_at) AS date, COUNT(*) AS count
             FROM quiz_attempts
             WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
               AND completed_at IS NOT NULL
             GROUP BY DATE(started_at)
             ORDER BY date ASC'
        );
        $stmt->execute([$days]);
        return self::fillDateRange($stmt->fetchAll(), $days);
    }

    /** Ensure every date in range has an entry (fill zeros for missing days). */
    private static function fillDateRange(array $rows, int $days): array
    {
        $map = [];
        foreach ($rows as $r) {
            $map[$r['date']] = (int)$r['count'];
        }
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime("-$i days"));
            $result[] = ['date' => $date, 'count' => $map[$date] ?? 0];
        }
        return $result;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  COURSE REPORTS
    // ══════════════════════════════════════════════════════════════════════════

    public static function courseReport(int $page = 1, int $perPage = 25, string $search = ''): array
    {
        $pdo    = self::pdo();
        $where  = $search ? 'WHERE c.title LIKE :s' : '';
        $params = $search ? [':s' => "%$search%"] : [];
        $offset = ($page - 1) * $perPage;

        $total = (int)$pdo->prepare("SELECT COUNT(*) FROM courses c $where")
            ->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM courses c $where") : 0;

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM courses c $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT c.id, c.title, c.slug, c.status, c.level, c.language,
                    c.created_at, c.published_at, c.grade_points,
                    cat.name AS category,
                    (SELECT COUNT(*) FROM sections s WHERE s.course_id=c.id) AS sections,
                    (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lessons,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS enrollments,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id AND e.status='completed') AS completions,
                    (SELECT ROUND(AVG(rating),1) FROM course_reviews r WHERE r.course_id=c.id AND r.is_approved=1) AS avg_rating,
                    (SELECT COUNT(*) FROM course_reviews r WHERE r.course_id=c.id AND r.is_approved=1) AS review_count,
                    (SELECT COUNT(*) FROM quiz_attempts qa JOIN quizzes qz ON qz.id=qa.quiz_id JOIN lessons l2 ON l2.id=qz.lesson_id WHERE l2.course_id=c.id AND qa.completed_at IS NOT NULL) AS quiz_attempts,
                    (SELECT ROUND(AVG(qa.score),1) FROM quiz_attempts qa JOIN quizzes qz ON qz.id=qa.quiz_id JOIN lessons l2 ON l2.id=qz.lesson_id WHERE l2.course_id=c.id AND qa.completed_at IS NOT NULL) AS avg_quiz_score
             FROM courses c
             LEFT JOIN categories cat ON cat.id=c.category_id
             $where
             ORDER BY enrollments DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return compact('rows', 'total', 'page', 'perPage');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  USER REPORTS
    // ══════════════════════════════════════════════════════════════════════════

    public static function userReport(int $page = 1, int $perPage = 25, string $search = '', string $role = ''): array
    {
        $pdo    = self::pdo();
        $where  = ['1=1'];
        $params = [];

        if ($search) {
            $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($role) {
            $where[]  = 'r.name = ?';
            $params[] = $role;
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT u.id, u.uuid, u.first_name, u.last_name, u.email,
                    u.is_active, u.created_at, u.last_login_at,
                    r.name AS role_name, r.display_name AS role_display,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.user_id=u.id) AS enrollments,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.user_id=u.id AND e.status='completed') AS completions,
                    (SELECT COALESCE(SUM(gp.points),0) FROM grade_points gp WHERE gp.user_id=u.id) AS total_points,
                    (SELECT COUNT(*) FROM forum_threads ft WHERE ft.user_id=u.id) AS forum_posts,
                    (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.user_id=u.id AND qa.completed_at IS NOT NULL) AS quiz_attempts
             FROM users u
             JOIN roles r ON r.id=u.role_id
             WHERE $whereStr
             ORDER BY u.created_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return compact('rows', 'total', 'page', 'perPage');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  ENROLLMENT REPORTS
    // ══════════════════════════════════════════════════════════════════════════

    public static function enrollmentReport(int $page = 1, int $perPage = 25, string $search = '', string $status = ''): array
    {
        $pdo    = self::pdo();
        $where  = ['1=1'];
        $params = [];

        if ($search) {
            $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR c.title LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status) {
            $where[]  = 'e.status = ?';
            $params[] = $status;
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM enrollments e
             JOIN users u ON u.id=e.user_id
             JOIN courses c ON c.id=e.course_id
             WHERE $whereStr"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT e.id, e.status, e.enrolled_at, e.completed_at, e.expires_at,
                    u.first_name, u.last_name, u.email,
                    c.title AS course_title, c.uuid AS course_uuid, c.level,
                    ROUND(
                      COUNT(CASE WHEN lp.status='completed' THEN 1 END) * 100.0
                      / NULLIF(COUNT(l.id),0)
                    ,0) AS progress_pct,
                    COUNT(l.id) AS total_lessons,
                    COUNT(CASE WHEN lp.status='completed' THEN 1 END) AS completed_lessons
             FROM enrollments e
             JOIN users u ON u.id=e.user_id
             JOIN courses c ON c.id=e.course_id
             LEFT JOIN lessons l ON l.course_id=c.id
             LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id AND lp.enrollment_id=e.id
             WHERE $whereStr
             GROUP BY e.id
             ORDER BY e.enrolled_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return compact('rows', 'total', 'page', 'perPage');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  AUDIT LOG
    // ══════════════════════════════════════════════════════════════════════════

    public static function auditLog(int $page = 1, int $perPage = 30, string $search = '', string $action = ''): array
    {
        $pdo    = self::pdo();
        $where  = ['1=1'];
        $params = [];

        if ($search) {
            $where[]  = '(al.action LIKE ? OR u.email LIKE ? OR al.entity_type LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($action) {
            $where[]  = 'al.action LIKE ?';
            $params[] = $action . '%';
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM audit_logs al
             LEFT JOIN users u ON u.id=al.user_id
             WHERE $whereStr"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT al.*, u.first_name, u.last_name, u.email
             FROM audit_logs al
             LEFT JOIN users u ON u.id=al.user_id
             WHERE $whereStr
             ORDER BY al.created_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return compact('rows', 'total', 'page', 'perPage');
    }

    /** Distinct action prefixes for audit filter dropdown */
    public static function auditActionGroups(): array
    {
        $pdo  = self::pdo();
        $stmt = $pdo->query(
            "SELECT DISTINCT SUBSTRING_INDEX(action, '.', 1) AS grp
             FROM audit_logs ORDER BY grp"
        );
        return array_column($stmt->fetchAll(), 'grp');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  CSV EXPORT
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Stream a CSV file to the browser.
     *
     * @param string   $filename  e.g. 'enrollments-2025-01.csv'
     * @param array    $headers   Column headers
     * @param iterable $rows      Data rows (each is an assoc array)
     * @param array    $columns   Keys to extract from each row (in order)
     */
    public static function streamCsv(string $filename, array $headers, iterable $rows, array $columns): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');

        // BOM for Excel UTF-8 compatibility
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, $headers);

        foreach ($rows as $row) {
            $line = array_map(fn($k) => $row[$k] ?? '', $columns);
            fputcsv($out, $line);
        }

        fclose($out);
        exit;
    }
}

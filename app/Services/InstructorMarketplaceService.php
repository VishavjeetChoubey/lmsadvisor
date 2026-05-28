<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * InstructorMarketplaceService — manages instructor applications, approvals,
 * and revenue split tracking.
 */
class InstructorMarketplaceService
{
    /** All applications with user info */
    public static function allApplications(string $status = ''): array
    {
        $pdo   = Database::getInstance();
        $where = $status ? 'WHERE ia.status=?' : '';
        $stmt  = $pdo->prepare(
            "SELECT ia.*, u.first_name, u.last_name, u.email,
                    (SELECT COUNT(*) FROM courses c WHERE c.created_by=ia.user_id) AS course_count,
                    (SELECT COUNT(*) FROM enrollments e JOIN courses c2 ON c2.id=e.course_id WHERE c2.created_by=ia.user_id) AS student_count
             FROM instructor_applications ia
             JOIN users u ON u.id=ia.user_id
             {$where}
             ORDER BY ia.applied_at DESC"
        );
        $status ? $stmt->execute([$status]) : $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Submit application */
    public static function apply(int $userId, array $data): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO instructor_applications (user_id, bio, expertise, portfolio_url)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE bio=VALUES(bio), expertise=VALUES(expertise),
             portfolio_url=VALUES(portfolio_url), status="pending", applied_at=NOW()'
        )->execute([
            $userId,
            $data['bio']           ?? '',
            $data['expertise']     ?? '',
            $data['portfolio_url'] ?? null,
        ]);
    }

    /** Approve or reject */
    public static function review(int $applicationId, string $decision, int $reviewerId, int $revenuePct = 70): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'UPDATE instructor_applications
             SET status=?, reviewed_by=?, reviewed_at=NOW(), revenue_pct=?
             WHERE id=?'
        )->execute([$decision, $reviewerId, $revenuePct, $applicationId]);

        if ($decision === 'approved') {
            // Grant instructor role
            $app = $pdo->prepare('SELECT user_id FROM instructor_applications WHERE id=? LIMIT 1');
            $app->execute([$applicationId]);
            $userId = (int)($app->fetchColumn() ?? 0);
            if ($userId) {
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name='manager' LIMIT 1");
                $roleStmt->execute();
                $roleId = (int)$roleStmt->fetchColumn();
                if ($roleId) {
                    $pdo->prepare('UPDATE users SET role_id=? WHERE id=?')->execute([$roleId, $userId]);
                }
            }
        }
    }

    /** Revenue stats per instructor */
    public static function revenueStats(int $instructorUserId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT c.title, c.uuid,
                    COUNT(e.id) AS enrollments,
                    ia.revenue_pct
             FROM courses c
             JOIN enrollments e ON e.course_id=c.id
             LEFT JOIN instructor_applications ia ON ia.user_id=c.created_by
             WHERE c.created_by=?
             GROUP BY c.id
             ORDER BY enrollments DESC"
        );
        $stmt->execute([$instructorUserId]);
        return $stmt->fetchAll();
    }

    /** Platform-wide revenue summary */
    public static function platformRevenue(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query(
            "SELECT ia.user_id, u.first_name, u.last_name, u.email,
                    ia.revenue_pct, ia.status,
                    COUNT(DISTINCT c.id) AS courses,
                    COUNT(DISTINCT e.id) AS total_enrollments
             FROM instructor_applications ia
             JOIN users u ON u.id=ia.user_id
             LEFT JOIN courses c ON c.created_by=ia.user_id
             LEFT JOIN enrollments e ON e.course_id=c.id
             WHERE ia.status='approved'
             GROUP BY ia.user_id
             ORDER BY total_enrollments DESC"
        );
        return $stmt->fetchAll();
    }
}

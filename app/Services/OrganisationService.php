<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Helpers\Uuid;

/**
 * OrganisationService — corporate training portal.
 * Managers bulk-assign courses, track completion, export compliance reports.
 */
class OrganisationService
{
    public static function all(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query(
            'SELECT o.*, (SELECT COUNT(*) FROM organisation_members om WHERE om.organisation_id=o.id) AS member_count
             FROM organisations o ORDER BY o.name'
        );
        return $stmt->fetchAll();
    }

    public static function findByUuid(string $uuid): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM organisations WHERE uuid=? LIMIT 1');
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): string
    {
        $pdo  = Database::getInstance();
        $uuid = Uuid::v4();
        $pdo->prepare(
            'INSERT INTO organisations (uuid, name, domain, seat_limit, billing_email) VALUES (?,?,?,?,?)'
        )->execute([$uuid, $data['name'], $data['domain'] ?? null, $data['seat_limit'] ?? 50, $data['billing_email'] ?? null]);
        return $uuid;
    }

    public static function members(int $orgId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT om.*, u.first_name, u.last_name, u.email, u.last_login_at
             FROM organisation_members om
             JOIN users u ON u.id=om.user_id
             WHERE om.organisation_id=?
             ORDER BY om.role DESC, u.first_name'
        );
        $stmt->execute([$orgId]);
        return $stmt->fetchAll();
    }

    public static function addMember(int $orgId, int $userId, string $role = 'employee', string $dept = ''): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT IGNORE INTO organisation_members (organisation_id, user_id, role, department) VALUES (?,?,?,?)'
        )->execute([$orgId, $userId, $role, $dept]);
        $pdo->prepare('UPDATE organisations SET seats_used=(SELECT COUNT(*) FROM organisation_members WHERE organisation_id=?) WHERE id=?')
            ->execute([$orgId, $orgId]);
    }

    public static function assignCourse(int $orgId, int $courseId, int $assignedBy, ?string $dueDate = null, bool $mandatory = true): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO course_assignments (organisation_id, course_id, assigned_by, due_date, is_mandatory)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE due_date=VALUES(due_date), is_mandatory=VALUES(is_mandatory)'
        )->execute([$orgId, $courseId, $assignedBy, $dueDate, (int)$mandatory]);

        // Auto-enroll all current members
        $members = $pdo->prepare('SELECT user_id FROM organisation_members WHERE organisation_id=?');
        $members->execute([$orgId]);
        $model = new \App\Models\Enrollment();
        foreach ($members->fetchAll(\PDO::FETCH_COLUMN) as $userId) {
            $existing = $pdo->prepare('SELECT id FROM enrollments WHERE course_id=? AND user_id=? LIMIT 1');
            $existing->execute([$courseId, $userId]);
            if (!$existing->fetch()) {
                $model->enroll($courseId, (int)$userId, $assignedBy);
            }
        }
    }

    public static function complianceReport(int $orgId): array
    {
        $pdo  = Database::getInstance();

        $assignments = $pdo->prepare(
            'SELECT ca.*, c.title AS course_title, c.uuid AS course_uuid
             FROM course_assignments ca
             JOIN courses c ON c.id=ca.course_id
             WHERE ca.organisation_id=?'
        );
        $assignments->execute([$orgId]);
        $assignments = $assignments->fetchAll();

        $members = self::members($orgId);

        $report = [];
        foreach ($members as $member) {
            $row = [
                'user_id'    => $member['user_id'],
                'name'       => $member['first_name'] . ' ' . $member['last_name'],
                'email'      => $member['email'],
                'department' => $member['department'],
                'role'       => $member['role'],
                'courses'    => [],
            ];
            foreach ($assignments as $a) {
                $enrolStmt = $pdo->prepare(
                    'SELECT e.status, e.completed_at FROM enrollments e
                     WHERE e.course_id=? AND e.user_id=? LIMIT 1'
                );
                $enrolStmt->execute([$a['course_id'], $member['user_id']]);
                $enrol = $enrolStmt->fetch();

                $overdue = false;
                if ($a['due_date'] && !($enrol && $enrol['status']==='completed')) {
                    $overdue = strtotime($a['due_date']) < time();
                }

                $row['courses'][] = [
                    'course_title' => $a['course_title'],
                    'mandatory'    => (bool)$a['is_mandatory'],
                    'due_date'     => $a['due_date'],
                    'status'       => $enrol ? $enrol['status'] : 'not_enrolled',
                    'completed_at' => $enrol['completed_at'] ?? null,
                    'overdue'      => $overdue,
                ];
            }
            $report[] = $row;
        }
        return $report;
    }
}

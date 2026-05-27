<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Helpers\Uuid;

class GroupService
{
    public static function all(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query(
            'SELECT g.*,
                    COUNT(DISTINCT gm.user_id)  AS member_count,
                    COUNT(DISTINCT gc.course_id) AS course_count,
                    CONCAT(u.first_name," ",u.last_name) AS manager_name
             FROM user_groups g
             LEFT JOIN user_group_members gm ON gm.group_id=g.id
             LEFT JOIN user_group_courses gc ON gc.group_id=g.id
             LEFT JOIN users u ON u.id=g.manager_id
             GROUP BY g.id ORDER BY g.name'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM user_groups WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUuid(string $uuid): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM user_groups WHERE uuid=? LIMIT 1');
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO user_groups (uuid,name,description,manager_id,created_by) VALUES (?,?,?,?,?)'
        )->execute([
            Uuid::v4(), $data['name'], $data['description'] ?? null,
            $data['manager_id'] ? (int)$data['manager_id'] : null,
            $data['created_by'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'UPDATE user_groups SET name=?, description=?, manager_id=? WHERE id=?'
        )->execute([
            $data['name'], $data['description'] ?? null,
            $data['manager_id'] ? (int)$data['manager_id'] : null,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->prepare('DELETE FROM user_groups WHERE id=?')->execute([$id]);
    }

    public static function members(int $groupId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.uuid, u.first_name, u.last_name, u.email, gm.joined_at
             FROM user_group_members gm JOIN users u ON u.id=gm.user_id
             WHERE gm.group_id=? ORDER BY u.first_name'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public static function courses(int $groupId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT c.id, c.uuid, c.title, gc.assigned_at
             FROM user_group_courses gc JOIN courses c ON c.id=gc.course_id
             WHERE gc.group_id=? ORDER BY c.title'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public static function addMember(int $groupId, int $userId): void
    {
        Database::getInstance()->prepare(
            'INSERT IGNORE INTO user_group_members (group_id, user_id) VALUES (?,?)'
        )->execute([$groupId, $userId]);
    }

    public static function removeMember(int $groupId, int $userId): void
    {
        Database::getInstance()->prepare(
            'DELETE FROM user_group_members WHERE group_id=? AND user_id=?'
        )->execute([$groupId, $userId]);
    }

    public static function assignCourse(int $groupId, int $courseId): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT IGNORE INTO user_group_courses (group_id, course_id) VALUES (?,?)'
        )->execute([$groupId, $courseId]);

        // Bulk-enroll all current members
        $members = self::members($groupId);
        foreach ($members as $m) {
            // Direct enroll without requiring EnrollmentService instance
            $pdo2 = \App\Core\Database::getInstance();
            $exists = $pdo2->prepare('SELECT id FROM enrollments WHERE course_id=? AND user_id=? LIMIT 1');
            $exists->execute([$courseId, (int)$m['id']]);
            if (!$exists->fetch()) {
                $pdo2->prepare('INSERT INTO enrollments (course_id, user_id, enrolled_by, status) VALUES (?,?,?,\'active\')')
                    ->execute([$courseId, (int)$m['id'], (int)$m['id']]);
            }
        }
    }

    public static function removeCourse(int $groupId, int $courseId): void
    {
        Database::getInstance()->prepare(
            'DELETE FROM user_group_courses WHERE group_id=? AND course_id=?'
        )->execute([$groupId, $courseId]);
    }

    public static function groupsForUser(int $userId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT g.* FROM user_group_members gm
             JOIN user_groups g ON g.id=gm.group_id
             WHERE gm.user_id=?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function progressReport(int $groupId): array
    {
        $pdo     = Database::getInstance();
        $members = self::members($groupId);
        $courses = self::courses($groupId);
        $report  = [];

        foreach ($members as $m) {
            $row = ['user' => $m, 'courses' => []];
            foreach ($courses as $c) {
                $e = $pdo->prepare(
                    'SELECT status, progress_pct FROM enrollments
                     WHERE user_id=? AND course_id=? LIMIT 1'
                );
                $e->execute([$m['id'], $c['id']]);
                $enroll = $e->fetch() ?: ['status'=>'not_enrolled','progress_pct'=>0];
                $row['courses'][$c['id']] = $enroll;
            }
            $report[] = $row;
        }
        return ['members'=>$members, 'courses'=>$courses, 'progress'=>$report];
    }
}

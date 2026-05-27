<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

class CollaborationService
{
    // ── Lesson Notes ─────────────────────────────────────────────────────────

    public static function getNotes(int $userId, int $lessonId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT * FROM lesson_notes WHERE user_id=? AND lesson_id=? ORDER BY created_at ASC'
        );
        $stmt->execute([$userId, $lessonId]);
        return $stmt->fetchAll();
    }

    public static function saveNote(int $userId, int $lessonId, int $courseId, string $note, int $timestampSec = 0): int
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO lesson_notes (user_id, lesson_id, course_id, note, timestamp_sec)
             VALUES (?,?,?,?,?)'
        )->execute([$userId, $lessonId, $courseId, $note, $timestampSec]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateNote(int $id, int $userId, string $note): void
    {
        Database::getInstance()->prepare(
            'UPDATE lesson_notes SET note=? WHERE id=? AND user_id=?'
        )->execute([$note, $id, $userId]);
    }

    public static function deleteNote(int $id, int $userId): void
    {
        Database::getInstance()->prepare(
            'DELETE FROM lesson_notes WHERE id=? AND user_id=?'
        )->execute([$id, $userId]);
    }

    public static function allNotesForCourse(int $userId, int $courseId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT ln.*, l.title AS lesson_title
             FROM lesson_notes ln JOIN lessons l ON l.id=ln.lesson_id
             WHERE ln.user_id=? AND ln.course_id=?
             ORDER BY l.sort_order, ln.created_at'
        );
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetchAll();
    }

    // ── Lesson Comments ──────────────────────────────────────────────────────

    public static function getComments(int $lessonId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT lc.*, CONCAT(u.first_name," ",u.last_name) AS author_name,
                    u.avatar AS author_avatar, r.name AS author_role
             FROM lesson_comments lc
             JOIN users u ON u.id=lc.user_id
             JOIN roles r ON r.id=u.role_id
             WHERE lc.lesson_id=? AND lc.parent_id IS NULL AND lc.is_approved=1
             ORDER BY lc.is_pinned DESC, lc.created_at ASC'
        );
        $stmt->execute([$lessonId]);
        $comments = $stmt->fetchAll();

        // Load replies
        foreach ($comments as &$comment) {
            $replies = $pdo->prepare(
                'SELECT lc.*, CONCAT(u.first_name," ",u.last_name) AS author_name,
                        u.avatar AS author_avatar, r.name AS author_role
                 FROM lesson_comments lc
                 JOIN users u ON u.id=lc.user_id
                 JOIN roles r ON r.id=u.role_id
                 WHERE lc.parent_id=? AND lc.is_approved=1
                 ORDER BY lc.created_at ASC'
            );
            $replies->execute([$comment['id']]);
            $comment['replies'] = $replies->fetchAll();
        }
        return $comments;
    }

    public static function addComment(int $lessonId, int $userId, string $body, ?int $parentId = null): int
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO lesson_comments (lesson_id, user_id, body, parent_id) VALUES (?,?,?,?)'
        )->execute([$lessonId, $userId, $body, $parentId]);
        return (int)$pdo->lastInsertId();
    }

    public static function deleteComment(int $id, int $userId, string $role): void
    {
        $pdo = Database::getInstance();
        if (in_array($role, ['admin','super_admin'])) {
            $pdo->prepare('DELETE FROM lesson_comments WHERE id=?')->execute([$id]);
        } else {
            $pdo->prepare('DELETE FROM lesson_comments WHERE id=? AND user_id=?')->execute([$id, $userId]);
        }
    }

    public static function pinComment(int $id, bool $pin): void
    {
        Database::getInstance()->prepare(
            'UPDATE lesson_comments SET is_pinned=? WHERE id=?'
        )->execute([$pin ? 1 : 0, $id]);
    }

    // ── "Ask a question" → creates forum thread linked to lesson ─────────────

    public static function askQuestion(int $lessonId, int $userId, string $title, string $body): int
    {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare('SELECT course_id FROM lessons WHERE id=? LIMIT 1');
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();
        if (!$lesson) return 0;

        // Check if lesson_id column exists (added by migration 0020)
        try {
            $pdo->prepare(
                'INSERT INTO forum_threads (course_id, user_id, title, body, lesson_id)
                 VALUES (?,?,?,?,?)'
            )->execute([$lesson['course_id'], $userId, $title, $body, $lessonId]);
        } catch (\Throwable) {
            // Fallback: no lesson_id column yet (migration not run)
            $pdo->prepare(
                'INSERT INTO forum_threads (course_id, user_id, title, body)
                 VALUES (?,?,?,?)'
            )->execute([$lesson['course_id'], $userId, $title, $body]);
        }
        return (int)$pdo->lastInsertId();
    }

    // ── Notes export (all notes for a course as JSON) ────────────────────────

    public static function exportNotes(int $userId, int $courseId): string
    {
        $notes = self::allNotesForCourse($userId, $courseId);
        return json_encode([
            'exported_at' => date('c'),
            'notes'       => array_map(fn($n) => [
                'lesson'    => $n['lesson_title'],
                'note'      => $n['note'],
                'timestamp' => $n['timestamp_sec'],
                'created'   => $n['created_at'],
            ], $notes),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

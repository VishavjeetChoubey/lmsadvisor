<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Forum extends Model
{
    protected string $table = 'forum_threads';

    // ── Threads ───────────────────────────────────────────────────────────────

    public function threads(int $courseId, string $search = '', int $page = 1, int $perPage = 20): array
    {
        $where  = ['t.course_id = ?'];
        $params = [$courseId];

        if ($search !== '') {
            $where[]  = '(t.title LIKE ? OR t.body LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $total = (int)($this->queryOne(
            "SELECT COUNT(*) AS cnt FROM forum_threads t WHERE $whereStr",
            $params
        )['cnt'] ?? 0);

        $rows = $this->query(
            "SELECT t.*,
                    u.first_name, u.last_name, u.uuid AS user_uuid,
                    r.name AS role_name
             FROM forum_threads t
             JOIN users u ON u.id = t.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE $whereStr
             ORDER BY t.is_pinned DESC, t.updated_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return compact('rows', 'total', 'page', 'perPage');
    }

    public function allThreadsAdmin(string $search = '', int $courseId = 0, int $page = 1, int $perPage = 25): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(t.title LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($courseId > 0) {
            $where[]  = 't.course_id = ?';
            $params[] = $courseId;
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $total = (int)($this->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM forum_threads t
             JOIN users u ON u.id = t.user_id
             WHERE $whereStr",
            $params
        )['cnt'] ?? 0);

        $rows = $this->query(
            "SELECT t.*, c.title AS course_title, c.uuid AS course_uuid,
                    u.first_name, u.last_name, u.email
             FROM forum_threads t
             JOIN courses c ON c.id = t.course_id
             JOIN users u   ON u.id = t.user_id
             WHERE $whereStr
             ORDER BY t.is_pinned DESC, t.updated_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return compact('rows', 'total', 'page', 'perPage');
    }

    public function findThread(int $id): ?array
    {
        return $this->queryOne(
            'SELECT t.*, c.title AS course_title, c.uuid AS course_uuid,
                    c.forum_enrolled_only, c.forum_enabled,
                    u.first_name, u.last_name, u.uuid AS user_uuid, r.name AS role_name
             FROM forum_threads t
             JOIN courses c ON c.id = t.course_id
             JOIN users u   ON u.id = t.user_id
             JOIN roles r   ON r.id = u.role_id
             WHERE t.id = ? LIMIT 1',
            [$id]
        );
    }

    public function createThread(array $d): int
    {
        return $this->insert(
            'INSERT INTO forum_threads (course_id, user_id, title, body) VALUES (?,?,?,?)',
            [(int)$d['course_id'], (int)$d['user_id'], $d['title'], $d['body']]
        );
    }

    public function updateThread(int $id, array $d): void
    {
        $this->execute(
            'UPDATE forum_threads SET title=?, body=?, updated_at=NOW() WHERE id=?',
            [$d['title'], $d['body'], $id]
        );
    }

    public function deleteThread(int $id): void
    {
        $this->execute('DELETE FROM forum_threads WHERE id=?', [$id]);
    }

    public function pinThread(int $id, bool $pin): void
    {
        $this->execute('UPDATE forum_threads SET is_pinned=? WHERE id=?', [(int)$pin, $id]);
    }

    public function lockThread(int $id, bool $lock): void
    {
        $this->execute('UPDATE forum_threads SET is_locked=? WHERE id=?', [(int)$lock, $id]);
    }

    public function incrementReplyCount(int $threadId): void
    {
        $this->execute('UPDATE forum_threads SET reply_count = reply_count + 1, updated_at=NOW() WHERE id=?', [$threadId]);
    }

    public function decrementReplyCount(int $threadId): void
    {
        $this->execute('UPDATE forum_threads SET reply_count = GREATEST(reply_count - 1, 0) WHERE id=?', [$threadId]);
    }

    // ── Replies ───────────────────────────────────────────────────────────────

    public function replies(int $threadId): array
    {
        return $this->query(
            'SELECT r.*, u.first_name, u.last_name, u.uuid AS user_uuid,
                    ro.name AS role_name
             FROM forum_replies r
             JOIN users u  ON u.id = r.user_id
             JOIN roles ro ON ro.id = u.role_id
             WHERE r.thread_id = ?
             ORDER BY r.created_at ASC',
            [$threadId]
        );
    }

    public function findReply(int $id): ?array
    {
        return $this->queryOne(
            'SELECT r.*, u.first_name, u.last_name FROM forum_replies r
             JOIN users u ON u.id = r.user_id WHERE r.id = ? LIMIT 1',
            [$id]
        );
    }

    public function createReply(array $d): int
    {
        $id = $this->insert(
            'INSERT INTO forum_replies (thread_id, user_id, body) VALUES (?,?,?)',
            [(int)$d['thread_id'], (int)$d['user_id'], $d['body']]
        );
        $this->incrementReplyCount((int)$d['thread_id']);
        return $id;
    }

    public function deleteReply(int $id, int $threadId): void
    {
        $this->execute('DELETE FROM forum_replies WHERE id=?', [$id]);
        $this->decrementReplyCount($threadId);
    }

    public function markSolution(int $replyId, int $threadId, bool $isSolution): void
    {
        // Unmark all other solutions in the thread first
        if ($isSolution) {
            $this->execute(
                'UPDATE forum_replies SET is_solution=0 WHERE thread_id=?',
                [$threadId]
            );
        }
        $this->execute(
            'UPDATE forum_replies SET is_solution=? WHERE id=?',
            [(int)$isSolution, $replyId]
        );
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function stats(): array
    {
        return [
            'total_threads' => (int)($this->queryOne('SELECT COUNT(*) AS cnt FROM forum_threads')['cnt'] ?? 0),
            'total_replies' => (int)($this->queryOne('SELECT COUNT(*) AS cnt FROM forum_replies')['cnt'] ?? 0),
            'pinned'        => (int)($this->queryOne('SELECT COUNT(*) AS cnt FROM forum_threads WHERE is_pinned=1')['cnt'] ?? 0),
            'locked'        => (int)($this->queryOne('SELECT COUNT(*) AS cnt FROM forum_threads WHERE is_locked=1')['cnt'] ?? 0),
        ];
    }

    public function courseStats(int $courseId): array
    {
        return [
            'threads' => (int)($this->queryOne('SELECT COUNT(*) AS cnt FROM forum_threads WHERE course_id=?', [$courseId])['cnt'] ?? 0),
            'replies' => (int)($this->queryOne(
                'SELECT COUNT(*) AS cnt FROM forum_replies r
                 JOIN forum_threads t ON t.id = r.thread_id
                 WHERE t.course_id=?', [$courseId])['cnt'] ?? 0),
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Review extends Model
{
    protected string $table = 'course_reviews';

    // ── Admin list ────────────────────────────────────────────────────────────

    public function paginate(
        int    $page     = 1,
        int    $perPage  = 25,
        string $search   = '',
        string $status   = '',   // 'pending'|'approved'
        int    $courseId = 0,
        int    $rating   = 0
    ): array {
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.title LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status === 'pending') {
            $where[] = 'r.is_approved = 0';
        } elseif ($status === 'approved') {
            $where[] = 'r.is_approved = 1';
        }
        if ($courseId > 0) {
            $where[]  = 'r.course_id = ?';
            $params[] = $courseId;
        }
        if ($rating > 0) {
            $where[]  = 'r.rating = ?';
            $params[] = $rating;
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $total = (int)($this->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM course_reviews r
             JOIN users u ON u.id = r.user_id
             JOIN courses c ON c.id = r.course_id
             WHERE $whereStr",
            $params
        )['cnt'] ?? 0);

        $rows = $this->query(
            "SELECT r.*,
                    u.first_name, u.last_name, u.email, u.uuid AS user_uuid,
                    c.title AS course_title, c.uuid AS course_uuid
             FROM course_reviews r
             JOIN users u ON u.id = r.user_id
             JOIN courses c ON c.id = r.course_id
             WHERE $whereStr
             ORDER BY r.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return compact('rows', 'total', 'page', 'perPage');
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function stats(): array
    {
        $row = $this->queryOne(
            'SELECT
               COUNT(*) AS total,
               COUNT(CASE WHEN is_approved = 0 THEN 1 END) AS pending,
               COUNT(CASE WHEN is_approved = 1 THEN 1 END) AS approved,
               ROUND(AVG(rating), 1) AS avg_rating
             FROM course_reviews'
        );
        return $row ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'avg_rating' => 0];
    }

    public function ratingDistribution(): array
    {
        $rows = $this->query(
            'SELECT rating, COUNT(*) AS cnt FROM course_reviews GROUP BY rating ORDER BY rating DESC'
        );
        $dist = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
        foreach ($rows as $r) {
            $dist[(int)$r['rating']] = (int)$r['cnt'];
        }
        return $dist;
    }

    public function courseAvgRating(int $courseId): array
    {
        return $this->queryOne(
            'SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total_reviews
             FROM course_reviews WHERE course_id = ? AND is_approved = 1',
            [$courseId]
        ) ?? ['avg_rating' => 0, 'total_reviews' => 0];
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        return $this->queryOne(
            'SELECT r.*, u.first_name, u.last_name, c.title AS course_title
             FROM course_reviews r
             JOIN users u ON u.id = r.user_id
             JOIN courses c ON c.id = r.course_id
             WHERE r.id = ? LIMIT 1',
            [$id]
        );
    }

    public function approve(int $id): void
    {
        $this->execute('UPDATE course_reviews SET is_approved = 1 WHERE id = ?', [$id]);
    }

    public function unapprove(int $id): void
    {
        $this->execute('UPDATE course_reviews SET is_approved = 0 WHERE id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM course_reviews WHERE id = ?', [$id]);
    }

    public function bulkApprove(array $ids): void
    {
        if (empty($ids)) return;
        $in = implode(',', array_map('intval', $ids));
        $this->execute("UPDATE course_reviews SET is_approved = 1 WHERE id IN ($in)");
    }

    public function bulkDelete(array $ids): void
    {
        if (empty($ids)) return;
        $in = implode(',', array_map('intval', $ids));
        $this->execute("DELETE FROM course_reviews WHERE id IN ($in)");
    }

    // ── Student submit ────────────────────────────────────────────────────────

    public function create(int $courseId, int $userId, int $rating, string $review): int
    {
        $autoApprove = (bool)(int)(\App\Models\Setting::get('reviews_auto_approve', '0'));
        return $this->insert(
            'INSERT INTO course_reviews (course_id, user_id, rating, review, is_approved)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating=VALUES(rating), review=VALUES(review),
               is_approved=VALUES(is_approved), created_at=NOW()',
            [$courseId, $userId, $rating, $review, (int)$autoApprove]
        );
    }

    public function findByUserCourse(int $userId, int $courseId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM course_reviews WHERE user_id = ? AND course_id = ? LIMIT 1',
            [$userId, $courseId]
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class NotificationService
{
    /**
     * Send an in-app notification to a user.
     */
    public static function send(int $userId, string $type, array $payload): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, body, data)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $userId,
            $type,
            $payload['title'] ?? 'Notification',
            $payload['body']  ?? '',
            isset($payload['data']) ? json_encode($payload['data']) : null,
        ]);
    }

    /**
     * Send to multiple users at once.
     */
    public static function sendBulk(array $userIds, string $type, array $payload): void
    {
        foreach (array_unique($userIds) as $uid) {
            self::send((int)$uid, $type, $payload);
        }
    }

    /**
     * Get unread notifications for a user (latest 20).
     */
    public static function unread(int $userId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT * FROM notifications WHERE user_id=? AND is_read=0
             ORDER BY created_at DESC LIMIT 20'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all notifications for a user (paginated).
     */
    public static function all(int $userId, int $page = 1, int $perPage = 30): array
    {
        $pdo    = Database::getInstance();
        $offset = ($page - 1) * $perPage;
        $stmt   = $pdo->prepare(
            'SELECT * FROM notifications WHERE user_id=?
             ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $perPage, $offset]);
        $rows = $stmt->fetchAll();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=?');
        $countStmt->execute([$userId]);
        $total = (int)$countStmt->fetchColumn();

        return compact('rows', 'total', 'page', 'perPage');
    }

    /**
     * Count unread.
     */
    public static function unreadCount(int $userId): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mark one or all notifications as read.
     */
    public static function markRead(int $userId, ?int $notifId = null): void
    {
        $pdo = Database::getInstance();
        if ($notifId) {
            $pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')
                ->execute([$notifId, $userId]);
        } else {
            $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')
                ->execute([$userId]);
        }
    }

    /**
     * Auto-send notifications for common LMS events.
     */
    public static function onEnrollment(int $userId, string $courseTitle): void
    {
        self::send($userId, 'enrollment', [
            'title' => 'Enrolled: ' . $courseTitle,
            'body'  => "You've been enrolled in \"{$courseTitle}\". Start learning now!",
        ]);
    }

    public static function onCompletion(int $userId, string $courseTitle): void
    {
        self::send($userId, 'completion', [
            'title' => '🎉 Course Completed: ' . $courseTitle,
            'body'  => "Congratulations! You've completed \"{$courseTitle}\". Your certificate is ready.",
        ]);
    }

    public static function onQuizResult(int $userId, string $quizTitle, float $score, bool $passed): void
    {
        self::send($userId, 'quiz_result', [
            'title' => ($passed ? '✓ Passed' : '✗ Failed') . ': ' . $quizTitle,
            'body'  => "You scored {$score}% on \"{$quizTitle}\"." . ($passed ? ' Great job!' : ' Review and try again.'),
        ]);
    }
}

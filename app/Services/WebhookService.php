<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Helpers\Uuid;
use App\Models\Setting;

/**
 * WebhookService — Phase 30: Integrations Hub
 * Outgoing webhooks with HMAC-SHA256 signing, retry logic, and delivery logs.
 */
class WebhookService
{
    /** Fire an event to all active matching webhooks. Non-blocking (best effort). */
    public static function fire(string $event, array $payload): void
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM webhooks WHERE is_active=1 AND fail_count<5");
        foreach ($stmt->fetchAll() as $hook) {
            $events = json_decode($hook['events'] ?? '[]', true);
            if (!in_array($event, (array)$events, true) && !in_array('*', (array)$events, true)) {
                continue;
            }
            self::deliver($hook, $event, $payload);
        }
    }

    private static function deliver(array $hook, string $event, array $payload): void
    {
        $pdo     = Database::getInstance();
        $body    = json_encode([
            'event'     => $event,
            'timestamp' => date('c'),
            'lms_url'   => rtrim(APP_URL, '/'),
            'data'      => $payload,
        ]);
        $sig  = 'sha256=' . hash_hmac('sha256', $body, $hook['secret']);
        $t    = microtime(true);

        $ch = curl_init($hook['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-LMS-Event: '     . $event,
                'X-LMS-Signature: ' . $sig,
                'X-LMS-Delivery: '  . Uuid::v4(),
            ],
        ]);
        $resp    = curl_exec($ch);
        $code    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ms      = (int)round((microtime(true) - $t) * 1000);
        $success = $code >= 200 && $code < 300;

        // Log delivery
        $pdo->prepare(
            'INSERT INTO webhook_logs
             (webhook_id, event_type, payload, response_code, response_body, duration_ms, success)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([
            $hook['id'], $event, $body, $code,
            substr((string)$resp, 0, 1000), $ms, $success ? 1 : 0,
        ]);

        // Update hook stats
        if ($success) {
            $pdo->prepare(
                'UPDATE webhooks SET last_fired=NOW(), fail_count=0 WHERE id=?'
            )->execute([$hook['id']]);
        } else {
            $pdo->prepare(
                'UPDATE webhooks SET fail_count=fail_count+1 WHERE id=?'
            )->execute([$hook['id']]);
        }
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public static function all(): array
    {
        return Database::getInstance()->query('SELECT * FROM webhooks ORDER BY created_at DESC')->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO webhooks (uuid,name,url,secret,events,is_active)
             VALUES (?,?,?,?,?,?)'
        )->execute([
            Uuid::v4(),
            $data['name'],
            $data['url'],
            $data['secret'] ?: bin2hex(random_bytes(20)),
            json_encode(array_values(array_filter((array)($data['events'] ?? [])))),
            $data['is_active'] ? 1 : 0,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::getInstance()->prepare(
            'UPDATE webhooks SET name=?,url=?,events=?,is_active=? WHERE id=?'
        )->execute([
            $data['name'],
            $data['url'],
            json_encode(array_values(array_filter((array)($data['events'] ?? [])))),
            $data['is_active'] ? 1 : 0,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->prepare('DELETE FROM webhooks WHERE id=?')->execute([$id]);
    }

    public static function logs(int $webhookId, int $limit = 30): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT * FROM webhook_logs WHERE webhook_id=? ORDER BY fired_at DESC LIMIT ?'
        );
        $stmt->execute([$webhookId, $limit]);
        return $stmt->fetchAll();
    }

    /** Rotate the secret for a webhook. */
    public static function rotateSecret(int $id): string
    {
        $secret = bin2hex(random_bytes(20));
        Database::getInstance()->prepare(
            'UPDATE webhooks SET secret=? WHERE id=?'
        )->execute([$secret, $id]);
        return $secret;
    }

    /** Test-fire a webhook with a sample payload. */
    public static function test(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM webhooks WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $hook = $stmt->fetch();
        if (!$hook) return false;
        self::deliver($hook, 'test', ['message' => 'Test delivery from LMSAdvisor']);
        return true;
    }

    // ── Slack Notification ────────────────────────────────────────────────────

    public static function slackNotify(string $event, array $payload): void
    {
        $url = Setting::get('slack_webhook_url', '');
        if (!$url || !(bool)(int)Setting::get('slack_notifications', '0')) return;

        $icons = [
            'enroll'   => '📚', 'complete' => '🎉', 'quiz_pass' => '✅',
            'quiz_fail'=> '❌', 'grade'    => '📝', 'badge'     => '🏅',
        ];
        $icon = $icons[$event] ?? 'ℹ️';

        $text = match($event) {
            'enroll'    => "{$icon} *{$payload['student_name']}* enrolled in *{$payload['course_title']}*",
            'complete'  => "{$icon} *{$payload['student_name']}* completed *{$payload['course_title']}*!",
            'quiz_pass' => "{$icon} *{$payload['student_name']}* passed the quiz *{$payload['quiz_title']}* ({$payload['score']}%)",
            'badge'     => "{$icon} *{$payload['student_name']}* earned the *{$payload['badge_name']}* badge!",
            default     => "{$icon} LMSAdvisor event: {$event}",
        };

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['text' => $text]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

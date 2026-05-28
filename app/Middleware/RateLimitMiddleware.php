<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Database;

/**
 * RateLimitMiddleware — per-IP and per-token rate limiting for API endpoints.
 * Uses a sliding window stored in the database.
 */
class RateLimitMiddleware
{
    /**
     * Check rate limit. Sends 429 and exits if exceeded.
     *
     * @param int $maxRequests Max requests in the window
     * @param int $windowSec   Window size in seconds
     * @param string $key      Identifier (IP, token, user_id)
     */
    public static function check(int $maxRequests = 60, int $windowSec = 60, string $key = ''): void
    {
        if (!$key) {
            $key = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        try {
            $pdo   = Database::getInstance();
            $now   = time();
            $since = date('Y-m-d H:i:s', $now - $windowSec);
            $cacheKey = 'rl_' . md5($key);

            // Ensure rate_limit_log table exists
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS rate_limit_log (
                    id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    cache_key  VARCHAR(64)  NOT NULL,
                    hit_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_rl_key (cache_key),
                    KEY idx_rl_at  (hit_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            // Count hits in window
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limit_log WHERE cache_key=? AND hit_at >= ?');
            $stmt->execute([$cacheKey, $since]);
            $count = (int)$stmt->fetchColumn();

            if ($count >= $maxRequests) {
                http_response_code(429);
                header('Content-Type: application/json');
                header('Retry-After: ' . $windowSec);
                header('X-RateLimit-Limit: ' . $maxRequests);
                header('X-RateLimit-Remaining: 0');
                echo json_encode([
                    'error'   => 'Too Many Requests',
                    'message' => "Rate limit exceeded. Max {$maxRequests} requests per {$windowSec} seconds.",
                    'retry_after' => $windowSec,
                ]);
                exit;
            }

            // Log this hit
            $pdo->prepare('INSERT INTO rate_limit_log (cache_key) VALUES (?)')->execute([$cacheKey]);

            // Cleanup old entries periodically (1% chance to keep overhead low)
            if (rand(1, 100) === 1) {
                $pdo->prepare('DELETE FROM rate_limit_log WHERE hit_at < ?')
                    ->execute([date('Y-m-d H:i:s', $now - 3600)]);
            }

            // Set headers
            header('X-RateLimit-Limit: ' . $maxRequests);
            header('X-RateLimit-Remaining: ' . max(0, $maxRequests - $count - 1));
            header('X-RateLimit-Reset: ' . ($now + $windowSec));

        } catch (\Throwable $e) {
            // Never block on rate limiter failure
            error_log('[RateLimit] ' . $e->getMessage());
        }
    }

    /** Strict rate limit for auth endpoints (prevent brute force) */
    public static function auth(): void
    {
        self::check(10, 300, 'auth_' . ($_SERVER['REMOTE_ADDR'] ?? '0')); // 10 per 5 min
    }

    /** Standard API rate limit */
    public static function api(string $token = ''): void
    {
        self::check(120, 60, 'api_' . ($token ?: ($_SERVER['REMOTE_ADDR'] ?? '0'))); // 120 per min
    }
}

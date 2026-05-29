<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Database;

/**
 * RateLimitMiddleware — rate limiting for external API and auth endpoints.
 *
 * IMPORTANT: Only apply to genuinely external-facing endpoints.
 * Do NOT add to internal AJAX, admin pages, or student portal requests.
 *
 * Applied only to:
 *   - POST /api/v1/auth/token        (brute force: 10/5min per IP)
 *   - External WooCommerce API calls  (600/min per token — generous)
 *
 * NOT applied to:
 *   - AI Tutor AJAX (internal, session-authenticated)
 *   - Admin AJAX (internal, session-authenticated)
 *   - Student portal pages (normal page loads)
 */
class RateLimitMiddleware
{
    private static bool $tableChecked = false;

    /** Check rate limit — sends 429 JSON and exits if exceeded. */
    public static function check(int $maxRequests, int $windowSec, string $key): void
    {
        // Skip entirely in development or for localhost
        if (defined('APP_ENV') && APP_ENV === 'development') return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) return;

        try {
            $pdo      = Database::getInstance();
            $cacheKey = 'rl_' . md5($key);
            $since    = date('Y-m-d H:i:s', time() - $windowSec);

            // Ensure table exists once per process
            if (!self::$tableChecked) {
                $pdo->exec(
                    'CREATE TABLE IF NOT EXISTS rate_limit_log (
                        id        BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                        cache_key VARCHAR(64)  NOT NULL,
                        hit_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        KEY idx_rl_key_at (cache_key, hit_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
                );
                self::$tableChecked = true;
            }

            // Count hits in window
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM rate_limit_log WHERE cache_key=? AND hit_at >= ?'
            );
            $stmt->execute([$cacheKey, $since]);
            $count = (int)$stmt->fetchColumn();

            if ($count >= $maxRequests) {
                http_response_code(429);
                header('Content-Type: application/json');
                header('Retry-After: ' . $windowSec);
                header('X-RateLimit-Limit: ' . $maxRequests);
                header('X-RateLimit-Remaining: 0');
                echo json_encode([
                    'error'       => 'Too Many Requests',
                    'message'     => "Rate limit exceeded. Max {$maxRequests} requests per {$windowSec}s.",
                    'retry_after' => $windowSec,
                ]);
                exit;
            }

            // Record this hit
            $pdo->prepare('INSERT INTO rate_limit_log (cache_key) VALUES (?)')
                ->execute([$cacheKey]);

            // Probabilistic cleanup (1% of requests)
            if (rand(1, 100) === 1) {
                $pdo->prepare('DELETE FROM rate_limit_log WHERE hit_at < ?')
                    ->execute([date('Y-m-d H:i:s', time() - 3600)]);
            }

            header('X-RateLimit-Limit: ' . $maxRequests);
            header('X-RateLimit-Remaining: ' . max(0, $maxRequests - $count - 1));

        } catch (\Throwable $e) {
            // Never block traffic on rate limiter failure — log only
            error_log('[RateLimit] ' . $e->getMessage());
        }
    }

    /**
     * Brute-force protection for login/token endpoints.
     * 20 attempts per 5 minutes per IP — only for POST /api/v1/auth/token.
     */
    public static function auth(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
        self::check(20, 300, 'auth_' . $ip);
    }

    /**
     * External API rate limit — for WooCommerce plugin and third-party integrations.
     * 600 requests per minute per token (very generous for normal use).
     * Only call this for truly external API consumers, not internal AJAX.
     */
    public static function external(string $token = ''): void
    {
        $key = $token ?: ($_SERVER['REMOTE_ADDR'] ?? '0');
        self::check(600, 60, 'ext_' . md5($key));
    }
}

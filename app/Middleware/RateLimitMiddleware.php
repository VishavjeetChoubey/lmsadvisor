<?php
declare(strict_types=1);

namespace App\Middleware;

/**
 * Simple file-based rate limiter.
 * Uses STORE_PATH/cache/ratelimit/ for counters.
 */
class RateLimitMiddleware
{
    /**
     * Check rate limit. Throws 429 if exceeded.
     *
     * @param string $key     Identifier (e.g. 'login:' . $ip)
     * @param int    $max     Maximum hits allowed
     * @param int    $window  Time window in seconds
     */
    public static function check(string $key, int $max = 10, int $window = 60): void
    {
        if (!defined('STORE_PATH')) return; // safety

        $dir  = STORE_PATH . '/cache/ratelimit/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $file = $dir . md5($key) . '.json';
        $now  = time();
        $data = ['count' => 0, 'reset_at' => $now + $window];

        if (file_exists($file)) {
            $raw  = file_get_contents($file);
            $data = json_decode($raw, true) ?: $data;
        }

        // Reset window if expired
        if ($now > $data['reset_at']) {
            $data = ['count' => 0, 'reset_at' => $now + $window];
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);

        if ($data['count'] > $max) {
            $retryAfter = $data['reset_at'] - $now;
            header('Retry-After: ' . $retryAfter);
            header('X-RateLimit-Limit: ' . $max);
            header('X-RateLimit-Remaining: 0');
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests. Please wait ' . $retryAfter . ' seconds.']);
            exit;
        }

        header('X-RateLimit-Limit: '    . $max);
        header('X-RateLimit-Remaining: '. max(0, $max - $data['count']));
        header('X-RateLimit-Reset: '    . $data['reset_at']);
    }

    /** Apply rate limit to API routes */
    public static function api(): void
    {
        $ip  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // API: 100 req/min per IP
        if (str_contains($uri, '/api/')) {
            self::check('api:' . $ip, 100, 60);
        }
        // Login: 10 attempts per 5 minutes per IP
        if (str_contains($uri, '/login') || str_contains($uri, '/api/v1/auth/token')) {
            self::check('login:' . $ip, 10, 300);
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

/**
 * AnalyticsService — SOC2-compliant page-view and event tracking.
 *
 * SOC2 compliance:
 * - IP addresses are NEVER stored raw — stored as SHA-256 hash only
 * - No PII (name/email/user_id) stored in analytics tables
 * - User role stored at most (admin/student/guest)
 * - Geo-location is city/country only (no precise coordinates)
 * - Data retention enforced via purgeOld()
 * - Opt-out via analytics_enabled setting
 */
class AnalyticsService
{
    /** Record a page view. Call from App::run() after routing. */
    public static function track(string $path, string $title = ''): void
    {
        if (!self::enabled()) return;
        if (self::isBot()) return;

        // Skip admin-only paths that could reveal sensitive structure
        // (still track /admin/* but scrub sensitive IDs from path)
        $path = self::sanitizePath($path);

        try {
            $ip        = self::clientIp();
            $ua        = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
            $ipHash    = hash('sha256', $ip);
            $sessHash  = hash('sha256', $ip . $ua . date('Y-m-d'));

            $device    = self::detectDevice($ua);
            [$browser, $os] = self::detectBrowserOs($ua);
            [$country, $city, $cc] = self::geoLocate($ip);

            $isLoggedIn = isset($_SESSION['user_id']) ? 1 : 0;
            $role       = $_SESSION['user_role'] ?? 'guest';
            $referrer   = self::sanitizeReferrer($_SERVER['HTTP_REFERER'] ?? '');

            $pdo = Database::getInstance();
            $pdo->prepare(
                'INSERT INTO analytics_pageviews
                 (session_hash, ip_hash, path, page_title, referrer,
                  country_code, country_name, city, device_type, browser, os,
                  is_logged_in, user_role)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $sessHash, $ipHash, $path, substr($title, 0, 255), $referrer,
                $cc, $country, $city, $device, $browser, $os,
                $isLoggedIn, $role,
            ]);
        } catch (\Throwable $e) {
            // Never crash the app due to analytics failure
            error_log('[Analytics] track error: ' . $e->getMessage());
        }
    }

    /** Record a named event (enrollment, completion, quiz result, etc.) */
    public static function event(string $type, string $entityType = '', int $entityId = 0): void
    {
        if (!self::enabled()) return;
        try {
            $ip       = self::clientIp();
            $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
            $sessHash = hash('sha256', $ip . $ua . date('Y-m-d'));
            $pdo      = Database::getInstance();
            $pdo->prepare(
                'INSERT INTO analytics_events (session_hash, event_type, entity_type, entity_id)
                 VALUES (?,?,?,?)'
            )->execute([$sessHash, $type, $entityType ?: null, $entityId ?: null]);
        } catch (\Throwable) {}
    }

    // ── Reporting queries ─────────────────────────────────────────────────────

    public static function overview(int $days = 30): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));

        // Unique visitors (by session_hash), page views, avg session duration
        $row = $pdo->prepare(
            'SELECT
               COUNT(*)                    AS total_pageviews,
               COUNT(DISTINCT session_hash)AS unique_visitors,
               COUNT(DISTINCT ip_hash)     AS unique_ips,
               ROUND(AVG(duration_sec))    AS avg_duration
             FROM analytics_pageviews WHERE created_at >= ?'
        );
        $row->execute([$from]);
        $stats = $row->fetch();

        // Trend: daily visitors last N days
        $trend = $pdo->prepare(
            'SELECT DATE(created_at) AS d,
                    COUNT(DISTINCT session_hash) AS visitors,
                    COUNT(*) AS pageviews
             FROM analytics_pageviews WHERE created_at >= ?
             GROUP BY DATE(created_at) ORDER BY d ASC'
        );
        $trend->execute([$from]);
        $stats['trend'] = $trend->fetchAll();

        return $stats;
    }

    public static function topPages(int $days = 30, int $limit = 20): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            'SELECT path, page_title,
                    COUNT(*) AS pageviews,
                    COUNT(DISTINCT session_hash) AS unique_visitors
             FROM analytics_pageviews WHERE created_at >= ?
             GROUP BY path ORDER BY pageviews DESC LIMIT ?'
        );
        $stmt->execute([$from, $limit]);
        return $stmt->fetchAll();
    }

    public static function devices(int $days = 30): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            'SELECT device_type, COUNT(DISTINCT session_hash) AS visitors
             FROM analytics_pageviews WHERE created_at >= ?
             GROUP BY device_type ORDER BY visitors DESC'
        );
        $stmt->execute([$from]);
        return $stmt->fetchAll();
    }

    public static function browsers(int $days = 30): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            'SELECT browser, COUNT(DISTINCT session_hash) AS visitors
             FROM analytics_pageviews WHERE created_at >= ?
             GROUP BY browser ORDER BY visitors DESC LIMIT 10'
        );
        $stmt->execute([$from]);
        return $stmt->fetchAll();
    }

    public static function countries(int $days = 30, int $limit = 15): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            'SELECT country_name, country_code,
                    COUNT(DISTINCT session_hash) AS visitors,
                    COUNT(*) AS pageviews
             FROM analytics_pageviews
             WHERE created_at >= ? AND country_name IS NOT NULL AND country_name != ""
             GROUP BY country_name, country_code ORDER BY visitors DESC LIMIT ?'
        );
        $stmt->execute([$from, $limit]);
        return $stmt->fetchAll();
    }

    public static function hourlyHeatmap(int $days = 30): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            'SELECT DAYOFWEEK(created_at)-1 AS dow,
                    HOUR(created_at)         AS hr,
                    COUNT(DISTINCT session_hash) AS visitors
             FROM analytics_pageviews WHERE created_at >= ?
             GROUP BY dow, hr'
        );
        $stmt->execute([$from]);
        return $stmt->fetchAll();
    }

    public static function events(int $days = 30): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            'SELECT event_type, COUNT(*) AS total
             FROM analytics_events WHERE created_at >= ?
             GROUP BY event_type ORDER BY total DESC'
        );
        $stmt->execute([$from]);
        return $stmt->fetchAll();
    }

    public static function userRoles(int $days = 30): array
    {
        $pdo  = Database::getInstance();
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare(
            'SELECT user_role, COUNT(DISTINCT session_hash) AS visitors
             FROM analytics_pageviews WHERE created_at >= ?
             GROUP BY user_role ORDER BY visitors DESC'
        );
        $stmt->execute([$from]);
        return $stmt->fetchAll();
    }

    /** Delete old records beyond retention window (call from cron or on-demand) */
    public static function purgeOld(): int
    {
        $days = max(7, (int)Setting::get('analytics_retention_days', 90));
        $pdo  = Database::getInstance();
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));
        $pdo->prepare('DELETE FROM analytics_pageviews WHERE created_at < ?')->execute([$cutoff]);
        $pdo->prepare('DELETE FROM analytics_events WHERE created_at < ?')->execute([$cutoff]);
        $affected = $pdo->prepare('SELECT ROW_COUNT()')->execute() ? (int)$pdo->lastInsertId() : 0;
        return $affected;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function enabled(): bool
    {
        return (bool)(int)Setting::get('analytics_enabled', '1');
    }

    private static function isBot(): bool
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        $bots = ['bot','crawl','spider','slurp','mediapartners','apis-google',
                 'adsbot','googlebot','bingbot','yandex','baidu','duckduck',
                 'facebot','ia_archiver','msnbot','teoma','wget','curl','python',
                 'java','ruby','go-http','postman','insomnia','axios'];
        foreach ($bots as $b) {
            if (str_contains($ua, $b)) return true;
        }
        return false;
    }

    private static function sanitizePath(string $path): string
    {
        // Remove UUIDs from path to avoid cardinality explosion
        // e.g. /learn/courses/3cc70a73-a366-4f17-82d1-f48ddacb8b32/learn → /learn/courses/:uuid/learn
        $path = preg_replace(
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i',
            ':uuid', $path
        );
        // Remove pure numeric IDs from path
        $path = preg_replace('#/(\d{3,})(?=/|$)#', '/:id', $path);
        return substr($path, 0, 500);
    }

    private static function sanitizeReferrer(string $ref): string
    {
        if (!$ref) return '';
        // Store only domain + path, strip query strings (may contain tokens)
        $parts = parse_url($ref);
        $clean = ($parts['host'] ?? '') . ($parts['path'] ?? '');
        return substr($clean, 0, 500);
    }

    private static function clientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    private static function detectDevice(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'bot') || str_contains($ua, 'crawler')) return 'bot';
        if (preg_match('/ipad|tablet|kindle|playbook|silk|(android(?!.*mobile))/i', $ua)) return 'tablet';
        if (preg_match('/mobile|iphone|ipod|blackberry|windows phone|android.*mobile/i', $ua)) return 'mobile';
        return 'desktop';
    }

    private static function detectBrowserOs(string $ua): array
    {
        // Browser
        $browser = 'Other';
        $browsers = [
            'Edg'     => 'Edge',
            'OPR'     => 'Opera',
            'Chrome'  => 'Chrome',
            'Safari'  => 'Safari',
            'Firefox' => 'Firefox',
            'MSIE'    => 'IE',
            'Trident' => 'IE',
        ];
        foreach ($browsers as $key => $name) {
            if (str_contains($ua, $key)) { $browser = $name; break; }
        }

        // OS
        $os = 'Other';
        $systems = [
            'Windows NT 10' => 'Windows 10/11',
            'Windows NT 6'  => 'Windows 7/8',
            'Mac OS X'      => 'macOS',
            'iPhone'        => 'iOS',
            'iPad'          => 'iPadOS',
            'Android'       => 'Android',
            'Linux'         => 'Linux',
            'CrOS'          => 'ChromeOS',
        ];
        foreach ($systems as $key => $name) {
            if (str_contains($ua, $key)) { $os = $name; break; }
        }

        return [$browser, $os];
    }

    /**
     * Geo-locate via ip-api.com (free, no key needed, GDPR-friendly).
     * Returns [country, city, country_code].
     * Caches in file for 24h to avoid hammering the API.
     */
    private static function geoLocate(string $ip): array
    {
        // Local/private IPs → no geo data
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return ['Local', 'Local', 'LO'];
        }

        $cacheDir  = STORE_PATH . '/cache/geo/';
        $cacheFile = $cacheDir . hash('sha256', $ip) . '.json';

        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

        // Use cached result (24h)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) return [$cached['country'] ?? '', $cached['city'] ?? '', $cached['cc'] ?? ''];
        }

        // Call ip-api.com (free, 45 req/min limit, no PII sent — only IP)
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $raw = @file_get_contents(
            "http://ip-api.com/json/{$ip}?fields=country,city,countryCode",
            false, $ctx
        );

        if ($raw) {
            $data = json_decode($raw, true);
            if (!empty($data['country'])) {
                $result = [
                    'country' => $data['country'],
                    'city'    => $data['city'] ?? '',
                    'cc'      => $data['countryCode'] ?? '',
                ];
                @file_put_contents($cacheFile, json_encode($result));
                return [$result['country'], $result['city'], $result['cc']];
            }
        }

        return ['Unknown', '', ''];
    }
}

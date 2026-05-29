<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * ApiMarketplaceService — tracks usage, enforces rate limits, provides endpoint docs.
 */
class ApiMarketplaceService
{
    /** Increment call counter and check rate limit. Returns true if allowed. */
    public static function trackCall(string $token): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT id, rate_limit, calls_today FROM api_tokens WHERE token=? AND is_active=1 LIMIT 1'
        );
        $stmt->execute([$token]);
        $t = $stmt->fetch();
        if (!$t) return false;

        // Rate limit check
        if ($t['rate_limit'] && (int)$t['calls_today'] >= (int)$t['rate_limit']) {
            return false; // Rate limited
        }

        $pdo->prepare(
            'UPDATE api_tokens SET calls_today=calls_today+1, calls_total=calls_total+1,
             last_used=NOW() WHERE id=?'
        )->execute([$t['id']]);

        return true;
    }

    /** Reset daily counters — run via cron at midnight */
    public static function resetDailyCounters(): void
    {
        Database::getInstance()->exec('UPDATE api_tokens SET calls_today=0');
    }

    /** Get usage stats for a token */
    public static function tokenStats(int $tokenId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT calls_today, calls_total, rate_limit, last_used_at, app_name, app_url
             FROM api_tokens WHERE id=? LIMIT 1'
        );
        $stmt->execute([$tokenId]);
        return $stmt->fetch() ?: [];
    }

    /** Full API endpoint catalogue */
    public static function endpointDocs(): array
    {
        return [
            'Authentication' => [
                ['POST', '/api/v1/auth/token',      'Generate API token',     'email, password',        'Requires user credentials'],
                ['DELETE','/api/v1/auth/token',     'Revoke token',           '—',                      'Invalidates current token'],
                ['POST', '/api/v1/auth/sso-token',  'Generate SSO URL',       'email, redirect_path',   'For WooCommerce SSO'],
            ],
            'Courses' => [
                ['GET',  '/api/v1/courses',                    'List published courses',  '?status, ?per_page, ?page', 'Paginated'],
                ['GET',  '/api/v1/courses/:uuid',              'Get course detail',       '—',                         'Includes sections+lessons'],
                ['GET',  '/api/v1/courses/:uuid/progress',     'Student progress',        '—',                         'Auth required'],
            ],
            'Enrollments' => [
                ['GET',  '/api/v1/enrollments',      'List enrollments',        '?email',                'Admin: filter by email'],
                ['POST', '/api/v1/enrollments',      'Enroll student',          'email, course_uuid',    'Creates account if needed'],
            ],
            'Users' => [
                ['GET',  '/api/v1/users',            'List users (admin)',       '?per_page, ?page',      'Admin only'],
                ['GET',  '/api/v1/users/:uuid',      'Get user',                '—',                     '—'],
                ['POST', '/api/v1/users',            'Create student',          'email, first_name, last_name', 'Idempotent on email'],
                ['PUT',  '/api/v1/users/:uuid',      'Update user',             'first_name, last_name', '—'],
            ],
            'Recommendations' => [
                ['GET',  '/api/v1/recommendations',  'Get recommendations',     '—',                     'Auth required'],
            ],
            'Webhooks' => [
                ['GET',  '/api/v1/health',           'Health check',            '—',                     'Returns version + DB status'],
            ],
        ];
    }

    /** Get all tokens with their app info + usage */
    public static function allTokens(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            'SELECT t.*, u.first_name, u.last_name, u.email
             FROM api_tokens t
             JOIN users u ON u.id=t.user_id
             ORDER BY t.calls_total DESC'
        )->fetchAll();
    }
}

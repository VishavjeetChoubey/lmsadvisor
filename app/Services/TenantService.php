<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Helpers\Uuid;

/**
 * TenantService — manages white-label client accounts.
 *
 * Resolution order (called from App::run()):
 *   1. Check X-Tenant-Slug header (API calls)
 *   2. Check subdomain: {slug}.lmsadvisor.com
 *   3. Check custom_domain match
 *   4. Fall back to master tenant (null)
 */
class TenantService
{
    private static ?array $current = null;

    // ── Resolve current tenant from request ───────────────────────────────────

    public static function resolve(): ?array
    {
        if (self::$current !== null) return self::$current;

        $pdo = Database::getInstance();

        // 1. Header (API)
        $slug = $_SERVER['HTTP_X_TENANT_SLUG'] ?? '';
        if ($slug) {
            $stmt = $pdo->prepare('SELECT * FROM tenants WHERE slug=? AND status="active" LIMIT 1');
            $stmt->execute([$slug]);
            $tenant = $stmt->fetch() ?: null;
            if ($tenant) { self::$current = $tenant; return $tenant; }
        }

        // 2. Subdomain
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // e.g. acme.lmsadvisor.com → slug = acme
        if (preg_match('/^([a-z0-9-]+)\.lmsadvisor\.com$/i', $host, $m)) {
            $stmt = $pdo->prepare('SELECT * FROM tenants WHERE slug=? AND status="active" LIMIT 1');
            $stmt->execute([$m[1]]);
            $tenant = $stmt->fetch() ?: null;
            if ($tenant) { self::$current = $tenant; return $tenant; }
        }

        // 3. Custom domain
        if ($host) {
            $stmt = $pdo->prepare('SELECT * FROM tenants WHERE custom_domain=? AND status="active" LIMIT 1');
            $stmt->execute([$host]);
            $tenant = $stmt->fetch() ?: null;
            if ($tenant) { self::$current = $tenant; return $tenant; }
        }

        self::$current = null;
        return null;
    }

    public static function current(): ?array { return self::$current ?? self::resolve(); }

    public static function id(): ?int
    {
        $t = self::current();
        return $t ? (int)$t['id'] : null;
    }

    // ── Branding helpers ──────────────────────────────────────────────────────

    public static function brandingCss(): string
    {
        $t = self::current();
        if (!$t) return '';

        $primary = htmlspecialchars($t['primary_color'] ?? '#5b5ef6', ENT_QUOTES);
        $accent  = htmlspecialchars($t['accent_color']  ?? '#3b82f6', ENT_QUOTES);

        $css = ":root { --primary:{$primary}; --accent:{$accent}; }";
        if (!empty($t['custom_css'])) {
            $css .= "\n" . $t['custom_css'];
        }
        return $css;
    }

    public static function siteName(): string
    {
        $t = self::current();
        return $t ? $t['name'] : (Setting::get('site_name', 'LMSAdvisor') ?? 'LMSAdvisor');
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public static function all(int $page = 1, int $per = 20): array
    {
        $pdo    = Database::getInstance();
        $offset = ($page - 1) * $per;
        $total  = (int)$pdo->query('SELECT COUNT(*) FROM tenants')->fetchColumn();
        $stmt   = $pdo->prepare(
            'SELECT t.*, (SELECT COUNT(*) FROM users u WHERE u.tenant_id=t.id) AS user_count
             FROM tenants t ORDER BY t.created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$per, $offset]);
        return ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per];
    }

    public static function create(array $data): int
    {
        $pdo  = Database::getInstance();
        $uuid = Uuid::v4();
        $pdo->prepare(
            'INSERT INTO tenants (uuid, name, slug, custom_domain, plan, logo_url, favicon_url,
                primary_color, accent_color, email_from, email_name, seat_limit, trial_ends_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $uuid,
            $data['name'],
            $data['slug'],
            $data['custom_domain'] ?: null,
            $data['plan'] ?? 'trial',
            $data['logo_url'] ?? null,
            $data['favicon_url'] ?? null,
            $data['primary_color'] ?? '#5b5ef6',
            $data['accent_color']  ?? '#3b82f6',
            $data['email_from']    ?? null,
            $data['email_name']    ?? $data['name'],
            $data['seat_limit']    ?? 100,
            $data['trial_ends_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findByUuid(string $uuid): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM tenants WHERE uuid=? LIMIT 1');
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'UPDATE tenants SET name=?, slug=?, custom_domain=?, plan=?, status=?,
             primary_color=?, accent_color=?, logo_url=?, favicon_url=?,
             email_from=?, email_name=?, seat_limit=?, custom_css=?, custom_js=?
             WHERE id=?'
        )->execute([
            $data['name'],
            $data['slug'],
            $data['custom_domain'] ?: null,
            $data['plan'],
            $data['status'],
            $data['primary_color'] ?? '#5b5ef6',
            $data['accent_color']  ?? '#3b82f6',
            $data['logo_url']      ?? null,
            $data['favicon_url']   ?? null,
            $data['email_from']    ?? null,
            $data['email_name']    ?? $data['name'],
            (int)($data['seat_limit'] ?? 100),
            $data['custom_css']    ?? null,
            $data['custom_js']     ?? null,
            $id,
        ]);
    }

    public static function stats(int $tenantId): array
    {
        $pdo = Database::getInstance();
        return [
            'users'       => (int)$pdo->prepare('SELECT COUNT(*) FROM users WHERE tenant_id=?')->execute([$tenantId]) ? (int)$pdo->query("SELECT COUNT(*) FROM users WHERE tenant_id={$tenantId}")->fetchColumn() : 0,
            'courses'     => (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE tenant_id={$tenantId}")->fetchColumn(),
            'enrollments' => (int)$pdo->query("SELECT COUNT(*) FROM enrollments e JOIN users u ON u.id=e.user_id WHERE u.tenant_id={$tenantId}")->fetchColumn(),
        ];
    }
}

<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * MenuService — returns only the nav items a given role is allowed to see.
 * Settings are stored in menu_permissions table and editable by Super Admins.
 */
class MenuService
{
    /** Full menu definition — single source of truth */
    public static function allItems(): array
    {
        return [
            'dashboard'      => ['Dashboard',      'bi-speedometer2',            'admin/dashboard',        '/admin/dashboard'],
            'analytics'      => ['Analytics',      'bi-bar-chart-fill',          'admin/analytics',        '/admin/analytics'],
            'learner_data'   => ['Learner Data',   'bi-person-lines-fill',       'admin/learner-analytics','/admin/learner-analytics'],
            'at_risk'        => ['At-Risk',         'bi-exclamation-triangle-fill','admin/dropout',         '/admin/dropout'],
            'courses'        => ['Courses',         'bi-book-fill',               'admin/courses',          '/admin/courses'],
            'learning_paths' => ['Learning Paths', 'bi-signpost-2-fill',         'admin/learning-paths',   '/admin/learning-paths'],
            'groups'         => ['Groups',          'bi-people-fill',             'admin/groups',           '/admin/groups'],
            'assignments'    => ['Assignments',     'bi-clipboard-check-fill',    'admin/courses',          '/admin/courses'],
            'badges'         => ['Badges',          'bi-award-fill',              'admin/badges',           '/admin/badges'],
            'email'          => ['Email',           'bi-envelope-fill',           'admin/email',            '/admin/email'],
            'enrollments'    => ['Enrollments',     'bi-person-check-fill',       'admin/enrollments',      '/admin/enrollments'],
            'users'          => ['Users',           'bi-person-lines-fill',       'admin/users',            '/admin/users'],
            'categories'     => ['Categories',      'bi-grid-fill',               'admin/categories',       '/admin/categories'],
            'quizzes'        => ['Quizzes',         'bi-patch-question-fill',     'admin/quizzes',          '/admin/quizzes'],
            'forum'          => ['Forum',           'bi-chat-dots-fill',          'admin/forum',            '/admin/forum'],
            'reviews'        => ['Reviews',         'bi-star-fill',               'admin/reviews',          '/admin/reviews'],
            'leaderboard'    => ['Leaderboard',     'bi-trophy-fill',             'admin/leaderboard',      '/admin/leaderboard'],
            'knowledge_base' => ['Knowledge Base',  'bi-journals',                'admin/knowledge-base',   '/admin/knowledge-base'],
            'webinars'       => ['Webinars',        'bi-camera-video-fill',       'admin/webinars',         '/admin/webinars'],
            'reports'        => ['Reports',         'bi-bar-chart-line-fill',     'admin/reports',          '/admin/reports'],
            'api'            => ['API Tokens',      'bi-braces-asterisk',         'admin/api',              '/admin/api'],
            'webhooks'       => ['Webhooks',        'bi-plug-fill',               'admin/webhooks',         '/admin/webhooks'],
            'settings'       => ['Settings',        'bi-gear-fill',               'admin/settings',         '/admin/settings'],
            'database'       => ['Database',        'bi-database-fill-gear',      'admin/database',         '/admin/database'],
            'tenants'        => ['Tenants',         'bi-building-fill',           'admin/tenants',          '/admin/tenants'],
            'corporate'      => ['Corporate',       'bi-briefcase-fill',          'admin/organisations',    '/admin/organisations'],
            'marketplace'    => ['Marketplace',     'bi-shop',                    'admin/marketplace/api',  '/admin/marketplace'],
            'reporting'      => ['Reporting',       'bi-bar-chart-fill',          'admin/reporting',        '/admin/reporting'],
            'help_center'    => ['Help Center',     'bi-question-circle-fill',    'help',                   '/help'],
            'menu_settings'  => ['Menu Permissions','bi-sliders',                 'admin/menu-settings',    '/admin/menu-settings'],
        ];
    }

    /** Get permissions from DB — cached per request */
    private static ?array $cache = null;

    public static function permissions(): array
    {
        if (self::$cache !== null) return self::$cache;

        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->query('SELECT menu_key, roles FROM menu_permissions ORDER BY sort_order');
            $rows = $stmt->fetchAll();
            $perms = [];
            foreach ($rows as $row) {
                $perms[$row['menu_key']] = json_decode($row['roles'], true) ?? [];
            }
            self::$cache = $perms;
            return $perms;
        } catch (\Throwable $e) {
            // Table may not exist yet — return defaults (super_admin only)
            self::$cache = [];
            return [];
        }
    }

    /** Get menu items visible to a specific role */
    public static function forRole(string $role): array
    {
        // Super admin always sees everything
        if ($role === 'super_admin') {
            return self::allItems();
        }

        $perms   = self::permissions();
        $allItems = self::allItems();
        $visible = [];

        foreach ($allItems as $key => $item) {
            $allowedRoles = $perms[$key] ?? ['super_admin']; // Default: super_admin only if not in DB
            if (in_array($role, $allowedRoles, true)) {
                $visible[$key] = $item;
            }
        }

        return $visible;
    }

    /** Update permissions for a menu item */
    public static function updatePermissions(string $menuKey, array $roles): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'UPDATE menu_permissions SET roles=? WHERE menu_key=?'
        )->execute([json_encode(array_values($roles)), $menuKey]);
        self::$cache = null; // Invalidate cache
    }

    /** All available roles */
    public static function roles(): array
    {
        return ['super_admin', 'admin', 'manager', 'instructor'];
    }

    public static function roleLabel(string $role): string
    {
        return match($role) {
            'super_admin' => 'Super Admin',
            'admin'       => 'Admin',
            'manager'     => 'Manager',
            'instructor'  => 'Instructor',
            default       => ucfirst($role),
        };
    }
}

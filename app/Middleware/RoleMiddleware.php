<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

class RoleMiddleware
{
    /**
     * Require one of the given roles. Must be called after AuthMiddleware::handle().
     *
     * @param string[] $roles  e.g. ['admin', 'super_admin']
     */
    public static function require(array $roles): void
    {
        $role = AuthService::role();

        if (!in_array($role, $roles, true)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html><head><title>403 Forbidden</title>'
               . '<style>body{font-family:Inter,sans-serif;display:flex;align-items:center;'
               . 'justify-content:center;min-height:100vh;margin:0;background:#f1f5f9}'
               . '.box{text-align:center}.code{font-size:5rem;font-weight:700;color:#e02424;margin:0}'
               . '.msg{color:#64748b}</style></head><body>'
               . '<div class="box"><p class="code">403</p>'
               . '<p class="msg">You do not have permission to access this page.</p>'
               . '<a href="' . APP_URL . '/admin/dashboard" style="color:#1a56db">← Dashboard</a>'
               . '</div></body></html>';
            exit;
        }
    }

    /**
     * Shorthand for admin-only pages.
     */
    public static function adminOnly(): void
    {
        self::require(['admin', 'super_admin']);
    }

    /**
     * Shorthand for super admin only.
     */
    public static function superAdminOnly(): void
    {
        self::require(['super_admin']);
    }
}

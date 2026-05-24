<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;

class ImpersonationService
{
    /**
     * Start impersonating a target user.
     * Saves the original admin's session data and switches to the target user.
     *
     * @throws \RuntimeException if impersonation is not allowed
     */
    public static function impersonate(int $targetUserId): void
    {
        $currentRole = $_SESSION['user_role'] ?? '';

        // Only admin/super_admin may impersonate
        if (!in_array($currentRole, ['admin', 'super_admin'], true)) {
            throw new \RuntimeException('Permission denied.');
        }

        $userModel  = new User();
        $targetUser = $userModel->findWithRole($targetUserId);

        if (!$targetUser) {
            throw new \RuntimeException('User not found.');
        }

        // Admins cannot impersonate super_admins
        if ($targetUser['role_name'] === 'super_admin') {
            throw new \RuntimeException('You cannot impersonate a Super Admin.');
        }

        // Save original session
        $_SESSION['impersonator'] = [
            'user_id'      => $_SESSION['user_id'],
            'user_uuid'    => $_SESSION['user_uuid'],
            'user_email'   => $_SESSION['user_email'],
            'user_name'    => $_SESSION['user_name'],
            'user_role'    => $_SESSION['user_role'],
            'role_display' => $_SESSION['role_display'] ?? '',
            'session_token'=> $_SESSION['session_token'] ?? '',
        ];

        // Switch to target user (keep same session, just swap data)
        session_regenerate_id(true);
        $_SESSION['user_id']      = $targetUser['id'];
        $_SESSION['user_uuid']    = $targetUser['uuid'];
        $_SESSION['user_email']   = $targetUser['email'];
        $_SESSION['user_name']    = $targetUser['first_name'] . ' ' . $targetUser['last_name'];
        $_SESSION['user_role']    = $targetUser['role_name'];
        $_SESSION['role_display'] = $targetUser['role_display'];

        AuditLog::write(
            'user.impersonate_start',
            'user',
            $targetUserId,
            null,
            ['impersonated_by' => $_SESSION['impersonator']['user_id']]
        );
    }

    /**
     * Revert back to the original admin session.
     */
    public static function revert(): void
    {
        if (!self::isImpersonating()) {
            return;
        }

        $orig = $_SESSION['impersonator'];

        AuditLog::write(
            'user.impersonate_end',
            'user',
            (int)$_SESSION['user_id'],
            null,
            ['reverted_to' => $orig['user_id']]
        );

        // Restore original session data
        session_regenerate_id(true);
        $_SESSION['user_id']       = $orig['user_id'];
        $_SESSION['user_uuid']     = $orig['user_uuid'];
        $_SESSION['user_email']    = $orig['user_email'];
        $_SESSION['user_name']     = $orig['user_name'];
        $_SESSION['user_role']     = $orig['user_role'];
        $_SESSION['role_display']  = $orig['role_display'];
        $_SESSION['session_token'] = $orig['session_token'];

        unset($_SESSION['impersonator']);
    }

    public static function isImpersonating(): bool
    {
        return !empty($_SESSION['impersonator']);
    }

    public static function impersonatorName(): string
    {
        return $_SESSION['impersonator']['user_name'] ?? '';
    }

    public static function impersonatorId(): int
    {
        return (int)($_SESSION['impersonator']['user_id'] ?? 0);
    }
}

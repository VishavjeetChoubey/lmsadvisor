<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    protected string $table = 'audit_logs';

    /**
     * Write an audit entry.
     *
     * @param string     $action      e.g. 'user.login', 'user.create'
     * @param string     $entityType  e.g. 'user', 'course'
     * @param int|null   $entityId
     * @param array|null $oldValue
     * @param array|null $newValue
     */
    public static function write(
        string  $action,
        string  $entityType = '',
        ?int    $entityId   = null,
        ?array  $oldValue   = null,
        ?array  $newValue   = null
    ): void {
        $userId = $_SESSION['user_id'] ?? null;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua     = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $instance = new self();
        $instance->execute(
            'INSERT INTO audit_logs
             (user_id, action, entity_type, entity_id, old_value, new_value, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $action,
                $entityType ?: null,
                $entityId,
                $oldValue  !== null ? json_encode($oldValue)  : null,
                $newValue  !== null ? json_encode($newValue)  : null,
                $ip,
                $ua,
            ]
        );
    }
}

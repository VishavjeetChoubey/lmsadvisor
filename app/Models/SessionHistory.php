<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SessionHistory extends Model
{
    protected string $table = 'session_history';

    public function logLogin(int $userId, string $sessionToken, string $ip, string $ua, array $parsed): int
    {
        return $this->insert(
            'INSERT INTO session_history
             (user_id, session_token, ip_address, user_agent, device_type, browser, os, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $userId,
                $sessionToken,
                $ip,
                $ua,
                $parsed['device'],
                $parsed['browser'],
                $parsed['os'],
            ]
        );
    }

    public function logLogout(string $sessionToken): void
    {
        $this->execute(
            'UPDATE session_history
             SET logout_at = NOW(), is_active = 0,
                 duration_sec = TIMESTAMPDIFF(SECOND, login_at, NOW())
             WHERE session_token = ? AND is_active = 1',
            [$sessionToken]
        );
    }

    public function forUser(int $userId, int $limit = 10): array
    {
        return $this->query(
            'SELECT * FROM session_history WHERE user_id = ?
             ORDER BY login_at DESC LIMIT ' . (int)$limit,
            [$userId]
        );
    }

    public function terminateAllForUser(int $userId): void
    {
        $this->execute(
            'UPDATE session_history SET is_active = 0, logout_at = NOW()
             WHERE user_id = ? AND is_active = 1',
            [$userId]
        );
    }
}

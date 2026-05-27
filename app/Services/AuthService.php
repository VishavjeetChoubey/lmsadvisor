<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\SessionHistory;
use App\Models\Setting;
use App\Helpers\UserAgent;

class AuthService
{
    private User           $userModel;
    private SessionHistory $sessionModel;

    public function __construct()
    {
        $this->userModel    = new User();
        $this->sessionModel = new SessionHistory();
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    /**
     * Attempt to log in a user.
     *
     * Returns one of:
     *   ['success' => true,  'user' => [...]]
     *   ['success' => false, 'error' => 'message', 'field' => 'email|password|general']
     */
    public function attempt(string $email, string $password, string $ip, string $ua): array
    {
        $email = strtolower(trim($email));

        // 1. Find user
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password.', 'field' => 'email'];
        }

        // 2. Account active?
        if (!(bool)$user['is_active']) {
            return ['success' => false, 'error' => 'Your account has been deactivated. Please contact an administrator.', 'field' => 'general'];
        }

        // 3. Locked out?
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = (int)ceil((strtotime($user['locked_until']) - time()) / 60);
            return [
                'success' => false,
                'error'   => "Account locked. Try again in {$remaining} minute(s).",
                'field'   => 'general',
            ];
        }

        // 4. Verify password
        if (!password_verify($password, (string)$user['password_hash'])) {
            $this->handleFailedAttempt($user);
            return ['success' => false, 'error' => 'Invalid email or password.', 'field' => 'password'];
        }

        // 5. Success — reset attempts, update last login, start session
        $this->userModel->resetLoginAttempts($user['id']);
        $this->userModel->updateLastLogin($user['id']);
        $this->startSession($user, $ip, $ua);

        AuditLog::write('user.login', 'user', (int)$user['id']);

        return ['success' => true, 'user' => $user];
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionToken = $_SESSION['session_token'] ?? null;
        $userId       = $_SESSION['user_id'] ?? null;

        if ($sessionToken) {
            $this->sessionModel->logLogout($sessionToken);
        }

        if ($userId) {
            AuditLog::write('user.logout', 'user', (int)$userId);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── Session ───────────────────────────────────────────────────────────────

    private function startSession(array $user, string $ip, string $ua): void
    {
        // Regenerate session ID on login (session fixation protection)
        session_regenerate_id(true);

        $sessionToken = bin2hex(random_bytes(32));
        $parsed       = UserAgent::parse($ua);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_uuid']     = $user['uuid'];
        $_SESSION['user_email']    = $user['email'];
        $_SESSION['user_name']     = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role']     = $user['role_name'];
        $_SESSION['role_display']  = $user['role_display'];
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['logged_in_at']  = time();

        $this->sessionModel->logLogin(
            (int)$user['id'],
            $sessionToken,
            $ip,
            $ua,
            $parsed
        );

        // Gamification: update daily login streak
        try { \App\Services\GamificationService::updateStreak((int)$user['id']); } catch (\Throwable) {}

        // Analytics: login event
        try { \App\Services\AnalyticsService::event('login'); } catch (\Throwable) {}
    }

    // ── Lockout ───────────────────────────────────────────────────────────────

    private function handleFailedAttempt(array $user): void
    {
        // Read from settings table (with fallback defaults)
        $maxAttempts  = (int)(Setting::get('login_max_attempts', 5));
        $lockoutMins  = (int)(Setting::get('login_lockout_min', 15));

        $attempts = (int)$user['login_attempts'] + 1;

        if ($attempts >= $maxAttempts) {
            $this->userModel->lockAccount((int)$user['id'], $lockoutMins);
            AuditLog::write('user.locked', 'user', (int)$user['id']);
        } else {
            $this->userModel->incrementLoginAttempts((int)$user['id']);
        }
    }

    // ── reCAPTCHA ─────────────────────────────────────────────────────────────

    public function verifyRecaptcha(string $token): bool
    {
        $secret = Setting::get('recaptcha_secret', '');
        if (!$secret || !$token) return false;

        // Use cURL — works even when allow_url_fopen is Off
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if (!$response || $curlErr) {
            error_log('[reCAPTCHA] cURL error: ' . $curlErr);
            return false;
        }

        $data = json_decode($response, true);
        return (bool)($data['success'] ?? false);
    }

    // ── Check auth ────────────────────────────────────────────────────────────

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return [
            'id'           => $_SESSION['user_id'],
            'uuid'         => $_SESSION['user_uuid'],
            'email'        => $_SESSION['user_email'],
            'name'         => $_SESSION['user_name'],
            'role'         => $_SESSION['user_role'],
            'role_display' => $_SESSION['role_display'] ?? '',
        ];
    }

    public static function role(): string
    {
        return $_SESSION['user_role'] ?? '';
    }

    public static function isAdmin(): bool
    {
        return in_array(self::role(), ['admin', 'super_admin'], true);
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    public static function isStudent(): bool
    {
        return self::role() === 'student';
    }
}

<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;
use App\Helpers\Uuid;

/**
 * SocialAuthService — Phase 30: Google + GitHub OAuth2 SSO
 * Pure PHP, no Composer — uses curl for token exchange.
 */
class SocialAuthService
{
    // ── Google OAuth ──────────────────────────────────────────────────────────

    public static function googleAuthUrl(): string
    {
        $clientId    = Setting::get('google_client_id', '');
        $redirectUri = rtrim(APP_URL, '/') . '/auth/google/callback';
        $state       = self::generateState();
        $_SESSION['oauth_state'] = $state;

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
        ]);
    }

    public static function googleCallback(string $code, string $state): ?array
    {
        self::verifyState($state);
        $clientId     = Setting::get('google_client_id', '');
        $clientSecret = Setting::get('google_client_secret', '');
        $redirectUri  = rtrim(APP_URL, '/') . '/auth/google/callback';

        // Exchange code for token
        $token = self::post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($token['access_token'])) return null;

        // Get user info
        $info = self::get('https://www.googleapis.com/oauth2/v3/userinfo', $token['access_token']);
        if (empty($info['email'])) return null;

        return self::findOrCreate([
            'email'      => $info['email'],
            'first_name' => $info['given_name']  ?? explode(' ', $info['name'] ?? '')[0] ?? 'User',
            'last_name'  => $info['family_name'] ?? '',
            'avatar_url' => $info['picture']     ?? null,
            'provider'   => 'google',
            'provider_id'=> $info['sub'],
        ]);
    }

    // ── GitHub OAuth ──────────────────────────────────────────────────────────

    public static function githubAuthUrl(): string
    {
        $clientId = Setting::get('github_client_id', '');
        $state    = self::generateState();
        $_SESSION['oauth_state'] = $state;

        return 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id' => $clientId,
            'scope'     => 'user:email',
            'state'     => $state,
        ]);
    }

    public static function githubCallback(string $code, string $state): ?array
    {
        self::verifyState($state);
        $clientId     = Setting::get('github_client_id', '');
        $clientSecret = Setting::get('github_client_secret', '');

        $token = self::post('https://github.com/login/oauth/access_token', [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
        ]);

        if (empty($token['access_token'])) return null;

        $info   = self::get('https://api.github.com/user', $token['access_token'], true);
        $emails = self::get('https://api.github.com/user/emails', $token['access_token'], true);

        $email = $info['email'] ?? null;
        if (!$email && is_array($emails)) {
            foreach ($emails as $e) {
                if (!empty($e['primary']) && !empty($e['verified'])) {
                    $email = $e['email']; break;
                }
            }
        }
        if (!$email) return null;

        $nameParts = explode(' ', $info['name'] ?? 'GitHub User', 2);
        return self::findOrCreate([
            'email'      => $email,
            'first_name' => $nameParts[0],
            'last_name'  => $nameParts[1] ?? '',
            'avatar_url' => $info['avatar_url'] ?? null,
            'provider'   => 'github',
            'provider_id'=> (string)$info['id'],
        ]);
    }

    // ── Shared: find or create user ───────────────────────────────────────────

    private static function findOrCreate(array $ssoData): array
    {
        $pdo  = Database::getInstance();
        $email = strtolower(trim($ssoData['email']));

        // Try to find existing user
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Create new student account
            $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name='student' LIMIT 1");
            $roleStmt->execute();
            $roleId = $roleStmt->fetchColumn();

            $pdo->prepare(
                'INSERT INTO users (uuid, first_name, last_name, email, password_hash, role_id, is_active)
                 VALUES (?,?,?,?,?,?,1)'
            )->execute([
                Uuid::v4(),
                $ssoData['first_name'],
                $ssoData['last_name'],
                $email,
                password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                $roleId,
            ]);
            $userId = (int)$pdo->lastInsertId();

            $stmt2 = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
            $stmt2->execute([$userId]);
            $user = $stmt2->fetch();
        }

        // Start session
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_uuid']  = $user['uuid'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role']  = 'student';

        return $user;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function post(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        $parsed = json_decode($raw, true);
        if (!$parsed) parse_str($raw, $parsed); // GitHub returns form-encoded
        return $parsed ?: [];
    }

    private static function get(string $url, string $token, bool $isGitHub = false): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
                'User-Agent: LMSAdvisor/2.0',
            ],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw, true) ?: [];
    }

    private static function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }

    private static function verifyState(string $state): void
    {
        if (empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
            throw new \RuntimeException('OAuth state mismatch — possible CSRF attack.');
        }
        unset($_SESSION['oauth_state']);
    }
}

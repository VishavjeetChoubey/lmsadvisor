<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Helpers\Sanitizer;

class AuthController extends Controller
{
    // ── POST /api/v1/auth/token ───────────────────────────────────────────────
    public function token(array $params): void
    {
        $this->setCorsHeaders();
        $email    = Sanitizer::email($this->request->post('email', ''));
        $password = (string)$this->request->post('password', '');
        $name     = Sanitizer::string($this->request->post('token_name', 'API Token'), 120);
        $ip       = $this->clientIp();

        $userModel = new User();
        $user      = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
            $this->logSecurityEvent('api_auth_failed', null, $ip, ['email' => $email]);
            http_response_code(401);
            $this->json(['error' => 'Invalid credentials.', 'code' => 'AUTH_FAILED']);
        }
        if (!$user['is_active']) {
            http_response_code(403);
            $this->json(['error' => 'Account is inactive.', 'code' => 'ACCOUNT_INACTIVE']);
        }

        $pdo   = Database::getInstance();
        $token = bin2hex(random_bytes(20)); // 40-char secure token

        $pdo->prepare(
            'INSERT INTO api_tokens (user_id, token, name, scopes, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, NOW())'
        )->execute([$user['id'], $token, $name, 'read,write']);

        $this->logSecurityEvent('api_auth_success', (int)$user['id'], $ip);

        $this->json([
            'success'    => true,
            'token'      => $token,
            'token_type' => 'Bearer',
            'user' => [
                'uuid'  => $user['uuid'],
                'name'  => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role'  => $user['role_name'] ?? 'student',
            ],
        ]);
    }

    // ── DELETE /api/v1/auth/token ─────────────────────────────────────────────
    public function revoke(array $params): void
    {
        $this->setCorsHeaders();
        $user = $this->apiAuth();
        $pdo  = Database::getInstance();
        $pdo->prepare('UPDATE api_tokens SET is_active=0 WHERE token=?')
            ->execute([$this->getBearerToken()]);
        $this->json(['success' => true, 'message' => 'Token revoked.']);
    }

    // ── Shared API Auth ───────────────────────────────────────────────────────

    /**
     * Authenticate API request. Returns user row or sends 401 JSON.
     *
     * @param string|null $requiredScope e.g. 'write', 'admin'
     */
    protected function apiAuth(?string $requiredScope = null): array
    {
        $this->setCorsHeaders();
        $token = $this->getBearerToken();

        if (!$token) {
            http_response_code(401);
            $this->json([
                'error' => 'No authentication token provided.',
                'code'  => 'TOKEN_MISSING',
                'hint'  => 'Add header: Authorization: Bearer YOUR_TOKEN',
            ]);
        }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT t.*, u.id AS uid, u.uuid, u.first_name, u.last_name, u.email,
                    u.is_active, r.name AS role_name, r.display_name AS role_display
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE t.token = ? AND t.is_active = 1
               AND (t.expires_at IS NULL OR t.expires_at > NOW())
             LIMIT 1'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->logSecurityEvent('api_auth_failed', null, $this->clientIp(), ['reason' => 'invalid_token']);
            http_response_code(401);
            $this->json(['error' => 'Invalid or expired token.', 'code' => 'TOKEN_INVALID']);
        }

        if (!$row['is_active']) {
            http_response_code(403);
            $this->json(['error' => 'Account is inactive.', 'code' => 'ACCOUNT_INACTIVE']);
        }

        // IP whitelist check
        if ($row['ip_whitelist']) {
            $allowed = array_map('trim', explode(',', $row['ip_whitelist']));
            $ip      = $this->clientIp();
            if (!in_array($ip, $allowed, true)) {
                $this->logSecurityEvent('api_ip_blocked', (int)$row['uid'], $ip);
                http_response_code(403);
                $this->json(['error' => 'IP address not whitelisted.', 'code' => 'IP_NOT_ALLOWED']);
            }
        }

        // Scope check
        if ($requiredScope) {
            $scopes = array_map('trim', explode(',', $row['scopes'] ?? ''));
            if (!in_array($requiredScope, $scopes, true) && !in_array('admin', $scopes, true)) {
                http_response_code(403);
                $this->json([
                    'error' => "Token missing required scope: {$requiredScope}",
                    'code'  => 'SCOPE_MISSING',
                ]);
            }
        }

        // Update last_used + increment request count
        $pdo->prepare(
            'UPDATE api_tokens SET last_used=NOW(), request_count=request_count+1 WHERE token=?'
        )->execute([$token]);

        // Build user array for downstream use
        return [
            'id'           => $row['uid'],
            'uuid'         => $row['uuid'],
            'first_name'   => $row['first_name'],
            'last_name'    => $row['last_name'],
            'name'         => $row['first_name'] . ' ' . $row['last_name'],
            'email'        => $row['email'],
            'role_name'    => $row['role_name'],
            'role_display' => $row['role_display'],
            'token_scopes' => $row['scopes'] ?? '',
            'token_id'     => $row['id'],
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function getBearerToken(): string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) return trim($m[1]);
        // Also accept ?token= query param for playground convenience
        return Sanitizer::string($this->request->get('token', ''), 60);
    }

    protected function clientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    protected function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Token, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        header('Content-Type: application/json; charset=UTF-8');
        // SOC2: Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    protected function logSecurityEvent(string $type, ?int $userId, string $ip, array $details = []): void
    {
        try {
            $pdo = Database::getInstance();
            $pdo->prepare(
                'INSERT INTO security_events (event_type, user_id, ip_address, user_agent, details)
                 VALUES (?,?,?,?,?)'
            )->execute([
                $type, $userId, $ip,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                $details ? json_encode($details) : null,
            ]);
        } catch (\Throwable) {}
    }

    protected function apiPaginate(int $total, int $page, int $perPage): array
    {
        return [
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'last_page'  => max(1, (int)ceil($total / $perPage)),
            'from'       => $total > 0 ? ($page - 1) * $perPage + 1 : 0,
            'to'         => min($total, $page * $perPage),
        ];
    }
}

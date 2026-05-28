<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Database;
use App\Helpers\Sanitizer;

/**
 * AuthApiController — SSO token generation for WooCommerce → LMS auto-login.
 *
 * POST /api/v1/auth/sso-token
 *   Body: { email, redirect_path }
 *   Returns: { success, redirect_url, expires_at }
 */
class AuthApiController extends AuthController
{
    public function ssoToken(array $p): void
    {
        $this->apiAuth();

        $email        = Sanitizer::email($this->request->post('email', ''));
        $redirectPath = '/' . ltrim(
            Sanitizer::string($this->request->post('redirect_path', '/learn/dashboard'), 300),
            '/'
        );

        if (!$email) {
            $this->json(['success' => false, 'message' => 'Email is required.'], 422);
        }

        $pdo = Database::getInstance();

        // ── 1. Find the LMS user ──────────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->json([
                'success' => false,
                'message' => "No active LMS account found for {$email}. Ensure enrollment created the account first.",
            ], 404);
        }

        // ── 2. Generate token ─────────────────────────────────────────────────
        $token   = bin2hex(random_bytes(32)); // 64 hex chars
        $expires = date('Y-m-d H:i:s', time() + 900); // 15 min

        // ── 3. Insert into sso_tokens ─────────────────────────────────────────
        // Table is created by migration 0023 — but ensure it exists here too as safety net
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS sso_tokens (
                    id            INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
                    user_id       INT UNSIGNED    NOT NULL,
                    token         VARCHAR(64)     NOT NULL UNIQUE,
                    redirect_path VARCHAR(300)    NOT NULL DEFAULT \'/learn/dashboard\',
                    expires_at    TIMESTAMP       NOT NULL,
                    used          TINYINT(1)      NOT NULL DEFAULT 0,
                    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_token (token),
                    KEY idx_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (\Throwable $e) {
            // Table already exists or other non-critical error — ignore
        }

        // Delete expired tokens for this user — wrapped in case table just created
        try {
            $pdo->prepare('DELETE FROM sso_tokens WHERE expires_at < NOW() OR (user_id = ? AND used = 1)')
                ->execute([(int)$user['id']]);
        } catch (\Throwable $e) {
            // Non-fatal — table may have just been created
        }

        // Insert new token
        $pdo->prepare(
            'INSERT INTO sso_tokens (user_id, token, redirect_path, expires_at) VALUES (?, ?, ?, ?)'
        )->execute([(int)$user['id'], $token, $redirectPath, $expires]);

        // ── 4. Build redirect URL ─────────────────────────────────────────────
        $redirectUrl = rtrim(APP_URL, '/') . '/sso/login?token=' . $token;

        $this->json([
            'success'      => true,
            'redirect_url' => $redirectUrl,
            'expires_at'   => $expires,
            'token_length' => strlen($token), // debug — should be 64
        ]);
    }
}

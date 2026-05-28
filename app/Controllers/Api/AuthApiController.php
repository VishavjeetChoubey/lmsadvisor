<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Database;
use App\Helpers\Sanitizer;

/**
 * POST /api/v1/auth/sso-token
 * Generates a 15-minute single-use SSO token for WooCommerce → LMS auto-login.
 */
class AuthApiController extends AuthController
{
    public function ssoToken(array $p): void
    {
        $this->apiAuth();

        $email        = Sanitizer::email($this->request->post('email', ''));
        $redirectPath = Sanitizer::string($this->request->post('redirect_path', '/learn/dashboard'), 300);

        if (!$email) {
            $this->json(['success' => false, 'message' => 'Email is required.'], 422);
        }

        $pdo  = Database::getInstance();

        // Find active user by email
        $stmt = $pdo->prepare('SELECT id, uuid FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->json(['success' => false, 'message' => 'No active LMS account found for this email.'], 404);
        }

        // Ensure sso_tokens table exists
        $pdo->exec('CREATE TABLE IF NOT EXISTS sso_tokens (
            id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            user_id      INT UNSIGNED NOT NULL,
            token        CHAR(48)     NOT NULL UNIQUE,
            redirect_path VARCHAR(300) DEFAULT \'/learn/dashboard\',
            expires_at   TIMESTAMP    NOT NULL,
            used         TINYINT(1)   DEFAULT 0,
            created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Clean up expired tokens
        $pdo->exec('DELETE FROM sso_tokens WHERE expires_at < NOW()');

        // Generate token
        $token   = bin2hex(random_bytes(24)); // 48 hex chars
        $expires = date('Y-m-d H:i:s', time() + 900); // 15 minutes

        $pdo->prepare(
            'INSERT INTO sso_tokens (user_id, token, redirect_path, expires_at) VALUES (?, ?, ?, ?)'
        )->execute([(int)$user['id'], $token, $redirectPath, $expires]);

        $redirectUrl = rtrim(APP_URL, '/') . '/sso/login?token=' . $token;

        $this->json([
            'success'      => true,
            'redirect_url' => $redirectUrl,
            'expires_at'   => $expires,
        ]);
    }
}

<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Database;
use App\Helpers\Sanitizer;

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

        try {
            $pdo = Database::getInstance();

            // Ensure table exists
            $pdo->exec('CREATE TABLE IF NOT EXISTS sso_tokens (
                id            INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
                user_id       INT UNSIGNED    NOT NULL,
                token         VARCHAR(64)     NOT NULL UNIQUE,
                redirect_path VARCHAR(300)    NOT NULL DEFAULT \'/learn/dashboard\',
                expires_at    TIMESTAMP       NOT NULL,
                used          TINYINT(1)      NOT NULL DEFAULT 0,
                created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_token (token),
                KEY idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            // Find user
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->json(['success' => false, 'message' => 'No LMS account found for ' . $email], 404);
            }

            // Clean up old tokens
            $pdo->prepare('DELETE FROM sso_tokens WHERE user_id = ? AND (used = 1 OR expires_at < NOW())')
                ->execute([(int)$user['id']]);

            // Generate and store token
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 900);

            $pdo->prepare('INSERT INTO sso_tokens (user_id, token, redirect_path, expires_at) VALUES (?,?,?,?)')
                ->execute([(int)$user['id'], $token, $redirectPath, $expires]);

            $this->json([
                'success'      => true,
                'redirect_url' => rtrim(APP_URL, '/') . '/sso/login?token=' . $token,
                'expires_at'   => $expires,
            ]);

        } catch (\Throwable $e) {
            // Return exact error so we can debug
            error_log('[SSO] ssoToken error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->json([
                'success' => false,
                'message' => 'SSO internal error: ' . $e->getMessage(),
                'file'    => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }
}

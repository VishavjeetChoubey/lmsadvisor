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

            // Fix column size if table was created with wrong CHAR(48)
            $pdo->exec("ALTER TABLE sso_tokens MODIFY COLUMN token VARCHAR(64) NOT NULL");

            // Find user
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->json(['success' => false, 'message' => 'No LMS account found for ' . $email], 404);
            }

            // Clean up old tokens
            try {
                $pdo->prepare('DELETE FROM sso_tokens WHERE user_id = ? AND (used = 1 OR expires_at < NOW())')
                    ->execute([(int)$user['id']]);
            } catch (\Throwable $e) {}

            // Generate 48-char token — fits both CHAR(48) and VARCHAR(64)
            $token   = bin2hex(random_bytes(24)); // 48 hex chars
            $expires = date('Y-m-d H:i:s', time() + 900);

            $pdo->prepare('INSERT INTO sso_tokens (user_id, token, redirect_path, expires_at) VALUES (?,?,?,?)')
                ->execute([(int)$user['id'], $token, $redirectPath, $expires]);

            $this->json([
                'success'      => true,
                'redirect_url' => rtrim(APP_URL, '/') . '/sso/login?token=' . $token,
                'expires_at'   => $expires,
            ]);

        } catch (\Throwable $e) {
            error_log('[SSO] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'SSO error: ' . $e->getMessage()], 500);
        }
    }
}

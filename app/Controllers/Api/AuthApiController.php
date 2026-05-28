<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Sanitizer;
use App\Middleware\ApiAuthMiddleware;

class AuthApiController extends Controller
{
    /** POST /api/v1/auth/sso-token — generate short-lived SSO redirect URL */
    public function ssoToken(array $p): void
    {
        ApiAuthMiddleware::handle();
        $email        = Sanitizer::email($this->request->post('email', ''));
        $redirectPath = Sanitizer::string($this->request->post('redirect_path', '/learn/dashboard'), 200);
        if (!$email) { $this->json(['success'=>false,'message'=>'Email required.'], 422); }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, uuid FROM users WHERE email=? AND is_active=1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) { $this->json(['success'=>false,'message'=>'User not found.'], 404); }

        // Generate 15-minute SSO token
        $token   = bin2hex(random_bytes(24));
        $expires = date('Y-m-d H:i:s', time() + 900);

        // Store in sso_tokens table (create if not exists)
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS sso_tokens (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    user_id INT UNSIGNED NOT NULL,
                    token CHAR(48) NOT NULL UNIQUE,
                    redirect_path VARCHAR(300) DEFAULT \'/learn/dashboard\',
                    expires_at TIMESTAMP NOT NULL,
                    used TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        } catch (\PDOException) {}

        $pdo->prepare(
            'INSERT INTO sso_tokens (user_id, token, redirect_path, expires_at) VALUES (?,?,?,?)'
        )->execute([(int)$user['id'], $token, $redirectPath, $expires]);

        $redirectUrl = rtrim(APP_URL, '/') . '/sso/login?token=' . $token;
        $this->json(['success'=>true,'redirect_url'=>$redirectUrl,'expires_at'=>$expires]);
    }
}

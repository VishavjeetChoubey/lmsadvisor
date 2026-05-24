<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Helpers\Sanitizer;

class AuthController extends Controller
{
    // POST /api/v1/auth/token
    public function token(array $params): void
    {
        $email    = Sanitizer::email($this->request->post('email', ''));
        $password = (string)$this->request->post('password', '');

        $userModel = new User();
        $user      = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
            http_response_code(401);
            $this->json(['error' => 'Invalid credentials.']);
        }
        if (!$user['is_active']) {
            http_response_code(403);
            $this->json(['error' => 'Account is inactive.']);
        }

        $pdo   = Database::getInstance();
        $token = bin2hex(random_bytes(16));

        $pdo->prepare(
            'INSERT INTO api_tokens (user_id, token, name, created_at)
             VALUES (?, ?, ?, NOW())'
        )->execute([$user['id'], $token, 'API Login ' . date('Y-m-d')]);

        $this->json([
            'token' => $token,
            'user'  => [
                'uuid'       => $user['uuid'],
                'name'       => $user['first_name'] . ' ' . $user['last_name'],
                'email'      => $user['email'],
                'role'       => $user['role_name'] ?? 'student',
            ],
        ]);
    }

    // DELETE /api/v1/auth/token
    public function revoke(array $params): void
    {
        $user = $this->apiAuth();
        $pdo  = Database::getInstance();
        $pdo->prepare('UPDATE api_tokens SET is_active=0 WHERE user_id=? AND token=?')
            ->execute([$user['id'], $this->getBearerToken()]);
        $this->json(['message' => 'Token revoked.']);
    }

    protected function apiAuth(): array
    {
        $token = $this->getBearerToken();
        if (!$token) { http_response_code(401); $this->json(['error'=>'No token provided.']); }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.*, r.name AS role_name FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE t.token=? AND t.is_active=1
             AND (t.expires_at IS NULL OR t.expires_at > NOW()) LIMIT 1'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) { http_response_code(401); $this->json(['error'=>'Invalid or expired token.']); }

        // Update last_used
        $pdo->prepare('UPDATE api_tokens SET last_used=NOW() WHERE token=?')->execute([$token]);
        return $user;
    }

    private function getBearerToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) return trim($m[1]);
        return trim($header);
    }
}

<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Uuid;
use App\Helpers\Sanitizer;
use App\Middleware\ApiAuthMiddleware;

class UserApiController extends Controller
{
    public function create(array $p): void
    {
        ApiAuthMiddleware::handle();
        $email     = Sanitizer::email($this->request->post('email', ''));
        $firstName = Sanitizer::string($this->request->post('first_name', ''), 80);
        $lastName  = Sanitizer::string($this->request->post('last_name', ''),  80);
        if (!$email) { $this->json(['success'=>false,'message'=>'Email required.'], 422); }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, uuid FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        if ($existing) {
            $this->json(['success'=>true,'uuid'=>$existing['uuid'],'created'=>false,'message'=>'User already exists.']);
        }

        $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name='student' LIMIT 1");
        $roleStmt->execute();
        $roleId = (int)$roleStmt->fetchColumn();

        $uuid = Uuid::v4();
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $pdo->prepare(
            'INSERT INTO users (uuid, first_name, last_name, email, password_hash, role_id, is_active)
             VALUES (?,?,?,?,?,?,1)'
        )->execute([$uuid, $firstName, $lastName, $email, $hash, $roleId]);

        $this->json(['success'=>true,'uuid'=>$uuid,'created'=>true,'message'=>'Student account created.']);
    }

    public function update(array $p): void
    {
        ApiAuthMiddleware::handle();
        $pdo       = Database::getInstance();
        $firstName = Sanitizer::string($this->request->post('first_name',''), 80);
        $lastName  = Sanitizer::string($this->request->post('last_name',''), 80);
        $pdo->prepare('UPDATE users SET first_name=?, last_name=? WHERE uuid=?')
            ->execute([$firstName, $lastName, $p['uuid'] ?? '']);
        $this->json(['success'=>true,'message'=>'User updated.']);
    }
}

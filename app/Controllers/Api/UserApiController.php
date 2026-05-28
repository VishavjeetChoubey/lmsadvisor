<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Database;
use App\Helpers\Uuid;
use App\Helpers\Sanitizer;

/**
 * UserApiController — Create and update LMS student accounts.
 * Used by WooCommerce plugin on purchase.
 *
 * POST /api/v1/users        — create student (returns uuid, idempotent on email)
 * PUT  /api/v1/users/:uuid  — update name
 */
class UserApiController extends AuthController
{
    /** POST /api/v1/users */
    public function create(array $p): void
    {
        $this->apiAuth(); // Validates Bearer token

        $email     = Sanitizer::email($this->request->post('email', ''));
        $firstName = Sanitizer::string($this->request->post('first_name', 'Student'), 80);
        $lastName  = Sanitizer::string($this->request->post('last_name', ''), 80);
        $role      = Sanitizer::string($this->request->post('role', 'student'), 20);

        if (!$email) {
            $this->json(['success' => false, 'message' => 'Email is required.'], 422);
        }

        $pdo = Database::getInstance();

        // Idempotent — return existing user if email already registered
        $stmt = $pdo->prepare('SELECT id, uuid, first_name, last_name FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        if ($existing) {
            $this->json([
                'success' => true,
                'uuid'    => $existing['uuid'],
                'created' => false,
                'message' => 'User already exists.',
            ]);
        }

        // Get student role id
        $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
        $roleStmt->execute([in_array($role, ['student','admin','manager'], true) ? $role : 'student']);
        $roleId = (int) $roleStmt->fetchColumn();
        if (!$roleId) {
            $roleStmt->execute(['student']);
            $roleId = (int) $roleStmt->fetchColumn();
        }

        // Generate a random password — student can reset via forgot password
        $uuid = Uuid::v4();
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

        $pdo->prepare(
            'INSERT INTO users (uuid, first_name, last_name, email, password_hash, role_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        )->execute([$uuid, $firstName, $lastName, $email, $hash, $roleId]);

        $this->json([
            'success' => true,
            'uuid'    => $uuid,
            'created' => true,
            'message' => 'Student account created.',
        ]);
    }

    /** PUT /api/v1/users/:uuid */
    public function update(array $p): void
    {
        $this->apiAuth();
        $pdo       = Database::getInstance();
        $uuid      = $p['uuid'] ?? '';
        $firstName = Sanitizer::string($this->request->post('first_name', ''), 80);
        $lastName  = Sanitizer::string($this->request->post('last_name', ''), 80);

        $pdo->prepare('UPDATE users SET first_name = ?, last_name = ? WHERE uuid = ?')
            ->execute([$firstName, $lastName, $uuid]);

        $this->json(['success' => true, 'message' => 'User updated.']);
    }

    /** GET /api/v1/users — admin list */
    public function index(array $p): void
    {
        $user = $this->apiAuth();
        if (!in_array($user['role_name'], ['admin', 'super_admin', 'manager'], true)) {
            $this->json(['error' => 'Insufficient permissions.'], 403);
        }
        $pdo  = Database::getInstance();
        $page = max(1, (int) $this->request->get('page', 1));
        $per  = min(100, (int) $this->request->get('per_page', 20));
        $stmt = $pdo->prepare(
            'SELECT uuid, first_name, last_name, email, is_active, created_at
             FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$per, ($page - 1) * $per]);
        $this->json(['data' => $stmt->fetchAll()]);
    }

    /** GET /api/v1/users/:uuid */
    public function show(array $p): void
    {
        $this->apiAuth();
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT uuid, first_name, last_name, email, is_active FROM users WHERE uuid = ? LIMIT 1');
        $stmt->execute([$p['uuid'] ?? '']);
        $user = $stmt->fetch();
        if (!$user) { $this->json(['error' => 'User not found.'], 404); }
        $this->json(['data' => $user]);
    }
}

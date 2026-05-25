<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Helpers\Sanitizer;

class UserApiController extends AuthController
{
    // GET /api/v1/users  (admin/manager only)
    public function index(array $params): void
    {
        $user = $this->apiAuth('read');
        if (!in_array($user['role_name'], ['admin','super_admin','manager'])) {
            http_response_code(403); $this->json(['error'=>'Insufficient permissions.','code'=>'FORBIDDEN']);
        }
        $pdo    = Database::getInstance();
        $page   = max(1,(int)$this->request->get('page',1));
        $search = Sanitizer::string($this->request->get('search',''),100);
        $role   = Sanitizer::string($this->request->get('role',''),30);
        $perPage= 25;
        $where  = ['1=1']; $binds = [];
        if ($search) { $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)'; $binds[] = "%$search%"; $binds[] = "%$search%"; $binds[] = "%$search%"; }
        if ($role)   { $where[] = 'r.name = ?'; $binds[] = $role; }
        $ws = implode(' AND ', $where);
        $offset = ($page-1)*$perPage;
        $countS = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE $ws");
        $countS->execute($binds); $total = (int)$countS->fetchColumn();
        $stmt = $pdo->prepare("SELECT u.uuid,u.first_name,u.last_name,u.email,u.is_active,u.created_at,u.last_login_at,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE $ws ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($binds);
        $this->json(['data'=>$stmt->fetchAll(),'meta'=>$this->apiPaginate($total,$page,$perPage)]);
    }

    // GET /api/v1/users/:uuid
    public function show(array $params): void
    {
        $authUser = $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT u.uuid,u.first_name,u.last_name,u.email,u.is_active,u.created_at,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.uuid=? LIMIT 1');
        $stmt->execute([$params['uuid']??'']);
        $u = $stmt->fetch();
        if (!$u) { http_response_code(404); $this->json(['error'=>'User not found.']); }
        // Only admin or self
        if (!in_array($authUser['role_name'],['admin','super_admin']) && $authUser['uuid'] !== $u['uuid']) {
            http_response_code(403); $this->json(['error'=>'Forbidden.','code'=>'FORBIDDEN']);
        }
        $this->json(['data'=>$u]);
    }
}

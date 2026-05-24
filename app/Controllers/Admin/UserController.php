<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\ImpersonationService;
use App\Models\User;
use App\Models\AuditLog;
use App\Helpers\Sanitizer;
use App\Helpers\Validator;
use App\Helpers\Uuid;

class UserController extends Controller
{
    private User $userModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->userModel = new User();
    }

    // ── GET /admin/users ──────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $page       = max(1, (int)$this->request->get('page', 1));
        $search     = Sanitizer::string($this->request->get('search', ''), 100);
        $roleFilter = Sanitizer::string($this->request->get('role', ''), 30);
        $perPage    = 20;

        $data = $this->userModel->paginate($page, $perPage, $search, $roleFilter);

        // Count by role for stats
        $roleCounts = $this->userModel->countByRole();

        $this->view('admin.users.index', [
            'title'       => 'Users — LMSAdvisor',
            'page_title'  => 'User Management',
            'breadcrumbs' => [['label' => 'Users']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'rows'        => $data['rows'],
            'total'       => $data['total'],
            'page'        => $data['page'],
            'perPage'     => $data['perPage'],
            'search'      => $search,
            'roleFilter'  => $roleFilter,
            'roleCounts'  => $roleCounts,
            'totalPages'  => (int)ceil($data['total'] / $perPage),
        ]);
    }

    // ── GET /admin/users/create ───────────────────────────────────────────────
    public function create(array $params): void
    {
        RoleMiddleware::require(['super_admin', 'admin']);

        $this->view('admin.users.create', [
            'title'       => 'Add User — LMSAdvisor',
            'page_title'  => 'Add New User',
            'breadcrumbs' => [['label' => 'Users', 'url' => '/admin/users'], ['label' => 'Add User']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'roles'       => $this->getRoles(),
            'csrf_token'  => CsrfMiddleware::token(),
        ]);
    }

    // ── POST /admin/users/create ──────────────────────────────────────────────
    public function store(array $params): void
    {
        RoleMiddleware::require(['super_admin', 'admin']);
        CsrfMiddleware::verify();

        $firstName = Sanitizer::string($this->request->post('first_name', ''), 80);
        $lastName  = Sanitizer::string($this->request->post('last_name', ''), 80);
        $email     = Sanitizer::email($this->request->post('email', ''));
        $roleId    = (int)$this->request->post('role_id', 4);
        $password  = (string)$this->request->post('password', '');
        $isActive  = (int)(bool)$this->request->post('is_active', 1);

        $v = (new Validator())
            ->required('first_name', $firstName, 'First name')
            ->required('last_name',  $lastName,  'Last name')
            ->required('email',      $email,     'Email')
            ->email('email',         $email)
            ->required('password',   $password,  'Password')
            ->minLength('password',  $password,  8, 'Password');

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/admin/users/create');
        }

        // Duplicate email check
        if ($this->userModel->findByEmail($email)) {
            $this->flash('error', 'A user with this email already exists.');
            $this->redirect('/admin/users/create');
        }

        // Super admin cannot be set by non-super_admin
        if ($roleId === 1 && !AuthService::isSuperAdmin()) {
            $this->flash('error', 'Only a Super Admin can assign the Super Admin role.');
            $this->redirect('/admin/users/create');
        }

        $newId = $this->userModel->create([
            'uuid'              => Uuid::v4(),
            'role_id'           => $roleId,
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'email'             => $email,
            'password_hash'     => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'is_active'         => $isActive,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        AuditLog::write('user.create', 'user', $newId, null, ['email' => $email]);

        $this->flash('success', 'User created successfully.');
        $this->redirect('/admin/users');
    }

    // ── GET /admin/users/:uuid/edit ───────────────────────────────────────────
    public function edit(array $params): void
    {
        RoleMiddleware::require(['super_admin', 'admin']);

        $user = $this->userModel->findByUuidWithRole($params['uuid'] ?? '');
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('/admin/users');
        }

        // Non-super_admin cannot edit super_admin
        if ($user['role_name'] === 'super_admin' && !AuthService::isSuperAdmin()) {
            $this->flash('error', 'Permission denied.');
            $this->redirect('/admin/users');
        }

        $this->view('admin.users.edit', [
            'title'       => 'Edit User — LMSAdvisor',
            'page_title'  => 'Edit User',
            'breadcrumbs' => [
                ['label' => 'Users', 'url' => '/admin/users'],
                ['label' => 'Edit: ' . $user['first_name'] . ' ' . $user['last_name']],
            ],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'user'        => $user,
            'roles'       => $this->getRoles(),
            'csrf_token'  => CsrfMiddleware::token(),
            'canImpersonate' => (
                AuthService::isAdmin() &&
                $user['role_name'] !== 'super_admin' &&
                $user['id'] !== AuthService::user()['id']
            ),
        ]);
    }

    // ── POST /admin/users/:uuid/edit ──────────────────────────────────────────
    public function update(array $params): void
    {
        RoleMiddleware::require(['super_admin', 'admin']);
        CsrfMiddleware::verify();

        $user = $this->userModel->findByUuidWithRole($params['uuid'] ?? '');
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('/admin/users');
        }

        $firstName = Sanitizer::string($this->request->post('first_name', ''), 80);
        $lastName  = Sanitizer::string($this->request->post('last_name', ''), 80);
        $email     = Sanitizer::email($this->request->post('email', ''));
        $roleId    = (int)$this->request->post('role_id', $user['role_id']);
        $isActive  = (int)(bool)$this->request->post('is_active', 1);
        $password  = (string)$this->request->post('password', '');

        $v = (new Validator())
            ->required('first_name', $firstName, 'First name')
            ->required('last_name',  $lastName,  'Last name')
            ->required('email',      $email,     'Email')
            ->email('email',         $email);

        if ($password !== '') {
            $v->minLength('password', $password, 8, 'Password');
        }

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/admin/users/' . $params['uuid'] . '/edit');
        }

        // Duplicate email check (exclude current user)
        $existing = $this->userModel->findByEmail($email);
        if ($existing && (int)$existing['id'] !== (int)$user['id']) {
            $this->flash('error', 'Another user with this email already exists.');
            $this->redirect('/admin/users/' . $params['uuid'] . '/edit');
        }

        // Protect super_admin role assignment
        if ($roleId === 1 && !AuthService::isSuperAdmin()) {
            $roleId = (int)$user['role_id'];
        }

        $updateData = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'role_id'    => $roleId,
            'is_active'  => $isActive,
        ];

        if ($password !== '') {
            $updateData['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $old = ['email' => $user['email'], 'role_id' => $user['role_id']];
        $this->userModel->update((int)$user['id'], $updateData);

        AuditLog::write('user.update', 'user', (int)$user['id'], $old, $updateData);

        $this->flash('success', 'User updated successfully.');
        $this->redirect('/admin/users/' . $params['uuid'] . '/edit');
    }

    // ── POST /admin/users/:uuid/delete ────────────────────────────────────────
    public function delete(array $params): void
    {
        RoleMiddleware::require(['super_admin', 'admin']);
        CsrfMiddleware::verify();

        $user = $this->userModel->findByUuidWithRole($params['uuid'] ?? '');
        if (!$user) {
            $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        // Cannot delete self
        if ((int)$user['id'] === (int)(AuthService::user()['id'] ?? 0)) {
            $this->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
        }

        // Cannot delete super_admin unless you are super_admin
        if ($user['role_name'] === 'super_admin' && !AuthService::isSuperAdmin()) {
            $this->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        AuditLog::write('user.delete', 'user', (int)$user['id'], ['email' => $user['email']]);
        $this->userModel->delete((int)$user['id']);

        $this->json(['success' => true, 'message' => 'User deleted.']);
    }

    // ── POST /admin/users/:uuid/toggle-active ─────────────────────────────────
    public function toggleActive(array $params): void
    {
        RoleMiddleware::require(['super_admin', 'admin']);
        CsrfMiddleware::verify();

        $user = $this->userModel->findByUuidWithRole($params['uuid'] ?? '');
        if (!$user) {
            $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $this->userModel->update((int)$user['id'], ['is_active' => $newStatus]);
        AuditLog::write('user.' . ($newStatus ? 'activate' : 'deactivate'), 'user', (int)$user['id']);

        $this->json(['success' => true, 'is_active' => $newStatus]);
    }

    // ── POST /admin/users/:uuid/impersonate ───────────────────────────────────
    public function impersonate(array $params): void
    {
        RoleMiddleware::require(['super_admin', 'admin']);
        CsrfMiddleware::verify();

        $user = $this->userModel->findByUuidWithRole($params['uuid'] ?? '');
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('/admin/users');
        }

        try {
            ImpersonationService::impersonate((int)$user['id']);
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/admin/users');
        }

        // Redirect to the appropriate portal for the impersonated user's role
        $role = $user['role_name'];
        if (in_array($role, ['admin', 'super_admin', 'manager'], true)) {
            $this->redirect('/admin/dashboard');
        } else {
            $this->redirect('/learn/dashboard');
        }
    }

    // ── POST /admin/impersonate/revert ────────────────────────────────────────
    public function revertImpersonation(array $params): void
    {
        ImpersonationService::revert();
        $this->flash('success', 'Returned to your admin account.');
        $this->redirect('/admin/users');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function getRoles(): array
    {
        $pdo   = \App\Core\Database::getInstance();
        $stmt  = $pdo->query('SELECT * FROM roles ORDER BY id');
        return $stmt->fetchAll();
    }
}

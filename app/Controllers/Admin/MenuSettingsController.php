<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\MenuService;
use App\Services\AuthService;

class MenuSettingsController extends Controller
{
    public function index(array $p): void
    {
        AuthMiddleware::handle();
        $user = AuthService::user();

        // Only super_admin can manage menu permissions
        $userRole = $user['role_name'] ?? $user['role'] ?? '';
        if ($userRole !== 'super_admin') {
            $this->flash('error', 'Only Super Admins can manage menu permissions.');
            $this->redirect('/admin/dashboard');
        }

        $pdo  = \App\Core\Database::getInstance();
        $perms = $pdo->query('SELECT * FROM menu_permissions ORDER BY sort_order')->fetchAll();

        $this->view('admin.menu_settings.index', [
            'title'      => 'Menu Permissions',
            'perms'      => $perms,
            'allItems'   => MenuService::allItems(),
            'roles'      => MenuService::roles(),
            'flash'      => $this->getFlash(),
            'auth_user'  => $user,
            'csrf_token' => CsrfMiddleware::token(),
        ]);
    }

    public function save(array $p): void
    {
        AuthMiddleware::handle();
        CsrfMiddleware::verify();

        $user = AuthService::user();
        if (($user['role_name'] ?? $user['role'] ?? '') !== 'super_admin') {
            $this->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $pdo      = \App\Core\Database::getInstance();
        $allItems = MenuService::allItems();
        $roles    = MenuService::roles();
        $saved    = 0;

        foreach ($allItems as $key => $_) {
            // Get submitted roles for this menu item
            $allowedRoles = [];
            foreach ($roles as $role) {
                if ($role === 'super_admin') continue; // super_admin always included
                if (!empty($_POST['menu'][$key][$role])) {
                    $allowedRoles[] = $role;
                }
            }
            // super_admin always sees everything — always include
            array_unshift($allowedRoles, 'super_admin');

            $pdo->prepare(
                'UPDATE menu_permissions SET roles=? WHERE menu_key=?'
            )->execute([json_encode(array_values(array_unique($allowedRoles))), $key]);
            $saved++;
        }

        $this->flash('success', "Menu permissions saved. {$saved} items updated.");
        $this->redirect('/admin/menu-settings');
    }

    public function reset(array $p): void
    {
        AuthMiddleware::handle();
        CsrfMiddleware::verify();

        $user = AuthService::user();
        if (($user['role_name'] ?? $user['role'] ?? '') !== 'super_admin') {
            $this->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        // Re-run the seed defaults
        $pdo = \App\Core\Database::getInstance();
        $defaults = [
            'dashboard'      => ['super_admin','admin','manager','instructor'],
            'analytics'      => ['super_admin','admin'],
            'learner_data'   => ['super_admin','admin'],
            'at_risk'        => ['super_admin','admin'],
            'courses'        => ['super_admin','admin','manager','instructor'],
            'learning_paths' => ['super_admin','admin','manager'],
            'groups'         => ['super_admin','admin','manager'],
            'assignments'    => ['super_admin','admin','manager','instructor'],
            'badges'         => ['super_admin','admin'],
            'email'          => ['super_admin','admin'],
            'enrollments'    => ['super_admin','admin','manager'],
            'users'          => ['super_admin','admin'],
            'categories'     => ['super_admin','admin'],
            'quizzes'        => ['super_admin','admin','manager','instructor'],
            'forum'          => ['super_admin','admin','manager'],
            'reviews'        => ['super_admin','admin'],
            'leaderboard'    => ['super_admin','admin','manager'],
            'knowledge_base' => ['super_admin','admin','manager'],
            'webinars'       => ['super_admin','admin','manager','instructor'],
            'reports'        => ['super_admin','admin'],
            'api'            => ['super_admin','admin'],
            'webhooks'       => ['super_admin'],
            'settings'       => ['super_admin','admin'],
            'database'       => ['super_admin'],
            'corporate'      => ['super_admin','admin'],
            'marketplace'    => ['super_admin','admin'],
            'reporting'      => ['super_admin','admin'],
            'help_center'    => ['super_admin','admin','manager','instructor'],
        ];
        foreach ($defaults as $key => $roles) {
            $pdo->prepare('UPDATE menu_permissions SET roles=? WHERE menu_key=?')
                ->execute([json_encode($roles), $key]);
        }

        $this->flash('success', 'Menu permissions reset to defaults.');
        $this->redirect('/admin/menu-settings');
    }
}

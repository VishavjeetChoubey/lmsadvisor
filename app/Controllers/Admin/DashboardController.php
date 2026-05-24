<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuthService;

class DashboardController extends Controller
{
    public function index(array $params): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);

        $user = AuthService::user();

        $this->view('admin.dashboard.index', [
            'title'       => 'Dashboard — LMSAdvisor',
            'page_title'  => 'Dashboard',
            'breadcrumbs' => [['label' => 'Dashboard']],
            'flash'       => $this->getFlash(),
            'auth_user'   => $user,
        ], 'admin');
    }
}

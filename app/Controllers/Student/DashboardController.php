<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;

class DashboardController extends Controller
{
    public function index(array $params): void
    {
        AuthMiddleware::handle();

        $user = AuthService::user();

        $this->view('student.dashboard.index', [
            'title'      => 'My Dashboard — LMSAdvisor',
            'page_title' => 'My Dashboard',
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
        ], 'student');
    }
}

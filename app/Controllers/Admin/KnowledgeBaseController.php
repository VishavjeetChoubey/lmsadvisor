<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuthService;

class KnowledgeBaseController extends Controller
{
    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
    }

    public function index(array $params): void
    {
        $this->view('admin.knowledge_base.index', [
            'title'       => 'Knowledge Base — LMSAdvisor',
            'page_title'  => 'Knowledge Base',
            'breadcrumbs' => [['label' => 'Knowledge Base']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
        ]);
    }
}

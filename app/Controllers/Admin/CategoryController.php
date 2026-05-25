<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Models\Category;
use App\Models\AuditLog;
use App\Helpers\Sanitizer;
use App\Helpers\Slug;
use App\Helpers\Validator;

class CategoryController extends Controller
{
    private Category $model;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->model = new Category();
    }

    // Alias for sidebar /admin/categories route
    public function listPage(array $params): void { $this->index($params); }

    public function index(array $params): void
    {
        $this->view('admin.categories.index', [
            'title'       => 'Categories — LMSAdvisor',
            'page_title'  => 'Course Categories',
            'breadcrumbs' => [
                ['label' => 'Courses', 'url' => '/admin/courses'],
                ['label' => 'Categories'],
            ],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'categories'  => $this->model->allWithParent(),
            'roots'       => $this->model->roots(),
        ]);
    }

    public function store(array $params): void
    {
        CsrfMiddleware::verify();
        RoleMiddleware::require(['super_admin', 'admin']);

        $name     = Sanitizer::string($this->request->post('name', ''), 120);
        $parentId = (int)$this->request->post('parent_id', 0) ?: null;

        $v = (new Validator())->required('name', $name, 'Category name');
        if ($v->fails()) {
            $this->json(['success' => false, 'message' => $v->firstError()]);
        }

        $slug = Slug::unique($name, 'categories', 'slug');
        $id   = $this->model->create($name, $slug, $parentId);
        AuditLog::write('category.create', 'category', $id, null, ['name' => $name]);

        $this->json(['success' => true, 'id' => $id, 'name' => $name, 'slug' => $slug]);
    }

    public function update(array $params): void
    {
        CsrfMiddleware::verify();
        RoleMiddleware::require(['super_admin', 'admin']);

        $id       = (int)($params['id'] ?? 0);
        $name     = Sanitizer::string($this->request->post('name', ''), 120);
        $parentId = (int)$this->request->post('parent_id', 0) ?: null;

        $v = (new Validator())->required('name', $name, 'Category name');
        if ($v->fails()) {
            $this->json(['success' => false, 'message' => $v->firstError()]);
        }

        // Prevent self-referencing
        if ($parentId === $id) {
            $this->json(['success' => false, 'message' => 'A category cannot be its own parent.']);
        }

        $slug = Slug::unique($name, 'categories', 'slug', $id);
        $this->model->update($id, $name, $slug, $parentId);
        AuditLog::write('category.update', 'category', $id);

        $this->json(['success' => true, 'name' => $name, 'slug' => $slug]);
    }

    public function delete(array $params): void
    {
        CsrfMiddleware::verify();
        RoleMiddleware::require(['super_admin', 'admin']);

        $id = (int)($params['id'] ?? 0);
        AuditLog::write('category.delete', 'category', $id);
        $this->model->delete($id);
        $this->json(['success' => true]);
    }
}

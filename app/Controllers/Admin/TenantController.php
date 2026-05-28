<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\TenantService;
use App\Services\AuthService;
use App\Helpers\Sanitizer;

class TenantController extends Controller
{
    private function guard(): void { AuthMiddleware::handle(); }

    public function index(array $p): void
    {
        $this->guard();
        $page = max(1, (int)$this->request->get('page', 1));
        $data = TenantService::all($page, 20);
        $this->view('admin.tenants.index', [
            'title'    => 'Tenants',
            'tenants'  => $data['data'],
            'total'    => $data['total'],
            'page'     => $page,
            'flash'    => $this->getFlash(),
            'auth_user'=> AuthService::user(),
        ]);
    }

    public function create(array $p): void
    {
        $this->guard();
        $this->view('admin.tenants.form', [
            'title'    => 'New Tenant',
            'tenant'   => null,
            'flash'    => $this->getFlash(),
            'csrf_token'=> CsrfMiddleware::token(),
            'auth_user'=> AuthService::user(),
        ]);
    }

    public function store(array $p): void
    {
        $this->guard(); CsrfMiddleware::verify();
        $name  = Sanitizer::string($this->request->post('name',''), 120);
        $slug  = preg_replace('/[^a-z0-9-]/', '-', strtolower(Sanitizer::string($this->request->post('slug',''), 80)));
        if (!$name || !$slug) { $this->flash('error','Name and slug are required.'); $this->redirect('/admin/tenants/create'); }
        TenantService::create([
            'name'         => $name,
            'slug'         => $slug,
            'custom_domain'=> Sanitizer::string($this->request->post('custom_domain',''), 255),
            'plan'         => $this->request->post('plan','trial'),
            'primary_color'=> $this->request->post('primary_color','#5b5ef6'),
            'accent_color' => $this->request->post('accent_color','#3b82f6'),
            'email_from'   => Sanitizer::email($this->request->post('email_from','')),
            'seat_limit'   => (int)$this->request->post('seat_limit',100),
            'trial_ends_at'=> $this->request->post('trial_ends_at','') ?: date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);
        $this->flash('success', "Tenant \"{$name}\" created.");
        $this->redirect('/admin/tenants');
    }

    public function edit(array $p): void
    {
        $this->guard();
        $tenant = TenantService::findByUuid($p['uuid'] ?? '');
        if (!$tenant) { $this->flash('error','Tenant not found.'); $this->redirect('/admin/tenants'); }
        $this->view('admin.tenants.form', [
            'title'    => 'Edit Tenant — ' . $tenant['name'],
            'tenant'   => $tenant,
            'stats'    => TenantService::stats((int)$tenant['id']),
            'flash'    => $this->getFlash(),
            'csrf_token'=> CsrfMiddleware::token(),
            'auth_user'=> AuthService::user(),
        ]);
    }

    public function update(array $p): void
    {
        $this->guard(); CsrfMiddleware::verify();
        $tenant = TenantService::findByUuid($p['uuid'] ?? '');
        if (!$tenant) { $this->flash('error','Not found.'); $this->redirect('/admin/tenants'); }
        TenantService::update((int)$tenant['id'], [
            'name'         => Sanitizer::string($this->request->post('name',''), 120),
            'slug'         => preg_replace('/[^a-z0-9-]/', '-', strtolower(Sanitizer::string($this->request->post('slug',''), 80))),
            'custom_domain'=> Sanitizer::string($this->request->post('custom_domain',''), 255),
            'plan'         => $this->request->post('plan','trial'),
            'status'       => $this->request->post('status','active'),
            'primary_color'=> $this->request->post('primary_color','#5b5ef6'),
            'accent_color' => $this->request->post('accent_color','#3b82f6'),
            'email_from'   => Sanitizer::email($this->request->post('email_from','')),
            'email_name'   => Sanitizer::string($this->request->post('email_name',''), 120),
            'seat_limit'   => (int)$this->request->post('seat_limit',100),
            'custom_css'   => $this->request->post('custom_css',''),
            'custom_js'    => $this->request->post('custom_js',''),
        ]);
        $this->flash('success','Tenant updated.');
        $this->redirect('/admin/tenants/' . ($p['uuid'] ?? '') . '/edit');
    }

    public function previewBranding(array $p): void
    {
        $this->guard();
        $tenant = TenantService::findByUuid($p['uuid'] ?? '');
        if (!$tenant) { http_response_code(404); exit; }
        $this->json([
            'primary_color' => $tenant['primary_color'],
            'accent_color'  => $tenant['accent_color'],
            'name'          => $tenant['name'],
            'logo_url'      => $tenant['logo_url'],
        ]);
    }
}

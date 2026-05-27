<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\WebhookService;
use App\Helpers\Sanitizer;

class WebhookController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin','admin']);
    }

    public function index(array $p): void
    {
        $this->view('admin.webhooks.index', [
            'title'       => 'Webhooks & Integrations',
            'page_title'  => 'Webhooks & Integrations',
            'breadcrumbs' => [['label'=>'Webhooks']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'flash'       => $this->getFlash(),
            'webhooks'    => WebhookService::all(),
        ]);
    }

    public function store(array $p): void
    {
        CsrfMiddleware::verify();
        WebhookService::create([
            'name'      => Sanitizer::string($this->request->post('name',''), 120),
            'url'       => trim($this->request->post('url','')),
            'secret'    => trim($this->request->post('secret','')),
            'events'    => $this->request->post('events',[]),
            'is_active' => 1,
        ]);
        $this->flash('success', 'Webhook created.');
        $this->redirect('/admin/webhooks');
    }

    public function update(array $p): void
    {
        CsrfMiddleware::verify();
        WebhookService::update((int)$p['id'], [
            'name'      => Sanitizer::string($this->request->post('name',''), 120),
            'url'       => trim($this->request->post('url','')),
            'events'    => $this->request->post('events',[]),
            'is_active' => $this->request->post('is_active','0') ? 1 : 0,
        ]);
        $this->json(['success'=>true,'message'=>'Webhook updated.']);
    }

    public function delete(array $p): void
    {
        CsrfMiddleware::verify();
        WebhookService::delete((int)$p['id']);
        $this->json(['success'=>true]);
    }

    public function test(array $p): void
    {
        CsrfMiddleware::verify();
        $ok = WebhookService::test((int)$p['id']);
        $this->json(['success'=>$ok,'message'=>$ok?'Test delivery sent.':'Webhook not found.']);
    }

    public function rotateSecret(array $p): void
    {
        CsrfMiddleware::verify();
        $secret = WebhookService::rotateSecret((int)$p['id']);
        $this->json(['success'=>true,'secret'=>$secret]);
    }

    public function logs(array $p): void
    {
        $logs = WebhookService::logs((int)$p['id']);
        $this->json(['success'=>true,'logs'=>$logs]);
    }
}

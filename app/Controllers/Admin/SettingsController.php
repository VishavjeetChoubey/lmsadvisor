<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Models\AuditLog;
use App\Helpers\Sanitizer;

class SettingsController extends Controller
{
    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin']);
    }

    // ── GET /admin/settings ───────────────────────────────────────────────────
    public function index(array $params): void
    {
        $tab = Sanitizer::string($this->request->get('tab', 'general'), 30);
        if (!array_key_exists($tab, SettingsService::tabs())) {
            $tab = 'general';
        }

        $this->view('admin.settings.index', [
            'title'       => 'Settings — LMSAdvisor',
            'page_title'  => 'Settings',
            'breadcrumbs' => [['label' => 'Settings']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'activeTab'   => $tab,
            'tabs'        => SettingsService::tabs(),
            'settings'    => SettingsService::loadGroup($tab),
        ]);
    }

    // ── POST /admin/settings ──────────────────────────────────────────────────
    public function save(array $params): void
    {
        CsrfMiddleware::verify();

        $tab = Sanitizer::string($this->request->post('tab', 'general'), 30);
        if (!array_key_exists($tab, SettingsService::tabs())) {
            $tab = 'general';
        }

        try {
            // Handle file uploads (logo, favicon)
            if ($tab === 'general') {
                if (!empty($_FILES['site_logo']['name'])) {
                    SettingsService::uploadImage('site_logo', 'site_logo');
                }
                if (!empty($_FILES['site_favicon']['name'])) {
                    SettingsService::uploadImage('site_favicon', 'site_favicon');
                }
            }

            SettingsService::saveGroup($tab, $_POST);
            AuditLog::write('settings.save', 'settings', null, null, ['tab' => $tab]);

            $this->flash('success', 'Settings saved successfully.');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }

        $this->redirect('/admin/settings?tab=' . $tab);
    }

    // ── POST /admin/settings/test-email ──────────────────────────────────────
    public function testEmail(array $params): void
    {
        CsrfMiddleware::verify();

        $to = Sanitizer::email($this->request->post('test_email_to', ''));
        if (!$to) {
            $this->json(['success' => false, 'message' => 'Please enter a valid email address.']);
        }

        $result = SettingsService::sendTestEmail($to);
        $this->json($result);
    }

    /** SMTP debug test — super_admin only, shows full SMTP conversation */
    public function smtpTest(array $p): void
    {
        AuthMiddleware::handle();
        CsrfMiddleware::verify();

        $user = \App\Services\AuthService::user();
        if (($user['role'] ?? '') !== 'super_admin') {
            $this->json(['success' => false, 'message' => 'Access denied. Super admin only.']);
        }

        $toEmail = trim($this->request->post('test_email', $user['email'] ?? ''));
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Invalid email address.']);
        }

        $result = \App\Services\EmailService::testSmtp($toEmail);
        $this->json($result);
    }
}

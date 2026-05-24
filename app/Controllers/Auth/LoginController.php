<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Models\Setting;
use App\Helpers\Sanitizer;
use App\Helpers\Validator;

class LoginController extends Controller
{
    private AuthService $auth;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->auth = new AuthService();
    }

    // ── GET /login ────────────────────────────────────────────────────────────
    public function showLogin(array $params): void
    {
        AuthMiddleware::guest(); // redirect if already logged in

        $csrfToken        = CsrfMiddleware::token();
        $recaptchaEnabled = (bool)(int)Setting::get('recaptcha_enabled', 0);
        $recaptchaSiteKey = Setting::get('recaptcha_site_key', '');

        echo View::renderWithLayout('auth', 'auth.login', [
            'title'            => 'Sign In — LMSAdvisor',
            'flash'            => $this->getFlash(),
            'csrf_token'       => $csrfToken,
            'recaptcha_enabled'=> $recaptchaEnabled,
            'recaptcha_site_key'=> $recaptchaSiteKey,
        ]);
    }

    // ── POST /login ───────────────────────────────────────────────────────────
    public function handleLogin(array $params): void
    {
        AuthMiddleware::guest();
        CsrfMiddleware::verify();

        $email    = Sanitizer::email($this->request->post('email', ''));
        $password = (string)$this->request->post('password', '');

        // Basic validation
        $v = (new Validator())
            ->required('email',    $email,    'Email')
            ->email('email',       $email)
            ->required('password', $password, 'Password');

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/login');
        }

        // reCAPTCHA check
        $recaptchaEnabled = (bool)(int)Setting::get('recaptcha_enabled', 0);
        if ($recaptchaEnabled) {
            $token = (string)$this->request->post('g-recaptcha-response', '');
            if (!$this->auth->verifyRecaptcha($token)) {
                $this->flash('error', 'reCAPTCHA verification failed. Please try again.');
                $this->redirect('/login');
            }
        }

        // Attempt login
        $result = $this->auth->attempt(
            $email,
            $password,
            $this->request->ip(),
            $this->request->userAgent()
        );

        if (!$result['success']) {
            $this->flash('error', $result['error']);
            $this->redirect('/login');
        }

        // Redirect to intended URL or role-based default
        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);

        if ($intended && str_starts_with($intended, '/')) {
            $this->redirect($intended);
        }

        $role = AuthService::role();
        if (in_array($role, ['admin', 'super_admin', 'manager'], true)) {
            $this->redirect('/admin/dashboard');
        } else {
            $this->redirect('/learn/dashboard');
        }
    }

    // ── GET /logout ───────────────────────────────────────────────────────────
    public function logout(array $params): void
    {
        $this->auth->logout();
        $this->redirect('/login');
    }
}

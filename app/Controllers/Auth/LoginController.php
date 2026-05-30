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
        AuthMiddleware::guest();

        $csrfToken         = CsrfMiddleware::token();
        $recaptchaEnabled  = (bool)(int)Setting::get('recaptcha_enabled', 0);
        $recaptchaSiteKey  = Setting::get('recaptcha_site_key', '');
        $recaptchaSecret   = Setting::get('recaptcha_secret', '');
        // Only truly enabled when toggle is on AND both keys are filled in
        $recaptchaActive   = $recaptchaEnabled && $recaptchaSiteKey && $recaptchaSecret;

        echo View::renderWithLayout('auth', 'auth.login', [
            'title'             => 'Sign In — LMSAdvisor',
            'flash'             => $this->getFlash(),
            'csrf_token'        => $csrfToken,
            'recaptcha_enabled' => $recaptchaActive,
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

        $v = (new Validator())
            ->required('email',    $email,    'Email')
            ->email('email',       $email)
            ->required('password', $password, 'Password');

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/login');
        }

        // reCAPTCHA — only enforce if enabled AND both keys are configured
        $recaptchaEnabled = (bool)(int)Setting::get('recaptcha_enabled', 0);
        $recaptchaSiteKey = Setting::get('recaptcha_site_key', '');
        $recaptchaSecret  = Setting::get('recaptcha_secret', '');
        if ($recaptchaEnabled && $recaptchaSiteKey && $recaptchaSecret) {
            // Emergency bypass: set recaptcha_debug_bypass=1 in settings to skip verification
            $bypass = (bool)(int)Setting::get('recaptcha_debug_bypass', 0);
            if (!$bypass) {
                $token = (string)$this->request->post('g-recaptcha-response', '');
                if ($token === '') {
                    $this->flash('error', 'Please complete the reCAPTCHA checkbox before logging in.');
                    $this->redirect('/login');
                }
                if (!$this->auth->verifyRecaptcha($token)) {
                    $this->flash('error', 'reCAPTCHA verification failed. Please tick the checkbox and try again.');
                    $this->redirect('/login');
                }
            }
        }

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

        // ── Redirect after login ──────────────────────────────────────────────
        // $intended is the raw REQUEST_URI (e.g. /lmsadvisor-dev/admin/dashboard).
        // We strip the app subfolder prefix so redirect() doesn't double it.
        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);

        if ($intended) {
            // Strip the subfolder prefix (same logic as Request.php)
            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
            if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($intended, $scriptDir)) {
                $intended = substr($intended, strlen($scriptDir));
            }
            $intended = '/' . ltrim($intended, '/');

            // Safety: never redirect to API endpoints, login page, or non-page URLs
            $blocked = str_starts_with($intended, '/api/')
                    || str_starts_with($intended, '/login')
                    || str_starts_with($intended, '/logout')
                    || str_contains($intended, '.')  // e.g. .json, .js files
                    || strlen($intended) <= 1;

            if (!$blocked) {
                $this->redirect($intended);
            }
        }

        // Default redirect by role
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

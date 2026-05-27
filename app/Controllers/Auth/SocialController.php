<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Services\SocialAuthService;
use App\Models\Setting;

class SocialController extends Controller
{
    /** GET /auth/google */
    public function googleRedirect(array $p): void
    {
        if (!(bool)(int)Setting::get('google_sso_enabled','0')) {
            $this->flash('error','Google SSO is not enabled.');
            $this->redirect('/login');
        }
        header('Location: ' . SocialAuthService::googleAuthUrl());
        exit;
    }

    /** GET /auth/google/callback */
    public function googleCallback(array $p): void
    {
        $code  = $this->request->get('code','');
        $state = $this->request->get('state','');
        if (!$code) { $this->flash('error','Google login failed.'); $this->redirect('/login'); }
        try {
            $user = SocialAuthService::googleCallback($code, $state);
            if ($user) {
                $this->flash('success','Logged in with Google. Welcome, ' . $user['first_name'] . '!');
                $this->redirect('/learn/dashboard');
            } else {
                $this->flash('error','Could not retrieve your Google account details.');
                $this->redirect('/login');
            }
        } catch (\Throwable $e) {
            $this->flash('error', 'Google login error: ' . $e->getMessage());
            $this->redirect('/login');
        }
    }

    /** GET /auth/github */
    public function githubRedirect(array $p): void
    {
        if (!(bool)(int)Setting::get('github_sso_enabled','0')) {
            $this->flash('error','GitHub SSO is not enabled.');
            $this->redirect('/login');
        }
        header('Location: ' . SocialAuthService::githubAuthUrl());
        exit;
    }

    /** GET /auth/github/callback */
    public function githubCallback(array $p): void
    {
        $code  = $this->request->get('code','');
        $state = $this->request->get('state','');
        if (!$code) { $this->flash('error','GitHub login failed.'); $this->redirect('/login'); }
        try {
            $user = SocialAuthService::githubCallback($code, $state);
            if ($user) {
                $this->flash('success','Logged in with GitHub. Welcome, ' . $user['first_name'] . '!');
                $this->redirect('/learn/dashboard');
            } else {
                $this->flash('error','Could not retrieve your GitHub account email.');
                $this->redirect('/login');
            }
        } catch (\Throwable $e) {
            $this->flash('error', 'GitHub login error: ' . $e->getMessage());
            $this->redirect('/login');
        }
    }
}

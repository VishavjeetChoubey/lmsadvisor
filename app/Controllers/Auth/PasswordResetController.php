<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Database;
use App\Services\EmailService;
use App\Models\Setting;
use App\Helpers\Sanitizer;

class PasswordResetController extends Controller
{
    // GET /forgot-password
    public function forgot(array $p): void
    {
        $this->view('auth.forgot', [
            'title'      => 'Forgot Password',
            'flash'      => $this->getFlash(),
            'csrf_token' => \App\Middleware\CsrfMiddleware::token(),
        ], 'auth');
    }

    // POST /forgot-password
    public function sendReset(array $p): void
    {
        \App\Middleware\CsrfMiddleware::verify();
        $email = Sanitizer::email($this->request->post('email', ''));

        if (!$email) {
            $this->flash('error', 'Please enter your email address.');
            $this->redirect('/forgot-password');
        }

        $pdo  = Database::getInstance();
        $user = $pdo->prepare('SELECT id, first_name FROM users WHERE email=? AND is_active=1 LIMIT 1');
        $user->execute([$email]);
        $user = $user->fetch();

        // Always show success (don't reveal if email exists)
        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $pdo->prepare(
                'INSERT INTO password_resets (email, token, expires_at)
                 VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE token=VALUES(token), expires_at=VALUES(expires_at), used=0'
            )->execute([$email, $token, $expires]);

            $resetUrl = rtrim(APP_URL, '/') . '/reset-password?token=' . $token;

            try {
                EmailService::queue($email, $user['first_name'], 'password_reset', [
                    'student_name' => $user['first_name'],
                    'reset_url'    => $resetUrl,
                    'expires_in'   => '1 hour',
                    'site_name'    => Setting::get('site_name', 'LMSAdvisor'),
                ]);
            } catch (\Throwable $e) {
                error_log('[PasswordReset] Email failed: ' . $e->getMessage());
            }
        }

        $this->flash('success', 'If that email exists we\'ve sent a password reset link. Check your inbox.');
        $this->redirect('/forgot-password');
    }

    // GET /reset-password?token=xxx
    public function resetForm(array $p): void
    {
        $token = $this->request->get('token', '');
        $valid = $this->validateToken($token);

        $this->view('auth.reset', [
            'title'      => 'Reset Password',
            'token'      => $token,
            'valid'      => $valid,
            'flash'      => $this->getFlash(),
            'csrf_token' => \App\Middleware\CsrfMiddleware::token(),
        ], 'auth');
    }

    // POST /reset-password
    public function doReset(array $p): void
    {
        \App\Middleware\CsrfMiddleware::verify();
        $token    = $this->request->post('token', '');
        $password = $this->request->post('password', '');
        $confirm  = $this->request->post('password_confirm', '');

        if (strlen($password) < 8) {
            $this->flash('error', 'Password must be at least 8 characters.');
            $this->redirect('/reset-password?token=' . urlencode($token));
        }
        if ($password !== $confirm) {
            $this->flash('error', 'Passwords do not match.');
            $this->redirect('/reset-password?token=' . urlencode($token));
        }

        $row = $this->validateToken($token);
        if (!$row) {
            $this->flash('error', 'This link has expired or already been used. Please request a new one.');
            $this->redirect('/forgot-password');
        }

        $pdo  = Database::getInstance();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash=? WHERE email=?')
            ->execute([$hash, $row['email']]);
        $pdo->prepare('UPDATE password_resets SET used=1 WHERE token=?')
            ->execute([$token]);

        $this->flash('success', 'Password updated successfully. You can now log in.');
        $this->redirect('/login');
    }

    private function validateToken(string $token): array|false
    {
        if (!$token) return false;
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT * FROM password_resets
             WHERE token=? AND used=0 AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: false;
    }
}

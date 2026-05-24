<?php
declare(strict_types=1);

namespace App\Middleware;

class CsrfMiddleware
{
    /**
     * Generate (or return existing) CSRF token for this session.
     */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify the token submitted with a POST request.
     * Call this at the top of every POST handler.
     */
    public static function verify(): void
    {
        $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected  = $_SESSION['csrf_token'] ?? '';

        if (!$expected || !hash_equals($expected, $submitted)) {
            http_response_code(419);
            // Rotate token so next form submission works
            unset($_SESSION['csrf_token']);
            echo '<!DOCTYPE html><html><head><title>419 — CSRF Token Mismatch</title></head>'
               . '<body style="font-family:sans-serif;text-align:center;padding:60px">'
               . '<h2>Session Expired</h2><p>Please go back and try again.</p>'
               . '<a href="javascript:history.back()">← Go back</a>'
               . '</body></html>';
            exit;
        }
    }

    /**
     * Render a hidden CSRF input field.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

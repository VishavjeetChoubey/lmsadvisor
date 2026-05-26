<?php
declare(strict_types=1);

namespace App\Core;

class App
{
    private Router   $router;
    private Request  $request;
    private Response $response;

    public function __construct()
    {
        $this->request  = new Request();
        $this->response = new Response();
        $this->router   = new Router();
    }

    public function run(): void
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_name('lmsadvisor_sess');
            session_set_cookie_params([
                'lifetime' => (int)env('SESSION_LIFETIME', 7200),
                'path'     => '/',
                'domain'   => '',
                'secure'   => (bool)env('SESSION_SECURE', false),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        // Load routes
        $router = $this->router;
        require BASE_PATH . '/config/routes.php';

        // Apply rate limiting (login + API routes)
        \App\Middleware\RateLimitMiddleware::api();

        // Track page view (SOC2-compliant: hashed IPs, no PII)
        \App\Services\AnalyticsService::track(
            $this->request->path,
            $_SERVER['HTTP_X_PAGE_TITLE'] ?? ''
        );

        // Dispatch — wrap in exception handler so errors show properly
        try {
            $this->router->dispatch($this->request, $this->response);
        } catch (\Throwable $e) {
            error_log('[LMSAdvisor] Unhandled exception: ' . $e->getMessage()
                . ' in ' . $e->getFile() . ':' . $e->getLine()
                . "\n" . $e->getTraceAsString());

            if (defined('APP_ENV') && APP_ENV === 'development') {
                http_response_code(500);
                echo '<pre style="background:#1e293b;color:#f8fafc;padding:20px;font-size:13px">';
                echo '<strong style="color:#f87171">Error:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n";
                echo '<strong style="color:#93c5fd">File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . "\n\n";
                echo '<strong style="color:#86efac">Trace:</strong>' . "\n" . htmlspecialchars($e->getTraceAsString());
                echo '</pre>';
            } else {
                http_response_code(500);
                echo '<!DOCTYPE html><html><head><title>500 — Server Error</title></head><body>'
                   . '<h1>Something went wrong</h1><p>Please try again later.</p></body></html>';
            }
        }
    }
}

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

        // Load routes — $router is available in the routes file
        $router = $this->router;
        require BASE_PATH . '/config/routes.php';

        // Dispatch
        $this->router->dispatch($this->request, $this->response);
    }
}

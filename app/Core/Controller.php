<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    public function __construct(
        protected Request  $request,
        protected Response $response
    ) {}

    /**
     * Render a view wrapped in the admin layout and send to browser.
     */
    protected function view(
        string $view,
        array  $data   = [],
        string $layout = 'admin'
    ): void {
        echo View::renderWithLayout($layout, $view, $data);
    }

    /**
     * Render a raw view (no layout) and send to browser.
     */
    protected function viewRaw(string $view, array $data = []): void
    {
        echo View::render($view, $data);
    }

    /**
     * Redirect helper.
     */
    protected function redirect(string $path): never
    {
        $this->response->redirect($path);
    }

    /**
     * JSON response helper.
     */
    protected function json(mixed $data, int $status = 200): never
    {
        $this->response->json($data, $status);
    }

    /**
     * Flash a message into the session.
     */
    protected function flash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get and clear the flash messages.
     */
    protected function getFlash(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}

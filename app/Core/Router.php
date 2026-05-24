<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<array{method:string,pattern:string,handler:string,params:array}> */
    private array $routes = [];

    // ── Registration ──────────────────────────────────────────────────────────

    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, string $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, string $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, string $handler): void
    {
        // Convert :param segments to named regex groups
        // e.g. /admin/users/:uuid  →  /admin/users/(?P<uuid>[^/]+)
        $pattern = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    // ── Dispatch ──────────────────────────────────────────────────────────────

    public function dispatch(Request $request, Response $response): void
    {
        $response->sendSecurityHeaders();

        $method = $request->method;
        $path   = $request->path;

        // Support _method override for HTML forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            // Named capture groups only
            $params = array_filter(
                $matches,
                fn($k) => is_string($k),
                ARRAY_FILTER_USE_KEY
            );

            $this->callHandler($route['handler'], $request, $response, $params);
            return;
        }

        // No route matched → 404
        $this->handle404($request, $response);
    }

    // ── Handler resolution ────────────────────────────────────────────────────

    private function callHandler(
        string   $handler,
        Request  $request,
        Response $response,
        array    $params
    ): void {
        // Format: "Namespace\ControllerName@method"
        // The namespace prefix is always App\Controllers\
        if (!str_contains($handler, '@')) {
            throw new \InvalidArgumentException("Route handler must be 'Controller@method', got: $handler");
        }

        [$class, $action] = explode('@', $handler, 2);

        $fqcn = 'App\\Controllers\\' . $class;

        if (!class_exists($fqcn)) {
            error_log("[Router] Controller not found: $fqcn");
            $this->handle404($request, $response);
            return;
        }

        $controller = new $fqcn($request, $response);

        if (!method_exists($controller, $action)) {
            error_log("[Router] Method '$action' not found on $fqcn");
            $this->handle404($request, $response);
            return;
        }

        $controller->$action($params);
    }

    private function handle404(Request $request, Response $response): void
    {
        http_response_code(404);

        // Try a dedicated 404 controller, fall back to inline
        $fqcn = 'App\\Controllers\\ErrorController';
        if (class_exists($fqcn)) {
            $ctrl = new $fqcn($request, $response);
            if (method_exists($ctrl, 'notFound')) {
                $ctrl->notFound([]);
                return;
            }
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
           . '<title>404 — Page Not Found</title>'
           . '<style>body{font-family:Inter,sans-serif;display:flex;align-items:center;'
           . 'justify-content:center;min-height:100vh;margin:0;background:#f1f5f9}'
           . '.box{text-align:center}.code{font-size:6rem;font-weight:700;color:#1a56db;margin:0}'
           . '.msg{color:#64748b;margin-top:.5rem}</style></head><body>'
           . '<div class="box"><p class="code">404</p>'
           . '<p class="msg">The page you are looking for does not exist.</p>'
           . '<a href="' . APP_URL . '/admin" style="color:#1a56db">← Go to Dashboard</a>'
           . '</div></body></html>';
    }
}

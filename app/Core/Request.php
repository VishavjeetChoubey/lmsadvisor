<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly string $path;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Full URI (with query string)
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip the subfolder prefix so routes match regardless of install location.
        // SCRIPT_NAME = /lmsadvisor-dev/index.php → dirname = /lmsadvisor-dev
        // e.g. /lmsadvisor-dev/admin  →  /admin
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $scriptDir  = rtrim(dirname($scriptName), '/\\');
        $path       = parse_url($this->uri, PHP_URL_PATH) ?? '/';

        // Only strip when app lives in a subfolder (not at webroot)
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        $this->path = '/' . ltrim($path ?: '/', '/');
    }

    // ── Input helpers ──────────────────────────────────────────────────────────

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization') ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }
}

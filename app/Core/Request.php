<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly string $path;

    /** Parsed JSON body — populated once on first access */
    private ?array $jsonBody = null;
    private bool   $jsonParsed = false;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $scriptDir  = rtrim(dirname($scriptName), '/\\');
        $path       = parse_url($this->uri, PHP_URL_PATH) ?? '/';

        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        $this->path = '/' . ltrim($path ?: '/', '/');
    }

    // ── JSON body parser ────────────────────────────────────────────────────────

    /**
     * Parse JSON body once and cache it.
     * WooCommerce plugin sends Content-Type: application/json — $_POST is empty.
     */
    private function getJsonBody(): ?array
    {
        if ($this->jsonParsed) return $this->jsonBody;
        $this->jsonParsed = true;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $decoded = json_decode($raw, true);
                $this->jsonBody = is_array($decoded) ? $decoded : null;
            }
        }
        return $this->jsonBody;
    }

    // ── Input helpers ───────────────────────────────────────────────────────────

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Read a POST field. Falls back to JSON body if Content-Type is application/json.
     * This makes the API work whether the client sends form-data or JSON.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        // Form-encoded (standard browser/curl)
        if (isset($_POST[$key])) return $_POST[$key];

        // JSON body (WooCommerce plugin uses wp_json_encode)
        $json = $this->getJsonBody();
        if ($json !== null && array_key_exists($key, $json)) {
            return $json[$key];
        }

        return $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post($key) ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        $json = $this->getJsonBody() ?? [];
        return array_merge($_GET, $_POST, $json);
    }

    public function isPost(): bool  { return $this->method === 'POST'; }
    public function isGet(): bool   { return $this->method === 'GET'; }

    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function ip(): string         { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
    public function userAgent(): string  { return $_SERVER['HTTP_USER_AGENT'] ?? ''; }

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

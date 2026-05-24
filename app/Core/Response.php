<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    /** @var array<string,string> */
    private array $headers = [];

    public function setStatus(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /** Redirect and exit. */
    public function redirect(string $url): never
    {
        // Support relative paths → prepend APP_URL
        if (!str_starts_with($url, 'http')) {
            $url = APP_URL . '/' . ltrim($url, '/');
        }
        http_response_code(302);
        header('Location: ' . $url);
        exit;
    }

    /** Send standard security headers on every response. */
    public function sendSecurityHeaders(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin');
        // CSP is intentionally loose in dev; tighten in production
        if (APP_ENV !== 'development') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }

    /** Send JSON response and exit. */
    public function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($this->headers as $k => $v) {
            header("$k: $v");
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Abort with HTTP status. */
    public function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        if ($message) {
            echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        }
        exit;
    }
}

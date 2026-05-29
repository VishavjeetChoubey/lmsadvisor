<?php
declare(strict_types=1);

/**
 * Load .env file into constants/environment.
 * No Composer — plain file parse.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

loadEnv(dirname(__DIR__) . '/.env');

// ── Helper ──────────────────────────────────────────────────────────────────
function env(string $key, mixed $default = null): mixed
{
    $val = $_ENV[$key] ?? getenv($key);
    if ($val === false || $val === null || $val === '') {
        return $default;
    }
    return match (strtolower((string)$val)) {
        'true'  => true,
        'false' => false,
        'null'  => null,
        default => $val,
    };
}

// ── Core constants ───────────────────────────────────────────────────────────
define('BASE_PATH',    dirname(__DIR__));
define('APP_PATH',     BASE_PATH . '/app');
define('VIEW_PATH',    APP_PATH  . '/Views');
define('STORE_PATH',   BASE_PATH . '/storage');
define('LOG_PATH',     STORE_PATH . '/logs');

define('APP_VERSION',  '3.0.0');

define('APP_URL',    rtrim((string)env('APP_URL', 'http://localhost/lmsadvisor-dev'), '/'));
define('APP_DEBUG',  (bool)env('APP_DEBUG', false));
define('APP_ENV',    (string)env('APP_ENV', 'production'));

// ── Error display ────────────────────────────────────────────────────────────
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('UTC');

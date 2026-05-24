<?php
declare(strict_types=1);

/**
 * Custom PSR-4 autoloader.
 *
 * Namespace → Directory mapping:
 *   App\Core\         → app/Core/
 *   App\Controllers\  → app/Controllers/
 *   App\Models\       → app/Models/
 *   App\Middleware\   → app/Middleware/
 *   App\Services\     → app/Services/
 *   App\Helpers\      → app/Helpers/
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $base   = APP_PATH . '/';          // APP_PATH defined in config/app.php

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    // Strip the leading namespace prefix
    $relative = substr($class, strlen($prefix));

    // Convert namespace separators to directory separators
    $file = $base . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

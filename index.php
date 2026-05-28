<?php
declare(strict_types=1);

/**
 * LMSAdvisor — Root front controller
 */

define('ROOT_PATH', __DIR__);

require_once __DIR__ . '/config/app.php';

// Only show errors in development — NEVER on production (breaks JSON API responses)
if (defined('APP_ENV') && APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

require_once APP_PATH . '/autoload.php';

(new App\Core\App())->run();

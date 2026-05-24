<?php
declare(strict_types=1);

/**
 * LMSAdvisor — Root front controller
 *
 * Sits at /lmsadvisor-dev/index.php so Apache finds it immediately.
 * Bootstraps the app exactly like public/index.php would.
 *
 * Assets (CSS/JS/images) are served directly from public/assets/
 * via the root .htaccess rewrite rules.
 */

define('ROOT_PATH', __DIR__);

// Point the app at the real public/ directory for asset URL building
// but keep all logic in app/
require_once __DIR__ . '/config/app.php';
require_once APP_PATH . '/autoload.php';

(new App\Core\App())->run();

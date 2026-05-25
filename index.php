<?php
declare(strict_types=1);

/**
 * LMSAdvisor — Root front controller
 */

// Show errors immediately during boot (before config loads) so XAMPP doesn't hide them
ini_set('display_errors', '1');
error_reporting(E_ALL);

define('ROOT_PATH', __DIR__);

require_once __DIR__ . '/config/app.php';
require_once APP_PATH . '/autoload.php';

(new App\Core\App())->run();

<?php
declare(strict_types=1);

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/config/app.php';
require_once APP_PATH . '/autoload.php';

// ── Run application ───────────────────────────────────────────────────────────
(new App\Core\App())->run();

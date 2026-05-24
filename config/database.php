<?php
declare(strict_types=1);

return [
    'host'    => (string)env('DB_HOST', '127.0.0.1'),
    'port'    => (int)env('DB_PORT', 3306),
    'dbname'  => (string)env('DB_NAME', 'lmsadvisor'),
    'user'    => (string)env('DB_USER', 'root'),
    'pass'    => (string)env('DB_PASS', ''),
    'charset' => 'utf8mb4',
];

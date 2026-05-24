<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $cfg = require BASE_PATH . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['dbname'],
            $cfg['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
        } catch (PDOException $e) {
            // Log and show friendly error — never expose credentials
            error_log('[LMSAdvisor] DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo '<!DOCTYPE html><html><head><title>Database Error</title></head><body>'
               . '<h2 style="font-family:sans-serif;color:#e02424">Database Connection Failed</h2>'
               . '<p style="font-family:sans-serif">Please check your <code>.env</code> database settings.</p>'
               . (APP_DEBUG ? '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>' : '')
               . '</body></html>';
            exit;
        }

        return self::$instance;
    }

    /** Prevent cloning */
    private function __clone() {}
}

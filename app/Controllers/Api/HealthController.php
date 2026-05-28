<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;

class HealthController extends AuthController
{
    public function index(array $params): void
    {
        // Validate Bearer token — wrong/missing token returns 401
        $this->apiAuth();

        $dbOk = false;
        try {
            Database::getInstance()->query('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {}

        $this->json([
            'status'    => $dbOk ? 'ok' : 'degraded',
            'database'  => $dbOk ? 'connected' : 'unreachable',
            'timestamp' => date('c'),
            'version'   => '2.0',
        ], $dbOk ? 200 : 503);
    }
}

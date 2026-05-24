<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;

class ErrorController extends Controller
{
    public function notFound(array $params): void
    {
        http_response_code(404);
        echo View::renderWithLayout('auth', 'errors.404', [
            'title' => '404 — Page Not Found',
        ]);
    }
}

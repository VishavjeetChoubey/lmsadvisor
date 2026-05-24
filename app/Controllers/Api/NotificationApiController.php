<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\NotificationService;

class NotificationApiController extends Controller
{
    private function guard(): array
    {
        AuthMiddleware::handle('/login');
        return AuthService::user();
    }

    // GET  /api/notifications
    public function index(array $params): void
    {
        $user = $this->guard();
        $page = max(1, (int)$this->request->get('page', 1));
        $data = NotificationService::all((int)$user['id'], $page);
        $this->json($data);
    }

    // GET  /api/notifications/unread-count
    public function unreadCount(array $params): void
    {
        $user  = $this->guard();
        $count = NotificationService::unreadCount((int)$user['id']);
        $this->json(['count' => $count]);
    }

    // POST /api/notifications/read-all
    public function readAll(array $params): void
    {
        $user = $this->guard();
        NotificationService::markRead((int)$user['id']);
        $this->json(['success' => true]);
    }

    // POST /api/notifications/:id/read
    public function read(array $params): void
    {
        $user = $this->guard();
        NotificationService::markRead((int)$user['id'], (int)($params['id'] ?? 0));
        $this->json(['success' => true]);
    }
}

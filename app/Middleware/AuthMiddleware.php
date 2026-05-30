<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware
{
    /**
     * Require the user to be logged in.
     * Also verifies the session user ID still exists in the DB —
     * this catches stale sessions after `migrate.php fresh`.
     */
    public static function handle(string $redirectTo = '/login'): void
    {
        if (!AuthService::check()) {
            $uri    = $_SERVER['REQUEST_URI'] ?? '/';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

            // Only save intended URL for GET page requests — not API calls, not POSTs.
            // API routes (/api/...) firing from JS fetch() would redirect back to a JSON
            // endpoint after login, breaking the user experience.
            $isApiRequest  = str_contains($uri, '/api/');
            $isPageRequest = $method === 'GET' && !$isApiRequest;

            if ($isPageRequest) {
                $_SESSION['intended'] = $uri;
            }

            // API calls get JSON 401 instead of redirect
            if ($isApiRequest) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthenticated']);
                exit;
            }

            $_SESSION['flash']['warning'] = 'Please sign in to continue.';
            header('Location: ' . APP_URL . $redirectTo);
            exit;
        }

        // Validate session user ID still exists in DB.
        // Cheap: runs once per request, single indexed PK lookup.
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            try {
                $pdo  = \App\Core\Database::getInstance();
                $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                if (!$stmt->fetch()) {
                    // User no longer in DB (e.g. after migrate fresh) — force logout
                    self::forceLogout($redirectTo);
                }
            } catch (\Throwable) {
                // DB unreachable — don't kill the session, just continue
            }
        }
    }

    /**
     * Redirect already-logged-in users away from auth pages (login, register).
     */
    public static function guest(): void
    {
        if (AuthService::check()) {
            $role = AuthService::role();
            $dest = in_array($role, ['admin', 'super_admin', 'manager'], true)
                ? '/admin/dashboard'
                : '/learn/dashboard';
            header('Location: ' . APP_URL . $dest);
            exit;
        }
    }

    /**
     * Destroy the current session and redirect to login.
     * Used when the session is stale/invalid.
     */
    private static function forceLogout(string $redirectTo): never
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        // Restart session so flash message survives the redirect
        session_start();
        $_SESSION['flash']['warning'] = 'Your session has expired or the database was reset. Please log in again.';

        header('Location: ' . APP_URL . $redirectTo);
        exit;
    }
}

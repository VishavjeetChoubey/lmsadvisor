<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\LeaderboardService;
use App\Models\AuditLog;
use App\Helpers\Sanitizer;

class LeaderboardController extends Controller
{
    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
    }

    // ── GET /admin/leaderboard ────────────────────────────────────────────────
    public function index(array $params): void
    {
        $top  = LeaderboardService::topN(50);
        $pdo  = \App\Core\Database::getInstance();

        // Recent point awards
        $recent = $pdo->query(
            'SELECT gp.*, u.first_name, u.last_name, u.email, c.title AS course_title
             FROM grade_points gp
             JOIN users u ON u.id = gp.user_id
             LEFT JOIN courses c ON c.id = gp.course_id
             ORDER BY gp.created_at DESC
             LIMIT 30'
        )->fetchAll();

        // Per-course leaderboard
        $courseStmt = $pdo->query(
            'SELECT c.id, c.title, c.uuid,
                    COUNT(DISTINCT gp.user_id) AS earners,
                    SUM(gp.points) AS total_points_awarded
             FROM courses c
             LEFT JOIN grade_points gp ON gp.course_id = c.id
             WHERE c.status = "published"
             GROUP BY c.id
             HAVING total_points_awarded > 0
             ORDER BY total_points_awarded DESC
             LIMIT 10'
        )->fetchAll();

        $this->view('admin.leaderboard.index', [
            'title'       => 'Leaderboard — LMSAdvisor',
            'page_title'  => 'Leaderboard',
            'breadcrumbs' => [['label' => 'Leaderboard']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'top'         => $top,
            'recent'      => $recent,
            'courseStats' => $courseStmt,
        ]);
    }

    // ── POST /admin/leaderboard/award ─────────────────────────────────────────
    public function award(array $params): void
    {
        CsrfMiddleware::verify();
        RoleMiddleware::require(['super_admin', 'admin']);

        $userId   = (int)$this->request->post('user_id', 0);
        $courseId = (int)$this->request->post('course_id', 0) ?: null;
        $points   = (int)$this->request->post('points', 0);
        $reason   = Sanitizer::string($this->request->post('reason', ''), 191);

        if (!$userId || $points === 0) {
            $this->json(['success' => false, 'message' => 'User and points are required.']);
        }
        if ($points < -9999 || $points > 9999) {
            $this->json(['success' => false, 'message' => 'Points must be between -9999 and 9999.']);
        }

        LeaderboardService::award($userId, $courseId, $points, $reason ?: 'manual_award');
        AuditLog::write('leaderboard.manual_award', 'user', $userId, null,
            ['points' => $points, 'reason' => $reason]);

        $this->json([
            'success' => true,
            'message' => ($points > 0 ? '+' : '') . $points . ' points awarded.',
        ]);
    }

    // ── POST /admin/leaderboard/reset ─────────────────────────────────────────
    public function reset(array $params): void
    {
        CsrfMiddleware::verify();
        RoleMiddleware::require(['super_admin']);

        $userId = (int)$this->request->post('user_id', 0);

        if ($userId) {
            // Reset one user
            $pdo = \App\Core\Database::getInstance();
            $pdo->prepare('DELETE FROM grade_points WHERE user_id = ?')->execute([$userId]);
            AuditLog::write('leaderboard.reset_user', 'user', $userId);
            $this->json(['success' => true, 'message' => 'Points reset for user.']);
        } else {
            $this->json(['success' => false, 'message' => 'User ID required.']);
        }
    }

    // ── GET /admin/leaderboard/data (JSON for AJAX refresh) ──────────────────
    public function data(array $params): void
    {
        $top = LeaderboardService::topN(50);
        $this->json(['success' => true, 'leaderboard' => $top]);
    }
}

<?php
declare(strict_types=1);
namespace App\Controllers\Student;

use App\Core\Controller;
use App\Core\Database;
use App\Services\GamificationService;

class PublicProfileController extends Controller
{
    public function show(array $params): void
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.avatar, u.created_at,
                    r.name AS role
             FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.uuid=? AND u.is_active=1 LIMIT 1'
        );
        $stmt->execute([$params['uuid'] ?? '']);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo '<html><body style="font-family:sans-serif;text-align:center;padding:60px"><h2>Profile not found</h2></body></html>';
            return;
        }

        $stats  = GamificationService::userStats((int)$user['id']);

        // Completed courses (public)
        $courses = $pdo->prepare(
            'SELECT c.uuid, c.title, c.thumbnail, c.level, e.completed_at
             FROM enrollments e JOIN courses c ON c.id=e.course_id
             WHERE e.user_id=? AND e.status=\'completed\'
             ORDER BY e.completed_at DESC LIMIT 12'
        );
        $courses->execute([$user['id']]);
        $completed = $courses->fetchAll();

        // Leaderboard rank
        $rank = $pdo->prepare(
            'SELECT COUNT(*)+1 FROM (
               SELECT user_id, SUM(points) AS total
               FROM grade_points GROUP BY user_id
               HAVING total > (SELECT COALESCE(SUM(p),0) FROM (SELECT points AS p FROM grade_points WHERE user_id=?) sub)
             ) ranked'
        );
        $rank->execute([$user['id']]);
        $leaderboardRank = (int)$rank->fetchColumn();

        $avatarUrl = !empty($user['avatar'])
            ? APP_URL . '/storage/uploads/avatars/' . $user['avatar']
            : null;

        $this->view('student.profile.public', [
            'title'          => $user['first_name'] . ' ' . $user['last_name'] . ' — LMSAdvisor',
            'page_title'     => $user['first_name'] . '\'s Profile',
            'profile_user'   => $user,
            'stats'          => $stats,
            'completed'      => $completed,
            'avatar_url'     => $avatarUrl,
            'rank'           => $leaderboardRank,
            'auth_user'      => \App\Services\AuthService::user() ?? [],
        ], 'student');
    }
}

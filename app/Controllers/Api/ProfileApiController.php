<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\User;
use App\Models\Setting;
use App\Core\Database;

class ProfileApiController extends AuthController
{
    // GET /api/v1/profile
    public function show(array $params): void
    {
        $user    = $this->apiAuth();
        $model   = new User();
        $full    = $model->findWithRole((int)$user['id']);
        unset($full['password_hash']);
        $this->json(['data'=>$full]);
    }

    // GET /api/v1/leaderboard
    public function leaderboard(array $params): void
    {
        $this->apiAuth();
        $pdo  = Database::getInstance();
        $stmt = $pdo->query('SELECT u.uuid,u.first_name,u.last_name,COALESCE(SUM(gp.points),0) AS total_points,
          (SELECT COUNT(*) FROM enrollments e WHERE e.user_id=u.id AND e.status="completed") AS courses_completed
          FROM users u LEFT JOIN grade_points gp ON gp.user_id=u.id GROUP BY u.id
          HAVING total_points > 0 ORDER BY total_points DESC LIMIT 50');
        $this->json(['data'=>$stmt->fetchAll()]);
    }

    // GET /api/v1/health (update existing)
    public function health(array $params): void
    {
        $this->json([
            'status'  => 'ok',
            'version' => '1.0',
            'time'    => date('c'),
        ]);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Models\Enrollment;

class EnrollmentApiController extends AuthController
{
    // GET /api/v1/enrollments
    public function index(array $params): void
    {
        $user = $this->apiAuth();
        $model = new Enrollment();
        $this->json(['data' => $model->forUser((int)$user['id'])]);
    }

    // POST /api/v1/enrollments
    public function store(array $params): void
    {
        $user = $this->apiAuth();
        if (!in_array($user['role_name'],['admin','super_admin','manager'])) {
            http_response_code(403); $this->json(['error'=>'Insufficient permissions.']);
        }
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id FROM courses WHERE uuid=? AND status="published" LIMIT 1');
        $stmt->execute([$this->request->post('course_uuid','')]);
        $course = $stmt->fetch();
        if (!$course) { http_response_code(404); $this->json(['error'=>'Course not found.']); }
        $targetUserId = (int)$this->request->post('user_id', $user['id']);
        $model = new Enrollment();
        if ($model->findEnrollment((int)$course['id'], $targetUserId)) {
            $this->json(['message'=>'Already enrolled.']);
        }
        $model->enroll((int)$course['id'], $targetUserId, (int)$user['id']);
        $this->json(['message'=>'Enrolled successfully.']);
    }
}

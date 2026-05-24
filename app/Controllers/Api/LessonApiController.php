<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Models\Enrollment;

class LessonApiController extends AuthController
{
    // POST /api/v1/lessons/:id/complete
    public function complete(array $params): void
    {
        $user     = $this->apiAuth();
        $lessonId = (int)($params['id']??0);
        $pdo      = Database::getInstance();

        $stmt = $pdo->prepare('SELECT l.*,c.id AS course_id FROM lessons l JOIN courses c ON c.id=l.course_id WHERE l.id=? LIMIT 1');
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();
        if (!$lesson) { http_response_code(404); $this->json(['error'=>'Lesson not found.']); }

        $model  = new Enrollment();
        $enroll = $model->findEnrollment((int)$lesson['course_id'],(int)$user['id']);
        if (!$enroll) { http_response_code(403); $this->json(['error'=>'Not enrolled.']); }

        $model->markLessonProgress((int)$enroll['id'],$lessonId,'completed',100);
        $this->json(['success'=>true,'message'=>'Lesson marked complete.']);
    }
}

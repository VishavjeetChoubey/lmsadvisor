<?php declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Database;
class QuizApiController extends AuthController {
    // GET /api/v1/courses/:uuid/quizzes
    public function index(array $params): void {
        $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT q.*,l.title AS lesson_title FROM quizzes q JOIN lessons l ON l.id=q.lesson_id JOIN courses c ON c.id=l.course_id WHERE c.uuid=? ORDER BY l.sort_order');
        $stmt->execute([$params['uuid']??'']);
        $this->json(['data'=>$stmt->fetchAll()]);
    }
    // GET /api/v1/quizzes/:id/results  (my attempts)
    public function results(array $params): void {
        $user = $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT qa.*,q.title AS quiz_title FROM quiz_attempts qa JOIN quizzes q ON q.id=qa.quiz_id WHERE qa.quiz_id=? AND qa.user_id=? ORDER BY qa.started_at DESC LIMIT 10');
        $stmt->execute([$params['id']??0,$user['id']]);
        $this->json(['data'=>$stmt->fetchAll()]);
    }
}

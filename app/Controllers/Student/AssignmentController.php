<?php
declare(strict_types=1);
namespace App\Controllers\Student;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\AssignmentService;
use App\Core\Database;

class AssignmentController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
    }

    public function show(array $p): void
    {
        $user   = AuthService::user();
        $pdo    = Database::getInstance();
        $stmt   = $pdo->prepare(
            'SELECT l.*, c.uuid AS course_uuid, c.title AS course_title,
                    e.id AS enrollment_id
             FROM lessons l JOIN courses c ON c.id=l.course_id
             JOIN enrollments e ON e.course_id=c.id AND e.user_id=?
             WHERE c.uuid=? AND l.id=? LIMIT 1'
        );
        $stmt->execute([(int)$user['id'], $p['uuid'], (int)$p['lesson_id']]);
        $lesson = $stmt->fetch();
        if (!$lesson) { $this->redirect('/learn/courses'); }

        $assignment  = AssignmentService::getByLesson((int)$p['lesson_id']);
        $submissions = $assignment
            ? AssignmentService::mySubmissions((int)$user['id'], $assignment['id'])
            : [];

        $this->view('student.assignments.show', [
            'title'       => 'Assignment: ' . ($assignment['title'] ?? 'Assignment'),
            'page_title'  => $assignment['title'] ?? 'Assignment',
            'auth_user'   => $user,
            'lesson'      => $lesson,
            'assignment'  => $assignment,
            'submissions' => $submissions,
            'csrf_token'  => CsrfMiddleware::token(),
        ], 'student');
    }

    public function submit(array $p): void
    {
        CsrfMiddleware::verify();
        $user = AuthService::user();
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT e.id FROM enrollments e
             JOIN courses c ON c.id=e.course_id
             WHERE c.uuid=? AND e.user_id=? LIMIT 1'
        );
        $stmt->execute([$p['uuid'], (int)$user['id']]);
        $enrollment = $stmt->fetch();
        if (!$enrollment) { $this->json(['success'=>false,'message'=>'Not enrolled.']); }

        $assignment = AssignmentService::getByLesson((int)$p['lesson_id']);
        if (!$assignment) { $this->json(['success'=>false,'message'=>'Assignment not found.']); }

        if (empty($_FILES['file']['name'])) {
            $this->json(['success'=>false,'message'=>'Please select a file to upload.']);
        }

        try {
            $subId = AssignmentService::submit(
                $assignment['id'],
                (int)$enrollment['id'],
                (int)$user['id'],
                $_FILES['file'],
                trim($this->request->post('comment',''))
            );
            $this->json(['success'=>true,'message'=>'Submission received! Your instructor will review it.','id'=>$subId]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }
}

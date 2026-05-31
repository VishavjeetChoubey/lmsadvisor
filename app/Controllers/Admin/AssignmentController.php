<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\AssignmentService;
use App\Core\Database;

class AssignmentController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin','admin','manager']);
    }

    public function index(array $p): void
    {
        $pdo    = Database::getInstance();
        $stmt   = $pdo->prepare('SELECT id FROM courses WHERE uuid=? LIMIT 1');
        $stmt->execute([$p['uuid'] ?? '']);
        $course = $stmt->fetch();
        if (!$course) { $this->redirect('/admin/courses'); }

        $submissions = AssignmentService::submissionsForCourse($course['id']);
        $this->view('admin.assignments.review', [
            'title'       => 'Assignment Submissions',
            'page_title'  => 'Assignment Submissions',
            'breadcrumbs' => [['label'=>'Courses','url'=>'admin/courses'],['label'=>'Assignments']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'flash'       => $this->getFlash(),
            'submissions' => $submissions,
            'course_uuid' => $p['uuid'],
        ]);
    }

    public function grade(array $p): void
    {
        CsrfMiddleware::verify();
        $user    = AuthService::user();
        $score   = (int)$this->request->post('score', 0);
        $feedback= trim($this->request->post('feedback', ''));
        AssignmentService::grade((int)$p['sub_id'], $score, $feedback, (int)$user['id']);
        $this->json(['success'=>true,'message'=>'Submission graded.']);
    }
}

<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;

class AssignmentsOverviewController extends Controller
{
    public function index(array $p): void
    {
        AuthMiddleware::handle();
        $pdo = Database::getInstance();

        // All courses that have at least one assignment lesson with submissions
        $courses = $pdo->query(
            "SELECT c.uuid, c.title, c.status,
                    COUNT(DISTINCT l.id) AS assignment_count,
                    COUNT(DISTINCT s.id) AS submission_count,
                    COUNT(DISTINCT CASE WHEN s.status='pending' THEN s.id END) AS pending_count
             FROM courses c
             JOIN lessons l ON l.course_id=c.id AND l.type='assignment'
             LEFT JOIN assignment_submissions s ON s.lesson_id=l.id
             WHERE c.status='published'
             GROUP BY c.id
             ORDER BY pending_count DESC, c.title"
        )->fetchAll();

        $this->view('admin.assignments.index', [
            'title'     => 'Assignments',
            'courses'   => $courses,
            'flash'     => $this->getFlash(),
            'auth_user' => AuthService::user(),
        ]);
    }
}

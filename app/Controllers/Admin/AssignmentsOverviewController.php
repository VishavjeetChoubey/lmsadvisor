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
        $pdo     = Database::getInstance();
        $courses = [];
        $error   = null;

        try {
            // Check assignment_submissions table exists first
            $tableCheck = $pdo->query(
                "SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                 AND table_name = 'assignment_submissions'"
            )->fetchColumn();

            if (!$tableCheck) {
                $error = 'Run pending migrations first — the assignment_submissions table is missing. Go to Admin → Database → Run Pending Migrations.';
            } else {
                $courses = $pdo->query(
                    "SELECT c.uuid, c.title, c.status,
                            COUNT(DISTINCT a.id)  AS assignment_count,
                            COUNT(DISTINCT s.id)  AS submission_count,
                            COUNT(DISTINCT CASE WHEN s.status='submitted' THEN s.id END) AS pending_count
                     FROM courses c
                     INNER JOIN lessons l ON l.course_id = c.id AND l.type = 'assignment'
                     INNER JOIN assignments a ON a.lesson_id = l.id
                     LEFT JOIN assignment_submissions s ON s.assignment_id = a.id
                     GROUP BY c.id, c.uuid, c.title, c.status
                     ORDER BY pending_count DESC, c.title"
                )->fetchAll();
            }
        } catch (\Throwable $e) {
            $error = 'Database error: ' . $e->getMessage();
        }

        $this->view('admin.assignments.index', [
            'title'     => 'Assignments',
            'courses'   => $courses,
            'error'     => $error,
            'flash'     => $this->getFlash(),
            'auth_user' => AuthService::user(),
        ]);
    }
}

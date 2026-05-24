<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\EnrollmentService;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use App\Helpers\Sanitizer;

class EnrollmentController extends Controller
{
    private EnrollmentService $service;
    private Enrollment        $model;
    private Course            $courseModel;
    private User              $userModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->service     = new EnrollmentService();
        $this->model       = new Enrollment();
        $this->courseModel = new Course();
        $this->userModel   = new User();
    }

    // ── GET /admin/enrollments ────────────────────────────────────────────────
    public function index(array $params): void
    {
        $page     = max(1, (int)$this->request->get('page', 1));
        $search   = Sanitizer::string($this->request->get('search', ''), 100);
        $status   = Sanitizer::string($this->request->get('status', ''), 20);
        $courseId = (int)$this->request->get('course_id', 0);

        $data = $this->model->paginate($page, 25, $search, $status, $courseId);

        // Courses dropdown for filter
        $courses = $this->courseModel->paginate(1, 200);

        $this->view('admin.enrollments.index', [
            'title'        => 'Enrollments — LMSAdvisor',
            'page_title'   => 'Enrollments',
            'breadcrumbs'  => [['label' => 'Enrollments']],
            'flash'        => $this->getFlash(),
            'auth_user'    => AuthService::user(),
            'csrf_token'   => CsrfMiddleware::token(),
            'rows'         => $data['rows'],
            'total'        => $data['total'],
            'page'         => $data['page'],
            'perPage'      => $data['perPage'],
            'totalPages'   => (int)ceil($data['total'] / $data['perPage']),
            'search'       => $search,
            'statusFilter' => $status,
            'courseFilter' => $courseId,
            'statusCounts' => $this->model->countByStatus(),
            'courses'      => $courses['rows'],
        ]);
    }

    // ── POST /admin/enrollments/enroll ────────────────────────────────────────
    public function enroll(array $params): void
    {
        CsrfMiddleware::verify();

        $courseId  = (int)$this->request->post('course_id', 0);
        $userId    = (int)$this->request->post('user_id', 0);
        $expiresAt = $this->request->post('expires_at', '') ?: null;
        $authUser  = AuthService::user();

        if (!$courseId || !$userId) {
            $this->json(['success' => false, 'message' => 'Course and user are required.']);
        }

        $result = $this->service->enroll($courseId, $userId, (int)$authUser['id'], $expiresAt);
        $this->json($result);
    }

    // ── POST /admin/enrollments/:id/remove ────────────────────────────────────
    public function remove(array $params): void
    {
        CsrfMiddleware::verify();
        $id       = (int)($params['id'] ?? 0);
        $authUser = AuthService::user();
        $result   = $this->service->remove($id, (int)$authUser['id']);
        $this->json($result);
    }

    // ── POST /admin/enrollments/:id/status ────────────────────────────────────
    public function updateStatus(array $params): void
    {
        CsrfMiddleware::verify();
        $id     = (int)($params['id'] ?? 0);
        $status = Sanitizer::string($this->request->post('status', ''), 20);

        $allowed = ['active', 'completed', 'suspended', 'expired'];
        if (!in_array($status, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Invalid status.']);
        }

        if ($status === 'completed') {
            $this->service->markComplete($id);
        } else {
            $this->model->updateStatus($id, $status);
        }

        $this->json(['success' => true, 'status' => $status]);
    }

    // ── POST /admin/enrollments/csv ───────────────────────────────────────────
    public function importCsv(array $params): void
    {
        CsrfMiddleware::verify();

        $courseId = (int)$this->request->post('course_id', 0);
        $authUser = AuthService::user();

        if (!$courseId) {
            $this->flash('error', 'Please select a course before importing.');
            $this->redirect('/admin/enrollments');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please upload a CSV file.');
            $this->redirect('/admin/enrollments');
        }

        $tmpPath = $_FILES['csv_file']['tmp_name'];
        $result  = $this->service->enrollFromCsv($tmpPath, $courseId, (int)$authUser['id']);

        $msg = "Enrolled: {$result['enrolled']} · Skipped: {$result['skipped']}";
        if (!empty($result['errors'])) {
            $msg .= ' · Errors: ' . implode('; ', array_slice($result['errors'], 0, 5));
        }

        $type = $result['enrolled'] > 0 ? 'success' : 'warning';
        $this->flash($type, $msg);
        $this->redirect('/admin/enrollments?course_id=' . $courseId);
    }

    // ── GET /admin/courses/:uuid/enrollments (page view) ──────────────────────
    public function courseEnrollmentsPage(array $params): void
    {
        $course = $this->courseModel->findByUuidFull($params['uuid'] ?? '');
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('/admin/courses');
        }

        $enrollments = $this->model->forCourse((int)$course['id']);

        $this->view('admin.enrollments.course', [
            'title'       => 'Enrolled Students — ' . $course['title'],
            'page_title'  => 'Enrolled: ' . $course['title'],
            'breadcrumbs' => [
                ['label' => 'Courses', 'url' => '/admin/courses'],
                ['label' => $course['title'], 'url' => '/admin/courses/' . $course['uuid'] . '/edit'],
                ['label' => 'Enrolled Students'],
            ],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'course'      => $course,
            'enrollments' => $enrollments,
        ]);
    }

    // ── GET /admin/courses/:uuid/enrollments (AJAX — enrolled tab) ────────────
    public function courseEnrolled(array $params): void
    {
        $course = $this->courseModel->findByUuidFull($params['uuid'] ?? '');
        if (!$course) {
            $this->json(['success' => false, 'message' => 'Course not found.'], 404);
        }

        $search = Sanitizer::string($this->request->get('search', ''), 100);
        $status = Sanitizer::string($this->request->get('status', ''), 20);
        $rows   = $this->model->forCourse((int)$course['id'], $search, $status);

        $this->json(['success' => true, 'rows' => $rows, 'total' => count($rows)]);
    }

    // ── GET /admin/users/search (AJAX — user picker) ──────────────────────────
    public function searchUsers(array $params): void
    {
        $q    = Sanitizer::string($this->request->get('q', ''), 100);
        $role = Sanitizer::string($this->request->get('role', 'student'), 30);

        if (strlen($q) < 2) {
            $this->json(['users' => []]);
        }

        $pdo  = \App\Core\Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.uuid, u.first_name, u.last_name, u.email, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = 1
               AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)
             ORDER BY u.first_name, u.last_name
             LIMIT 15'
        );
        $like = "%$q%";
        $stmt->execute([$like, $like, $like]);

        $this->json(['users' => $stmt->fetchAll()]);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\ReportService;
use App\Helpers\Sanitizer;

class ReportController extends Controller
{
    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
    }

    // ── GET /admin/reports ────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $tab  = Sanitizer::string($this->request->get('tab', 'overview'), 20);
        $tabs = ['overview', 'courses', 'users', 'enrollments', 'audit'];
        if (!in_array($tab, $tabs, true)) $tab = 'overview';

        $days   = (int)$this->request->get('days', 30);
        $page   = max(1, (int)$this->request->get('page', 1));
        $search = Sanitizer::string($this->request->get('search', ''), 100);

        $viewData = [
            'title'       => 'Reports — LMSAdvisor',
            'page_title'  => 'Reports & Analytics',
            'breadcrumbs' => [['label' => 'Reports']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'activeTab'   => $tab,
            'days'        => $days,
            'search'      => $search,
            'page'        => $page,
        ];

        switch ($tab) {
            case 'overview':
                $viewData['stats']           = ReportService::overviewStats();
                $viewData['enrollTrend']     = ReportService::enrollmentsTrend($days);
                $viewData['usersTrend']      = ReportService::usersTrend($days);
                $viewData['quizTrend']       = ReportService::quizAttemptsTrend($days);
                break;

            case 'courses':
                $data = ReportService::courseReport($page, 25, $search);
                $viewData = array_merge($viewData, $data, ['totalPages' => (int)ceil($data['total'] / 25)]);
                break;

            case 'users':
                $role = Sanitizer::string($this->request->get('role', ''), 30);
                $data = ReportService::userReport($page, 25, $search, $role);
                $viewData = array_merge($viewData, $data, [
                    'totalPages' => (int)ceil($data['total'] / 25),
                    'roleFilter' => $role,
                ]);
                break;

            case 'enrollments':
                $status = Sanitizer::string($this->request->get('status', ''), 20);
                $data   = ReportService::enrollmentReport($page, 25, $search, $status);
                $viewData = array_merge($viewData, $data, [
                    'totalPages'   => (int)ceil($data['total'] / 25),
                    'statusFilter' => $status,
                ]);
                break;

            case 'audit':
                $action = Sanitizer::string($this->request->get('action', ''), 40);
                $data   = ReportService::auditLog($page, 30, $search, $action);
                $viewData = array_merge($viewData, $data, [
                    'totalPages'    => (int)ceil($data['total'] / 30),
                    'actionFilter'  => $action,
                    'actionGroups'  => ReportService::auditActionGroups(),
                ]);
                break;
        }

        $this->view('admin.reports.index', $viewData);
    }

    // ── GET /admin/reports/export/:type ───────────────────────────────────────
    public function export(array $params): void
    {
        $type   = Sanitizer::string($params['type'] ?? '', 20);
        $search = Sanitizer::string($this->request->get('search', ''), 100);
        $today  = date('Y-m-d');

        switch ($type) {
            case 'courses':
                $data = ReportService::courseReport(1, 99999, $search);
                ReportService::streamCsv(
                    "courses-report-$today.csv",
                    ['Title', 'Category', 'Status', 'Level', 'Enrollments', 'Completions', 'Avg Rating', 'Reviews', 'Lessons', 'Published At'],
                    $data['rows'],
                    ['title', 'category', 'status', 'level', 'enrollments', 'completions', 'avg_rating', 'review_count', 'lessons', 'published_at']
                );

            case 'users':
                $role = Sanitizer::string($this->request->get('role', ''), 30);
                $data = ReportService::userReport(1, 99999, $search, $role);
                ReportService::streamCsv(
                    "users-report-$today.csv",
                    ['First Name', 'Last Name', 'Email', 'Role', 'Active', 'Enrollments', 'Completions', 'Points', 'Forum Posts', 'Joined'],
                    $data['rows'],
                    ['first_name', 'last_name', 'email', 'role_display', 'is_active', 'enrollments', 'completions', 'total_points', 'forum_posts', 'created_at']
                );

            case 'enrollments':
                $status = Sanitizer::string($this->request->get('status', ''), 20);
                $data   = ReportService::enrollmentReport(1, 99999, $search, $status);
                ReportService::streamCsv(
                    "enrollments-report-$today.csv",
                    ['Student', 'Email', 'Course', 'Status', 'Progress %', 'Enrolled At', 'Completed At', 'Expires At'],
                    $data['rows'],
                    ['first_name', 'email', 'course_title', 'status', 'progress_pct', 'enrolled_at', 'completed_at', 'expires_at']
                );

            default:
                $this->flash('error', 'Unknown export type.');
                $this->redirect('/admin/reports');
        }
    }

    // ── GET /admin/reports/chart-data (AJAX) ──────────────────────────────────
    public function chartData(array $params): void
    {
        $days = max(7, min(365, (int)$this->request->get('days', 30)));
        $this->json([
            'success'       => true,
            'enrollments'   => ReportService::enrollmentsTrend($days),
            'users'         => ReportService::usersTrend($days),
            'quiz_attempts' => ReportService::quizAttemptsTrend($days),
        ]);
    }
}

<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\AdvancedAnalyticsService;
use App\Core\Database;

class LearnerAnalyticsController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin','admin','manager']);
    }

    /** GET /admin/learner-analytics */
    public function index(array $p): void
    {
        $days       = max(7, min(90, (int)$this->request->get('days', 30)));
        $engagement = AdvancedAnalyticsService::engagementScores($days);
        $atRisk     = AdvancedAnalyticsService::atRisk(14);

        $pdo = Database::getInstance();
        $courses = $pdo->query(
            'SELECT id, uuid, title FROM courses WHERE status=\'published\' ORDER BY title'
        )->fetchAll();

        $this->view('admin.learner_analytics.index', [
            'title'       => 'Learner Analytics',
            'page_title'  => 'Advanced Learner Analytics',
            'breadcrumbs' => [['label'=>'Learner Analytics']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'days'        => $days,
            'engagement'  => $engagement,
            'at_risk'     => $atRisk,
            'courses'     => $courses,
        ]);
    }

    /** GET /admin/learner-analytics/course/:uuid */
    public function course(array $p): void
    {
        $pdo    = Database::getInstance();
        $stmt   = $pdo->prepare('SELECT * FROM courses WHERE uuid=? LIMIT 1');
        $stmt->execute([$p['uuid'] ?? '']);
        $course = $stmt->fetch();
        if (!$course) { $this->redirect('/admin/learner-analytics'); }

        $stats   = AdvancedAnalyticsService::courseStats($course['id']);
        $funnel  = AdvancedAnalyticsService::completionFunnel($course['id']);
        $times   = AdvancedAnalyticsService::avgLessonTime($course['id']);
        $hardest = AdvancedAnalyticsService::quizHardestQuestions($course['id'], 8);
        $grades  = AdvancedAnalyticsService::gradeBook($course['id']);

        $this->view('admin.learner_analytics.course', [
            'title'       => 'Analytics — ' . $course['title'],
            'page_title'  => 'Course Analytics',
            'breadcrumbs' => [
                ['label'=>'Learner Analytics','url'=>'admin/learner-analytics'],
                ['label'=>$course['title']],
            ],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'course'      => $course,
            'stats'       => $stats,
            'funnel'      => $funnel,
            'times'       => $times,
            'hardest'     => $hardest,
            'grades'      => $grades,
        ]);
    }
}

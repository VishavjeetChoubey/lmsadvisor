<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Services\ReportingService;
use App\Services\AuthService;

class ReportingController extends Controller
{
    private function guard(): void { AuthMiddleware::handle(); }

    public function index(array $p): void
    {
        $this->guard();
        $this->view('admin.reporting.index', [
            'title'      => 'Executive Reports',
            'kpis'       => ReportingService::kpis(),
            'topCourses' => ReportingService::topCourses(),
            'ltv'        => ReportingService::studentLtv(),
            'cohorts'    => ReportingService::cohortRetention(),
            'categories' => ReportingService::categoryBreakdown(),
            'enrollment_trend' => ReportingService::enrollmentTrend(60),
            'completion_trend' => ReportingService::completionTrend(60),
            'flash'      => $this->getFlash(),
            'auth_user'  => AuthService::user(),
        ]);
    }

    public function chartData(array $p): void
    {
        $this->guard();
        $type = $this->request->get('type', 'enrollment');
        $data = match($type) {
            'enrollment' => ReportingService::enrollmentTrend(60),
            'completion' => ReportingService::completionTrend(60),
            'cohort'     => ReportingService::cohortRetention(),
            'categories' => ReportingService::categoryBreakdown(),
            default      => [],
        };
        $this->json(['success'=>true,'data'=>$data]);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin']);
    }

    public function index(array $params): void
    {
        $days = max(1, min(365, (int)$this->request->get('days', 30)));

        $overview  = AnalyticsService::overview($days);
        $topPages  = AnalyticsService::topPages($days, 15);
        $devices   = AnalyticsService::devices($days);
        $browsers  = AnalyticsService::browsers($days);
        $countries = AnalyticsService::countries($days, 12);
        $heatmap   = AnalyticsService::hourlyHeatmap($days);
        $events    = AnalyticsService::events($days);
        $roles     = AnalyticsService::userRoles($days);

        $this->view('admin.analytics.index', [
            'title'       => 'Analytics — LMSAdvisor',
            'page_title'  => 'Analytics',
            'breadcrumbs' => [['label' => 'Analytics']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'days'        => $days,
            'overview'    => $overview,
            'top_pages'   => $topPages,
            'devices'     => $devices,
            'browsers'    => $browsers,
            'countries'   => $countries,
            'heatmap'     => $heatmap,
            'events'      => $events,
            'roles'       => $roles,
        ]);
    }

    public function purge(array $params): void
    {
        CsrfMiddleware::verify();
        $count = AnalyticsService::purgeOld();
        $this->json(['success' => true, 'purged' => $count]);
    }
}

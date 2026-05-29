<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\ApiMarketplaceService;
use App\Services\InstructorMarketplaceService;
use App\Services\AuthService;

class MarketplaceController extends Controller
{
    private function guard(): void { AuthMiddleware::handle(); }

    // ── API Developer Portal ──────────────────────────────────────────────────

    public function apiPortal(array $p): void
    {
        $this->guard();
        $this->view('admin.marketplace.api', [
            'title'     => 'API Developer Portal',
            'docs'      => ApiMarketplaceService::endpointDocs(),
            'tokens'    => ApiMarketplaceService::allTokens(),
            'flash'     => $this->getFlash(),
            'auth_user' => AuthService::user(),
            'csrf_token'=> CsrfMiddleware::token(),
        ]);
    }

    // ── Instructor Marketplace ────────────────────────────────────────────────

    public function instructors(array $p): void
    {
        $this->guard();
        $this->view('admin.marketplace.instructors', [
            'title'       => 'Instructor Marketplace',
            'pending'     => InstructorMarketplaceService::allApplications('pending'),
            'approved'    => InstructorMarketplaceService::allApplications('approved'),
            'rejected'    => InstructorMarketplaceService::allApplications('rejected'),
            'revenue'     => InstructorMarketplaceService::platformRevenue(),
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
        ]);
    }

    public function reviewApplication(array $p): void
    {
        $this->guard(); CsrfMiddleware::verify();
        $id       = (int)($p['id'] ?? 0);
        $decision = $this->request->post('decision', '');
        $revPct   = (int)$this->request->post('revenue_pct', 70);
        $user     = AuthService::user();
        if (!in_array($decision, ['approved','rejected'], true)) {
            $this->json(['success'=>false,'message'=>'Invalid decision'], 422);
        }
        InstructorMarketplaceService::review($id, $decision, (int)$user['id'], $revPct);
        $this->json(['success'=>true,'message'=>'Application ' . $decision . '.']);
    }
}

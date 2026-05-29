<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Services\RecommendationService;

class RecommendationApiController extends AuthController
{
    public function index(array $p): void
    {
        $user = $this->apiAuth();
        $recs = RecommendationService::getForUser((int)$user['id'], 6);
        $this->json(['success' => true, 'data' => $recs]);
    }

    public function dismiss(array $p): void
    {
        $user = $this->apiAuth();
        RecommendationService::dismiss((int)$user['id'], (int)($p['course_id'] ?? 0));
        $this->json(['success' => true]);
    }
}

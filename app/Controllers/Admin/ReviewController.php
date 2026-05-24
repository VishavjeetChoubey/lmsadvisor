<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Models\Review;
use App\Models\Course;
use App\Models\AuditLog;
use App\Helpers\Sanitizer;

class ReviewController extends Controller
{
    private Review $review;
    private Course $courseModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->review      = new Review();
        $this->courseModel = new Course();
    }

    // ── GET /admin/reviews ────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $page     = max(1, (int)$this->request->get('page', 1));
        $search   = Sanitizer::string($this->request->get('search', ''), 100);
        $status   = Sanitizer::string($this->request->get('status', ''), 20);
        $courseId = (int)$this->request->get('course_id', 0);
        $rating   = (int)$this->request->get('rating', 0);

        $data    = $this->review->paginate($page, 25, $search, $status, $courseId, $rating);
        $stats   = $this->review->stats();
        $dist    = $this->review->ratingDistribution();
        $courses = $this->courseModel->paginate(1, 200);

        $this->view('admin.reviews.index', [
            'title'        => 'Reviews — LMSAdvisor',
            'page_title'   => 'Course Reviews',
            'breadcrumbs'  => [['label' => 'Reviews']],
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
            'ratingFilter' => $rating,
            'stats'        => $stats,
            'dist'         => $dist,
            'courses'      => $courses['rows'],
        ]);
    }

    // ── POST /admin/reviews/:id/approve ───────────────────────────────────────
    public function approve(array $params): void
    {
        CsrfMiddleware::verify();
        $id = (int)($params['id'] ?? 0);
        $this->review->approve($id);
        AuditLog::write('review.approve', 'review', $id);
        $this->json(['success' => true, 'is_approved' => 1]);
    }

    // ── POST /admin/reviews/:id/unapprove ─────────────────────────────────────
    public function unapprove(array $params): void
    {
        CsrfMiddleware::verify();
        $id = (int)($params['id'] ?? 0);
        $this->review->unapprove($id);
        AuditLog::write('review.unapprove', 'review', $id);
        $this->json(['success' => true, 'is_approved' => 0]);
    }

    // ── POST /admin/reviews/:id/delete ────────────────────────────────────────
    public function delete(array $params): void
    {
        CsrfMiddleware::verify();
        $id = (int)($params['id'] ?? 0);
        AuditLog::write('review.delete', 'review', $id);
        $this->review->delete($id);
        $this->json(['success' => true]);
    }

    // ── POST /admin/reviews/bulk ───────────────────────────────────────────────
    public function bulk(array $params): void
    {
        CsrfMiddleware::verify();
        $action = Sanitizer::string($this->request->post('action', ''), 20);
        $ids    = array_map('intval', (array)$this->request->post('ids', []));

        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'No reviews selected.']);
        }

        match ($action) {
            'approve' => $this->review->bulkApprove($ids),
            'delete'  => $this->review->bulkDelete($ids),
            default   => $this->json(['success' => false, 'message' => 'Invalid action.']),
        };

        AuditLog::write('review.bulk_' . $action, 'review', null, null,
            ['count' => count($ids)]);

        $this->json([
            'success' => true,
            'message' => count($ids) . ' review(s) ' . ($action === 'approve' ? 'approved' : 'deleted') . '.',
        ]);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Models\Forum;
use App\Models\Course;
use App\Models\AuditLog;
use App\Helpers\Sanitizer;

class ForumController extends Controller
{
    private Forum  $forum;
    private Course $courseModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->forum       = new Forum();
        $this->courseModel = new Course();
    }

    // ── GET /admin/forum ──────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $page     = max(1, (int)$this->request->get('page', 1));
        $search   = Sanitizer::string($this->request->get('search', ''), 100);
        $courseId = (int)$this->request->get('course_id', 0);

        $data    = $this->forum->allThreadsAdmin($search, $courseId, $page, 25);
        $stats   = $this->forum->stats();
        $courses = $this->courseModel->paginate(1, 200, '', 'published');

        $this->view('admin.forum.index', [
            'title'        => 'Forum — LMSAdvisor',
            'page_title'   => 'Forum Moderation',
            'breadcrumbs'  => [['label' => 'Forum']],
            'flash'        => $this->getFlash(),
            'auth_user'    => AuthService::user(),
            'csrf_token'   => CsrfMiddleware::token(),
            'rows'         => $data['rows'],
            'total'        => $data['total'],
            'page'         => $data['page'],
            'perPage'      => $data['perPage'],
            'totalPages'   => (int)ceil($data['total'] / $data['perPage']),
            'search'       => $search,
            'courseFilter' => $courseId,
            'stats'        => $stats,
            'courses'      => $courses['rows'],
        ]);
    }

    // ── GET /admin/forum/threads/:id ──────────────────────────────────────────
    public function thread(array $params): void
    {
        $thread = $this->forum->findThread((int)($params['id'] ?? 0));
        if (!$thread) {
            $this->flash('error', 'Thread not found.');
            $this->redirect('/admin/forum');
        }

        $replies = $this->forum->replies((int)$thread['id']);

        $this->view('admin.forum.thread', [
            'title'       => 'Thread: ' . $thread['title'] . ' — LMSAdvisor',
            'page_title'  => 'Forum Thread',
            'breadcrumbs' => [
                ['label' => 'Forum', 'url' => '/admin/forum'],
                ['label' => $thread['course_title'], 'url' => '/admin/forum?course_id=' . $thread['course_id']],
                ['label' => $thread['title']],
            ],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'thread'      => $thread,
            'replies'     => $replies,
        ]);
    }

    // ── POST /admin/forum/threads/:id/pin ─────────────────────────────────────
    public function pin(array $params): void
    {
        CsrfMiddleware::verify();
        $id    = (int)($params['id'] ?? 0);
        $pin   = (bool)(int)$this->request->post('pin', 0);
        $this->forum->pinThread($id, $pin);
        AuditLog::write($pin ? 'forum.pin' : 'forum.unpin', 'forum_thread', $id);
        $this->json(['success' => true, 'pinned' => $pin]);
    }

    // ── POST /admin/forum/threads/:id/lock ────────────────────────────────────
    public function lock(array $params): void
    {
        CsrfMiddleware::verify();
        $id   = (int)($params['id'] ?? 0);
        $lock = (bool)(int)$this->request->post('lock', 0);
        $this->forum->lockThread($id, $lock);
        AuditLog::write($lock ? 'forum.lock' : 'forum.unlock', 'forum_thread', $id);
        $this->json(['success' => true, 'locked' => $lock]);
    }

    // ── POST /admin/forum/threads/:id/delete ──────────────────────────────────
    public function deleteThread(array $params): void
    {
        CsrfMiddleware::verify();
        $id = (int)($params['id'] ?? 0);
        AuditLog::write('forum.delete_thread', 'forum_thread', $id);
        $this->forum->deleteThread($id);
        $this->json(['success' => true]);
    }

    // ── POST /admin/forum/replies/:id/delete ──────────────────────────────────
    public function deleteReply(array $params): void
    {
        CsrfMiddleware::verify();
        $id    = (int)($params['id'] ?? 0);
        $reply = $this->forum->findReply($id);
        if (!$reply) {
            $this->json(['success' => false, 'message' => 'Reply not found.'], 404);
        }
        AuditLog::write('forum.delete_reply', 'forum_reply', $id);
        $this->forum->deleteReply($id, (int)$reply['thread_id']);
        $this->json(['success' => true]);
    }

    // ── POST /admin/forum/replies/:id/solution ────────────────────────────────
    public function markSolution(array $params): void
    {
        CsrfMiddleware::verify();
        $replyId    = (int)($params['id'] ?? 0);
        $isSolution = (bool)(int)$this->request->post('is_solution', 0);
        $reply      = $this->forum->findReply($replyId);
        if (!$reply) {
            $this->json(['success' => false, 'message' => 'Reply not found.'], 404);
        }
        $this->forum->markSolution($replyId, (int)$reply['thread_id'], $isSolution);
        $this->json(['success' => true, 'is_solution' => $isSolution]);
    }

    // ── POST /admin/forum/threads (admin post new thread) ─────────────────────
    public function createThread(array $params): void
    {
        CsrfMiddleware::verify();
        $authUser = AuthService::user();
        $courseId = (int)$this->request->post('course_id', 0);
        $title    = Sanitizer::string($this->request->post('title', ''), 255);
        $body     = $this->request->post('body', '');

        if (!$courseId || !$title || !$body) {
            $this->json(['success' => false, 'message' => 'Course, title, and body are required.']);
        }

        $id = $this->forum->createThread([
            'course_id' => $courseId,
            'user_id'   => $authUser['id'],
            'title'     => $title,
            'body'      => $body,
        ]);

        AuditLog::write('forum.create_thread', 'forum_thread', $id);
        $this->json(['success' => true, 'thread_id' => $id]);
    }

    // ── POST /admin/forum/threads/:id/reply ───────────────────────────────────
    public function createReply(array $params): void
    {
        CsrfMiddleware::verify();
        $authUser = AuthService::user();
        $threadId = (int)($params['id'] ?? 0);
        $body     = trim($this->request->post('body', ''));

        if (!$body) {
            $this->json(['success' => false, 'message' => 'Reply body cannot be empty.']);
        }

        $thread = $this->forum->findThread($threadId);
        if (!$thread) {
            $this->json(['success' => false, 'message' => 'Thread not found.'], 404);
        }
        if ($thread['is_locked']) {
            $this->json(['success' => false, 'message' => 'This thread is locked.']);
        }

        $id = $this->forum->createReply([
            'thread_id' => $threadId,
            'user_id'   => $authUser['id'],
            'body'      => $body,
        ]);

        $user = $authUser;
        $this->json([
            'success'  => true,
            'reply_id' => $id,
            'reply'    => [
                'id'         => $id,
                'body'       => $body,
                'first_name' => $user['name'],
                'role_name'  => $user['role'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}

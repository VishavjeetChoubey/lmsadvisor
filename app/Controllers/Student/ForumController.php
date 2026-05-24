<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Models\Forum;
use App\Models\Enrollment;
use App\Models\Course;
use App\Helpers\Sanitizer;

class ForumController extends Controller
{
    private Forum      $forum;
    private Enrollment $enrollModel;
    private Course     $courseModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle('/login');
        $this->forum       = new Forum();
        $this->enrollModel = new Enrollment();
        $this->courseModel = new Course();
    }

    // ── GET /learn/courses/:uuid/forum ────────────────────────────────────────
    public function index(array $params): void
    {
        $user   = AuthService::user();
        $course = $this->getCourse($params['uuid'] ?? '');

        if (!$course['forum_enabled']) {
            $this->flash('error', 'Forum is not enabled for this course.');
            $this->redirect('/learn/courses/' . $course['uuid'] . '/learn');
        }

        $enrollment = $this->enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);
        if (!$enrollment && $course['forum_enrolled_only']) {
            $this->flash('error', 'You must be enrolled to access the forum.');
            $this->redirect('/learn/courses');
        }

        $page   = max(1, (int)$this->request->get('page', 1));
        $search = Sanitizer::string($this->request->get('search', ''), 100);
        $data   = $this->forum->threads((int)$course['id'], $search, $page, 15);

        $this->view('student.forum.index', [
            'title'       => 'Forum — ' . $course['title'],
            'page_title'  => 'Forum: ' . $course['title'],
            'auth_user'   => $user,
            'flash'       => $this->getFlash(),
            'course'      => $course,
            'enrollment'  => $enrollment,
            'rows'        => $data['rows'],
            'total'       => $data['total'],
            'page'        => $data['page'],
            'totalPages'  => (int)ceil($data['total'] / $data['perPage']),
            'search'      => $search,
            'csrf_token'  => CsrfMiddleware::token(),
        ], 'student');
    }

    // ── GET /learn/courses/:uuid/forum/threads/:id ────────────────────────────
    public function thread(array $params): void
    {
        $user   = AuthService::user();
        $course = $this->getCourse($params['uuid'] ?? '');
        $thread = $this->forum->findThread((int)($params['id'] ?? 0));

        if (!$thread || (int)$thread['course_id'] !== (int)$course['id']) {
            $this->flash('error', 'Thread not found.');
            $this->redirect('/learn/courses/' . $course['uuid'] . '/forum');
        }

        $enrollment = $this->enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);
        $replies    = $this->forum->replies((int)$thread['id']);

        $this->view('student.forum.thread', [
            'title'      => $thread['title'] . ' — Forum',
            'page_title' => 'Forum',
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
            'course'     => $course,
            'thread'     => $thread,
            'replies'    => $replies,
            'enrollment' => $enrollment,
            'csrf_token' => CsrfMiddleware::token(),
        ], 'student');
    }

    // ── POST /learn/courses/:uuid/forum/threads ───────────────────────────────
    public function createThread(array $params): void
    {
        CsrfMiddleware::verify();
        $user   = AuthService::user();
        $course = $this->getCourse($params['uuid'] ?? '');

        $enrollment = $this->enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);
        if (!$enrollment) {
            $this->json(['success' => false, 'message' => 'You must be enrolled to post.']);
        }

        $title = Sanitizer::string($this->request->post('title', ''), 255);
        $body  = trim($this->request->post('body', ''));

        if (!$title || !$body) {
            $this->json(['success' => false, 'message' => 'Title and body are required.']);
        }

        $id = $this->forum->createThread([
            'course_id' => $course['id'],
            'user_id'   => $user['id'],
            'title'     => $title,
            'body'      => $body,
        ]);

        $this->json(['success' => true, 'thread_id' => $id]);
    }

    // ── POST /learn/courses/:uuid/forum/threads/:id/reply ─────────────────────
    public function createReply(array $params): void
    {
        CsrfMiddleware::verify();
        $user     = AuthService::user();
        $course   = $this->getCourse($params['uuid'] ?? '');
        $threadId = (int)($params['id'] ?? 0);
        $thread   = $this->forum->findThread($threadId);

        if (!$thread || $thread['is_locked']) {
            $this->json(['success' => false, 'message' => 'Thread is locked or not found.']);
        }

        $enrollment = $this->enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);
        if (!$enrollment) {
            $this->json(['success' => false, 'message' => 'You must be enrolled to reply.']);
        }

        $body = trim($this->request->post('body', ''));
        if (!$body) {
            $this->json(['success' => false, 'message' => 'Reply cannot be empty.']);
        }

        $id = $this->forum->createReply([
            'thread_id' => $threadId,
            'user_id'   => $user['id'],
            'body'      => $body,
        ]);

        $this->json([
            'success'   => true,
            'reply_id'  => $id,
            'name'      => $user['name'],
            'body'      => $body,
            'created_at'=> date('d M Y H:i'),
        ]);
    }

    private function getCourse(string $uuid): array
    {
        $course = $this->courseModel->findByUuidFull($uuid);
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('/learn/courses');
        }
        return $course;
    }
}

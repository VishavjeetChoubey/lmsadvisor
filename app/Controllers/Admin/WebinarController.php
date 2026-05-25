<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\WebinarService;
use App\Services\NotificationService;
use App\Helpers\Sanitizer;
use App\Helpers\Uuid;
use App\Models\AuditLog;

class WebinarController extends Controller
{
    private \PDO $pdo;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->pdo = Database::getInstance();
    }

    // ── GET /admin/webinars ───────────────────────────────────────────────────
    public function index(array $params): void
    {
        $status = Sanitizer::string($this->request->get('status', ''), 20);
        $where  = '1=1';
        $binds  = [];
        if ($status) { $where = 'w.status = ?'; $binds[] = $status; }

        $stmt = $this->pdo->prepare(
            "SELECT w.*, c.title AS course_title, c.uuid AS course_uuid,
                    u.first_name, u.last_name
             FROM webinar_sessions w
             LEFT JOIN courses c ON c.id = w.course_id
             LEFT JOIN users u   ON u.id = w.created_by
             WHERE $where ORDER BY w.scheduled_at DESC LIMIT 50"
        );
        $stmt->execute($binds);
        $webinars = $stmt->fetchAll();

        $courses = $this->pdo->query("SELECT id, title, uuid FROM courses WHERE status='published' ORDER BY title")->fetchAll();

        $this->view('admin.webinar.index', [
            'title'       => 'Webinars — LMSAdvisor',
            'page_title'  => 'Webinars',
            'breadcrumbs' => [['label' => 'Webinars']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'webinars'    => $webinars,
            'courses'     => $courses,
            'statusFilter'=> $status,
        ]);
    }

    // ── POST /admin/webinars/create ───────────────────────────────────────────
    public function create(array $params): void
    {
        CsrfMiddleware::verify();
        $user     = AuthService::user();
        $provider = Sanitizer::string($this->request->post('provider', 'zoom'), 20);
        $courseId = (int)$this->request->post('course_id', 0);
        $title    = Sanitizer::string($this->request->post('title', ''), 255);
        $schedAt  = Sanitizer::string($this->request->post('scheduled_at', ''), 20);
        $duration = (int)$this->request->post('duration_min', 60);
        $uuid     = Uuid::v4();

        try {
            $meetData = $provider === 'zoom'
                ? WebinarService::createZoomMeeting(['title' => $title, 'scheduled_at' => $schedAt, 'duration_min' => $duration])
                : WebinarService::createGoogleMeet(['title' => $title, 'scheduled_at' => $schedAt, 'duration_min' => $duration]);
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/admin/webinars');
        }

        $this->pdo->prepare(
            'INSERT INTO webinar_sessions
             (uuid, course_id, title, provider, meeting_id, join_url, start_url, password, scheduled_at, duration_min, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $uuid, $courseId, $title, $provider,
            $meetData['meeting_id'], $meetData['join_url'],
            $meetData['start_url'],  $meetData['password'],
            $schedAt, $duration, $user['id'],
        ]);

        $webinarId = (int)$this->pdo->lastInsertId();

        // Notify enrolled students
        $enrolled = $this->pdo->prepare(
            'SELECT e.user_id FROM enrollments e WHERE e.course_id=? AND e.status="active"'
        );
        $enrolled->execute([$courseId]);
        foreach ($enrolled->fetchAll() as $row) {
            NotificationService::send((int)$row['user_id'], 'webinar_scheduled', [
                'title' => 'New Webinar: ' . $title,
                'body'  => 'A webinar has been scheduled for your course on ' . date('d M Y H:i', strtotime($schedAt)) . '.',
                'data'  => ['webinar_id' => $webinarId],
            ]);
        }

        AuditLog::write('webinar.create', 'webinar', $webinarId, null, ['title' => $title, 'provider' => $provider]);
        $this->flash('success', "Webinar created on $provider.");
        $this->redirect('/admin/webinars');
    }

    // ── POST /admin/webinars/:uuid/cancel ─────────────────────────────────────
    public function cancel(array $params): void
    {
        CsrfMiddleware::verify();
        $stmt = $this->pdo->prepare('SELECT * FROM webinar_sessions WHERE uuid=? LIMIT 1');
        $stmt->execute([$params['uuid'] ?? '']);
        $w = $stmt->fetch();
        if (!$w) { $this->json(['success' => false, 'message' => 'Not found.']); }

        if ($w['provider'] === 'zoom' && $w['meeting_id']) {
            try { WebinarService::deleteZoomMeeting($w['meeting_id']); } catch (\Throwable) {}
        }

        $this->pdo->prepare("UPDATE webinar_sessions SET status='cancelled' WHERE id=?")->execute([$w['id']]);
        AuditLog::write('webinar.cancel', 'webinar', (int)$w['id']);
        $this->json(['success' => true]);
    }

    // ── POST /admin/webinars/:uuid/start ──────────────────────────────────────
    public function start(array $params): void
    {
        CsrfMiddleware::verify();
        $stmt = $this->pdo->prepare('SELECT * FROM webinar_sessions WHERE uuid=? LIMIT 1');
        $stmt->execute([$params['uuid'] ?? '']);
        $w = $stmt->fetch();
        if (!$w) { $this->json(['success' => false]); }
        $this->pdo->prepare("UPDATE webinar_sessions SET status='live' WHERE id=?")->execute([$w['id']]);
        $this->json(['success' => true, 'start_url' => $w['start_url']]);
    }
}

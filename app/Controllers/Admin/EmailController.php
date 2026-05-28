<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Core\Database;

class EmailController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin','admin']);
    }

    /** GET /admin/email — template list + queue log */
    public function index(array $p): void
    {
        $pdo       = Database::getInstance();
        $templates = $pdo->query('SELECT * FROM email_templates ORDER BY name')->fetchAll();
        $queue     = $pdo->query('SELECT * FROM email_queue ORDER BY created_at DESC LIMIT 50')->fetchAll();

        $this->view('admin.email.index', [
            'title'       => 'Email Notifications',
            'page_title'  => 'Email Notifications',
            'breadcrumbs' => [['label'=>'Email Notifications']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'templates'   => $templates,
            'queue'       => $queue,
        ]);
    }

    /** GET /admin/email/templates/:slug/edit */
    public function editTemplate(array $p): void
    {
        $pdo  = Database::getInstance();
        $tpl  = $pdo->prepare('SELECT * FROM email_templates WHERE slug=? LIMIT 1');
        $tpl->execute([$p['slug'] ?? '']);
        $template = $tpl->fetch();
        if (!$template) { $this->flash('error','Template not found.'); $this->redirect('/admin/email'); }

        $this->view('admin.email.edit_template', [
            'title'       => 'Edit Template — ' . $template['name'],
            'page_title'  => 'Edit Email Template',
            'breadcrumbs' => [['label'=>'Email','url'=>'admin/email'],['label'=>$template['name']]],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'template'    => $template,
        ]);
    }

    /** POST /admin/email/templates/:slug/save */
    public function saveTemplate(array $p): void
    {
        CsrfMiddleware::verify();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'UPDATE email_templates SET name=?, subject=?, body_html=?, is_enabled=? WHERE slug=?'
        )->execute([
            trim($this->request->post('name','')),
            trim($this->request->post('subject','')),
            $this->request->post('body_html',''),
            $this->request->post('is_enabled','0') ? 1 : 0,
            $p['slug'] ?? '',
        ]);
        $this->flash('success','Template saved.');
        $this->redirect('/admin/email');
    }

    /** POST /admin/email/test — send a test email */
    public function sendTest(array $p): void
    {
        CsrfMiddleware::verify();
        $to   = trim($this->request->post('test_to',''));
        $slug = trim($this->request->post('template_slug','enrollment_confirmation'));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success'=>false,'message'=>'Invalid email address.']);
        }

        // Check SMTP is configured before queuing
        $smtpHost = \App\Models\Setting::get('smtp_host', '');
        $smtpEnabled = (bool)(int)\App\Models\Setting::get('smtp_enabled', '0');
        if (!$smtpEnabled || !$smtpHost) {
            $this->json(['success'=>false,'message'=>'SMTP is not enabled or configured. Go to Settings → Email and fill in SMTP Host, enable SMTP, then try again.']);
        }

        $user   = AuthService::user();
        $queued = EmailService::queue($to, $user['name'] ?? 'Test User', $slug, [
            'student_name'    => 'Test Student',
            'course_title'    => 'Sample Course Title',
            'course_level'    => 'Beginner',
            'course_duration' => '12 hours',
            'grade_points'    => 100,
            'course_url'      => APP_URL . '/learn/courses',
            'webinar_title'   => 'Sample Webinar',
            'webinar_date'    => date('l, d F Y'),
            'webinar_time'    => '14:00 UTC',
            'webinar_duration'=> 60,
            'webinar_provider'=> 'Zoom',
            'join_url'        => '#',
            'score'           => 85,
            'pass_percentage' => 70,
            'result'          => 'Passed ✓',
            'result_emoji'    => '🎉',
            'result_color'    => '#059669',
            'quiz_title'      => 'Sample Quiz',
            'certificate_url' => APP_URL . '/certificate/verify/sample',
        ]);

        if (!$queued) {
            $this->json(['success'=>false,'message'=>'Could not queue email. Check that SMTP is enabled and the email address is not unsubscribed.']);
        }

        $result = EmailService::processQueue(1);
        if ($result['sent'] > 0) {
            $this->json(['success'=>true,'message'=>'Test email sent to ' . $to . '. Check your inbox.']);
        }

        // Check queue for error message
        $pdo  = \App\Core\Database::getInstance();
        $last = $pdo->query("SELECT error_msg FROM email_queue WHERE status IN ('failed','pending') ORDER BY id DESC LIMIT 1")->fetch();
        $errMsg = $last['error_msg'] ?? 'SMTP send failed. Check your SMTP credentials.';
        $this->json(['success'=>false,'message'=>$errMsg]);
    }

    /** POST /admin/email/process-queue */
    public function processQueue(array $p): void
    {
        CsrfMiddleware::verify();
        $result = EmailService::processQueue(50);
        $this->json(['success'=>true,'sent'=>$result['sent'],'failed'=>$result['failed']]);
    }
}

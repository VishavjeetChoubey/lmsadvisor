<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Services\DropoutPredictorService;
use App\Services\EmailService;

class DropoutController extends Controller
{
    public function index(array $p): void
    {
        AuthMiddleware::handle();
        $level = $this->request->get('level', 'medium');
        $atRisk = DropoutPredictorService::getAtRisk($level, 100);

        $this->view('admin.dropout.index', [
            'title'   => 'Drop-out Predictor',
            'atRisk'  => $atRisk,
            'level'   => $level,
            'flash'   => $this->getFlash(),
            'auth_user' => \App\Services\AuthService::user(),
        ]);
    }

    public function recalculate(array $p): void
    {
        AuthMiddleware::handle();
        \App\Middleware\CsrfMiddleware::verify();
        $count = DropoutPredictorService::recalculateAll();
        $this->flash('success', "Risk scores recalculated for {$count} active enrollments.");
        $this->redirect('/admin/dropout');
    }

    public function sendAlert(array $p): void
    {
        AuthMiddleware::handle();
        \App\Middleware\CsrfMiddleware::verify();
        $enrollmentId = (int)$this->request->post('enrollment_id', 0);
        if (!$enrollmentId) { $this->json(['success'=>false,'message'=>'Invalid enrollment'], 422); }

        $pdo  = \App\Core\Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT dr.*, u.first_name, u.last_name, u.email, c.title AS course_title
             FROM dropout_risk dr
             JOIN enrollments e ON e.id = dr.enrollment_id
             JOIN users u ON u.id = dr.user_id
             JOIN courses c ON c.id = e.course_id
             WHERE dr.enrollment_id=? LIMIT 1'
        );
        $stmt->execute([$enrollmentId]);
        $row = $stmt->fetch();
        if (!$row) { $this->json(['success'=>false,'message'=>'Record not found'], 404); }

        try {
            EmailService::queue(
                $row['email'],
                $row['first_name'] . ' ' . $row['last_name'],
                'enrollment_confirmation', // reuse template slot
                [
                    'student_name' => $row['first_name'],
                    'course_title' => $row['course_title'],
                    'course_url'   => rtrim(APP_URL,'/') . '/learn',
                    'site_name'    => \App\Models\Setting::get('site_name','LMSAdvisor'),
                    'unsubscribe_url' => '#',
                    'course_level'    => '',
                    'course_duration' => '',
                    'grade_points'    => 0,
                ]
            );
            $pdo->prepare('UPDATE dropout_risk SET alert_sent=1 WHERE enrollment_id=?')
                ->execute([$enrollmentId]);
            $this->json(['success'=>true,'message'=>'Re-engagement email queued.']);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }
}

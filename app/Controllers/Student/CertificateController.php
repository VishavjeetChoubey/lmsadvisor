<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\CertificateService;
use App\Models\Enrollment;

class CertificateController extends Controller
{
    // ── GET /certificate/verify/:uuid — PUBLIC, no login required ────────────
    public function verify(array $params): void
    {
        $uuid = trim($params['uuid'] ?? '');
        $cert = $uuid ? CertificateService::findByUuid($uuid) : null;

        // Render standalone HTML (no layout wrapper — the view is a full HTML doc)
        $this->viewRaw('student.certificate.verify', [
            'title' => $cert ? 'Certificate Verified — ' . ($cert['course_title'] ?? '') : 'Certificate Not Found',
            'cert'  => $cert,
            'uuid'  => $uuid,
        ]);
    }

    // ── GET /learn/certificate/:enrollmentId — student downloads their cert ──
    public function view(array $params): void
    {
        AuthMiddleware::handle('/login');
        $user         = AuthService::user();
        $enrollmentId = (int)($params['enrollmentId'] ?? 0);

        $enrollModel = new Enrollment();
        $enrollment  = $enrollModel->findById($enrollmentId);

        if (!$enrollment || (int)$enrollment['user_id'] !== (int)$user['id']) {
            $this->flash('error', 'Certificate not found.');
            $this->redirect('/learn/courses');
        }

        if ($enrollment['status'] !== 'completed') {
            $this->flash('error', 'Complete the course to receive your certificate.');
            $this->redirect('/learn/courses');
        }

        // Issue if not already done
        $cert = CertificateService::findByEnrollment($enrollmentId);
        if (!$cert) {
            $certRow = CertificateService::issue(
                $enrollmentId,
                (int)$user['id'],
                (int)$enrollment['course_id']
            );
            $cert = CertificateService::findByEnrollment($enrollmentId);
        }

        // Render full standalone HTML cert page
        echo CertificateService::renderHtml($cert);
        exit;
    }

    // ── GET /learn/certificate/:enrollmentId/print — same but no nav ─────────
    public function print(array $params): void
    {
        $this->view($params); // Same as view, the HTML has print CSS
    }
}

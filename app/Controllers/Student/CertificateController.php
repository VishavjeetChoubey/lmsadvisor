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
    // ── GET /certificate/verify        — PUBLIC (no login) ───────────────────
    // ── GET /certificate/verify/:uuid  — PUBLIC (no login) ───────────────────
    public function verify(array $params): void
    {
        $uuid = trim($params['uuid'] ?? '');
        $cert = $uuid ? CertificateService::findByUuid($uuid) : null;

        // Render as a standalone HTML page (no layout — view has its own <html>)
        $this->viewRaw('student.certificate.verify', [
            'title' => $cert
                ? 'Certificate Verified — ' . ($cert['course_title'] ?? '')
                : 'Certificate Not Found',
            'cert'  => $cert,
            'uuid'  => $uuid,
        ]);
    }

    // ── GET /learn/certificate/:enrollmentId — Student downloads their cert ──
    public function show(array $params): void
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

        // Auto-issue if not yet issued
        $cert = CertificateService::findByEnrollment($enrollmentId);
        if (!$cert) {
            CertificateService::issue(
                $enrollmentId,
                (int)$user['id'],
                (int)$enrollment['course_id']
            );
            $cert = CertificateService::findByEnrollment($enrollmentId);
        }

        if (!$cert) {
            $this->flash('error', 'Could not generate certificate. Please try again.');
            $this->redirect('/learn/courses');
        }

        // Render full standalone HTML cert page (bypasses layout)
        echo CertificateService::renderHtml($cert);
        exit;
    }
}

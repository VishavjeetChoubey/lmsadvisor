<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\ScormService;
use App\Models\Enrollment;
use App\Models\Lesson;

class ScormController extends Controller
{
    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle('/login');
    }

    // ── GET /scorm/:lessonId/*filepath — serve extracted SCORM files ──────────
    public function serveFile(array $params): void
    {
        $lessonId = (int)($params['lessonId'] ?? 0);
        $filePath = $params['filepath'] ?? 'index.html';

        // Security: prevent directory traversal
        $filePath = str_replace(['../', '..\\', "\0"], '', $filePath);

        $absPath  = STORE_PATH . '/scorm_packages/' . $lessonId . '/' . $filePath;

        if (!file_exists($absPath) || !is_file($absPath)) {
            http_response_code(404);
            echo 'SCORM file not found: ' . htmlspecialchars($filePath);
            return;
        }

        // Determine MIME type
        $ext  = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
        $mimes = [
            'html' => 'text/html',   'htm' => 'text/html',
            'js'   => 'application/javascript',
            'css'  => 'text/css',
            'png'  => 'image/png',   'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',  'gif' => 'image/gif',
            'svg'  => 'image/svg+xml',
            'mp4'  => 'video/mp4',   'webm' => 'video/webm',
            'mp3'  => 'audio/mpeg',  'ogg'  => 'audio/ogg',
            'woff' => 'font/woff',   'woff2' => 'font/woff2',
            'ttf'  => 'font/ttf',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'swf'  => 'application/x-shockwave-flash',
        ];
        $mime = $mimes[$ext] ?? (mime_content_type($absPath) ?: 'application/octet-stream');

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($absPath));
        header('Cache-Control: public, max-age=3600');
        readfile($absPath);
        exit;
    }

    // ── POST /scorm/api/:lessonId — SCORM API data endpoint ──────────────────
    public function api(array $params): void
    {
        $lessonId    = (int)($params['lessonId'] ?? 0);
        $user        = AuthService::user();
        $action      = $this->request->post('action', '');

        // Find enrollment for this lesson's course
        $pdo         = \App\Core\Database::getInstance();
        $stmt        = $pdo->prepare(
            'SELECT e.id FROM enrollments e
             JOIN lessons l ON l.course_id = e.course_id
             WHERE l.id = ? AND e.user_id = ? LIMIT 1'
        );
        $stmt->execute([$lessonId, (int)$user['id']]);
        $enrollmentId = (int)($stmt->fetchColumn() ?: 0);

        if (!$enrollmentId) {
            $this->json(['success' => false, 'message' => 'Not enrolled.']);
        }

        if ($action === 'save') {
            $data = json_decode($this->request->post('data', '{}'), true) ?? [];
            ScormService::saveProgress($enrollmentId, $lessonId, $data);
            $this->json(['success' => true]);
        } elseif ($action === 'load') {
            $data = ScormService::getProgress($enrollmentId, $lessonId);
            $this->json(['success' => true, 'data' => $data]);
        } else {
            $this->json(['success' => false, 'message' => 'Unknown action.']);
        }
    }
}

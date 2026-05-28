<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Models\Enrollment;

class EnrollmentApiController extends AuthController
{
    // GET /api/v1/enrollments
    public function index(array $params): void
    {
        $authUser = $this->apiAuth();
        $pdo      = \App\Core\Database::getInstance();

        // WooCommerce plugin passes ?email= to get a student's enrollments by email
        $email = strtolower(trim($this->request->get('email', '')));
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Only admins/managers can look up other users' enrollments
            if (!in_array($authUser['role_name'], ['admin','super_admin','manager'], true)) {
                http_response_code(403);
                $this->json(['error' => 'Insufficient permissions.']);
            }
            $uStmt = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $uStmt->execute([$email]);
            $targetId = (int)($uStmt->fetchColumn() ?: 0);
            if (!$targetId) {
                $this->json(['data' => [], 'total' => 0]); // No user = no enrollments
            }

            $stmt = $pdo->prepare(
                'SELECT e.uuid AS enrollment_uuid, e.status, e.enrolled_at, e.completed_at,
                        c.uuid AS course_uuid, c.title AS course_title,
                        c.short_description, c.thumbnail, c.level, c.duration_hours,
                        c.grade_points,
                        CONCAT(?, \'/storage/uploads/\', c.thumbnail) AS thumbnail_url,
                        CONCAT(?, \'/learn/courses/\', c.uuid) AS course_url,
                        (SELECT ROUND(COUNT(CASE WHEN lp.status=\'completed\' THEN 1 END) * 100.0 / NULLIF(COUNT(l.id),0))
                         FROM lessons l LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id AND lp.enrollment_id=e.id
                         WHERE l.course_id=c.id) AS progress_pct,
                        cert.uuid AS certificate_uuid
                 FROM enrollments e
                 JOIN courses c ON c.id=e.course_id
                 LEFT JOIN certificates cert ON cert.enrollment_id=e.id
                 WHERE e.user_id=?
                 ORDER BY e.enrolled_at DESC'
            );
            $base = rtrim(APP_URL, '/');
            $stmt->execute([$base, $base, $targetId]);
            $rows = $stmt->fetchAll();

            // Build certificate URL
            foreach ($rows as &$row) {
                $row['progress_pct'] = (int)($row['progress_pct'] ?? 0);
                $row['certificate_url'] = !empty($row['certificate_uuid'])
                    ? $base . '/certificate/verify/' . $row['certificate_uuid']
                    : null;
                if (empty($row['thumbnail'])) {
                    $row['thumbnail_url'] = null;
                }
            }
            unset($row);

            $this->json(['data' => $rows, 'total' => count($rows)]);
        }

        // Default: return enrollments for the authenticated API user
        $model = new Enrollment();
        $this->json(['data' => $model->forUser((int)$authUser['id'])]);
    }

    // POST /api/v1/enrollments
    public function store(array $params): void
    {
        $user = $this->apiAuth();
        if (!in_array($user['role_name'], ['admin','super_admin','manager'])) {
            http_response_code(403);
            $this->json(['error' => 'Insufficient permissions.']);
        }

        $pdo        = Database::getInstance();
        $courseUuid = $this->request->post('course_uuid', '');
        $email      = strtolower(trim($this->request->post('email', '')));
        $userId     = (int)$this->request->post('user_id', 0);
        $firstName  = trim($this->request->post('first_name', ''));
        $lastName   = trim($this->request->post('last_name', ''));
        $createAcct = (bool)$this->request->post('create_account', false);

        // ── Resolve user by email (WooCommerce plugin sends email) ───────────
        if ($email && !$userId) {
            $u = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $u->execute([$email]);
            $found = $u->fetchColumn();

            if ($found) {
                $userId = (int)$found;
            } elseif ($createAcct) {
                // Auto-create student account
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name='student' LIMIT 1");
                $roleStmt->execute();
                $roleId = (int)$roleStmt->fetchColumn();

                $uuid = \App\Helpers\Uuid::v4();
                $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
                $pdo->prepare(
                    'INSERT INTO users (uuid, first_name, last_name, email, password_hash, role_id, is_active)
                     VALUES (?,?,?,?,?,?,1)'
                )->execute([$uuid, $firstName, $lastName, $email, $hash, $roleId]);
                $userId = (int)$pdo->lastInsertId();
            } else {
                http_response_code(404);
                $this->json(['error' => 'User not found. Enable create_account to auto-create.']);
            }
        }

        if (!$userId) {
            $userId = (int)$user['id'];
        }

        // ── Find course ───────────────────────────────────────────────────────
        $stmt = $pdo->prepare('SELECT id, uuid FROM courses WHERE uuid=? AND status="published" LIMIT 1');
        $stmt->execute([$courseUuid]);
        $course = $stmt->fetch();
        if (!$course) {
            http_response_code(404);
            $this->json(['error' => 'Course not found.']);
        }

        // ── Enroll (idempotent) ───────────────────────────────────────────────
        $model = new Enrollment();
        $existing = $model->findEnrollment((int)$course['id'], $userId);
        if ($existing) {
            $this->json([
                'message'         => 'Already enrolled.',
                'enrollment_uuid' => $existing['uuid'] ?? '',
                'already_enrolled'=> true,
            ]);
        }

        $result = $model->enroll((int)$course['id'], $userId, (int)$user['id']);
        $enrollId = $result['enrollment_id'] ?? 0;

        // Get uuid of new enrollment
        $eUuid = '';
        if ($enrollId) {
            $eq = $pdo->prepare('SELECT uuid FROM enrollments WHERE id=? LIMIT 1');
            $eq->execute([$enrollId]);
            $eUuid = (string)($eq->fetchColumn() ?: '');
        }

        // Send enrollment email if requested
        if ($this->request->post('send_welcome_email', false)) {
            try {
                $uData  = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
                $uData->execute([$userId]);
                $uRow   = $uData->fetch();
                $cData  = $pdo->prepare('SELECT * FROM courses WHERE id=? LIMIT 1');
                $cData->execute([(int)$course['id']]);
                $cRow   = $cData->fetch();
                if ($uRow && $cRow) {
                    \App\Services\EmailService::sendEnrollmentConfirmation($uRow, $cRow, []);
                }
            } catch (\Throwable) {}
        }

        $this->json([
            'message'         => 'Enrolled successfully.',
            'enrollment_uuid' => $eUuid,
            'user_id'         => $userId,
            'already_enrolled'=> false,
        ]);
    }
}

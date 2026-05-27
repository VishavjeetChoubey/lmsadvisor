<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use App\Models\Course;
use App\Models\AuditLog;

class EnrollmentService
{
    private Enrollment $enrollment;
    private User       $userModel;

    public function __construct()
    {
        $this->enrollment = new Enrollment();
        $this->userModel  = new User();
    }

    // ── Single Enrollment ─────────────────────────────────────────────────────

    /**
     * Enroll a user in a course.
     * Returns ['success'=>bool, 'message'=>string, 'enrollment_id'=>int|null]
     */
    public function enroll(int $courseId, int $userId, int $enrolledBy, ?string $expiresAt = null): array
    {
        // Already enrolled?
        if ($this->enrollment->findEnrollment($courseId, $userId)) {
            return ['success' => false, 'message' => 'User is already enrolled in this course.'];
        }

        $id = $this->enrollment->enroll($courseId, $userId, $enrolledBy, $expiresAt);

        // Calendar sync
        CalendarService::createEnrollmentEvents($id, $courseId, $userId);

        AuditLog::write('enrollment.create', 'enrollment', $id, null,
            ['course_id' => $courseId, 'user_id' => $userId]);

        // Webhook + Slack notification
        try {
            \App\Services\WebhookService::fire('enroll', [
                'user_id'      => $userId,
                'course_id'    => $courseId,
                'student_name' => ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
                'course_title' => $course['title'] ?? '',
            ]);
            \App\Services\WebhookService::slackNotify('enroll', [
                'student_name' => ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
                'course_title' => $course['title'] ?? '',
            ]);
        } catch (\Throwable) {}

        // Email notification
        try {
            $pdo    = \App\Core\Database::getInstance();
            $uStmt  = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
            $uStmt->execute([$userId]);
            $user   = $uStmt->fetch();
            $cStmt  = $pdo->prepare('SELECT * FROM courses WHERE id=? LIMIT 1');
            $cStmt->execute([$courseId]);
            $course = $cStmt->fetch();
            if ($user && $course) {
                \App\Services\EmailService::sendEnrollmentConfirmation($user, $course, []);
            }
        } catch (\Throwable) {}

        return ['success' => true, 'message' => 'Enrolled successfully.', 'enrollment_id' => $id];
    }

    /**
     * Remove an enrollment.
     */
    public function remove(int $enrollmentId, int $removedBy): array
    {
        $row = $this->enrollment->findById($enrollmentId);
        if (!$row) {
            return ['success' => false, 'message' => 'Enrollment not found.'];
        }

        AuditLog::write('enrollment.remove', 'enrollment', $enrollmentId,
            ['user_id' => $row['user_id'], 'course_id' => $row['course_id']]);

        $this->enrollment->remove($enrollmentId);
        CalendarService::removeEnrollmentEvents($enrollmentId);

        return ['success' => true, 'message' => 'Enrollment removed.'];
    }

    // ── CSV Bulk Enrollment ───────────────────────────────────────────────────

    /**
     * Process a CSV file and enroll users in a course.
     *
     * CSV format: email (first column, header row optional)
     * Returns ['enrolled'=>int, 'skipped'=>int, 'errors'=>string[]]
     */
    public function enrollFromCsv(string $csvPath, int $courseId, int $enrolledBy): array
    {
        if (!file_exists($csvPath)) {
            return ['enrolled' => 0, 'skipped' => 0, 'errors' => ['File not found.']];
        }

        $handle   = fopen($csvPath, 'r');
        $enrolled = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $row++;
            $email = strtolower(trim($line[0] ?? ''));

            // Skip header row and blank lines
            if ($row === 1 && str_contains(strtolower($email), 'email')) continue;
            if ($email === '') continue;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row $row: '$email' is not a valid email.";
                continue;
            }

            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                $errors[] = "Row $row: No user found with email '$email'.";
                $skipped++;
                continue;
            }

            $result = $this->enroll($courseId, (int)$user['id'], $enrolledBy);
            if ($result['success']) {
                $enrolled++;
            } else {
                $skipped++;
                $errors[] = "Row $row ($email): " . $result['message'];
            }
        }

        fclose($handle);
        return compact('enrolled', 'skipped', 'errors');
    }

    // ── Mark Complete ─────────────────────────────────────────────────────────

    public function markComplete(int $enrollmentId): void
    {
        $this->enrollment->updateStatus($enrollmentId, 'completed');

        $row = $this->enrollment->findById($enrollmentId);
        if ($row) {
            // Award grade points
            LeaderboardService::awardFromEnrollment($enrollmentId, (int)$row['user_id'], (int)$row['course_id']);
        }

        AuditLog::write('enrollment.complete', 'enrollment', $enrollmentId);
    }
}

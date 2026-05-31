<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

class AssignmentService
{
    public static function getByLesson(int $lessonId): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM assignments WHERE lesson_id=? LIMIT 1');
        $stmt->execute([$lessonId]);
        $row = $stmt->fetch() ?: null;

        // Auto-create default row if lesson exists with type=assignment but no config yet
        if (!$row) {
            $lessonStmt = $pdo->prepare("SELECT id, title FROM lessons WHERE id=? AND type='assignment' LIMIT 1");
            $lessonStmt->execute([$lessonId]);
            $lesson = $lessonStmt->fetch();
            if ($lesson) {
                $pdo->prepare(
                    'INSERT IGNORE INTO assignments (lesson_id, title, brief, max_score, pass_score, max_attempts, allowed_types, max_file_mb)
                     VALUES (?, ?, \'\', 100, 50, 3, \'pdf,zip,doc,docx,jpg,png\', 20)'
                )->execute([$lessonId, $lesson['title']]);
                $stmt->execute([$lessonId]);
                $row = $stmt->fetch() ?: null;
            }
        }

        return $row;
    }

    public static function create(int $lessonId, array $data): int
    {
        $pdo = Database::getInstance();
        // Upsert (lesson_id is UNIQUE)
        $pdo->prepare(
            'INSERT INTO assignments
             (lesson_id, title, brief, rubric, deadline, max_score, pass_score, max_attempts, allowed_types, max_file_mb)
             VALUES (?,?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
             title=VALUES(title), brief=VALUES(brief), rubric=VALUES(rubric),
             deadline=VALUES(deadline), max_score=VALUES(max_score), pass_score=VALUES(pass_score),
             max_attempts=VALUES(max_attempts), allowed_types=VALUES(allowed_types), max_file_mb=VALUES(max_file_mb)'
        )->execute([
            $lessonId,
            $data['title'],
            $data['brief']    ?? null,
            $data['rubric']   ?? null,
            !empty($data['deadline']) ? $data['deadline'] : null,
            (int)($data['max_score']  ?? 100),
            (int)($data['pass_score'] ?? 50),
            (int)($data['max_attempts'] ?? 3),
            $data['allowed_types'] ?? 'pdf,zip,doc,docx,jpg,png',
            (int)($data['max_file_mb'] ?? 20),
        ]);
        $stmt = $pdo->prepare('SELECT id FROM assignments WHERE lesson_id=?');
        $stmt->execute([$lessonId]);
        return (int)$stmt->fetchColumn();
    }

    public static function submit(int $assignmentId, int $enrollmentId, int $userId, array $file, string $comment = ''): int
    {
        $pdo = Database::getInstance();

        // Count previous attempts
        $attempt = $pdo->prepare(
            'SELECT COALESCE(MAX(attempt),0)+1 FROM assignment_submissions
             WHERE assignment_id=? AND user_id=?'
        );
        $attempt->execute([$assignmentId, $userId]);
        $attemptNo = (int)$attempt->fetchColumn();

        $assignment = $pdo->prepare('SELECT * FROM assignments WHERE id=?');
        $assignment->execute([$assignmentId]);
        $asgn = $assignment->fetch();

        if ($attemptNo > (int)($asgn['max_attempts'] ?? 3)) {
            throw new \RuntimeException('Maximum submission attempts reached.');
        }

        // Save file
        $dir = STORE_PATH . '/uploads/assignments/' . $assignmentId . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = explode(',', $asgn['allowed_types'] ?? 'pdf,zip,doc,docx,jpg,png');
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('File type .' . $ext . ' is not allowed.');
        }
        if ($file['size'] > (int)($asgn['max_file_mb'] ?? 20) * 1024 * 1024) {
            throw new \RuntimeException('File too large (max ' . $asgn['max_file_mb'] . 'MB).');
        }

        $filename = 'sub_' . $userId . '_' . $attemptNo . '_' . time() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $dir . $filename);
        $hash = hash_file('sha256', $dir . $filename);

        // Basic plagiarism: check if same hash exists from another user
        $dup = $pdo->prepare(
            'SELECT COUNT(*) FROM assignment_submissions
             WHERE assignment_id=? AND file_hash=? AND user_id!=?'
        );
        $dup->execute([$assignmentId, $hash, $userId]);
        if ((int)$dup->fetchColumn() > 0) {
            // Flag but don't block — just note it
            $comment .= ' [PLAGIARISM_FLAG]';
        }

        $pdo->prepare(
            'INSERT INTO assignment_submissions
             (assignment_id, enrollment_id, user_id, attempt, file_path, file_name, file_size, file_hash, comment)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([
            $assignmentId, $enrollmentId, $userId, $attemptNo,
            'assignments/' . $assignmentId . '/' . $filename,
            $file['name'], $file['size'], $hash, $comment,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function grade(int $submissionId, int $score, string $feedback, int $gradedBy): void
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM assignment_submissions WHERE id=? LIMIT 1');
        $stmt->execute([$submissionId]);
        $sub  = $stmt->fetch();
        if (!$sub) return;

        $asgn = $pdo->prepare('SELECT * FROM assignments WHERE id=?');
        $asgn->execute([$sub['assignment_id']]);
        $asgn = $asgn->fetch();

        $status = $score >= (int)($asgn['pass_score'] ?? 50) ? 'pass' : 'fail';

        $pdo->prepare(
            'UPDATE assignment_submissions
             SET score=?, feedback=?, status=?, graded_at=NOW(), graded_by=? WHERE id=?'
        )->execute([$score, $feedback, $status, $gradedBy, $submissionId]);

        // If passed, mark lesson complete
        if ($status === 'pass') {
            EnrollmentService::markLessonComplete($sub['enrollment_id'], $asgn['lesson_id'] ?? 0);
        }
    }

    public static function submissionsForCourse(int $courseId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT s.*, a.title AS assignment_title, a.max_score, a.pass_score,
                    l.title AS lesson_title,
                    CONCAT(u.first_name," ",u.last_name) AS student_name, u.email
             FROM assignment_submissions s
             JOIN assignments a ON a.id=s.assignment_id
             JOIN lessons l ON l.id=a.lesson_id
             JOIN courses c ON c.id=l.course_id
             JOIN users u ON u.id=s.user_id
             WHERE c.id=?
             ORDER BY s.submitted_at DESC'
        );
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public static function mySubmissions(int $userId, int $assignmentId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT * FROM assignment_submissions
             WHERE user_id=? AND assignment_id=?
             ORDER BY attempt DESC'
        );
        $stmt->execute([$userId, $assignmentId]);
        return $stmt->fetchAll();
    }
}

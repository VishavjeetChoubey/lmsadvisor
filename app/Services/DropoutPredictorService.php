<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * DropoutPredictorService — calculates risk score for each active enrollment.
 *
 * Risk factors (weighted 0–100 score):
 *   - Days since last login       (30 pts max)
 *   - Progress stall (days no new lesson) (25 pts)
 *   - Quiz failure rate           (20 pts)
 *   - Low progress + days enrolled (15 pts)
 *   - Forum/notes engagement      (-10 pts bonus for active students)
 *
 * Risk levels: low (<30), medium (30–60), high (60–80), critical (>80)
 */
class DropoutPredictorService
{
    public static function recalculateAll(): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query(
            "SELECT e.id AS enrollment_id, e.user_id, e.enrolled_at, e.course_id,
                    c.id AS cid, c.title AS course_title,
                    u.email, u.first_name, u.last_name, u.last_login_at
             FROM enrollments e
             JOIN users u ON u.id = e.user_id
             JOIN courses c ON c.id = e.course_id
             WHERE e.status = 'active'"
        );
        $enrollments = $stmt->fetchAll();
        $count = 0;
        foreach ($enrollments as $enrol) {
            self::calculateForEnrollment($enrol);
            $count++;
        }
        return $count;
    }

    public static function calculateForEnrollment(array $enrol): array
    {
        $pdo    = Database::getInstance();
        $score  = 0;
        $factors = [];

        $now = time();

        // ── Factor 1: Days since last login (30 pts) ──────────────────────────
        $lastLogin = $enrol['last_login_at'] ? strtotime($enrol['last_login_at']) : strtotime($enrol['enrolled_at']);
        $daysSinceLogin = (int)(($now - $lastLogin) / 86400);
        $loginScore = min(30, $daysSinceLogin * 2); // 15+ days = max points
        $score += $loginScore;
        $factors['days_since_login'] = $daysSinceLogin;
        if ($daysSinceLogin > 7) {
            $factors['alerts'][] = "No login for {$daysSinceLogin} days";
        }

        // ── Factor 2: Progress stall ──────────────────────────────────────────
        $lastActivity = $pdo->prepare(
            'SELECT MAX(last_accessed) FROM lesson_progress WHERE enrollment_id=?'
        );
        $lastActivity->execute([$enrol['enrollment_id']]);
        $lastAccess = $lastActivity->fetchColumn();
        $daysSinceProgress = $lastAccess
            ? (int)(($now - strtotime($lastAccess)) / 86400)
            : (int)(($now - strtotime($enrol['enrolled_at'])) / 86400);
        $progressScore = min(25, $daysSinceProgress * 1.5);
        $score += $progressScore;
        $factors['days_since_progress'] = $daysSinceProgress;

        // ── Factor 3: Quiz failure rate ───────────────────────────────────────
        $quizStmt = $pdo->prepare(
            'SELECT COUNT(*) AS total, SUM(CASE WHEN passed=0 THEN 1 ELSE 0 END) AS failed
             FROM quiz_attempts WHERE enrollment_id=? AND completed_at IS NOT NULL'
        );
        $quizStmt->execute([$enrol['enrollment_id']]);
        $quizData  = $quizStmt->fetch();
        $quizTotal = (int)($quizData['total'] ?? 0);
        $quizFailed= (int)($quizData['failed'] ?? 0);
        if ($quizTotal > 0) {
            $failRate = $quizFailed / $quizTotal;
            $quizScore = (int)($failRate * 20);
            $score += $quizScore;
            $factors['quiz_fail_rate'] = round($failRate * 100, 1);
            if ($failRate > 0.5) {
                $factors['alerts'][] = round($failRate * 100) . '% quiz failure rate';
            }
        }

        // ── Factor 4: Low progress relative to time enrolled ─────────────────
        $progressStmt = $pdo->prepare(
            'SELECT COUNT(*) AS completed FROM lesson_progress
             WHERE enrollment_id=? AND status="completed"'
        );
        $progressStmt->execute([$enrol['enrollment_id']]);
        $completedLessons = (int)($progressStmt->fetchColumn() ?? 0);

        $totalLessons = (int)($pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id=?')
            ->execute([$enrol['course_id']]) ? 0 : 0);

        // Simpler: get total from course
        $tlStmt = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id=?');
        $tlStmt->execute([$enrol['course_id']]);
        $totalLessons = (int)$tlStmt->fetchColumn();

        $daysEnrolled = max(1, (int)(($now - strtotime($enrol['enrolled_at'])) / 86400));
        $expectedProgress = min(100, $daysEnrolled * 5); // Expect ~5% per day early on
        $actualProgress = $totalLessons > 0 ? ($completedLessons / $totalLessons * 100) : 0;

        if ($actualProgress < $expectedProgress / 2 && $daysEnrolled > 3) {
            $score += 15;
            $factors['progress_gap'] = round($expectedProgress - $actualProgress, 1);
            $factors['alerts'][] = 'Progress significantly behind expected pace';
        }
        $factors['progress_pct'] = round($actualProgress, 1);

        // ── Bonus: Active engagement ──────────────────────────────────────────
        $engageStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM lesson_notes WHERE user_id=?'
        );
        $engageStmt->execute([$enrol['user_id']]);
        $noteCount = (int)$engageStmt->fetchColumn();
        if ($noteCount > 3) {
            $score = max(0, $score - 10);
            $factors['engagement_bonus'] = true;
        }

        // Clamp
        $score = min(100, max(0, (int)$score));

        $riskLevel = match(true) {
            $score >= 80 => 'critical',
            $score >= 60 => 'high',
            $score >= 30 => 'medium',
            default      => 'low',
        };

        // Upsert into dropout_risk
        $pdo->prepare(
            'INSERT INTO dropout_risk (user_id, enrollment_id, risk_score, risk_level, factors)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE risk_score=VALUES(risk_score), risk_level=VALUES(risk_level),
                                     factors=VALUES(factors), calculated_at=NOW()'
        )->execute([
            $enrol['user_id'],
            $enrol['enrollment_id'],
            $score,
            $riskLevel,
            json_encode($factors),
        ]);

        return ['score' => $score, 'level' => $riskLevel, 'factors' => $factors];
    }

    public static function getAtRisk(string $minLevel = 'medium', int $limit = 50): array
    {
        $pdo   = Database::getInstance();
        $levels = match($minLevel) {
            'critical' => ['critical'],
            'high'     => ['critical','high'],
            default    => ['critical','high','medium'],
        };
        $in = implode(',', array_fill(0, count($levels), '?'));
        $stmt = $pdo->prepare(
            "SELECT dr.*, u.first_name, u.last_name, u.email,
                    c.title AS course_title, c.uuid AS course_uuid,
                    e.enrolled_at, e.status AS enrollment_status
             FROM dropout_risk dr
             JOIN users u ON u.id = dr.user_id
             JOIN enrollments e ON e.id = dr.enrollment_id
             JOIN courses c ON c.id = e.course_id
             WHERE dr.risk_level IN ({$in})
             ORDER BY dr.risk_score DESC
             LIMIT {$limit}"
        );
        $stmt->execute($levels);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['factors'] = json_decode($r['factors'] ?? '{}', true);
        }
        return $rows;
    }
}

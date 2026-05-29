<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * AdaptiveQuizService — adjusts question difficulty in real time
 * based on student performance during a quiz attempt.
 *
 * Algorithm (3-Up-1-Down):
 *   - 3 correct in a row → next question is HARDER (+1 difficulty)
 *   - 1 wrong → next question is EASIER (-1 difficulty)
 *   - Start at the quiz's configured start difficulty (default: medium=2)
 *   - Difficulty clamps to 1–3 (easy/medium/hard)
 */
class AdaptiveQuizService
{
    // ── Difficulty labels ─────────────────────────────────────────────────────
    public static function difficultyLabel(int $d): string
    {
        return match($d) { 1 => 'Easy', 3 => 'Hard', default => 'Medium' };
    }

    // ── Get next question for adaptive mode ───────────────────────────────────

    /**
     * Select the next question for an in-progress adaptive attempt.
     * Returns question array or null if quiz is complete.
     */
    public static function nextQuestion(int $attemptId, int $quizId, int $userId): ?array
    {
        $pdo = Database::getInstance();

        // Load already-answered question IDs in this attempt
        $stmt = $pdo->prepare(
            'SELECT question_id, is_correct FROM question_responses WHERE attempt_id=? ORDER BY answered_at'
        );
        $stmt->execute([$attemptId]);
        $answered = $stmt->fetchAll();

        $answeredIds  = array_column($answered, 'question_id');
        $correctStreak = 0;
        foreach (array_reverse($answered) as $a) {
            if ($a['is_correct']) $correctStreak++;
            else break;
        }

        // Determine current difficulty
        $quiz = $pdo->prepare('SELECT adaptive_start_difficulty FROM quizzes WHERE id=? LIMIT 1');
        $quiz->execute([$quizId]);
        $quizRow = $quiz->fetch();
        $startDiff = (int)($quizRow['adaptive_start_difficulty'] ?? 2);

        // Recalculate current difficulty from history
        $currentDiff = $startDiff;
        foreach ($answered as $a) {
            if ($a['is_correct']) {
                // 3 correct in a row → harder
                // (handled below via streak count)
            } else {
                $currentDiff = max(1, $currentDiff - 1);
                $correctStreak = 0;
            }
        }
        if ($correctStreak >= 3) {
            $currentDiff = min(3, $currentDiff + 1);
        }

        // Total questions available
        $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE quiz_id=?');
        $totalStmt->execute([$quizId]);
        $total = (int)$totalStmt->fetchColumn();

        if (count($answeredIds) >= $total) return null; // Quiz complete

        // Try to find question at current difficulty — fallback to any unanswered
        $placeholders = $answeredIds ? implode(',', array_fill(0, count($answeredIds), '?')) : '0';
        $params       = array_merge([$quizId, $currentDiff], $answeredIds);

        $stmt = $pdo->prepare(
            "SELECT q.*, GROUP_CONCAT(qo.id,'::',qo.option_text,'::',qo.is_correct ORDER BY qo.sort_order SEPARATOR '||') AS options_raw
             FROM questions q
             LEFT JOIN question_options qo ON qo.question_id = q.id
             WHERE q.quiz_id = ? AND q.difficulty = ? AND q.id NOT IN ({$placeholders})
             GROUP BY q.id
             ORDER BY RAND() LIMIT 1"
        );
        $stmt->execute($params);
        $question = $stmt->fetch();

        if (!$question) {
            // Fallback: any unanswered question
            $params2 = array_merge([$quizId], $answeredIds);
            $stmt2   = $pdo->prepare(
                "SELECT q.*, GROUP_CONCAT(qo.id,'::',qo.option_text,'::',qo.is_correct ORDER BY qo.sort_order SEPARATOR '||') AS options_raw
                 FROM questions q
                 LEFT JOIN question_options qo ON qo.question_id = q.id
                 WHERE q.quiz_id = ? AND q.id NOT IN ({$placeholders})
                 GROUP BY q.id
                 ORDER BY ABS(q.difficulty - {$currentDiff}), RAND() LIMIT 1"
            );
            $stmt2->execute($params2);
            $question = $stmt2->fetch();
        }

        if (!$question) return null;

        // Parse options
        $question['options'] = self::parseOptions($question['options_raw'] ?? '');
        unset($question['options_raw']);
        $question['current_difficulty'] = $currentDiff;
        $question['answered_count']     = count($answeredIds);
        $question['total']              = $total;

        return $question;
    }

    // ── Record an answer ──────────────────────────────────────────────────────

    public static function recordAnswer(
        int $attemptId, int $questionId, int $userId,
        int $selectedOptionId, int $timeSec = 0
    ): array {
        $pdo = Database::getInstance();

        // Check correctness
        $optStmt = $pdo->prepare('SELECT is_correct FROM question_options WHERE id=? AND question_id=? LIMIT 1');
        $optStmt->execute([$selectedOptionId, $questionId]);
        $opt = $optStmt->fetch();
        $isCorrect = (int)(bool)($opt['is_correct'] ?? 0);

        // Record response
        $pdo->prepare(
            'INSERT INTO question_responses (attempt_id, question_id, user_id, is_correct, time_sec)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE is_correct=VALUES(is_correct), time_sec=VALUES(time_sec)'
        )->execute([$attemptId, $questionId, $userId, $isCorrect, $timeSec]);

        // Update question performance stats
        $pdo->prepare(
            'UPDATE questions SET times_shown=times_shown+1, times_correct=times_correct+? WHERE id=?'
        )->execute([$isCorrect, $questionId]);

        // Get correct answer(s)
        $correctStmt = $pdo->prepare('SELECT id, option_text FROM question_options WHERE question_id=? AND is_correct=1');
        $correctStmt->execute([$questionId]);
        $correctOptions = $correctStmt->fetchAll();

        $questionStmt = $pdo->prepare('SELECT explanation, difficulty FROM questions WHERE id=? LIMIT 1');
        $questionStmt->execute([$questionId]);
        $qData = $questionStmt->fetch();

        return [
            'is_correct'      => (bool)$isCorrect,
            'correct_options' => $correctOptions,
            'explanation'     => $qData['explanation'] ?? '',
            'difficulty'      => $qData['difficulty'] ?? 2,
        ];
    }

    // ── Calculate final score ─────────────────────────────────────────────────

    public static function finalScore(int $attemptId, int $quizId): array
    {
        $pdo = Database::getInstance();

        $totalQuestions = (int)$pdo->prepare('SELECT COUNT(*) FROM questions WHERE quiz_id=?')
                                    ->execute([$quizId]) ? $pdo->query('SELECT COUNT(*) FROM questions WHERE quiz_id=' . $quizId)->fetchColumn() : 0;

        $stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(is_correct) AS correct FROM question_responses WHERE attempt_id=?');
        $stmt->execute([$attemptId]);
        $row = $stmt->fetch();

        $answered = (int)($row['total'] ?? 0);
        $correct  = (int)($row['correct'] ?? 0);
        $score    = $answered > 0 ? round($correct / $answered * 100, 1) : 0;

        return ['score' => $score, 'correct' => $correct, 'answered' => $answered];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function parseOptions(string $raw): array
    {
        if (!$raw) return [];
        return array_map(function($opt) {
            [$id, $text, $correct] = array_pad(explode('::', $opt), 3, '');
            return ['id' => (int)$id, 'text' => $text, 'is_correct' => (bool)$correct];
        }, explode('||', $raw));
    }
}

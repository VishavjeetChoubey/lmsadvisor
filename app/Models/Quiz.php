<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Quiz extends Model
{
    protected string $table = 'quizzes';

    // ── Fetch ─────────────────────────────────────────────────────────────────

    public function findByLesson(int $lessonId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM quizzes WHERE lesson_id = ? LIMIT 1',
            [$lessonId]
        );
    }

    public function findWithQuestions(int $quizId): ?array
    {
        $quiz = $this->queryOne('SELECT * FROM quizzes WHERE id = ? LIMIT 1', [$quizId]);
        if (!$quiz) return null;
        $quiz['questions'] = $this->questionsWithOptions($quizId);
        return $quiz;
    }

    public function questionsWithOptions(int $quizId): array
    {
        $questions = $this->query(
            'SELECT * FROM questions WHERE quiz_id = ? ORDER BY sort_order, id',
            [$quizId]
        );
        foreach ($questions as &$q) {
            $q['options'] = $this->query(
                'SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order, id',
                [$q['id']]
            );
        }
        return $questions;
    }

    // ── Quiz CRUD ─────────────────────────────────────────────────────────────

    public function create(array $d): int
    {
        return $this->insert(
            'INSERT INTO quizzes
             (lesson_id, title, description, time_limit_sec, pass_percentage,
              shuffle_questions, shuffle_options, show_answers_after, max_attempts)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [
                (int)$d['lesson_id'],
                $d['title'],
                $d['description'] ?? null,
                ($d['time_limit_sec'] ?? '') !== '' ? (int)$d['time_limit_sec'] : null,
                (int)($d['pass_percentage'] ?? 70),
                (int)(bool)($d['shuffle_questions'] ?? 0),
                (int)(bool)($d['shuffle_options'] ?? 0),
                (int)(bool)($d['show_answers_after'] ?? 1),
                (int)($d['max_attempts'] ?? 3),
            ]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            'UPDATE quizzes SET
               title=?, description=?, time_limit_sec=?, pass_percentage=?,
               shuffle_questions=?, shuffle_options=?, show_answers_after=?, max_attempts=?,
               is_required=?
             WHERE id=?',
            [
                $d['title'],
                $d['description'] ?? null,
                ($d['time_limit_sec'] ?? '') !== '' ? (int)$d['time_limit_sec'] : null,
                (int)($d['pass_percentage'] ?? 70),
                (int)(bool)($d['shuffle_questions'] ?? 0),
                (int)(bool)($d['shuffle_options'] ?? 0),
                (int)(bool)($d['show_answers_after'] ?? 1),
                (int)($d['max_attempts'] ?? 3),
                (int)($d['is_required'] ?? 0),
                $id,
            ]
        );
    }

    // ── Question CRUD ─────────────────────────────────────────────────────────

    public function addQuestion(array $d): int
    {
        return $this->insert(
            'INSERT INTO questions (quiz_id, question, explanation, type, points, sort_order)
             VALUES (?,?,?,?,?,?)',
            [
                (int)$d['quiz_id'],
                $d['question'],
                $d['explanation'] ?? null,
                $d['type'] ?? 'single',
                (int)($d['points'] ?? 1),
                (int)($d['sort_order'] ?? 0),
            ]
        );
    }

    public function updateQuestion(int $id, array $d): void
    {
        $this->execute(
            'UPDATE questions SET question=?, explanation=?, type=?, points=?, sort_order=?,
             order_items=?, acceptable_answers=?, match_pairs=? WHERE id=?',
            [
                $d['question'],
                $d['explanation'] ?? null,
                $d['type'] ?? 'single',
                (int)($d['points'] ?? 1),
                (int)($d['sort_order'] ?? 0),
                $d['order_items']        ?? null,
                $d['acceptable_answers'] ?? null,
                $d['match_pairs']        ?? null,
                $id,
            ]
        );
    }

    public function deleteQuestion(int $id): void
    {
        $this->execute('DELETE FROM questions WHERE id = ?', [$id]);
    }

    public function reorderQuestions(array $ids): void
    {
        foreach ($ids as $order => $id) {
            $this->execute('UPDATE questions SET sort_order=? WHERE id=?', [$order, (int)$id]);
        }
    }

    // ── Option CRUD ───────────────────────────────────────────────────────────

    public function setOptions(int $questionId, array $options): void
    {
        // Delete existing
        $this->execute('DELETE FROM question_options WHERE question_id = ?', [$questionId]);

        foreach ($options as $i => $opt) {
            $this->insert(
                'INSERT INTO question_options (question_id, option_text, is_correct, sort_order)
                 VALUES (?,?,?,?)',
                [
                    $questionId,
                    $opt['text'],
                    (int)(bool)($opt['is_correct'] ?? 0),
                    $i,
                ]
            );
        }
    }

    // ── Attempts ──────────────────────────────────────────────────────────────

    public function countAttempts(int $quizId, int $userId): int
    {
        $row = $this->queryOne(
            'SELECT COUNT(*) AS cnt FROM quiz_attempts
             WHERE quiz_id=? AND user_id=? AND completed_at IS NOT NULL',
            [$quizId, $userId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function startAttempt(int $quizId, int $userId, int $enrollmentId): int
    {
        return $this->insert(
            'INSERT INTO quiz_attempts (quiz_id, user_id, enrollment_id) VALUES (?,?,?)',
            [$quizId, $userId, $enrollmentId]
        );
    }

    public function completeAttempt(int $attemptId, float $score, bool $passed, array $answers, int $timeSec): void
    {
        $this->execute(
            'UPDATE quiz_attempts
             SET score=?, passed=?, answers=?, time_taken_sec=?, completed_at=NOW()
             WHERE id=?',
            [$score, (int)$passed, json_encode($answers), $timeSec, $attemptId]
        );
    }

    public function attemptsForUser(int $quizId, int $userId): array
    {
        return $this->query(
            'SELECT * FROM quiz_attempts WHERE quiz_id=? AND user_id=? ORDER BY started_at DESC',
            [$quizId, $userId]
        );
    }

    public function bestScore(int $quizId, int $userId): ?float
    {
        $row = $this->queryOne(
            'SELECT MAX(score) AS best FROM quiz_attempts
             WHERE quiz_id=? AND user_id=? AND completed_at IS NOT NULL',
            [$quizId, $userId]
        );
        return ($row && $row['best'] !== null) ? (float)$row['best'] : null;
    }

    // ── Stats for admin ───────────────────────────────────────────────────────

    public function stats(int $quizId): array
    {
        $row = $this->queryOne(
            'SELECT
               COUNT(*) AS total_attempts,
               COUNT(CASE WHEN passed=1 THEN 1 END) AS passes,
               COUNT(CASE WHEN passed=0 THEN 1 END) AS fails,
               ROUND(AVG(score),1) AS avg_score,
               ROUND(MAX(score),1) AS best_score,
               ROUND(AVG(time_taken_sec),0) AS avg_time_sec
             FROM quiz_attempts WHERE quiz_id=? AND completed_at IS NOT NULL',
            [$quizId]
        );
        if (!$row) return [];
        return [
            'total_attempts' => (int)$row['total_attempts'],
            'passes'         => (int)$row['passes'],
            'fails'          => (int)$row['fails'],
            'avg_score'      => $row['avg_score'] !== null ? (float)$row['avg_score'] : null,
            'best_score'     => $row['best_score'] !== null ? (float)$row['best_score'] : null,
            'avg_time_sec'   => $row['avg_time_sec'] !== null ? (int)$row['avg_time_sec'] : null,
        ];
    }
}

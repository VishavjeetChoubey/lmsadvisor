<?php
declare(strict_types=1);

namespace App\Services;

class GradingService
{
    /**
     * Grade a quiz submission.
     *
     * @param array $questions  Questions with options (from Quiz::questionsWithOptions)
     * @param array $submitted  ['question_id' => 'answer_value_or_array']
     * @return array {
     *   score: float (0-100),
     *   points_earned: int,
     *   points_possible: int,
     *   passed: bool,
     *   results: [ {question_id, correct, points_earned, correct_options} ]
     * }
     */
    public static function grade(array $questions, array $submitted, int $passPercentage = 70): array
    {
        $pointsEarned   = 0;
        $pointsPossible = 0;
        $results        = [];

        foreach ($questions as $q) {
            $qId   = $q['id'];
            $type  = $q['type'];
            $pts   = (int)$q['points'];
            $pointsPossible += $pts;

            $answer  = $submitted[$qId] ?? null;
            $correct = false;

            $correctOptionIds = array_column(
                array_filter($q['options'], fn($o) => (bool)$o['is_correct']),
                'id'
            );

            switch ($type) {
                case 'single':
                case 'true_false':
                    // Answer is a single option id
                    $correct = in_array((int)$answer, $correctOptionIds, true);
                    break;

                case 'multiple':
                    // Answer is array of option ids; must match exactly
                    if (is_array($answer)) {
                        $submitted_ids = array_map('intval', $answer);
                        sort($submitted_ids);
                        sort($correctOptionIds);
                        $correct = $submitted_ids === $correctOptionIds;
                    }
                    break;

                case 'fill_blank':
                    // Answer is text; compare case-insensitively against correct option text
                    $correctTexts = array_map(
                        fn($o) => strtolower(trim($o['option_text'])),
                        array_filter($q['options'], fn($o) => (bool)$o['is_correct'])
                    );
                    $correct = in_array(strtolower(trim((string)$answer)), $correctTexts, true);
                    break;

                case 'short_answer':
                    // Compare against acceptable_answers JSON array (case-insensitive)
                    $acceptable = json_decode($q['acceptable_answers'] ?? '[]', true) ?: [];
                    $acceptable = array_map(fn($a) => strtolower(trim($a)), $acceptable);
                    $correct = in_array(strtolower(trim((string)$answer)), $acceptable, true);
                    break;

                case 'ordering':
                    // Answer is JSON array of item strings; must match order_items exactly
                    $correctOrder = json_decode($q['order_items'] ?? '[]', true) ?: [];
                    $submitted_order = is_string($answer) ? json_decode($answer, true) : $answer;
                    if (is_array($submitted_order) && is_array($correctOrder)) {
                        $correct = array_values($submitted_order) === array_values($correctOrder);
                    }
                    break;

                case 'matching':
                    // Answer is array [pairIndex => selectedRight]
                    // Compare each against the correct right value for that pair index
                    $pairs = json_decode($q['match_pairs'] ?? '[]', true) ?: [];
                    if (is_array($answer) && count($answer) === count($pairs)) {
                        $correct = true;
                        foreach ($pairs as $pi => $pair) {
                            $given   = strtolower(trim((string)($answer[$pi] ?? '')));
                            $expected = strtolower(trim($pair['right']));
                            if ($given !== $expected) { $correct = false; break; }
                        }
                    }
                    break;
            }

            if ($correct) {
                $pointsEarned += $pts;
            }

            $results[$qId] = [
                'question_id'     => $qId,
                'correct'         => $correct,
                'points_earned'   => $correct ? $pts : 0,
                'correct_options' => $correctOptionIds,
                'submitted'       => $answer,
            ];
        }

        $score  = $pointsPossible > 0
            ? round(($pointsEarned / $pointsPossible) * 100, 2)
            : 0.0;
        $passed = $score >= $passPercentage;

        return compact('score', 'pointsEarned', 'pointsPossible', 'passed', 'results');
    }

    /**
     * Format seconds as mm:ss string.
     */
    public static function formatTime(int $seconds): string
    {
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}

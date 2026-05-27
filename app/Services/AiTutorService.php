<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;
use App\Helpers\Encryption;

/**
 * AiTutorService — Phase 29: AI Tutor & Personalization
 *
 * All calls share the same provider/key config as course generation (AI Integration tab).
 * Uses claude-sonnet-4 for quality + cost balance.
 */
class AiTutorService
{
    private static function provider(): string
    {
        return Setting::get('ai_provider', 'anthropic');
    }

    private static function enabled(): bool
    {
        return (bool)(int)Setting::get('ai_enabled', 0);
    }

    // ── Lesson Summary ────────────────────────────────────────────────────────

    /**
     * Summarise a text lesson into 3 concise bullet points.
     * Returns ['bullets' => ['...','...','...']]
     */
    public static function summariseLesson(string $htmlContent, string $lessonTitle): array
    {
        self::requireEnabled();
        $text   = strip_tags($htmlContent);
        $prompt = "You are an expert educational summariser.\n\n"
            . "Lesson title: {$lessonTitle}\n\nLesson content:\n{$text}\n\n"
            . "Produce EXACTLY 3 concise bullet points summarising the key takeaways from this lesson. "
            . "Each bullet should be one sentence, actionable, and specific. "
            . "Return ONLY a JSON array of 3 strings, no other text:\n[\"bullet1\",\"bullet2\",\"bullet3\"]";

        $raw    = self::callAi($prompt, 300);
        $bullets = json_decode($raw, true);
        if (!is_array($bullets) || count($bullets) < 1) {
            // Fallback: split by newline
            $lines   = array_filter(explode("\n", $raw), fn($l) => trim($l) !== '');
            $bullets = array_values(array_slice($lines, 0, 3));
        }
        return ['bullets' => array_slice($bullets, 0, 3)];
    }

    // ── Chat / Tutor ──────────────────────────────────────────────────────────

    /**
     * Answer a student question in the context of a course lesson.
     * Maintains conversation history (last 10 messages).
     */
    public static function chat(
        int    $userId,
        int    $courseId,
        int    $lessonId,
        string $question,
        array  $courseContext  = [],
        array  $history        = []
    ): array {
        self::requireEnabled();

        $pdo = Database::getInstance();

        // Get or create session
        $sessStmt = $pdo->prepare(
            'SELECT id FROM ai_chat_sessions
             WHERE user_id=? AND course_id=? AND lesson_id=? LIMIT 1'
        );
        $sessStmt->execute([$userId, $courseId, $lessonId]);
        $session  = $sessStmt->fetch();

        if (!$session) {
            $pdo->prepare(
                'INSERT INTO ai_chat_sessions (user_id, course_id, lesson_id) VALUES (?,?,?)'
            )->execute([$userId, $courseId, $lessonId]);
            $sessionId = (int)$pdo->lastInsertId();
        } else {
            $sessionId = (int)$session['id'];
        }

        // Load last 10 messages for context
        $histStmt = $pdo->prepare(
            'SELECT role, content FROM ai_chat_messages
             WHERE session_id=? ORDER BY created_at DESC LIMIT 10'
        );
        $histStmt->execute([$sessionId]);
        $messages = array_reverse($histStmt->fetchAll(\PDO::FETCH_ASSOC));

        // Build system prompt with course context
        $courseName  = $courseContext['course_title'] ?? 'this course';
        $lessonName  = $courseContext['lesson_title'] ?? 'this lesson';
        $lessonText  = isset($courseContext['lesson_content'])
            ? substr(strip_tags($courseContext['lesson_content']), 0, 3000)
            : '';

        $systemPrompt = "You are an expert AI tutor for the course \"{$courseName}\", "
            . "specifically helping a student with the lesson \"{$lessonName}\".\n\n"
            . ($lessonText ? "Lesson content context:\n{$lessonText}\n\n" : '')
            . "Rules:\n"
            . "- Answer only questions related to this course or general learning\n"
            . "- Be concise, encouraging, and pedagogically helpful\n"
            . "- If unsure, say so honestly rather than making things up\n"
            . "- Use examples relevant to the lesson content\n"
            . "- Format responses with markdown (bold, bullet points) for clarity";

        // Add current question
        $messages[] = ['role' => 'user', 'content' => $question];

        $answer = self::callAiChat($systemPrompt, $messages, 600);

        // Persist both messages
        $pdo->prepare(
            'INSERT INTO ai_chat_messages (session_id, role, content) VALUES (?,?,?)'
        )->execute([$sessionId, 'user', $question]);
        $pdo->prepare(
            'INSERT INTO ai_chat_messages (session_id, role, content) VALUES (?,?,?)'
        )->execute([$sessionId, 'assistant', $answer]);

        return ['answer' => $answer, 'session_id' => $sessionId];
    }

    // ── Quiz Question Generation ──────────────────────────────────────────────

    /**
     * Generate MCQ questions from lesson HTML content.
     * Returns array of question objects ready to insert into DB.
     */
    public static function generateQuizQuestions(string $htmlContent, string $lessonTitle, int $count = 5): array
    {
        self::requireEnabled();
        $text   = substr(strip_tags($htmlContent), 0, 4000);
        $prompt = "You are an expert quiz creator for e-learning.\n\n"
            . "Lesson: {$lessonTitle}\nContent:\n{$text}\n\n"
            . "Generate EXACTLY {$count} MCQ questions based on this content. "
            . "Return ONLY a JSON array, no other text:\n"
            . '[{"question":"...","options":["A","B","C","D"],"correct_index":0,"explanation":"..."}]';

        $raw  = self::callAi($prompt, 2000);
        // Strip markdown fences
        $raw  = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $raw  = preg_replace('/```\s*$/m', '', $raw);
        $data = json_decode(trim($raw), true);
        if (!is_array($data)) return [];

        return array_slice(array_filter($data, fn($q) =>
            !empty($q['question']) && !empty($q['options']) && count($q['options']) >= 2
        ), 0, $count);
    }

    // ── Lesson Translation ────────────────────────────────────────────────────

    public static function translateLesson(string $htmlContent, string $targetLanguage): string
    {
        self::requireEnabled();
        $prompt = "Translate the following HTML educational content to {$targetLanguage}. "
            . "Preserve all HTML tags exactly. Only translate the text content inside tags. "
            . "Return ONLY the translated HTML, nothing else:\n\n{$htmlContent}";

        return self::callAi($prompt, 4000);
    }

    // ── Learning Path Recommendation ─────────────────────────────────────────

    public static function recommendPaths(int $userId): array
    {
        self::requireEnabled();
        $pdo = Database::getInstance();

        // Get user's completed courses + quiz scores
        $completed = $pdo->prepare(
            'SELECT c.title, c.level, cat.name AS category
             FROM enrollments e JOIN courses c ON c.id=e.course_id
             LEFT JOIN categories cat ON cat.id=c.category_id
             WHERE e.user_id=? AND e.status=\'completed\' LIMIT 10'
        );
        $completed->execute([$userId]);
        $done = $completed->fetchAll();

        // Get available learning paths
        $paths = $pdo->query(
            'SELECT lp.id, lp.uuid, lp.title, lp.description,
                    COUNT(lpc.course_id) AS course_count
             FROM learning_paths lp
             LEFT JOIN learning_path_courses lpc ON lpc.path_id=lp.id
             WHERE lp.is_published=1
             GROUP BY lp.id LIMIT 20'
        )->fetchAll();

        if (empty($paths)) return [];

        $completedList = implode(', ', array_column($done, 'title'));
        $pathList      = implode("\n", array_map(fn($p) =>
            "- [{$p['id']}] {$p['title']}: {$p['description']}", $paths));

        $prompt = "You are a learning advisor. A student has completed: {$completedList}.\n\n"
            . "Available learning paths:\n{$pathList}\n\n"
            . "Recommend the TOP 3 most suitable paths for this student. "
            . "Return ONLY a JSON array of path IDs in order of recommendation, e.g. [3,1,7]";

        $raw    = self::callAi($prompt, 100);
        $ids    = json_decode(trim($raw), true);
        if (!is_array($ids)) return $paths;

        // Return paths ordered by recommendation
        $ordered = [];
        foreach ($ids as $id) {
            foreach ($paths as $p) {
                if ((int)$p['id'] === (int)$id) { $ordered[] = $p; break; }
            }
        }
        return $ordered ?: $paths;
    }

    // ── Writing Assistance ────────────────────────────────────────────────────

    public static function improveWriting(string $text, string $context = 'forum post'): string
    {
        self::requireEnabled();
        $prompt = "You are a helpful writing assistant for an e-learning platform.\n"
            . "Improve the following {$context} for clarity, grammar, and professionalism. "
            . "Keep the same meaning and tone. Return ONLY the improved text, no explanation:\n\n{$text}";

        return self::callAi($prompt, 500);
    }

    // ── Core AI Caller ────────────────────────────────────────────────────────

    private static function requireEnabled(): void
    {
        if (!self::enabled()) {
            throw new \RuntimeException('AI features are disabled. Enable them in Settings → AI Integration.');
        }
    }

    private static function callAi(string $prompt, int $maxTokens = 1000): string
    {
        $messages = [['role' => 'user', 'content' => $prompt]];
        return self::callAiChat('', $messages, $maxTokens);
    }

    private static function callAiChat(string $system, array $messages, int $maxTokens = 1000): string
    {
        return match(self::provider()) {
            'openai'    => self::openai($system, $messages, $maxTokens),
            default     => self::anthropic($system, $messages, $maxTokens),
        };
    }

    private static function anthropic(string $system, array $messages, int $maxTokens): string
    {
        $key   = Encryption::decryptIfNeeded(Setting::get('ai_anthropic_key', ''));
        $model = Setting::get('ai_model', 'claude-sonnet-4-20250514');
        if (!$key) throw new \RuntimeException('Anthropic API key not configured.');

        $payload = ['model' => $model, 'max_tokens' => $maxTokens, 'messages' => $messages];
        if ($system) $payload['system'] = $system;

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) throw new \RuntimeException("Anthropic error {$code}");
        $resp = json_decode($raw, true);
        return $resp['content'][0]['text'] ?? '';
    }

    private static function openai(string $system, array $messages, int $maxTokens): string
    {
        $key   = Encryption::decryptIfNeeded(Setting::get('ai_openai_key', ''));
        $model = Setting::get('ai_model', 'gpt-4o');
        if (!$key) throw new \RuntimeException('OpenAI API key not configured.');

        if ($system) array_unshift($messages, ['role' => 'system', 'content' => $system]);
        $payload = ['model' => $model, 'max_tokens' => $maxTokens, 'messages' => $messages];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $key,
            ],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) throw new \RuntimeException("OpenAI error {$code}");
        $resp = json_decode($raw, true);
        return $resp['choices'][0]['message']['content'] ?? '';
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Helpers\Encryption;

class AiCourseService
{
    public static function generate(array $params): array
    {
        $provider = Setting::get('ai_provider', 'anthropic');
        $enabled  = (bool)(int)Setting::get('ai_enabled', 0);

        if (!$enabled) {
            throw new \RuntimeException('AI features are not enabled. Configure them in Settings → AI Integration.');
        }

        $prompt = self::buildPrompt($params);

        return match($provider) {
            'anthropic' => self::callAnthropic($prompt),
            'openai'    => self::callOpenAI($prompt),
            default     => throw new \RuntimeException("Unknown AI provider: {$provider}"),
        };
    }

    private static function buildPrompt(array $p): string
    {
        $topic        = $p['topic']           ?? 'Introduction to Programming';
        $level        = $p['level']           ?? 'beginner';
        $sections     = max(1, (int)($p['num_sections'] ?? 5));
        $lessonsPerSec= max(1, (int)($p['num_lessons']  ?? 3));
        $language     = $p['language']        ?? 'English';
        $extra        = $p['extra_instructions'] ?? '';
        $contentTypes = $p['content_types']   ?? ['text', 'video', 'quiz'];

        // Build type instruction based on selected types
        $typeList     = implode(', ', $contentTypes);
        $hasQuiz      = in_array('quiz', $contentTypes);
        $hasVideo     = in_array('video', $contentTypes);
        $hasText      = in_array('text', $contentTypes);
        $hasDoc       = in_array('document', $contentTypes);
        $hasSCORM     = in_array('scorm', $contentTypes);

        $typeGuide = "Allowed lesson types: $typeList\n";

        if ($hasQuiz && $hasText && $hasVideo) {
            $typeGuide .= "- Mix text lessons (theory), video lessons (demonstrations), and quiz lessons (assessment).\n";
            $typeGuide .= "- Place a quiz lesson at the end of each section to test understanding.\n";
            $typeGuide .= "- Use video for practical demonstrations and text for theory/reference.\n";
        } elseif ($hasQuiz && $hasText) {
            $typeGuide .= "- Use text lessons for theory content and quiz at end of each section.\n";
        } elseif ($hasText) {
            $typeGuide .= "- All lessons should be text type with rich content.\n";
        }

        if ($hasDoc) $typeGuide .= "- Use document type for reference materials, cheat sheets, or worksheets.\n";
        if ($hasSCORM) $typeGuide .= "- Mark interactive exercises as scorm type.\n";

        // Quiz question format instruction
        $quizInstruction = $hasQuiz ? '
For quiz lessons, include a "questions" array with 4-6 questions each:
{
  "title": "Section Quiz: Variables",
  "type": "quiz",
  "description": "Test your understanding of variables and data types",
  "duration_sec": 600,
  "questions": [
    {
      "question": "Which of the following is NOT a valid variable type in PHP?",
      "type": "mcq",
      "options": ["string", "integer", "character", "boolean"],
      "correct_index": 2,
      "explanation": "PHP does not have a character type. Single characters are strings."
    },
    {
      "question": "What symbol is used to declare variables in PHP?",
      "type": "mcq",
      "options": ["@", "$", "#", "%"],
      "correct_index": 1,
      "explanation": "PHP variables always start with the dollar sign $."
    }
  ]
}' : '';

        // Text lesson content instruction
        $textInstruction = $hasText ? '
For text lessons, include a "content_html" field with proper HTML content (headings, paragraphs, bullet lists, code blocks if relevant):
{
  "title": "Introduction to Variables",
  "type": "text",
  "content_html": "<h2>What are Variables?</h2><p>A variable is a container for storing data values...</p><ul><li>Variables must start with $</li><li>Variable names are case-sensitive</li></ul>",
  "description": "Learn what variables are and how to use them",
  "duration_sec": 600
}' : '';

        $videoInstruction = $hasVideo ? '
For video lessons, include video_topic and search_terms to help find the right video:
{
  "title": "Setting Up PHP Environment",
  "type": "video",
  "video_topic": "How to install XAMPP and set up PHP development environment",
  "description": "Step-by-step video guide to installing and configuring PHP",
  "duration_sec": 900
}' : '';

        return <<<PROMPT
You are an expert instructional designer and e-learning content creator.
Generate a COMPLETE, production-ready course in JSON format.

COURSE REQUIREMENTS:
- Topic: {$topic}
- Level: {$level}
- Language: {$language}
- Number of sections: {$sections}
- Lessons per section: {$lessonsPerSec}
{$extra}

CONTENT TYPE RULES:
{$typeGuide}

Return ONLY a valid JSON object with this EXACT structure (no markdown, no explanation):
{
  "title": "Complete course title",
  "short_description": "Compelling one-line summary (max 120 chars)",
  "description": "<h2>Course Overview</h2><p>What students will learn...</p><h3>Prerequisites</h3><p>...</p><h3>What You Will Learn</h3><ul><li>...</li></ul>",
  "level": "{$level}",
  "language": "{$language}",
  "duration_hours": 12,
  "grade_points": 100,
  "sections": [
    {
      "title": "Section title",
      "description": "What this section covers and why it matters",
      "lessons": [
        {lesson objects here}
      ]
    }
  ]
}

LESSON OBJECT FORMATS:
{$textInstruction}
{$videoInstruction}
{$quizInstruction}

IMPORTANT RULES:
1. Generate EXACTLY {$sections} sections with EXACTLY {$lessonsPerSec} lessons each
2. Every lesson must have: title, type, description, duration_sec
3. Quiz lessons MUST include the "questions" array with 4-6 MCQ questions each
4. Text lessons MUST include "content_html" with real educational HTML content
5. Make content genuinely educational and specific to the topic — not generic placeholders
6. The last lesson of the last section should always be a course summary or final assessment
7. Return ONLY the JSON object — no markdown fences, no explanation text
PROMPT;
    }

    private static function callAnthropic(string $prompt): array
    {
        $apiKey = Encryption::decryptIfNeeded(Setting::get('ai_anthropic_key', ''));
        $model  = Setting::get('ai_model', 'claude-sonnet-4-20250514');

        if (!$apiKey) {
            throw new \RuntimeException('Anthropic API key not configured. Go to Settings → AI Integration.');
        }

        $payload = json_encode([
            'model'      => $model,
            'max_tokens' => 8000,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException('Network error: ' . $err);
        if ($code !== 200) {
            $e = json_decode($raw ?: '{}', true);
            throw new \RuntimeException('Anthropic API error ' . $code . ': ' . ($e['error']['message'] ?? $raw));
        }

        $resp    = json_decode($raw, true);
        $content = $resp['content'][0]['text'] ?? '';
        return self::parseJsonResponse($content);
    }

    private static function callOpenAI(string $prompt): array
    {
        $apiKey = Encryption::decryptIfNeeded(Setting::get('ai_openai_key', ''));
        $model  = Setting::get('ai_model', 'gpt-4o');

        if (!$apiKey) {
            throw new \RuntimeException('OpenAI API key not configured. Go to Settings → AI Integration.');
        }

        $payload = json_encode([
            'model'      => $model,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 8000,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            $e = json_decode($raw ?: '{}', true);
            throw new \RuntimeException('OpenAI error ' . $code . ': ' . ($e['error']['message'] ?? $raw));
        }

        $resp    = json_decode($raw, true);
        $content = $resp['choices'][0]['message']['content'] ?? '';
        return self::parseJsonResponse($content);
    }

    private static function parseJsonResponse(string $content): array
    {
        // Strip markdown code fences if present
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/```\s*$/m', '', $content);
        $content = trim($content);

        // Sometimes AI wraps in extra text — extract just the JSON object
        if (!str_starts_with($content, '{')) {
            if (preg_match('/\{.+\}/s', $content, $m)) {
                $content = $m[0];
            }
        }

        $data = json_decode($content, true);
        if (!$data || !isset($data['title'], $data['sections'])) {
            throw new \RuntimeException('AI returned invalid JSON. Raw response: ' . substr($content, 0, 300));
        }

        // Sanitize and normalise
        $data['title']             = strip_tags($data['title'] ?? 'Untitled Course');
        $data['short_description'] = strip_tags($data['short_description'] ?? '');
        // description is allowed to contain HTML
        $data['duration_hours']    = (float)($data['duration_hours'] ?? 10);
        $data['grade_points']      = (int)($data['grade_points'] ?? 100);

        foreach ($data['sections'] as &$sec) {
            $sec['title']       = strip_tags($sec['title'] ?? 'Section');
            $sec['description'] = strip_tags($sec['description'] ?? '');

            foreach ($sec['lessons'] as &$les) {
                $les['title']        = strip_tags($les['title'] ?? 'Lesson');
                $les['type']         = in_array($les['type'] ?? '', ['text','video','quiz','document','scorm'])
                                        ? $les['type'] : 'text';
                $les['description']  = $les['description'] ?? '';
                $les['duration_sec'] = (int)($les['duration_sec'] ?? 600);

                // Normalise quiz questions
                if ($les['type'] === 'quiz' && !empty($les['questions'])) {
                    foreach ($les['questions'] as &$q) {
                        $q['question']      = $q['question'] ?? '';
                        $q['type']          = 'mcq';
                        $q['options']       = array_values($q['options'] ?? []);
                        $q['correct_index'] = (int)($q['correct_index'] ?? 0);
                        $q['explanation']   = $q['explanation'] ?? '';
                    }
                    unset($q);
                }

                // Use content_html for text lessons if provided
                if ($les['type'] === 'text' && !empty($les['content_html'])) {
                    $les['content'] = $les['content_html'];
                }
            }
            unset($les);
        }
        unset($sec);

        return $data;
    }
}

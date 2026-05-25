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
        // Compact lesson format examples (token-efficient)
        $quizInstruction = $hasQuiz ? '
Quiz type MUST include questions array (3-4 per quiz):
{"title":"Quiz Title","type":"quiz","description":"summary","duration_sec":600,"questions":[{"question":"Q?","type":"mcq","options":["A","B","C","D"],"correct_index":0,"explanation":"Why correct"}]}' : '';

        $textInstruction = $hasText ? '
Text type MUST include content_html with real HTML:
{"title":"Lesson Title","type":"text","content_html":"<h2>Title</h2><p>Explanation...</p><ul><li>Point</li></ul>","description":"summary","duration_sec":600}' : '';

        $videoInstruction = $hasVideo ? '
Video type format:
{"title":"Lesson Title","type":"video","video_topic":"search term for video","description":"summary","duration_sec":900}' : '';

        $documentInstruction = $hasDoc ? '
Document type format:
{"title":"Reference Sheet","type":"document","description":"Downloadable reference material","duration_sec":300}' : '';

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
{$documentInstruction}

IMPORTANT RULES:
1. Generate EXACTLY {$sections} sections with EXACTLY {$lessonsPerSec} lessons each
2. Every lesson must have: title, type, description, duration_sec
3. Quiz lessons MUST include the "questions" array with 3-4 MCQ questions each
4. Text lessons MUST include "content_html" with 2-3 paragraphs of real HTML
5. Keep content_html concise — 2-3 short paragraphs max per lesson
6. Make content specific to the topic — no generic placeholders
7. Return ONLY valid JSON — no markdown fences, no text before or after
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
            'max_tokens' => 16000,
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
            'max_tokens' => 16000,
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

        // If JSON is truncated (common with large responses), detect and report clearly
        if ($data === null) {
            $jsonErr = json_last_error_msg();
            $len     = strlen($content);

            // Check if it looks like truncation (ends abruptly without closing braces)
            $trimmed = rtrim($content);
            $isTruncated = !in_array(substr($trimmed, -1), ['}', ']']);

            if ($isTruncated) {
                throw new \RuntimeException(
                    'AI response was cut off (response too long for current token limit). ' .
                    'Try reducing the number of sections or lessons per section, ' .
                    'or remove some content types to keep the response shorter.'
                );
            }

            throw new \RuntimeException(
                'AI returned invalid JSON (' . $jsonErr . '). Raw: ' . substr($content, 0, 200)
            );
        }

        if (!isset($data['title'], $data['sections'])) {
            throw new \RuntimeException(
                'AI response missing required fields (title/sections). Got: ' .
                implode(', ', array_keys($data))
            );
        }

        // Sanitize and normalise
        $data['title']             = strip_tags($data['title'] ?? 'Untitled Course');
        $data['short_description'] = strip_tags($data['short_description'] ?? '');
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

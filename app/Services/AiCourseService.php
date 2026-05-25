<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Helpers\Encryption;

class AiCourseService
{
    /**
     * Generate a course outline using AI (OpenAI or Anthropic).
     *
     * @param array $params  topic, level, num_sections, num_lessons, language, extra_instructions
     * @return array         Generated course structure
     */
    public static function generate(array $params): array
    {
        $provider = Setting::get('ai_provider', 'anthropic');
        $enabled  = (bool)(int)Setting::get('ai_enabled', 0);

        if (!$enabled) {
            throw new \RuntimeException('AI features are not enabled. Please configure them in Settings → AI Integration.');
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
        $topic       = $p['topic']        ?? 'Introduction to Programming';
        $level       = $p['level']        ?? 'beginner';
        $sections    = (int)($p['num_sections'] ?? 5);
        $lessons     = (int)($p['num_lessons']  ?? 3);
        $language    = $p['language']     ?? 'English';
        $extra       = $p['extra_instructions'] ?? '';

        return "You are an expert instructional designer. Generate a complete course outline in JSON format.

Course requirements:
- Topic: {$topic}
- Level: {$level}
- Language: {$language}
- Number of sections: {$sections}
- Lessons per section: {$lessons}
{$extra}

Return ONLY a valid JSON object with this exact structure:
{
  \"title\": \"Course title here\",
  \"short_description\": \"One line summary (max 120 chars)\",
  \"description\": \"Rich HTML description with 3-4 paragraphs covering what students will learn\",
  \"level\": \"{$level}\",
  \"language\": \"{$language}\",
  \"duration_hours\": 10,
  \"grade_points\": 100,
  \"sections\": [
    {
      \"title\": \"Section title\",
      \"description\": \"What this section covers\",
      \"lessons\": [
        {
          \"title\": \"Lesson title\",
          \"type\": \"text\",
          \"description\": \"What this lesson covers (2-3 sentences)\",
          \"duration_sec\": 600
        }
      ]
    }
  ]
}

Lesson types allowed: text, video, quiz
Make the course practical, engaging, and well-structured. Return ONLY JSON, no markdown, no explanation.";
    }

    private static function callAnthropic(string $prompt): array
    {
        $apiKey = Encryption::decryptIfNeeded(Setting::get('ai_anthropic_key', ''));
        $model  = Setting::get('ai_model', 'claude-sonnet-4-20250514');

        if (!$apiKey) {
            throw new \RuntimeException('Anthropic API key not configured in Settings.');
        }

        $payload = json_encode([
            'model'      => $model,
            'max_tokens' => 4096,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$raw || $code !== 200) {
            $err = json_decode($raw ?: '{}', true);
            throw new \RuntimeException('Anthropic API error ' . $code . ': ' . ($err['error']['message'] ?? 'Unknown'));
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
            throw new \RuntimeException('OpenAI API key not configured in Settings.');
        }

        $payload = json_encode([
            'model'    => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 4096,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$raw || $code !== 200) {
            $err = json_decode($raw ?: '{}', true);
            throw new \RuntimeException('OpenAI API error ' . $code . ': ' . ($err['error']['message'] ?? 'Unknown'));
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

        $data = json_decode($content, true);
        if (!$data || !isset($data['title'], $data['sections'])) {
            throw new \RuntimeException('AI returned invalid JSON. Please try again.');
        }

        // Sanitize sections
        foreach ($data['sections'] as &$sec) {
            $sec['title']       = strip_tags($sec['title'] ?? 'Section');
            $sec['description'] = strip_tags($sec['description'] ?? '');
            foreach ($sec['lessons'] as &$les) {
                $les['title']       = strip_tags($les['title'] ?? 'Lesson');
                $les['type']        = in_array($les['type'] ?? '', ['text','video','quiz']) ? $les['type'] : 'text';
                $les['duration_sec']= (int)($les['duration_sec'] ?? 600);
            }
        }

        return $data;
    }
}

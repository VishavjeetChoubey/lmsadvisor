<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Helpers\Encryption;

class SettingsService
{
    // ── Tabs definition ───────────────────────────────────────────────────────

    /** All 8 tabs and their field keys */
    public static function tabs(): array
    {
        return [
            'general'     => 'General',
            'security'    => 'Security',
            'email'       => 'Email (SMTP)',
            'certificates'=> 'Certificates',
            'social_login'=> 'Social Login',
            'webinar'     => 'Webinar',
            'ai'          => 'AI Integration',
            'reviews'     => 'Reviews & Leaderboard',
            'lesson'      => 'Lesson Player',
            'custom_code' => 'Custom Code',
        ];
    }

    /** Keys whose values are encrypted in DB */
    private static array $encrypted = [
        'recaptcha_secret',
        'smtp_pass',
        'social_google_secret',
        'social_github_secret',
        'zoom_api_secret',
        'ai_openai_key',
        'ai_anthropic_key',
    ];

    /** Keys that are boolean toggles */
    private static array $booleans = [
        'recaptcha_enabled',
        'social_google_enabled',
        'social_github_enabled',
        'zoom_enabled',
        'gmeet_enabled',
        'ai_enabled',
        'smtp_enabled',
        'reviews_enabled',
        'reviews_auto_approve',
        'leaderboard_enabled',
        'leaderboard_public',
        // Lesson player toggles
        'lesson_show_ai_tutor',
        'lesson_show_notes',
        'lesson_show_collab_fab',
        'lesson_allow_dark_mode',
    ];

    // ── Load ──────────────────────────────────────────────────────────────────

    /** Map of tab → extra groups to also load */
    private static array $extraGroups = [
        'reviews' => ['leaderboard'],
        'lesson'  => ['lesson'],
    ];

    /** Return all settings for a group, with encrypted values decrypted. */
    public static function loadGroup(string $group): array
    {
        // Load primary group
        $groups = [$group];
        // Load any extra groups that belong to this tab
        if (isset(self::$extraGroups[$group])) {
            $groups = array_merge($groups, self::$extraGroups[$group]);
        }

        $out = [];
        foreach ($groups as $g) {
            $rows = Setting::group($g);
            foreach ($rows as $key => $row) {
                $val = $row['value'] ?? '';
                if (in_array($key, self::$encrypted, true) && $val !== '') {
                    try { $val = Encryption::decrypt($val); } catch (\Throwable) { $val = ''; }
                }
                $out[$key] = $val;
            }
        }
        return $out;
    }

    /** Load a single setting value (decrypted if needed). */
    public static function get(string $key, mixed $default = ''): mixed
    {
        $val = Setting::get($key, $default);
        if (in_array($key, self::$encrypted, true) && $val) {
            try { $val = Encryption::decrypt((string)$val); } catch (\Throwable) { $val = ''; }
        }
        return $val;
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    /**
     * Save posted settings for a given tab group.
     * Handles booleans, encrypted fields, and skips empty passwords.
     */
    public static function saveGroup(string $group, array $posted): void
    {
        // Collect all groups to save for this tab
        $groups = [$group];
        if (isset(self::$extraGroups[$group])) {
            $groups = array_merge($groups, self::$extraGroups[$group]);
        }

        $pdo = \App\Core\Database::getInstance();

        foreach ($groups as $g) {
            $stmt = $pdo->prepare('SELECT `key`, type FROM settings WHERE group_name = ?');
            $stmt->execute([$g]);
            $rows      = $stmt->fetchAll();
            $knownKeys = array_column($rows, 'key');

            foreach ($rows as $row) {
                $key  = $row['key'];

                // Boolean: if not in POST it was unchecked → 0
                if (in_array($key, self::$booleans, true)) {
                    Setting::set($key, isset($posted[$key]) ? '1' : '0');
                    continue;
                }

                // Encrypted: skip if blank (keep existing value)
                if (in_array($key, self::$encrypted, true)) {
                    if (!isset($posted[$key]) || trim($posted[$key]) === '') continue;
                    Setting::set($key, Encryption::encrypt(trim($posted[$key])));
                    continue;
                }

                // Normal text field
                if (array_key_exists($key, $posted)) {
                    Setting::set($key, trim((string)$posted[$key]));
                }
            }

            // Upsert unknown keys for dynamic groups (e.g. custom_code)
            $textareaGroups = ['custom_code'];
            if (in_array($g, $textareaGroups, true)) {
                foreach ($posted as $key => $val) {
                    if (!in_array($key, $knownKeys, true) && preg_match('/^[a-z_]+$/', $key)) {
                        $pdo->prepare(
                            "INSERT INTO settings (`key`, value, type, label, group_name)
                             VALUES (?, ?, 'textarea', ?, ?)
                             ON DUPLICATE KEY UPDATE value = VALUES(value)"
                        )->execute([$key, trim((string)$val), $key, $g]);
                    }
                }
            }
        }
    }

    // ── File upload ───────────────────────────────────────────────────────────

    /**
     * Handle logo/favicon uploads.
     * Returns the stored relative path or null on failure.
     */
    public static function uploadImage(string $inputName, string $settingKey): ?string
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file    = $_FILES[$inputName];
        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];
        $mime    = mime_content_type($file['tmp_name']);

        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Invalid image type. Allowed: PNG, JPG, GIF, WebP, ICO.');
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            throw new \RuntimeException('Image too large. Max 2MB.');
        }

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png');
        $name    = $settingKey . '_' . time() . '.' . $ext;

        // Store inside public/assets/uploads/ so Apache can serve it directly
        $destDir = BASE_PATH . '/public/assets/uploads/';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $dest = $destDir . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Failed to save uploaded file. Check directory permissions on public/assets/uploads/');
        }

        // Delete old file (stored as relative path from public/assets/)
        $old = Setting::get($settingKey, '');
        if ($old) {
            // Support both old format (/assets/uploads/...) and new format (uploads/...)
            $oldAbs = str_starts_with($old, '/')
                ? BASE_PATH . '/public' . $old
                : BASE_PATH . '/public/assets/' . $old;
            if (file_exists($oldAbs)) {
                @unlink($oldAbs);
            }
        }

        // Store as path relative to public/assets/ — used via View::asset()
        // e.g. "uploads/site_logo_1234.png" → View::asset('uploads/site_logo_1234.png')
        $relativePath = 'uploads/' . $name;
        Setting::set($settingKey, $relativePath);
        return $relativePath;
    }

    // ── Test Email ────────────────────────────────────────────────────────────

    /**
     * Send a test email via the configured SMTP settings.
     * Bypasses the queue and smtp_enabled flag so it always attempts delivery.
     */
    public static function sendTestEmail(string $to): array
    {
        $host      = Setting::get('smtp_host', '');
        $user      = Setting::get('smtp_user', '');
        $fromEmail = Setting::get('smtp_from_email', '') ?: $user;

        if (!$host) {
            return [
                'success' => false,
                'message' => 'SMTP not configured. Please fill in SMTP Host, Username, and From Email.',
            ];
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid recipient email address.'];
        }

        // Use testSmtp which bypasses the queue entirely
        return \App\Services\EmailService::testSmtp($to);
    }
}

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
        'reviews_enabled',
        'reviews_auto_approve',
        'leaderboard_enabled',
        'leaderboard_public',
    ];

    // ── Load ──────────────────────────────────────────────────────────────────

    /** Return all settings for a group, with encrypted values decrypted. */
    public static function loadGroup(string $group): array
    {
        $rows = Setting::group($group);
        $out  = [];
        foreach ($rows as $key => $row) {
            $val = $row['value'] ?? '';
            if (in_array($key, self::$encrypted, true) && $val !== '') {
                try { $val = Encryption::decrypt($val); } catch (\Throwable) { $val = ''; }
            }
            $out[$key] = $val;
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
        $pdo  = \App\Core\Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT `key`, type FROM settings WHERE group_name = ?'
        );
        $stmt->execute([$group]);
        $rows       = $stmt->fetchAll();
        $knownKeys  = array_column($rows, 'key');

        foreach ($rows as $row) {
            $key  = $row['key'];
            $type = $row['type'];

            // Boolean: if not in POST it was unchecked
            if (in_array($key, self::$booleans, true)) {
                $val = isset($posted[$key]) ? '1' : '0';
                Setting::set($key, $val);
                continue;
            }

            // Password/secret: skip if blank (keep existing)
            if (in_array($key, self::$encrypted, true)) {
                if (!isset($posted[$key]) || trim($posted[$key]) === '') {
                    continue; // keep old value
                }
                $val = Encryption::encrypt(trim($posted[$key]));
                Setting::set($key, $val);
                continue;
            }

            // Normal field
            if (array_key_exists($key, $posted)) {
                Setting::set($key, trim((string)$posted[$key]));
            }
        }

        // ── Upsert any POSTed keys not yet in DB for this group ───────────────
        // Handles custom_code and future dynamic tabs gracefully
        $textareaGroups = ['custom_code'];
        if (in_array($group, $textareaGroups, true)) {
            foreach ($posted as $key => $val) {
                if (!in_array($key, $knownKeys, true) && preg_match('/^[a-z_]+$/', $key)) {
                    $pdo->prepare(
                        "INSERT INTO settings (`key`, value, type, label, group_name)
                         VALUES (?, ?, 'textarea', ?, ?)
                         ON DUPLICATE KEY UPDATE value = VALUES(value)"
                    )->execute([$key, trim((string)$val), $key, $group]);
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
     * Send a test email using PHP's mail() or basic SMTP.
     * Returns ['success' => bool, 'message' => string].
     */
    public static function sendTestEmail(string $to): array
    {
        $from     = self::get('smtp_from', '');
        $fromName = Setting::get('smtp_from_name', 'LMSAdvisor');
        $subject  = 'LMSAdvisor — Test Email';
        $body     = "This is a test email from LMSAdvisor.\n\nIf you received this, your email settings are working correctly.";

        if (!$from) {
            return ['success' => false, 'message' => 'From address not configured in Email settings.'];
        }

        $headers = "From: {$fromName} <{$from}>\r\n"
                 . "Reply-To: {$from}\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/plain; charset=UTF-8";

        $sent = @mail($to, $subject, $body, $headers);

        if ($sent) {
            return ['success' => true, 'message' => "Test email sent to {$to} successfully."];
        }

        return [
            'success' => false,
            'message' => 'mail() failed. Check your XAMPP/server sendmail configuration or use an SMTP plugin.',
        ];
    }
}

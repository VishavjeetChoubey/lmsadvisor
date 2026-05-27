<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

class EmailService
{
    /** Queue a transactional email. */
    public static function queue(
        string $toEmail,
        string $toName,
        string $templateSlug,
        array  $vars = [],
        ?\DateTime $sendAt = null
    ): bool {
        if (!(bool)(int)Setting::get('smtp_enabled', '0')) return false;

        $pdo = Database::getInstance();
        $tpl = $pdo->prepare(
            'SELECT * FROM email_templates WHERE slug=? AND is_enabled=1 LIMIT 1'
        );
        $tpl->execute([$templateSlug]);
        $template = $tpl->fetch();
        if (!$template) return false;

        // Check unsubscribed
        $unsub = $pdo->prepare('SELECT id FROM email_unsubscribes WHERE email=? LIMIT 1');
        $unsub->execute([$toEmail]);
        if ($unsub->fetch()) return false;

        // Build unsubscribe URL
        $token = bin2hex(random_bytes(24));
        $pdo->prepare(
            'INSERT IGNORE INTO email_unsubscribes (email, token) VALUES (?,?)'
        )->execute([$toEmail, $token]);

        $vars['unsubscribe_url'] = rtrim(Setting::get('site_url', APP_URL), '/')
                                 . '/unsubscribe/' . $token;
        $vars['site_name']  = Setting::get('site_name', 'LMSAdvisor');
        $vars['site_logo']  = Setting::get('site_logo', '');

        $subject  = self::render($template['subject'],  $vars);
        $bodyHtml = self::render($template['body_html'], $vars);

        $pdo->prepare(
            'INSERT INTO email_queue
             (to_email, to_name, subject, body_html, template, scheduled_at)
             VALUES (?,?,?,?,?,?)'
        )->execute([
            $toEmail, $toName, $subject, $bodyHtml, $templateSlug,
            $sendAt ? $sendAt->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /** Process up to $limit pending emails from the queue. */
    public static function processQueue(int $limit = 20): array
    {
        $pdo   = Database::getInstance();
        $rows  = $pdo->prepare(
            'SELECT * FROM email_queue
             WHERE status=\'pending\' AND attempts<3 AND scheduled_at<=NOW()
             ORDER BY scheduled_at ASC LIMIT ?'
        );
        $rows->execute([$limit]);
        $jobs   = $rows->fetchAll();
        $sent   = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            try {
                $ok = self::sendSmtp(
                    $job['to_email'],
                    $job['to_name'] ?? '',
                    $job['subject'],
                    $job['body_html']
                );
                if ($ok) {
                    $pdo->prepare(
                        'UPDATE email_queue SET status=\'sent\', sent_at=NOW() WHERE id=?'
                    )->execute([$job['id']]);
                    $sent++;
                } else {
                    throw new \RuntimeException('SMTP send returned false');
                }
            } catch (\Throwable $e) {
                $attempts = (int)$job['attempts'] + 1;
                $status   = $attempts >= 3 ? 'failed' : 'pending';
                $pdo->prepare(
                    'UPDATE email_queue
                     SET status=?, attempts=?, error_msg=? WHERE id=?'
                )->execute([$status, $attempts, substr($e->getMessage(),0,500), $job['id']]);
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }

    /** Send via PHP's built-in mail() using SMTP settings. */
    private static function sendSmtp(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml
    ): bool {
        $host     = Setting::get('smtp_host', '');
        $port     = (int)Setting::get('smtp_port', '587');
        $user     = Setting::get('smtp_user', '');
        $pass     = \App\Helpers\Encryption::decryptIfNeeded(Setting::get('smtp_pass', ''));
        $fromName = Setting::get('smtp_from_name', Setting::get('site_name', 'LMSAdvisor'));
        $fromEmail= Setting::get('smtp_from_email', $user);

        if (!$host || !$fromEmail) {
            throw new \RuntimeException('SMTP not configured');
        }

        // Use PHPMailer-style manual SMTP (pure PHP, no Composer)
        $socket = fsockopen(
            ($port === 465 ? 'ssl://' : '') . $host,
            $port, $errno, $errstr, 10
        );
        if (!$socket) {
            throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
        }

        $read = function() use ($socket): string {
            $r = '';
            while ($line = fgets($socket, 515)) {
                $r .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $r;
        };
        $cmd = function(string $c) use ($socket, $read): string {
            fwrite($socket, $c . "\r\n");
            return $read();
        };

        $read(); // banner
        $cmd("EHLO {$host}");

        // STARTTLS for port 587
        if ($port === 587) {
            $cmd("STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cmd("EHLO {$host}");
        }

        $cmd("AUTH LOGIN");
        $cmd(base64_encode($user));
        $r = $cmd(base64_encode($pass));
        if (!str_starts_with($r, '235')) {
            fclose($socket);
            throw new \RuntimeException("SMTP auth failed: $r");
        }

        $cmd("MAIL FROM:<{$fromEmail}>");
        $cmd("RCPT TO:<{$toEmail}>");
        $cmd("DATA");

        $boundary = md5(uniqid());
        $headers  = implode("\r\n", [
            "From: {$fromName} <{$fromEmail}>",
            "To: {$toName} <{$toEmail}>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: LMSAdvisor/2.0",
        ]);

        $body = implode("\r\n", [
            "--{$boundary}",
            "Content-Type: text/plain; charset=UTF-8",
            "",
            strip_tags(str_replace(['<br>','<br/>','</p>'], "\n", $bodyHtml)),
            "",
            "--{$boundary}",
            "Content-Type: text/html; charset=UTF-8",
            "",
            $bodyHtml,
            "",
            "--{$boundary}--",
        ]);

        $r = $cmd("{$headers}\r\n\r\n{$body}\r\n.");
        $cmd("QUIT");
        fclose($socket);

        return str_starts_with(trim($r), '250');
    }

    /** Replace {{variable}} placeholders in template. */
    public static function render(string $template, array $vars): string
    {
        foreach ($vars as $key => $val) {
            $template = str_replace('{{' . $key . '}}', (string)$val, $template);
        }
        return $template;
    }

    // ── Trigger helpers — called from controllers ─────────────────────────

    public static function sendEnrollmentConfirmation(array $user, array $course, array $enrollment): void
    {
        self::queue($user['email'], ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
            'enrollment_confirmation', [
                'student_name'    => $user['first_name'] ?? 'Student',
                'course_title'    => $course['title'],
                'course_level'    => ucfirst($course['level'] ?? ''),
                'course_duration' => ($course['duration_hours'] ?? '?') . ' hours',
                'grade_points'    => $course['grade_points'] ?? 0,
                'course_url'      => rtrim(APP_URL,'/') . '/learn/courses/' . $course['uuid'],
            ]
        );
    }

    public static function sendCourseCompletion(array $user, array $course, ?array $cert = null): void
    {
        self::queue($user['email'], ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
            'course_completion', [
                'student_name'    => $user['first_name'] ?? 'Student',
                'course_title'    => $course['title'],
                'grade_points'    => $course['grade_points'] ?? 0,
                'certificate_url' => $cert
                    ? rtrim(APP_URL,'/') . '/certificate/verify/' . $cert['uuid']
                    : rtrim(APP_URL,'/') . '/learn/courses',
                'course_url'      => rtrim(APP_URL,'/') . '/learn/courses/' . $course['uuid'],
            ]
        );
    }

    public static function sendQuizResult(array $user, array $quiz, array $course, int $score, int $passPct): void
    {
        $passed = $score >= $passPct;
        self::queue($user['email'], ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
            'quiz_result', [
                'student_name'    => $user['first_name'] ?? 'Student',
                'quiz_title'      => $quiz['title'],
                'course_title'    => $course['title'],
                'score'           => $score,
                'pass_percentage' => $passPct,
                'result'          => $passed ? 'Passed ✓' : 'Failed ✗',
                'result_emoji'    => $passed ? '🎉' : '😞',
                'result_color'    => $passed ? '#059669' : '#dc2626',
                'course_url'      => rtrim(APP_URL,'/') . '/learn/courses/' . $course['uuid'],
            ]
        );
    }

    public static function sendWebinarReminder(array $user, array $webinar): void
    {
        $dt = new \DateTime($webinar['starts_at']);
        self::queue($user['email'], ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
            'webinar_reminder', [
                'student_name'      => $user['first_name'] ?? 'Student',
                'webinar_title'     => $webinar['title'],
                'webinar_date'      => $dt->format('l, d F Y'),
                'webinar_time'      => $dt->format('H:i') . ' UTC',
                'webinar_duration'  => $webinar['duration_min'] ?? 60,
                'webinar_provider'  => ucfirst($webinar['provider'] ?? 'Online'),
                'join_url'          => $webinar['join_url'] ?? '#',
            ]
        );
    }

    public static function sendCertificateReady(array $user, array $course, array $cert): void
    {
        self::queue($user['email'], ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
            'certificate_ready', [
                'student_name'    => $user['first_name'] ?? 'Student',
                'course_title'    => $course['title'],
                'certificate_url' => rtrim(APP_URL,'/') . '/certificate/verify/' . $cert['uuid'],
            ]
        );
    }
}

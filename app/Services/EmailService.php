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
        $vars['site_name']  = Setting::get('site_name', 'LMS Advisor');
        $logoPath = Setting::get('site_logo', '');
        // Logo must be a full URL for email clients — relative paths show as broken images
        if ($logoPath && !str_starts_with($logoPath, 'http')) {
            $logoPath = rtrim(Setting::get('site_url', APP_URL), '/') . '/' . ltrim($logoPath, '/');
        }
        $vars['site_logo']  = $logoPath;

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

    /**
     * Send email via SMTP.
     * @param bool $debug If true, returns full SMTP conversation instead of throwing
     */
    private static function sendSmtp(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        bool   $debug = false
    ): bool {
        // Always read fresh from DB — never use cached values for SMTP
        \App\Models\Setting::clearCache();
        $host       = Setting::get('smtp_host', '');
        $port       = (int)Setting::get('smtp_port', '587');
        $user       = Setting::get('smtp_user', '');
        $pass       = \App\Helpers\Encryption::decryptIfNeeded(Setting::get('smtp_pass', ''));
        $fromName   = Setting::get('smtp_from_name', Setting::get('site_name', 'LMS Advisor'));
        $fromEmail  = Setting::get('smtp_from_email', '') ?: $user; // fallback to username
        $encryption = Setting::get('smtp_encryption', 'tls');

        $log = []; // debug conversation log

        if (!$host) {
            throw new \RuntimeException('SMTP not configured. Set SMTP Host in Settings → Email.');
        }
        if (!$fromEmail) {
            throw new \RuntimeException('No From Email set. Add From Email or Username in Settings → Email.');
        }

        // ssl:// prefix for port 465 (SSL/TLS), plain socket for 587 (STARTTLS)
        $prefix  = ($encryption === 'ssl' || $port === 465) ? 'ssl://' : '';
        $context = stream_context_create([
            'ssl' => [
                // Allow self-signed / mismatched certs (common on cPanel shared hosting)
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ]);

        $socket = @stream_socket_client(
            "{$prefix}{$host}:{$port}",
            $errno, $errstr, 15,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new \RuntimeException("Cannot connect to {$host}:{$port} — {$errstr} (error {$errno}). Check host/port and that port {$port} is not blocked by your firewall.");
        }

        stream_set_timeout($socket, 15);

        // Read one complete SMTP response (handles multi-line 250-OK\r\n250 OK)
        $read = function() use ($socket, &$log, $debug): string {
            $response = '';
            while ($line = fgets($socket, 1024)) {
                $response .= $line;
                if ($debug) $log[] = "S: " . rtrim($line);
                // Last line of response has space after code: "250 OK"
                if (strlen($line) >= 4 && $line[3] === ' ') break;
            }
            return $response;
        };

        $cmd = function(string $command, bool $hideValue = false) use ($socket, $read, &$log, $debug): string {
            fwrite($socket, $command . "\r\n");
            if ($debug) $log[] = "C: " . ($hideValue ? '***HIDDEN***' : rtrim($command));
            return $read();
        };

        // EHLO uses 'localhost' as fallback — gethostname() can return FQDN that
        // remote servers reject. 'localhost' is universally accepted.
        $myHost = 'localhost';

        $banner = $read(); // Server greeting

        // EHLO
        $ehloResp = $cmd("EHLO {$myHost}");
        if (!str_starts_with($ehloResp, '2')) {
            $cmd("HELO {$myHost}");
        }

        // STARTTLS for port 587
        if ($encryption === 'tls' && $port !== 465) {
            $tlsResp = $cmd("STARTTLS");
            if (!str_starts_with($tlsResp, '220')) {
                @fclose($socket);
                throw new \RuntimeException("STARTTLS rejected by server: {$tlsResp}");
            }
            // Enable crypto — allow self-signed certs (cPanel/shared hosting)
            $ok = @stream_socket_enable_crypto(
                $socket, true,
                STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT |
                STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT |
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );
            if (!$ok) {
                @fclose($socket);
                throw new \RuntimeException("TLS handshake failed with {$host}:{$port}. Try setting Encryption to 'SSL' and Port to 465, or 'None' with Port 587.");
            }
            // Re-EHLO after TLS
            $cmd("EHLO {$myHost}");
        }

        // AUTH LOGIN
        if ($user && $pass) {
            $authResp = $cmd("AUTH LOGIN");
            if (!str_starts_with($authResp, '334')) {
                @fclose($socket);
                throw new \RuntimeException("AUTH LOGIN not accepted: {$authResp}. Server may require AUTH PLAIN or different auth method.");
            }
            $cmd(base64_encode($user), true);
            $authResult = $cmd(base64_encode($pass), true);
            if (!str_starts_with($authResult, '235')) {
                @fclose($socket);
                throw new \RuntimeException("Authentication failed (535): Wrong username or password. Used: {$user}");
            }
        }

        // Envelope
        $mailResp = $cmd("MAIL FROM:<{$fromEmail}>");
        if (!str_starts_with($mailResp, '250')) {
            @fclose($socket);
            throw new \RuntimeException("MAIL FROM rejected: {$mailResp}");
        }

        $rcptResp = $cmd("RCPT TO:<{$toEmail}>");
        if (!str_starts_with($rcptResp, '250') && !str_starts_with($rcptResp, '251')) {
            @fclose($socket);
            throw new \RuntimeException("RCPT TO <{$toEmail}> rejected: {$rcptResp}");
        }

        // Build multipart message
        $boundary = 'LMS_' . bin2hex(random_bytes(8));
        $bodyText = strip_tags(str_replace(['<br>','<br/>','</p>','<p>'], "\n", $bodyHtml));

        $message  = implode("\r\n", [
            "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>",
            "To: =?UTF-8?B?"   . base64_encode($toName ?: $toEmail) . "?= <{$toEmail}>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: LMSAdvisor/" . (defined('APP_VERSION') ? APP_VERSION : '3.0'),
            "Date: " . date('r'),
            "",
            "--{$boundary}",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode($bodyText)),
            "--{$boundary}",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode($bodyHtml)),
            "--{$boundary}--",
        ]);

        $cmd("DATA");
        fwrite($socket, $message . "\r\n.\r\n");
        if ($debug) $log[] = "C: [message body sent]";
        $dataResp = $read();

        $cmd("QUIT");
        @fclose($socket);

        if (!str_starts_with(trim($dataResp), '250')) {
            throw new \RuntimeException("Server rejected message: {$dataResp}");
        }

        if ($debug) {
            // Store conversation in session for super_admin to retrieve
            $_SESSION['smtp_debug_log'] = $log;
        }

        return true;
    }

    /**
     * Test SMTP and return full debug transcript — super_admin only.
     */
    public static function testSmtp(string $toEmail): array
    {
        // Clear static cache to ensure fresh values from DB
        \App\Models\Setting::clearCache();

        $host      = Setting::get('smtp_host', '');
        $port      = Setting::get('smtp_port', '587');
        $user      = Setting::get('smtp_user', '');
        $fromEmail = Setting::get('smtp_from_email', '') ?: $user;

        try {
            self::sendSmtp(
                $toEmail,
                $toEmail,
                'LMS Advisor — SMTP Test',
                '<p>This is a test email from LMS Advisor. If you received this, SMTP is working correctly.</p><p>Sent at: ' . date('Y-m-d H:i:s') . '</p>',
                true // debug mode
            );
            $log = $_SESSION['smtp_debug_log'] ?? [];
            return [
                'success' => true,
                'message' => "✓ Email sent successfully to {$toEmail}",
                'config'  => "Host: {$host}:{$port} | From: {$fromEmail}",
                'log'     => $log,
            ];
        } catch (\Throwable $e) {
            $log = $_SESSION['smtp_debug_log'] ?? [];
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'config'  => "Host: {$host}:{$port} | From: {$fromEmail}",
                'log'     => $log,
            ];
        }
    }

    /** Replace {{variable}} placeholders in template. */
    public static function render(string $template, array $vars): string
    {
        // Replace all {{variable}} placeholders
        foreach ($vars as $key => $val) {
            $template = str_replace('{{' . $key . '}}', (string)$val, $template);
        }

        $siteName = $vars['site_name'] ?? 'LMS Advisor';
        $logo     = $vars['site_logo'] ?? '';
        $unsub    = $vars['unsubscribe_url'] ?? '';
        $year     = date('Y');

        // Wrap in professional email layout with logo header
        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($siteName) . '</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

      <!-- Header with logo -->
      <tr>
        <td style="background:#6366f1;border-radius:12px 12px 0 0;padding:24px 32px;text-align:center">
          ' . ($logo
            ? '<img src="' . htmlspecialchars($logo) . '" alt="' . htmlspecialchars($siteName) . '" style="max-height:48px;max-width:200px;object-fit:contain">'
            : '<span style="color:#fff;font-size:22px;font-weight:700;letter-spacing:-0.5px">' . htmlspecialchars($siteName) . '</span>'
          ) . '
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style="background:#ffffff;padding:32px;color:#1e293b;font-size:15px;line-height:1.7;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0">
          ' . $template . '
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:20px 32px;text-align:center;font-size:12px;color:#94a3b8">
          &copy; ' . $year . ' ' . htmlspecialchars($siteName) . ' — All rights reserved<br>
          ' . ($unsub ? '<a href="' . htmlspecialchars($unsub) . '" style="color:#94a3b8">Unsubscribe</a>' : '') . '
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';
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

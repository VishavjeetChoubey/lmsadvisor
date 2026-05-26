<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;
use App\Helpers\Uuid;

class CertificateService
{
    /**
     * Issue (or re-issue) a certificate for a completed enrollment.
     * Generates an HTML-based printable cert + stores record in DB.
     *
     * @return array  Certificate row with uuid
     */
    public static function issue(int $enrollmentId, int $userId, int $courseId): array
    {
        $pdo = Database::getInstance();

        // Check if already issued
        $existing = $pdo->prepare('SELECT * FROM certificates WHERE enrollment_id=? LIMIT 1');
        $existing->execute([$enrollmentId]);
        $cert = $existing->fetch();
        if ($cert) return $cert;

        $uuid = Uuid::v4();

        $pdo->prepare(
            'INSERT INTO certificates (uuid, enrollment_id, user_id, course_id, issued_at)
             VALUES (?,?,?,?,NOW())'
        )->execute([$uuid, $enrollmentId, $userId, $courseId]);

        $cert = $pdo->prepare('SELECT * FROM certificates WHERE uuid=? LIMIT 1');
        $cert->execute([$uuid]);
        return $cert->fetch();
    }

    /**
     * Find a certificate by its UUID (public verify)
     */
    public static function findByUuid(string $uuid): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT c.*, e.enrolled_at, e.completed_at,
                    u.first_name, u.last_name, u.email,
                    co.title AS course_title, co.level, co.duration_hours,
                    co.grade_points, co.uuid AS course_uuid
             FROM certificates c
             JOIN enrollments e ON e.id  = c.enrollment_id
             JOIN users u       ON u.id  = c.user_id
             JOIN courses co    ON co.id = c.course_id
             WHERE c.uuid = ? LIMIT 1'
        );
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find certificate by enrollment ID
     */
    public static function findByEnrollment(int $enrollmentId): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT c.*, e.enrolled_at, e.completed_at,
                    u.first_name, u.last_name,
                    co.title AS course_title, co.level, co.duration_hours, co.grade_points
             FROM certificates c
             JOIN enrollments e ON e.id  = c.enrollment_id
             JOIN users u       ON u.id  = c.user_id
             JOIN courses co    ON co.id = c.course_id
             WHERE c.enrollment_id = ? LIMIT 1'
        );
        $stmt->execute([$enrollmentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Build the certificate HTML for display / printing
     */
    public static function renderHtml(array $cert): string
    {
        $signerName  = Setting::get('cert_signer_name',  'Administrator');
        $signerTitle = Setting::get('cert_signer_title', 'Chief Learning Officer');
        $accentColor = Setting::get('cert_template_color','#6366f1');
        $siteName    = Setting::get('site_name',         'LMSAdvisor');

        $studentName = htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name'], ENT_QUOTES);
        $courseName  = htmlspecialchars($cert['course_title'] ?? '', ENT_QUOTES);
        $completedOn = $cert['completed_at']
            ? date('d F Y', strtotime($cert['completed_at']))
            : date('d F Y', strtotime($cert['issued_at']));
        $certId      = strtoupper(substr($cert['uuid'], 0, 8));
        $verifyUrl   = APP_URL . '/certificate/verify/' . $cert['uuid'];

        $durationLine = $cert['duration_hours']
            ? htmlspecialchars($cert['duration_hours'], ENT_QUOTES) . ' hours of learning'
            : '';
        $levelLine = $cert['level']
            ? ucfirst(htmlspecialchars($cert['level'], ENT_QUOTES)) . ' level'
            : '';
        $durationMeta = $durationLine ? "<span>⏱ {$durationLine}</span>" : '';
        $levelMeta    = $levelLine    ? "<span>🎯 {$levelLine}</span>"    : '';

        $accent = htmlspecialchars($accentColor, ENT_QUOTES);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate — {$courseName}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 32px 16px; }
  .cert-page { width: 860px; max-width: 100%; }

  .cert-wrap {
    background: #fff;
    border: 3px solid {$accent};
    border-radius: 16px;
    padding: 6px;
    box-shadow: 0 20px 60px rgba(0,0,0,.12);
  }
  .cert-inner {
    border: 1.5px dashed {$accent}66;
    border-radius: 12px;
    padding: 52px 64px 44px;
    text-align: center;
    background: linear-gradient(150deg,#fafbff 0%,#fff 60%,#f8f4ff 100%);
    position: relative;
    overflow: hidden;
  }
  .cert-inner::before, .cert-inner::after {
    content: '';
    position: absolute;
    width: 200px; height: 200px;
    border-radius: 50%;
    opacity: .04;
    background: {$accent};
  }
  .cert-inner::before { top: -60px; left: -60px; }
  .cert-inner::after  { bottom: -60px; right: -60px; }

  .cert-logo {
    width: 68px; height: 68px; border-radius: 18px;
    background: linear-gradient(135deg,{$accent},{$accent}cc);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 12px;
    box-shadow: 0 8px 24px {$accent}44;
  }
  .cert-logo svg { width: 34px; height: 34px; fill: #fff; }
  .cert-org {
    font-size: 12px; font-weight: 700; letter-spacing: 3px;
    text-transform: uppercase; color: #94a3b8; margin-bottom: 20px;
  }
  .cert-divider-top {
    width: 60px; height: 2px; background: {$accent};
    border-radius: 2px; margin: 0 auto 28px;
  }

  .cert-of-completion {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 36px; font-weight: 700;
    color: #0f172a; letter-spacing: -0.5px;
    margin-bottom: 10px;
  }
  .cert-presented { font-size: 15px; color: #64748b; font-style: italic; margin-bottom: 18px; }

  .cert-name {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 46px; font-weight: 700; font-style: italic;
    color: #0f172a; letter-spacing: -1px;
    margin-bottom: 20px; line-height: 1.1;
  }

  .cert-text { font-size: 15px; color: #475569; margin-bottom: 8px; }

  .cert-course {
    font-size: 26px; font-weight: 800;
    color: {$accent}; margin: 10px 0 14px;
    letter-spacing: -0.3px; line-height: 1.3;
  }

  .cert-meta {
    display: flex; justify-content: center; gap: 24px;
    flex-wrap: wrap; margin: 12px 0 32px;
    font-size: 13px; color: #94a3b8;
  }
  .cert-meta span { display: flex; align-items: center; gap: 5px; }

  .cert-ribbon {
    display: flex; align-items: center; gap: 20px;
    margin: 24px 0 32px;
  }
  .cert-ribbon-line { flex: 1; height: 1px; background: {$accent}33; }
  .cert-seal {
    width: 64px; height: 64px; border-radius: 50%;
    background: linear-gradient(135deg,{$accent},{$accent}cc);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 16px {$accent}44;
  }
  .cert-seal svg { width: 32px; height: 32px; fill: #fff; }

  .cert-sig-row {
    display: flex; align-items: flex-end;
    justify-content: space-between; gap: 24px;
    margin-top: 8px;
  }
  .cert-sig-block { text-align: left; }
  .cert-sig-name { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
  .cert-sig-line { height: 1px; width: 180px; background: {$accent}44; margin-bottom: 4px; }
  .cert-sig-title { font-size: 12px; color: #94a3b8; font-style: italic; }

  .cert-id-block { text-align: right; }
  .cert-id-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #cbd5e1; }
  .cert-id-value { font-family: monospace; font-size: 13px; font-weight: 700; color: #0f172a; }
  .cert-verify { font-size: 10px; color: #94a3b8; margin-top: 3px; }

  @media print {
    body { background: #fff; padding: 0; }
    .cert-wrap { box-shadow: none; border-width: 2px; }
    .cert-inner { padding: 40px 52px 36px; }
  }
</style>
</head>
<body>
<div class="cert-page">
  <div class="cert-wrap">
    <div class="cert-inner">

      <!-- Logo + Org -->
      <div class="cert-logo">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
      </div>
      <div class="cert-org">{$siteName}</div>
      <div class="cert-divider-top"></div>

      <!-- Title -->
      <div class="cert-of-completion">Certificate of Completion</div>
      <div class="cert-presented">This is to certify that</div>

      <!-- Student name -->
      <div class="cert-name">{$studentName}</div>

      <!-- Course info -->
      <div class="cert-text">has successfully completed</div>
      <div class="cert-course">{$courseName}</div>

      <!-- Meta -->
      <div class="cert-meta">
        <span>📅 {$completedOn}</span>
        {$durationMeta}
        {$levelMeta}
      </div>

      <!-- Ribbon + seal -->
      <div class="cert-ribbon">
        <div class="cert-ribbon-line"></div>
        <div class="cert-seal">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        </div>
        <div class="cert-ribbon-line"></div>
      </div>

      <!-- Signatures -->
      <div class="cert-sig-row">
        <div class="cert-sig-block">
          <div class="cert-sig-name">{$signerName}</div>
          <div class="cert-sig-line"></div>
          <div class="cert-sig-title">{$signerTitle}</div>
        </div>
        <div class="cert-id-block">
          <div class="cert-id-label">Certificate ID</div>
          <div class="cert-id-value">{$certId}</div>
          <div class="cert-verify">Verify at: {$verifyUrl}</div>
          <div class="cert-id-label" style="margin-top:4px">Issued</div>
          <div class="cert-id-value" style="font-size:12px">{$completedOn}</div>
        </div>
      </div>

    </div>
  </div>
</div>
<div style="position:fixed;bottom:24px;right:24px;display:flex;gap:10px;z-index:999" class="no-print">
  <button onclick="window.print()"
    style="background:#5b5ef6;color:#fff;border:none;border-radius:10px;padding:12px 24px;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(91,94,246,.4);font-family:inherit">
    🖨 Download / Print PDF
  </button>
  <button onclick="window.close()"
    style="background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:10px;padding:12px 20px;font-size:15px;cursor:pointer;font-family:inherit">
    Close
  </button>
</div>
<style>@media print{.no-print{display:none!important}}</style>
</body>
</html>
HTML;
    }
}

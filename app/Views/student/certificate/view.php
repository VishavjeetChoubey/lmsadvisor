<?php
use App\Core\View;
use App\Models\Setting;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$signerName  = Setting::get('cert_signer_name',  'Administrator');
$signerTitle = Setting::get('cert_signer_title', 'Chief Learning Officer');
$accentColor = Setting::get('cert_template_color','#6366f1');
$siteName    = Setting::get('site_name',         'LMSAdvisor');
$completedOn = $enrollment['completed_at']
    ? date('d F Y', strtotime($enrollment['completed_at']))
    : date('d F Y');
?>

<!-- Print button -->
<div class="d-flex justify-content-end gap-2 mb-4 d-print-none">
  <a href="<?= $url('learn/courses') ?>" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to Courses
  </a>
  <button onclick="window.print()" class="btn btn-primary">
    <i class="bi bi-printer me-1"></i> Print / Save PDF
  </button>
</div>

<!-- Certificate -->
<div class="cert-wrap" id="certificate">
  <div class="cert-border">
    <div class="cert-inner">

      <!-- Header -->
      <div class="cert-header">
        <div class="cert-logo" style="background:<?= $e($accentColor) ?>">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="cert-site-name"><?= $e($siteName) ?></div>
        <div class="cert-header-line" style="background:<?= $e($accentColor) ?>"></div>
      </div>

      <!-- Body -->
      <div class="cert-of-completion">Certificate of Completion</div>
      <div class="cert-presented">This is to certify that</div>

      <div class="cert-name"><?= $e($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></div>

      <div class="cert-text">
        has successfully completed the course
      </div>

      <div class="cert-course-name" style="color:<?= $e($accentColor) ?>">
        <?= $e($enrollment['course_title']) ?>
      </div>

      <div class="cert-text" style="margin-top:8px">
        on <strong><?= $e($completedOn) ?></strong>
        <?php if ($course && $course['duration_hours']): ?>
          &nbsp;·&nbsp; <?= $e($course['duration_hours']) ?> hours of learning
        <?php endif; ?>
      </div>

      <!-- Divider -->
      <div class="cert-divider" style="background:<?= $e($accentColor) ?>20">
        <div class="cert-divider-line" style="background:<?= $e($accentColor) ?>"></div>
        <div class="cert-seal" style="background:<?= $e($accentColor) ?>">
          <i class="fas fa-award"></i>
        </div>
        <div class="cert-divider-line" style="background:<?= $e($accentColor) ?>"></div>
      </div>

      <!-- Signature -->
      <div class="cert-signature-row">
        <div class="cert-sig-block">
          <div class="cert-sig-name"><?= $e($signerName) ?></div>
          <div class="cert-sig-line" style="background:<?= $e($accentColor) ?>40"></div>
          <div class="cert-sig-title"><?= $e($signerTitle) ?></div>
        </div>
        <div class="cert-id-block">
          <div class="cert-id-label">Certificate ID</div>
          <div class="cert-id-value"><?= strtoupper(substr(md5($enrollment['id'] . $enrollment['course_id']), 0, 12)) ?></div>
          <div class="cert-id-label" style="margin-top:4px">Issued</div>
          <div class="cert-id-value" style="font-size:11px"><?= $e($completedOn) ?></div>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
/* Certificate styles */
.cert-wrap {
  max-width: 860px;
  margin: 0 auto;
  font-family: 'Georgia', 'Times New Roman', serif;
}
.cert-border {
  border: 3px solid <?= $e($accentColor) ?>;
  border-radius: 16px;
  padding: 6px;
  background: #fff;
  box-shadow: 0 20px 60px rgba(0,0,0,.12);
}
.cert-inner {
  border: 1.5px solid <?= $e($accentColor) ?>40;
  border-radius: 12px;
  padding: 48px 56px;
  background: linear-gradient(160deg,#fafbff 0%,#fff 60%,#f8f4ff 100%);
  text-align: center;
}

/* Header */
.cert-header { margin-bottom: 32px; }
.cert-logo {
  width: 64px; height: 64px;
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 10px;
  box-shadow: 0 6px 20px <?= $e($accentColor) ?>40;
}
.cert-logo i { font-size: 28px; color: #fff; }
.cert-site-name {
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: #64748b;
  margin-bottom: 16px;
}
.cert-header-line {
  height: 2px;
  width: 60px;
  margin: 0 auto;
  border-radius: 2px;
}

/* Body */
.cert-of-completion {
  font-size: 32px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.5px;
  margin-bottom: 8px;
}
.cert-presented {
  font-size: 14px;
  color: #64748b;
  margin-bottom: 16px;
  font-style: italic;
}
.cert-name {
  font-size: 38px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -1px;
  margin-bottom: 16px;
  font-family: 'Georgia', serif;
}
.cert-text {
  font-size: 15px;
  color: #475569;
  margin-bottom: 10px;
}
.cert-course-name {
  font-size: 24px;
  font-weight: 700;
  margin: 8px 0 16px;
  letter-spacing: -0.3px;
}

/* Divider */
.cert-divider {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 32px 0;
  padding: 16px 0;
  border-radius: 8px;
}
.cert-divider-line { flex: 1; height: 1px; }
.cert-seal {
  width: 56px; height: 56px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px <?= $e($accentColor) ?>40;
}
.cert-seal i { font-size: 24px; color: #fff; }

/* Signature */
.cert-signature-row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  margin-top: 8px;
}
.cert-sig-block { text-align: left; }
.cert-sig-name { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.cert-sig-line { height: 1px; width: 200px; margin: 0 0 4px; }
.cert-sig-title { font-size: 12px; color: #64748b; font-style: italic; }
.cert-id-block { text-align: right; }
.cert-id-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
.cert-id-value { font-family: monospace; font-size: 13px; font-weight: 700; color: #0f172a; }

/* Print */
@media print {
  .d-print-none { display: none !important; }
  body { background: #fff !important; }
  .student-bottom-nav, .student-sidebar, .student-topbar, .student-footer { display: none !important; }
  .student-main { margin: 0 !important; padding: 0 !important; }
  .cert-border { box-shadow: none !important; }
  .cert-wrap { max-width: 100% !important; }
}
</style>

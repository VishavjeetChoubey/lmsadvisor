<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $e($title ?? 'Certificate Verify') ?> — LMSAdvisor</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
  <style>
    body { font-family:'Inter',sans-serif; background:#f1f5f9; min-height:100vh; display:flex; flex-direction:column; }
    .verify-hero { background:linear-gradient(135deg,#0f172a,#1e1b4b); padding:32px 0; text-align:center; color:#fff; }
    .verify-logo { width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;margin:0 auto 12px; }
    .verify-logo i { font-size:22px;color:#fff; }
  </style>
</head>
<body>
<div class="verify-hero">
  <div class="verify-logo"><i class="bi bi-patch-check-fill"></i></div>
  <h4 class="fw-bold mb-1">LMSAdvisor Certificate Verification</h4>
  <p class="mb-0" style="color:rgba(255,255,255,.6);font-size:14px">Enter or scan a certificate ID to verify its authenticity</p>
</div>

<div class="container py-5" style="max-width:680px">

  <!-- Search form -->
  <div class="card border-0 shadow-sm mb-4" style="border-radius:16px">
    <div class="card-body p-4">
      <form method="GET" action="<?= $url('certificate/verify') ?>">
        <label class="form-label fw-semibold">Certificate UUID</label>
        <div class="input-group">
          <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" name="uuid"
                 value="<?= $e($uuid ?? '') ?>"
                 placeholder="e.g. 3cc70a73-a366-4f17-82d1-f48ddacb8b32"
                 style="font-family:monospace;font-size:13px">
          <button class="btn btn-primary" type="submit">Verify</button>
        </div>
        <div class="form-text">The UUID is printed on the certificate under "Certificate ID".</div>
      </form>
    </div>
  </div>

  <!-- Result -->
  <?php if ($uuid && !$cert): ?>
  <div class="card border-0 shadow-sm" style="border-radius:16px;border-left:4px solid #e02424 !important;">
    <div class="card-body p-4 d-flex align-items-center gap-3">
      <div style="width:52px;height:52px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="bi bi-x-circle-fill" style="font-size:24px;color:#e02424"></i>
      </div>
      <div>
        <h5 class="fw-bold mb-1 text-danger">Certificate Not Found</h5>
        <p class="text-muted mb-0" style="font-size:14px">
          No certificate exists for UUID <code><?= $e($uuid) ?></code>.<br>
          Please check the ID and try again.
        </p>
      </div>
    </div>
  </div>

  <?php elseif ($cert): ?>
  <div class="card border-0 shadow-sm" style="border-radius:16px;border-left:4px solid #0e9f6e !important;">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:60px;height:60px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi bi-patch-check-fill" style="font-size:28px;color:#0e9f6e"></i>
        </div>
        <div>
          <h5 class="fw-bold mb-1" style="color:#0e9f6e">✓ Certificate Verified</h5>
          <p class="text-muted mb-0" style="font-size:13.5px">This certificate is authentic and issued by LMSAdvisor.</p>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12">
          <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
            <div class="row g-3">
              <div class="col-md-6">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:2px">Student</div>
                <div class="fw-bold"><?= $e($cert['first_name'] . ' ' . $cert['last_name']) ?></div>
              </div>
              <div class="col-md-6">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:2px">Course</div>
                <div class="fw-bold"><?= $e($cert['course_title']) ?></div>
              </div>
              <div class="col-md-6">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:2px">Completed On</div>
                <div class="fw-semibold"><?= $cert['completed_at'] ? date('d F Y', strtotime($cert['completed_at'])) : date('d F Y', strtotime($cert['issued_at'])) ?></div>
              </div>
              <div class="col-md-6">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:2px">Certificate ID</div>
                <div style="font-family:monospace;font-size:13px"><?= $e(strtoupper(substr($cert['uuid'],0,8))) ?></div>
              </div>
              <?php if ($cert['level']): ?>
              <div class="col-md-6">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:2px">Level</div>
                <div class="fw-semibold"><?= $e(ucfirst($cert['level'])) ?></div>
              </div>
              <?php endif; ?>
              <?php if ($cert['duration_hours']): ?>
              <div class="col-md-6">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:2px">Duration</div>
                <div class="fw-semibold"><?= $e($cert['duration_hours']) ?> hours</div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-12">
          <a href="<?= $url('learn/certificate/' . $cert['enrollment_id']) ?>"
             target="_blank" class="btn btn-success w-100">
            <i class="bi bi-award me-2"></i> View Full Certificate
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <p class="text-center text-muted mt-4" style="font-size:12.5px">
    Powered by <strong>LMSAdvisor</strong> · Proudly developed by LMS Advisor
  </p>
</div>
</body>
</html>

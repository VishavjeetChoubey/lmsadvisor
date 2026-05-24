<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$completed  = count(array_filter($enrolled, fn($e) => $e['status'] === 'completed'));
$active     = count(array_filter($enrolled, fn($e) => $e['status'] === 'active'));
$fu         = $full_user ?? [];
$flash      = $flash ?? [];
$csrf       = $csrf_token ?? '';

$statusColors = ['active'=>'primary','completed'=>'success','suspended'=>'warning','expired'=>'danger'];
?>

<!-- Flash messages -->
<?php foreach ($flash as $type => $msg): ?>
  <?php $cls = $type === 'error' ? 'danger' : $e($type); ?>
  <div class="alert alert-<?= $cls ?> d-flex align-items-center gap-2 mb-4" style="border-radius:12px">
    <i class="bi <?= $type==='error'?'bi-exclamation-circle':'bi-check-circle' ?> flex-shrink-0"></i>
    <div><?= $e($msg) ?></div>
  </div>
<?php endforeach; ?>

<div class="row g-4">

  <!-- LEFT: Profile card -->
  <div class="col-12 col-lg-4">

    <!-- Avatar + stats -->
    <div class="card lms-card text-center p-4 mb-4">
      <div class="mx-auto mb-3" style="width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(99,102,241,.3)">
        <span style="color:#fff;font-size:32px;font-weight:700;line-height:1">
          <?= strtoupper(substr($fu['first_name'] ?? 'S', 0, 1)) ?><?= strtoupper(substr($fu['last_name'] ?? '', 0, 1)) ?>
        </span>
      </div>
      <h5 class="fw-bold mb-0"><?= $e(($fu['first_name'] ?? '') . ' ' . ($fu['last_name'] ?? '')) ?></h5>
      <div class="text-muted mb-2" style="font-size:13.5px"><?= $e($fu['email'] ?? '') ?></div>
      <span class="badge bg-primary px-3 py-2 mb-3" style="font-size:12px">
        <?= $e($fu['role_display'] ?? 'Student') ?>
      </span>

      <div class="row g-2 text-center">
        <div class="col-4">
          <div class="fw-bold" style="font-size:22px;color:#6366f1"><?= count($enrolled) ?></div>
          <div class="text-muted" style="font-size:11px">Enrolled</div>
        </div>
        <div class="col-4">
          <div class="fw-bold" style="font-size:22px;color:#0e9f6e"><?= $completed ?></div>
          <div class="text-muted" style="font-size:11px">Completed</div>
        </div>
        <div class="col-4">
          <div class="fw-bold" style="font-size:22px;color:#e3a008"><?= number_format((int)$points) ?></div>
          <div class="text-muted" style="font-size:11px">Points</div>
        </div>
      </div>

      <?php if ($fu['last_login_at']): ?>
      <div class="text-muted mt-3" style="font-size:11.5px">
        Last login: <?= date('d M Y, H:i', strtotime($fu['last_login_at'])) ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Certificates earned -->
    <?php $completedCourses = array_filter($enrolled, fn($e) => $e['status'] === 'completed' && $e['certificate_enabled']); ?>
    <?php if (!empty($completedCourses)): ?>
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h6 class="mb-0"><i class="bi bi-award text-warning me-2"></i> Certificates Earned</h6>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($completedCourses as $ce): ?>
        <a href="<?= $url('learn/certificate/' . $ce['id']) ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
          <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#e3a008,#f59e0b);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi bi-award-fill text-white"></i>
          </div>
          <div class="flex-grow-1" style="min-width:0">
            <div class="fw-semibold" style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= $e($ce['title']) ?>
            </div>
            <div class="text-muted" style="font-size:11.5px">
              Completed <?= $ce['completed_at'] ? date('d M Y', strtotime($ce['completed_at'])) : '' ?>
            </div>
          </div>
          <i class="bi bi-chevron-right text-muted" style="font-size:12px"></i>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- RIGHT: Edit forms -->
  <div class="col-12 col-lg-8">

    <!-- Edit Profile -->
    <div class="card lms-card mb-4">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i> Edit Profile</h5>
      </div>
      <div class="card-body p-4">
        <form action="<?= $url('learn/profile/update') ?>" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="first_name"
                     value="<?= $e($fu['first_name'] ?? '') ?>" required maxlength="80"
                     placeholder="Your first name">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="last_name"
                     value="<?= $e($fu['last_name'] ?? '') ?>" required maxlength="80"
                     placeholder="Your last name">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email"
                     value="<?= $e($fu['email'] ?? '') ?>" required maxlength="191"
                     placeholder="your@email.com">
            </div>
          </div>
          <div class="mt-4">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-circle me-1"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card lms-card mb-4">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-lock me-2"></i> Change Password</h5>
      </div>
      <div class="card-body p-4">
        <form action="<?= $url('learn/profile/change-password') ?>" method="POST" id="pwForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" class="form-control" name="current_password"
                       id="currentPw" placeholder="Enter current password" required>
                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="currentPw">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" class="form-control" name="new_password"
                       id="newPw" placeholder="Min. 8 characters" required minlength="8">
                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="newPw">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div id="pwStrengthBar" class="mt-1" style="height:4px;border-radius:2px;background:var(--border-color);overflow:hidden">
                <div id="pwStrengthFill" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:2px"></div>
              </div>
              <div id="pwStrengthLabel" class="mt-1" style="font-size:11.5px"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" class="form-control" name="confirm_password"
                       id="confirmPw" placeholder="Repeat new password" required>
                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="confirmPw">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div id="matchMsg" class="mt-1" style="font-size:11.5px"></div>
            </div>
          </div>
          <div class="mt-4">
            <button type="submit" class="btn btn-warning px-4 fw-semibold" id="pwSubmitBtn">
              <i class="bi bi-shield-lock me-1"></i> Update Password
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Recent Courses -->
    <?php if (!empty($enrolled)): ?>
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Recent Courses</h5>
      </div>
      <div class="table-responsive">
        <table class="table lms-table mb-0">
          <thead>
            <tr><th>Course</th><th>Status</th><th>Progress</th><th>Enrolled</th></tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($enrolled, 0, 6) as $e2):
              $pct = (int)($e2['progress_pct'] ?? 0);
            ?>
            <tr>
              <td class="fw-semibold" style="font-size:13px">
                <?= $e(mb_strimwidth($e2['title'], 0, 38, '…')) ?>
              </td>
              <td>
                <span class="badge bg-<?= $statusColors[$e2['status']] ?? 'secondary' ?>" style="font-size:11px">
                  <?= ucfirst($e($e2['status'])) ?>
                </span>
              </td>
              <td style="min-width:110px">
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
                    <div class="progress-bar bg-<?= $pct>=100?'success':'primary' ?>" style="width:<?= $pct ?>%"></div>
                  </div>
                  <span style="font-size:11.5px;color:var(--text-muted)"><?= $pct ?>%</span>
                </div>
              </td>
              <td class="text-muted" style="font-size:12px;white-space:nowrap">
                <?= date('d M Y', strtotime($e2['enrolled_at'])) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
// Password toggle visibility
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', function () {
    const target = document.getElementById(this.dataset.target);
    const icon   = this.querySelector('i');
    if (target.type === 'password') {
      target.type = 'text'; icon.className = 'bi bi-eye-slash';
    } else {
      target.type = 'password'; icon.className = 'bi bi-eye';
    }
  });
});

// Password strength meter
const newPwEl     = document.getElementById('newPw');
const confirmPwEl = document.getElementById('confirmPw');
const fillEl      = document.getElementById('pwStrengthFill');
const labelEl     = document.getElementById('pwStrengthLabel');
const matchEl     = document.getElementById('matchMsg');

newPwEl?.addEventListener('input', function () {
  const v = this.value;
  let strength = 0;
  if (v.length >= 8)           strength++;
  if (/[A-Z]/.test(v))         strength++;
  if (/[0-9]/.test(v))         strength++;
  if (/[^A-Za-z0-9]/.test(v)) strength++;

  const colors = ['','#e02424','#e3a008','#1a56db','#0e9f6e'];
  const labels = ['','Weak','Fair','Good','Strong'];

  fillEl.style.width      = (strength * 25) + '%';
  fillEl.style.background = colors[strength] || '';
  labelEl.textContent     = v.length ? labels[strength] : '';
  labelEl.style.color     = colors[strength] || '';
  checkMatch();
});

confirmPwEl?.addEventListener('input', checkMatch);

function checkMatch() {
  const nv = newPwEl?.value;
  const cv = confirmPwEl?.value;
  if (!cv) { matchEl.textContent = ''; return; }
  if (nv === cv) {
    matchEl.textContent = '✓ Passwords match';
    matchEl.style.color = '#0e9f6e';
  } else {
    matchEl.textContent = '✗ Passwords do not match';
    matchEl.style.color = '#e02424';
  }
}

// Prevent submit if passwords don't match
document.getElementById('pwForm')?.addEventListener('submit', function (e) {
  const nv = document.getElementById('newPw').value;
  const cv = document.getElementById('confirmPw').value;
  if (nv !== cv) {
    e.preventDefault();
    matchEl.textContent = '✗ Passwords do not match';
    matchEl.style.color = '#e02424';
    return;
  }
  const btn = document.getElementById('pwSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating…';
});
</script>

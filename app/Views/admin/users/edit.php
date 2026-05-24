<?php
use App\Core\View;
use App\Services\AuthService;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$roleColors  = ['super_admin'=>'warning','admin'=>'primary','manager'=>'info','student'=>'success'];
$roleIcons   = ['super_admin'=>'fa-crown','admin'=>'fa-shield-alt','manager'=>'fa-briefcase','student'=>'fa-graduation-cap'];
$rName       = $user['role_name'] ?? 'student';
$rColor      = $roleColors[$rName] ?? 'secondary';
$rIcon       = $roleIcons[$rName]  ?? 'fa-user';
$isSuperAdmin = AuthService::isSuperAdmin();
?>
<div class="row g-4">

  <!-- Edit form -->
  <div class="col-12 col-xl-8">
    <div class="card lms-card">
      <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Edit User</h5>
        <span class="badge bg-<?= $rColor ?>-subtle text-<?= $rColor ?> lms-role-badge">
          <i class="fas <?= $rIcon ?> me-1"></i><?= $e($user['role_display'] ?? ucfirst($rName)) ?>
        </span>
      </div>
      <div class="card-body p-4">
        <form action="<?= $url('admin/users/' . $user['uuid'] . '/edit') ?>" method="POST" novalidate id="editUserForm">
          <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="first_name"
                     value="<?= $e($user['first_name']) ?>" required maxlength="80">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="last_name"
                     value="<?= $e($user['last_name']) ?>" required maxlength="80">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email"
                     value="<?= $e($user['email']) ?>" required maxlength="191">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
              <select class="form-select" name="role_id" required>
                <?php foreach ($roles as $role): ?>
                  <?php if ($role['name'] === 'super_admin' && !$isSuperAdmin) continue; ?>
                  <option value="<?= $role['id'] ?>"
                    <?= (int)$role['id'] === (int)$user['role_id'] ? 'selected' : '' ?>>
                    <?= $e($role['display_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- New password (optional) -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">New Password <small class="text-muted fw-normal">(leave blank to keep)</small></label>
              <div class="input-group">
                <input type="password" class="form-control" name="password"
                       id="editPassword" placeholder="Min. 8 characters" minlength="8">
                <button type="button" class="btn btn-outline-secondary" id="toggleEditPw">
                  <i class="bi bi-eye" id="eyeEditPw"></i>
                </button>
              </div>
            </div>

            <div class="col-md-6 d-flex align-items-end">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="is_active"
                       value="1" id="isActive"
                       <?= $user['is_active'] ? 'checked' : '' ?>
                       <?= (int)$user['id'] === (int)(AuthService::user()['id'] ?? 0) ? 'disabled' : '' ?>>
                <label class="form-check-label fw-semibold" for="isActive">Account Active</label>
              </div>
            </div>
          </div>

          <hr class="my-4">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary px-4" id="submitEditBtn">
              <i class="bi bi-check-circle me-1"></i> Save Changes
            </button>
            <a href="<?= $url('admin/users') ?>" class="btn btn-outline-secondary px-4">Cancel</a>

            <!-- Impersonation -->
            <?php if ($canImpersonate ?? false): ?>
            <form action="<?= $url('admin/users/' . $user['uuid'] . '/impersonate') ?>"
                  method="POST" class="d-inline ms-auto">
              <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
              <button type="submit" class="btn btn-outline-purple"
                      onclick="return confirm('Switch to viewing as <?= $e($user['first_name']) ?>?')"
                      style="border-color:#7c3aed;color:#7c3aed">
                <i class="fas fa-user-secret me-1"></i> Login as This User
              </button>
            </form>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Info sidebar -->
  <div class="col-12 col-xl-4">
    <div class="card lms-card mb-3">
      <div class="card-header lms-card-header">
        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Account Info</h6>
      </div>
      <div class="card-body p-3">
        <dl class="row mb-0 small">
          <dt class="col-6 text-muted">User ID</dt>
          <dd class="col-6">#<?= $user['id'] ?></dd>
          <dt class="col-6 text-muted">UUID</dt>
          <dd class="col-6" style="font-size:10.5px;word-break:break-all"><?= $e($user['uuid']) ?></dd>
          <dt class="col-6 text-muted">Joined</dt>
          <dd class="col-6"><?= $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '—' ?></dd>
          <dt class="col-6 text-muted">Last Login</dt>
          <dd class="col-6"><?= $user['last_login_at'] ? date('d M Y H:i', strtotime($user['last_login_at'])) : 'Never' ?></dd>
          <dt class="col-6 text-muted">Email Verified</dt>
          <dd class="col-6">
            <?php if ($user['email_verified_at']): ?>
              <span class="badge bg-success">Yes</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">No</span>
            <?php endif; ?>
          </dd>
          <dt class="col-6 text-muted">Login Attempts</dt>
          <dd class="col-6"><?= (int)$user['login_attempts'] ?></dd>
          <?php if ($user['locked_until'] && strtotime($user['locked_until']) > time()): ?>
          <dt class="col-6 text-muted">Locked Until</dt>
          <dd class="col-6 text-danger"><?= date('H:i', strtotime($user['locked_until'])) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>

    <!-- Danger zone -->
    <?php if ($isSuperAdmin || (AuthService::isAdmin() && $rName !== 'super_admin' && (int)$user['id'] !== (int)(AuthService::user()['id'] ?? 0))): ?>
    <div class="card lms-card border-danger">
      <div class="card-header lms-card-header border-bottom border-danger">
        <h6 class="mb-0 text-danger"><i class="bi bi-exclamation-triangle me-2"></i> Danger Zone</h6>
      </div>
      <div class="card-body p-3">
        <p class="text-muted small mb-3">Permanently delete this user and all their data. This cannot be undone.</p>
        <button class="btn btn-sm btn-outline-danger w-100"
                id="deleteBtnPage"
                data-uuid="<?= $e($user['uuid']) ?>"
                data-name="<?= $e($user['first_name'] . ' ' . $user['last_name']) ?>">
          <i class="bi bi-trash3 me-1"></i> Delete This User
        </button>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<style>
.bg-warning-subtle{background:#fef9c3!important}
.bg-primary-subtle{background:#ebf2ff!important}
.bg-info-subtle{background:#e0f7fa!important}
.bg-success-subtle{background:#d1fae5!important}
.lms-role-badge{font-size:11.5px;padding:4px 8px;border-radius:20px;font-weight:600}
</style>

<script>
const CSRF = '<?= $e($csrf_token) ?>';
const BASE = '<?= rtrim(APP_URL, '/') ?>';

document.getElementById('toggleEditPw').addEventListener('click', function () {
  const pw = document.getElementById('editPassword');
  const ic = document.getElementById('eyeEditPw');
  if (pw.type === 'password') { pw.type = 'text'; ic.className = 'bi bi-eye-slash'; }
  else { pw.type = 'password'; ic.className = 'bi bi-eye'; }
});

document.getElementById('editUserForm').addEventListener('submit', function () {
  const btn = document.getElementById('submitEditBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';
});

const delBtn = document.getElementById('deleteBtnPage');
if (delBtn) {
  delBtn.addEventListener('click', function () {
    const uuid = this.dataset.uuid;
    const name = this.dataset.name;
    LMS.confirm('Delete user "' + name + '"? This cannot be undone.', function () {
      fetch(BASE + '/admin/users/' + uuid + '/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          LMS.toast('success', name + ' has been deleted.');
          setTimeout(() => window.location.href = BASE + '/admin/users', 900);
        } else {
          LMS.toast('error', data.message || 'Delete failed.');
        }
      });
    });
  });
}
</script>

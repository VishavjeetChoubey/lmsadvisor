<?php
use App\Core\View;
use App\Services\AuthService;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$roleColors = ['super_admin'=>'warning','admin'=>'primary','manager'=>'info','student'=>'success'];
$roleIcons  = ['super_admin'=>'fa-crown','admin'=>'fa-shield-alt','manager'=>'fa-briefcase','student'=>'fa-graduation-cap'];
$isSuperAdmin = AuthService::isSuperAdmin();
?>
<div class="row justify-content-center">
  <div class="col-12 col-xl-8">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i> Add New User</h5>
      </div>
      <div class="card-body p-4">

        <form action="<?= $url('admin/users/create') ?>" method="POST" novalidate id="createUserForm">
          <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">

          <div class="row g-3">

            <!-- First name -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="first_name"
                     value="<?= $e($_POST['first_name'] ?? '') ?>"
                     placeholder="John" required maxlength="80">
            </div>

            <!-- Last name -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="last_name"
                     value="<?= $e($_POST['last_name'] ?? '') ?>"
                     placeholder="Smith" required maxlength="80">
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email"
                     value="<?= $e($_POST['email'] ?? '') ?>"
                     placeholder="john@example.com" required maxlength="191">
            </div>

            <!-- Role -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
              <select class="form-select" name="role_id" required>
                <?php foreach ($roles as $role): ?>
                  <?php
                    // Non-super_admin cannot assign super_admin role
                    if ($role['name'] === 'super_admin' && !$isSuperAdmin) continue;
                    $selected = ((int)($_POST['role_id'] ?? 4) === (int)$role['id']) ? 'selected' : '';
                    $icon = $roleIcons[$role['name']] ?? 'fa-user';
                  ?>
                  <option value="<?= $role['id'] ?>" <?= $selected ?>>
                    <?= $e($role['display_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Password -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" class="form-control" name="password"
                       id="newPassword" placeholder="Min. 8 characters" required minlength="8">
                <button type="button" class="btn btn-outline-secondary" id="toggleNewPw">
                  <i class="bi bi-eye" id="eyeNewPw"></i>
                </button>
              </div>
              <div class="password-strength mt-1" id="pwStrength"></div>
            </div>

            <!-- Active -->
            <div class="col-md-6 d-flex align-items-end">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="is_active"
                       value="1" id="isActive"
                       <?= isset($_POST['is_active']) ? ($_POST['is_active'] ? 'checked' : '') : 'checked' ?>>
                <label class="form-check-label fw-semibold" for="isActive">
                  Account Active
                </label>
              </div>
            </div>

          </div><!-- /.row -->

          <hr class="my-4">

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4" id="submitBtn">
              <i class="bi bi-check-circle me-1"></i> Create User
            </button>
            <a href="<?= $url('admin/users') ?>" class="btn btn-outline-secondary px-4">
              Cancel
            </a>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Password toggle
document.getElementById('toggleNewPw').addEventListener('click', function () {
  const pw  = document.getElementById('newPassword');
  const ico = document.getElementById('eyeNewPw');
  if (pw.type === 'password') { pw.type = 'text'; ico.className = 'bi bi-eye-slash'; }
  else                        { pw.type = 'password'; ico.className = 'bi bi-eye'; }
});

// Password strength meter
document.getElementById('newPassword').addEventListener('input', function () {
  const val   = this.value;
  const el    = document.getElementById('pwStrength');
  let strength = 0;
  if (val.length >= 8)              strength++;
  if (/[A-Z]/.test(val))            strength++;
  if (/[0-9]/.test(val))            strength++;
  if (/[^A-Za-z0-9]/.test(val))    strength++;

  const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
  const colors = ['', '#e02424', '#e3a008', '#1a56db', '#0e9f6e'];
  el.innerHTML = val.length
    ? `<small style="color:${colors[strength]};font-weight:600">${labels[strength]}</small>`
    : '';
});

// Submit loading state
document.getElementById('createUserForm').addEventListener('submit', function () {
  document.getElementById('submitBtn').disabled = true;
  document.getElementById('submitBtn').innerHTML =
    '<span class="spinner-border spinner-border-sm me-1"></span> Creating…';
});
</script>

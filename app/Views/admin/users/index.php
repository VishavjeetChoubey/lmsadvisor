<?php
use App\Core\View;
use App\Services\AuthService;
use App\Middleware\CsrfMiddleware;
$e    = fn(mixed $v): string => View::e($v);
$url  = fn(string $p = ''): string => View::url($p);

$roleColors = [
    'super_admin' => 'warning',
    'admin'       => 'primary',
    'manager'     => 'info',
    'student'     => 'success',
];
$roleIcons = [
    'super_admin' => 'fa-crown',
    'admin'       => 'fa-shield-alt',
    'manager'     => 'fa-briefcase',
    'student'     => 'fa-graduation-cap',
];

$isSuperAdmin = AuthService::isSuperAdmin();
$isAdmin      = AuthService::isAdmin();
?>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <?php
  $statDefs = [
    ['super_admin', 'fa-crown',       'warning', 'Super Admins'],
    ['admin',       'fa-shield-alt',  'primary', 'Admins'],
    ['manager',     'fa-briefcase',   'info',    'Managers'],
    ['student',     'fa-graduation-cap','success','Students'],
  ];
  foreach ($statDefs as [$rKey, $icon, $color, $label]):
    $cnt = $roleCounts[$rKey] ?? 0;
  ?>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon text-<?= $color ?>"><i class="fas <?= $icon ?>"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($cnt) ?></div>
        <div class="stat-label"><?= $label ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Toolbar -->
<div class="card lms-card mb-4">
  <div class="card-body py-3 px-4">
    <div class="row g-2 align-items-center">

      <!-- Search -->
      <div class="col-12 col-md-5">
        <form method="GET" action="<?= $url('admin/users') ?>" id="searchForm">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" class="form-control border-start-0 ps-0"
                   name="search" placeholder="Search by name or email…"
                   value="<?= $e($search) ?>" id="searchInput">
            <?php if ($search): ?>
              <a href="<?= $url('admin/users') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i>
              </a>
            <?php endif; ?>
          </div>
          <input type="hidden" name="role" value="<?= $e($roleFilter) ?>">
        </form>
      </div>

      <!-- Role filter pills -->
      <div class="col-12 col-md-5">
        <div class="d-flex gap-1 flex-wrap">
          <a href="<?= $url('admin/users?search=' . urlencode($search)) ?>"
             class="btn btn-sm <?= $roleFilter === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">
            All <span class="badge bg-white text-dark ms-1"><?= array_sum($roleCounts) ?></span>
          </a>
          <?php foreach ($statDefs as [$rKey, $icon, $color, $label]): ?>
          <a href="<?= $url('admin/users?role=' . $rKey . '&search=' . urlencode($search)) ?>"
             class="btn btn-sm <?= $roleFilter === $rKey ? 'btn-' . $color : 'btn-outline-secondary' ?>">
            <i class="fas <?= $icon ?> me-1"></i><?= $label ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Add user button -->
      <div class="col-12 col-md-2 text-md-end">
        <?php if ($isAdmin): ?>
        <a href="<?= $url('admin/users/create') ?>" class="btn btn-primary">
          <i class="bi bi-person-plus me-1"></i> Add User
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Users table -->
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
    <h5 class="mb-0">
      <i class="bi bi-people me-2"></i> Users
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <small class="text-muted">
      <?= number_format(($page - 1) * $perPage + 1) ?>–<?= number_format(min($page * $perPage, $total)) ?> of <?= number_format($total) ?>
    </small>
  </div>

  <?php if (empty($rows)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-people"></i></div>
      <h6 class="mt-3" style="color:var(--text-muted)">No users found</h6>
      <?php if ($search || $roleFilter): ?>
        <a href="<?= $url('admin/users') ?>" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover lms-table mb-0">
        <thead>
          <tr>
            <th style="width:44px"></th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Last Login</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $user): ?>
          <?php
            $rName  = $user['role_name'] ?? 'student';
            $color  = $roleColors[$rName] ?? 'secondary';
            $icon   = $roleIcons[$rName]  ?? 'fa-user';
          ?>
          <tr id="user-row-<?= $user['id'] ?>">

            <!-- Avatar -->
            <td>
              <div class="user-table-avatar text-<?= $color ?>">
                <i class="fas <?= $icon ?>"></i>
              </div>
            </td>

            <!-- Name -->
            <td>
              <div class="fw-semibold" style="font-size:13.5px">
                <?= $e($user['first_name'] . ' ' . $user['last_name']) ?>
              </div>
              <div class="text-muted" style="font-size:11px">#<?= $user['id'] ?></div>
            </td>

            <!-- Email -->
            <td style="font-size:13px">
              <?= $e($user['email']) ?>
            </td>

            <!-- Role badge -->
            <td>
              <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> lms-role-badge">
                <i class="fas <?= $icon ?> me-1"></i>
                <?= $e($user['role_display'] ?? ucfirst($rName)) ?>
              </span>
            </td>

            <!-- Active toggle -->
            <td>
              <?php if ($isAdmin): ?>
              <div class="form-check form-switch mb-0" style="min-width:50px">
                <input class="form-check-input user-active-toggle"
                       type="checkbox"
                       role="switch"
                       <?= $user['is_active'] ? 'checked' : '' ?>
                       data-uuid="<?= $e($user['uuid']) ?>"
                       data-name="<?= $e($user['first_name']) ?>"
                       <?= $user['id'] == ($auth_user['id'] ?? 0) ? 'disabled' : '' ?>>
              </div>
              <?php else: ?>
                <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                  <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              <?php endif; ?>
            </td>

            <!-- Joined -->
            <td class="text-muted" style="font-size:12.5px;white-space:nowrap">
              <?= $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '—' ?>
            </td>

            <!-- Last Login -->
            <td class="text-muted" style="font-size:12.5px;white-space:nowrap">
              <?= $user['last_login_at'] ? date('d M Y, H:i', strtotime($user['last_login_at'])) : 'Never' ?>
            </td>

            <!-- Actions -->
            <td class="text-end">
              <div class="d-flex gap-1 justify-content-end">
                <?php if ($isAdmin): ?>
                  <a href="<?= $url('admin/users/' . $user['uuid'] . '/edit') ?>"
                     class="btn btn-sm btn-outline-primary" title="Edit user">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <?php if ((int)$user['id'] !== (int)($auth_user['id'] ?? 0) && $rName !== 'super_admin'): ?>
                  <button class="btn btn-sm btn-outline-danger btn-delete-user"
                          title="Delete user"
                          data-uuid="<?= $e($user['uuid']) ?>"
                          data-name="<?= $e($user['first_name'] . ' ' . $user['last_name']) ?>">
                    <i class="bi bi-trash3"></i>
                  </button>
                  <?php endif; ?>
                <?php else: ?>
                  <a href="<?= $url('admin/users/' . $user['uuid'] . '/edit') ?>"
                     class="btn btn-sm btn-outline-secondary" title="View">
                    <i class="bi bi-eye"></i>
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center py-3 px-4">
      <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <?php endif; ?>
          <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
          <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>"><?= $p ?></a>
          </li>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Hidden CSRF for AJAX -->
<input type="hidden" id="csrfToken" value="<?= $e(CsrfMiddleware::token()) ?>">

<style>
.user-table-avatar {
  width: 34px; height: 34px;
  background: var(--primary-light);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
}
.bg-warning-subtle { background: #fef9c3 !important; }
.bg-primary-subtle { background: #ebf2ff !important; }
.bg-info-subtle    { background: #e0f7fa !important; }
.bg-success-subtle { background: #d1fae5 !important; }
.lms-role-badge {
  font-size: 11.5px;
  padding: 4px 8px;
  border-radius: 20px;
  font-weight: 600;
}
</style>

<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';

// ── Live search debounce ──────────────────────────────────────────────────────
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function () {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => document.getElementById('searchForm').submit(), 400);
});

// ── Active toggle ─────────────────────────────────────────────────────────────
document.querySelectorAll('.user-active-toggle').forEach(function (toggle) {
  toggle.addEventListener('change', function () {
    const uuid = this.dataset.uuid;
    const name = this.dataset.name;
    const el   = this;

    fetch(BASE + '/admin/users/' + uuid + '/toggle-active', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF),
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const badge = el.closest('td').querySelector('.text-muted');
        LMS.toast(data.is_active ? 'success' : 'warning',
          name + ' has been ' + (data.is_active ? 'activated' : 'deactivated') + '.');
      } else {
        el.checked = !el.checked; // revert
        LMS.toast('error', data.message || 'Something went wrong.');
      }
    })
    .catch(() => { el.checked = !el.checked; LMS.toast('error', 'Request failed.'); });
  });
});

// ── Delete user ───────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-user').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const uuid = this.dataset.uuid;
    const name = this.dataset.name;

    LMS.confirm('Delete user "' + name + '"? This cannot be undone.', function () {
      fetch(BASE + '/admin/users/' + uuid + '/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const row = document.getElementById('user-row-' + uuid.split('-')[0]);
          // Find row by button
          btn.closest('tr').style.opacity = '0';
          setTimeout(() => btn.closest('tr').remove(), 300);
          LMS.toast('success', name + ' has been deleted.');
        } else {
          LMS.toast('error', data.message || 'Delete failed.');
        }
      })
      .catch(() => LMS.toast('error', 'Request failed.'));
    });
  });
});
</script>

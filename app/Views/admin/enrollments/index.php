<?php
use App\Core\View;
use App\Middleware\CsrfMiddleware;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$statusColors = [
    'active'    => 'success',
    'completed' => 'primary',
    'suspended' => 'warning',
    'expired'   => 'danger',
];
?>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <?php foreach (['active'=>'Active','completed'=>'Completed','suspended'=>'Suspended','expired'=>'Expired'] as $st => $lbl): ?>
  <div class="col-6 col-lg-3">
    <a href="<?= $url('admin/enrollments?status=' . $st) ?>"
       class="stat-card text-decoration-none <?= $statusFilter === $st ? 'ring-active' : '' ?>">
      <div class="stat-icon text-<?= $statusColors[$st] ?>">
        <i class="bi <?= $st==='active'?'bi-person-check':($st==='completed'?'bi-award':($st==='suspended'?'bi-pause-circle':'bi-clock-history')) ?>"></i>
      </div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($statusCounts[$st] ?? 0) ?></div>
        <div class="stat-label"><?= $lbl ?></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- Toolbar -->
<div class="card lms-card mb-4">
  <div class="card-body py-3 px-4">
    <div class="row g-2 align-items-center">

      <!-- Search -->
      <div class="col-12 col-md-4">
        <form method="GET" action="<?= $url('admin/enrollments') ?>" id="searchForm">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" class="form-control border-start-0 ps-0"
                   name="search" placeholder="Search name, email, course…"
                   value="<?= $e($search) ?>" id="enrollSearch">
            <input type="hidden" name="status"    value="<?= $e($statusFilter) ?>">
            <input type="hidden" name="course_id" value="<?= $e($courseFilter) ?>">
          </div>
        </form>
      </div>

      <!-- Course filter -->
      <div class="col-12 col-md-3">
        <select class="form-select form-select-sm" id="courseFilter" onchange="applyFilters()">
          <option value="">All Courses</option>
          <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (int)$courseFilter === (int)$c['id'] ? 'selected' : '' ?>>
            <?= $e($c['title']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Actions -->
      <div class="col-12 col-md-5 d-flex gap-2 justify-content-md-end flex-wrap">
        <button class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="modal" data-bs-target="#csvModal">
          <i class="bi bi-upload me-1"></i> CSV Import
        </button>
        <button class="btn btn-primary btn-sm"
                data-bs-toggle="modal" data-bs-target="#enrollModal">
          <i class="bi bi-person-plus me-1"></i> Enroll User
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Enrollments table -->
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="bi bi-person-check me-2"></i> Enrollments
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <small class="text-muted">
      <?= number_format(($page-1)*$perPage+1) ?>–<?= number_format(min($page*$perPage,$total)) ?> of <?= number_format($total) ?>
    </small>
  </div>

  <?php if (empty($rows)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-person-check"></i></div>
      <h6 class="mt-3 text-muted">No enrollments found</h6>
      <?php if ($search || $statusFilter || $courseFilter): ?>
        <a href="<?= $url('admin/enrollments') ?>" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead>
        <tr>
          <th>Student</th>
          <th>Course</th>
          <th>Progress</th>
          <th>Status</th>
          <th>Enrolled</th>
          <th>Expires</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr id="enroll-row-<?= $r['id'] ?>">

          <!-- Student -->
          <td>
            <div class="fw-semibold" style="font-size:13.5px">
              <?= $e($r['first_name'] . ' ' . $r['last_name']) ?>
            </div>
            <div class="text-muted" style="font-size:11.5px"><?= $e($r['email']) ?></div>
          </td>

          <!-- Course -->
          <td>
            <a href="<?= $url('admin/courses/' . $r['course_uuid'] . '/edit') ?>"
               class="text-decoration-none fw-semibold" style="font-size:13px">
              <?= $e($r['course_title']) ?>
            </a>
          </td>

          <!-- Progress bar -->
          <td style="min-width:120px">
            <?php $pct = (int)($r['progress_pct'] ?? 0); ?>
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
                <div class="progress-bar bg-<?= $pct >= 100 ? 'success' : 'primary' ?>"
                     style="width:<?= $pct ?>%"></div>
              </div>
              <span style="font-size:11.5px;color:var(--text-muted);white-space:nowrap"><?= $pct ?>%</span>
            </div>
          </td>

          <!-- Status badge + quick change -->
          <td>
            <div class="dropdown">
              <button class="badge border-0 bg-<?= $statusColors[$r['status']] ?? 'secondary' ?> dropdown-toggle"
                      style="cursor:pointer;font-size:11.5px;padding:5px 8px"
                      data-bs-toggle="dropdown">
                <?= ucfirst($e($r['status'])) ?>
              </button>
              <ul class="dropdown-menu shadow-sm" style="min-width:130px;font-size:13px">
                <?php foreach (['active','completed','suspended','expired'] as $st): ?>
                <?php if ($st !== $r['status']): ?>
                <li>
                  <button class="dropdown-item btn-change-status"
                          data-id="<?= $r['id'] ?>"
                          data-status="<?= $st ?>">
                    <?= ucfirst($st) ?>
                  </button>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            </div>
          </td>

          <!-- Enrolled date -->
          <td class="text-muted" style="font-size:12.5px;white-space:nowrap">
            <?= date('d M Y', strtotime($r['enrolled_at'])) ?>
          </td>

          <!-- Expires -->
          <td class="text-muted" style="font-size:12.5px;white-space:nowrap">
            <?php if ($r['expires_at']): ?>
              <?php $past = strtotime($r['expires_at']) < time(); ?>
              <span class="<?= $past ? 'text-danger' : '' ?>">
                <?= date('d M Y', strtotime($r['expires_at'])) ?>
              </span>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>

          <!-- Actions -->
          <td class="text-end">
            <button class="btn btn-sm btn-outline-danger btn-remove-enrollment"
                    title="Remove enrollment"
                    data-id="<?= $r['id'] ?>"
                    data-name="<?= $e($r['first_name'] . ' ' . $r['last_name']) ?>">
              <i class="bi bi-person-dash"></i>
            </button>
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
    <nav><ul class="pagination pagination-sm mb-0">
      <?php if ($page > 1): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&course_id=<?= $courseFilter ?>"><i class="bi bi-chevron-left"></i></a></li>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&course_id=<?= $courseFilter ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&course_id=<?= $courseFilter ?>"><i class="bi bi-chevron-right"></i></a></li>
      <?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ── Enroll User Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="enrollModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-person-plus me-2"></i>Enroll User</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">

        <!-- User search -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Find User</label>
          <input type="text" class="form-control" id="userSearchInput"
                 placeholder="Type name or email…" autocomplete="off">
          <div id="userSearchResults" class="list-group mt-1" style="max-height:200px;overflow-y:auto"></div>
          <input type="hidden" id="selectedUserId">
          <div id="selectedUserDisplay" class="mt-2 p-2 rounded d-none"
               style="background:var(--content-bg);font-size:13px"></div>
        </div>

        <!-- Course picker -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Course</label>
          <select class="form-select" id="enrollCourseId">
            <option value="">— Select a course —</option>
            <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (int)$courseFilter === (int)$c['id'] ? 'selected' : '' ?>>
              <?= $e($c['title']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Optional expiry -->
        <div>
          <label class="form-label fw-semibold">
            Expiry Date <small class="text-muted fw-normal">(optional — leave blank for no expiry)</small>
          </label>
          <input type="date" class="form-control" id="enrollExpiry"
                 min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="confirmEnrollBtn">
          <i class="bi bi-check-circle me-1"></i> Enroll
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── CSV Import Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="csvModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?= $url('admin/enrollments/csv') ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold"><i class="bi bi-upload me-2"></i>CSV Bulk Enrollment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body px-4">
          <div class="alert alert-info small d-flex gap-2">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div>
              CSV format: one email per row (first column).<br>
              Header row is optional (detected automatically).<br>
              Only existing users will be enrolled.
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Course <span class="text-danger">*</span></label>
            <select class="form-select" name="course_id" required>
              <option value="">— Select a course —</option>
              <?php foreach ($courses as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (int)$courseFilter === (int)$c['id'] ? 'selected' : '' ?>>
                <?= $e($c['title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
            <input type="file" class="form-control" name="csv_file" accept=".csv,.txt" required>
            <div class="form-text">Max 5MB. UTF-8 encoded.</div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-upload me-1"></i> Import
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.ring-active { box-shadow: 0 0 0 2px var(--primary) !important; }
</style>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';

// ── Live search ───────────────────────────────────────────────────────────────
let st;
document.getElementById('enrollSearch')?.addEventListener('input', function () {
  clearTimeout(st);
  st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
});

function applyFilters() {
  const course = document.getElementById('courseFilter').value;
  const search = document.getElementById('enrollSearch').value;
  const status = '<?= $e($statusFilter) ?>';
  window.location.href = BASE + '/admin/enrollments?course_id=' + course + '&search=' + encodeURIComponent(search) + '&status=' + status;
}

// ── User search (enroll modal) ────────────────────────────────────────────────
let userSearchTimer;
document.getElementById('userSearchInput').addEventListener('input', function () {
  const q = this.value.trim();
  clearTimeout(userSearchTimer);
  if (q.length < 2) { document.getElementById('userSearchResults').innerHTML = ''; return; }

  userSearchTimer = setTimeout(() => {
    fetch(BASE + '/admin/users/search?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
      const ul = document.getElementById('userSearchResults');
      ul.innerHTML = '';
      (data.users || []).forEach(u => {
        const li = document.createElement('a');
        li.href = '#';
        li.className = 'list-group-item list-group-item-action py-2';
        li.style.fontSize = '13px';
        li.innerHTML = '<strong>' + u.first_name + ' ' + u.last_name + '</strong> <span class="text-muted">(' + u.email + ')</span>';
        li.addEventListener('click', function(e) {
          e.preventDefault();
          document.getElementById('selectedUserId').value = u.id;
          document.getElementById('selectedUserDisplay').textContent =
            u.first_name + ' ' + u.last_name + ' — ' + u.email;
          document.getElementById('selectedUserDisplay').classList.remove('d-none');
          document.getElementById('userSearchInput').value = u.first_name + ' ' + u.last_name;
          ul.innerHTML = '';
        });
        ul.appendChild(li);
      });
    });
  }, 300);
});

// ── Confirm enrollment ────────────────────────────────────────────────────────
document.getElementById('confirmEnrollBtn').addEventListener('click', function () {
  const userId   = document.getElementById('selectedUserId').value;
  const courseId = document.getElementById('enrollCourseId').value;
  const expiry   = document.getElementById('enrollExpiry').value;

  if (!userId)   { LMS.toast('error', 'Please select a user.'); return; }
  if (!courseId) { LMS.toast('error', 'Please select a course.'); return; }

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

  const body = 'csrf_token=' + encodeURIComponent(CSRF) +
               '&user_id='   + encodeURIComponent(userId) +
               '&course_id=' + encodeURIComponent(courseId) +
               '&expires_at='+ encodeURIComponent(expiry);

  fetch(BASE + '/admin/enrollments/enroll', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      bootstrap.Modal.getInstance(document.getElementById('enrollModal'))?.hide();
      LMS.toast('success', d.message);
      setTimeout(() => location.reload(), 900);
    } else {
      LMS.toast('error', d.message);
    }
  })
  .finally(() => {
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Enroll';
  });
});

// ── Change status ─────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-change-status').forEach(btn => {
  btn.addEventListener('click', function () {
    const id     = this.dataset.id;
    const status = this.dataset.status;

    fetch(BASE + '/admin/enrollments/' + id + '/status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&status=' + encodeURIComponent(status),
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { LMS.toast('success', 'Status updated to ' + d.status + '.'); location.reload(); }
      else LMS.toast('error', d.message);
    });
  });
});

// ── Remove enrollment ─────────────────────────────────────────────────────────
document.querySelectorAll('.btn-remove-enrollment').forEach(btn => {
  btn.addEventListener('click', function () {
    const id   = this.dataset.id;
    const name = this.dataset.name;

    LMS.confirm('Remove enrollment for "' + name + '"? Their progress will be lost.', function () {
      fetch(BASE + '/admin/enrollments/' + id + '/remove', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('enroll-row-' + id)?.remove();
          LMS.toast('success', 'Enrollment removed.');
        } else LMS.toast('error', d.message);
      });
    });
  });
});
</script>

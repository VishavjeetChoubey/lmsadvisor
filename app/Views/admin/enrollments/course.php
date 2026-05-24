<?php
use App\Core\View;
use App\Middleware\CsrfMiddleware;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$statusColors = ['active'=>'success','completed'=>'primary','suspended'=>'warning','expired'=>'danger'];
?>

<!-- Course header -->
<div class="card lms-card mb-4">
  <div class="card-body p-4 d-flex align-items-center gap-4 flex-wrap">
    <?php if ($course['thumbnail']): ?>
    <img src="<?= $e(APP_URL . '/storage/uploads/' . $course['thumbnail']) ?>"
         alt="" style="width:80px;height:56px;object-fit:cover;border-radius:8px">
    <?php endif; ?>
    <div class="flex-grow-1">
      <h4 class="fw-bold mb-1"><?= $e($course['title']) ?></h4>
      <div class="text-muted" style="font-size:13px">
        <?= $e($course['language']) ?> · <?= ucfirst($e($course['level'])) ?>
        · <span class="badge bg-<?= $statusColors[$course['status']] ?? 'secondary' ?>"><?= ucfirst($e($course['status'])) ?></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= $url('admin/courses/' . $course['uuid'] . '/edit') ?>"
         class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-pencil me-1"></i> Edit Course
      </a>
      <button class="btn btn-sm btn-primary"
              data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-person-plus me-1"></i> Add Student
      </button>
    </div>
  </div>
</div>

<!-- Search + filter bar -->
<div class="card lms-card mb-3">
  <div class="card-body py-2 px-4">
    <div class="row g-2 align-items-center">
      <div class="col-md-6">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-end-0">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input type="text" class="form-control border-start-0 ps-0"
                 id="studentSearch" placeholder="Search student name or email…">
        </div>
      </div>
      <div class="col-md-3">
        <select class="form-select form-select-sm" id="statusFilter">
          <option value="">All Statuses</option>
          <?php foreach (['active','completed','suspended','expired'] as $st): ?>
          <option value="<?= $st ?>"><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 text-md-end">
        <span class="text-muted" style="font-size:13px" id="rowCount">
          <?= count($enrollments) ?> student<?= count($enrollments) !== 1 ? 's' : '' ?>
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Enrollment table -->
<div class="card lms-card">
  <div class="card-header lms-card-header">
    <h5 class="mb-0"><i class="bi bi-people me-2"></i> Enrolled Students</h5>
  </div>

  <?php if (empty($enrollments)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-people"></i></div>
      <h6 class="mt-3 text-muted">No students enrolled yet</h6>
      <button class="btn btn-sm btn-primary mt-2"
              data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-person-plus me-1"></i> Enroll First Student
      </button>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0" id="studentsTable">
      <thead>
        <tr>
          <th>Student</th>
          <th>Progress</th>
          <th>Status</th>
          <th>Enrolled</th>
          <th>Completed</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="studentsBody">
        <?php foreach ($enrollments as $r): ?>
        <?php $pct = (int)($r['progress_pct'] ?? 0); ?>
        <tr class="student-row"
            data-name="<?= $e(strtolower($r['first_name'] . ' ' . $r['last_name'])) ?>"
            data-email="<?= $e(strtolower($r['email'])) ?>"
            data-status="<?= $e($r['status']) ?>">

          <!-- Student info -->
          <td>
            <div class="fw-semibold" style="font-size:13.5px">
              <?= $e($r['first_name'] . ' ' . $r['last_name']) ?>
            </div>
            <div class="text-muted" style="font-size:11.5px"><?= $e($r['email']) ?></div>
          </td>

          <!-- Progress -->
          <td style="min-width:130px">
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
                <div class="progress-bar bg-<?= $pct>=100?'success':'primary' ?>"
                     style="width:<?= $pct ?>%"></div>
              </div>
              <span style="font-size:11.5px;color:var(--text-muted);white-space:nowrap"><?= $pct ?>%</span>
            </div>
          </td>

          <!-- Status -->
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
                          data-id="<?= $r['id'] ?>" data-status="<?= $st ?>">
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

          <!-- Completed date -->
          <td class="text-muted" style="font-size:12.5px;white-space:nowrap">
            <?= $r['completed_at'] ? date('d M Y', strtotime($r['completed_at'])) : '—' ?>
          </td>

          <!-- Actions -->
          <td class="text-end">
            <button class="btn btn-sm btn-outline-danger btn-remove-student"
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
  <?php endif; ?>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-person-plus me-2"></i>Add Student</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <div class="mb-3">
          <label class="form-label fw-semibold">Find Student</label>
          <input type="text" class="form-control" id="addStudentSearch"
                 placeholder="Type name or email…" autocomplete="off">
          <div id="addStudentResults" class="list-group mt-1" style="max-height:200px;overflow-y:auto"></div>
          <input type="hidden" id="addStudentUserId">
          <div id="addStudentDisplay" class="mt-2 p-2 rounded d-none"
               style="background:var(--content-bg);font-size:13px"></div>
        </div>
        <div>
          <label class="form-label fw-semibold">Expiry Date <small class="text-muted fw-normal">(optional)</small></label>
          <input type="date" class="form-control" id="addStudentExpiry"
                 min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="confirmAddStudent">
          <i class="bi bi-check-circle me-1"></i> Enroll
        </button>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<input type="hidden" id="courseId"  value="<?= $e($course['id']) ?>">

<script>
const CSRF     = document.getElementById('csrfToken').value;
const BASE     = '<?= rtrim(APP_URL, '/') ?>';
const COURSE_ID = document.getElementById('courseId').value;

// ── Client-side filter (search + status) ─────────────────────────────────────
function filterRows() {
  const q      = document.getElementById('studentSearch').value.toLowerCase();
  const status = document.getElementById('statusFilter').value;
  let shown    = 0;

  document.querySelectorAll('.student-row').forEach(row => {
    const matchQ = !q || row.dataset.name.includes(q) || row.dataset.email.includes(q);
    const matchS = !status || row.dataset.status === status;
    const show   = matchQ && matchS;
    row.style.display = show ? '' : 'none';
    if (show) shown++;
  });

  document.getElementById('rowCount').textContent =
    shown + ' student' + (shown !== 1 ? 's' : '');
}

document.getElementById('studentSearch').addEventListener('input', filterRows);
document.getElementById('statusFilter').addEventListener('change', filterRows);

// ── User search ───────────────────────────────────────────────────────────────
let timer;
document.getElementById('addStudentSearch').addEventListener('input', function () {
  const q = this.value.trim();
  clearTimeout(timer);
  if (q.length < 2) { document.getElementById('addStudentResults').innerHTML = ''; return; }

  timer = setTimeout(() => {
    fetch(BASE + '/admin/users/search?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
      const ul = document.getElementById('addStudentResults');
      ul.innerHTML = '';
      (data.users || []).forEach(u => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action py-2';
        a.style.fontSize = '13px';
        a.innerHTML = '<strong>' + u.first_name + ' ' + u.last_name + '</strong> <span class="text-muted">(' + u.email + ')</span>';
        a.addEventListener('click', function(e) {
          e.preventDefault();
          document.getElementById('addStudentUserId').value = u.id;
          document.getElementById('addStudentDisplay').textContent = u.first_name + ' ' + u.last_name + ' — ' + u.email;
          document.getElementById('addStudentDisplay').classList.remove('d-none');
          document.getElementById('addStudentSearch').value = u.first_name + ' ' + u.last_name;
          ul.innerHTML = '';
        });
        ul.appendChild(a);
      });
    });
  }, 300);
});

// ── Confirm add student ───────────────────────────────────────────────────────
document.getElementById('confirmAddStudent').addEventListener('click', function () {
  const userId = document.getElementById('addStudentUserId').value;
  const expiry = document.getElementById('addStudentExpiry').value;

  if (!userId) { LMS.toast('error', 'Please select a student.'); return; }

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

  fetch(BASE + '/admin/enrollments/enroll', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) +
          '&user_id=' + encodeURIComponent(userId) +
          '&course_id=' + encodeURIComponent(COURSE_ID) +
          '&expires_at=' + encodeURIComponent(expiry),
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      bootstrap.Modal.getInstance(document.getElementById('addStudentModal'))?.hide();
      LMS.toast('success', d.message);
      setTimeout(() => location.reload(), 800);
    } else LMS.toast('error', d.message);
  })
  .finally(() => {
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Enroll';
  });
});

// ── Change status ─────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-change-status').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id, status = this.dataset.status;
    fetch(BASE + '/admin/enrollments/' + id + '/status', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&status=' + encodeURIComponent(status),
    })
    .then(r => r.json())
    .then(d => { if (d.success) { LMS.toast('success', 'Status updated.'); location.reload(); } else LMS.toast('error', d.message); });
  });
});

// ── Remove student ────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-remove-student').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id, name = this.dataset.name;
    LMS.confirm('Remove ' + name + ' from this course? Their progress will be lost.', function () {
      fetch(BASE + '/admin/enrollments/' + id + '/remove', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          btn.closest('tr').remove();
          LMS.toast('success', name + ' removed.');
        } else LMS.toast('error', d.message);
      });
    });
  });
});
</script>

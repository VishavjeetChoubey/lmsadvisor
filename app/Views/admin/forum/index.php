<?php
use App\Core\View;
use App\Middleware\CsrfMiddleware;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <?php
  $statItems = [
    ['total_threads', 'Threads',   'bi-chat-square-text', 'primary'],
    ['total_replies', 'Replies',   'bi-reply-all',        'success'],
    ['pinned',        'Pinned',    'bi-pin-angle',        'warning'],
    ['locked',        'Locked',    'bi-lock',             'danger'],
  ];
  foreach ($statItems as [$key, $label, $icon, $color]):
  ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon text-<?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($stats[$key] ?? 0) ?></div>
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
      <div class="col-12 col-md-4">
        <form method="GET" action="<?= $url('admin/forum') ?>" id="searchForm">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" class="form-control border-start-0 ps-0"
                   name="search" id="forumSearch"
                   placeholder="Search threads…" value="<?= $e($search) ?>">
            <input type="hidden" name="course_id" value="<?= $e($courseFilter) ?>">
          </div>
        </form>
      </div>
      <div class="col-12 col-md-4">
        <select class="form-select form-select-sm" id="courseFilterSel" onchange="applyCourseFilter()">
          <option value="">All Courses</option>
          <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (int)$courseFilter === (int)$c['id'] ? 'selected' : '' ?>>
            <?= $e($c['title']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4 d-flex gap-2 justify-content-md-end">
        <button class="btn btn-primary btn-sm"
                data-bs-toggle="modal" data-bs-target="#newThreadModal">
          <i class="bi bi-plus-circle me-1"></i> New Thread
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Thread table -->
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="bi bi-chat-square-text me-2"></i> Threads
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <small class="text-muted">
      <?= number_format(($page-1)*$perPage+1) ?>–<?= number_format(min($page*$perPage,$total)) ?> of <?= number_format($total) ?>
    </small>
  </div>

  <?php if (empty($rows)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-chat-square"></i></div>
      <h6 class="mt-3 text-muted">No threads found</h6>
      <?php if ($search || $courseFilter): ?>
        <a href="<?= $url('admin/forum') ?>" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead>
        <tr>
          <th style="width:32px"></th>
          <th>Thread</th>
          <th>Course</th>
          <th>Author</th>
          <th class="text-center">Replies</th>
          <th>Last Activity</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $t): ?>
        <tr id="thread-row-<?= $t['id'] ?>">
          <!-- Pin/Lock indicators -->
          <td>
            <div class="d-flex flex-column gap-1 align-items-center">
              <?php if ($t['is_pinned']): ?>
                <i class="bi bi-pin-angle-fill text-warning" title="Pinned" style="font-size:13px"></i>
              <?php endif; ?>
              <?php if ($t['is_locked']): ?>
                <i class="bi bi-lock-fill text-danger" title="Locked" style="font-size:13px"></i>
              <?php endif; ?>
            </div>
          </td>

          <!-- Title -->
          <td>
            <a href="<?= $url('admin/forum/threads/' . $t['id']) ?>"
               class="fw-semibold text-decoration-none" style="font-size:13.5px">
              <?= $e($t['title']) ?>
            </a>
            <div class="text-muted mt-1" style="font-size:12px">
              <?= mb_strimwidth(strip_tags($t['body']), 0, 80, '…') ?>
            </div>
          </td>

          <!-- Course -->
          <td>
            <a href="<?= $url('admin/courses/' . $t['course_uuid'] . '/edit') ?>"
               class="text-decoration-none text-muted" style="font-size:13px">
              <?= $e($t['course_title']) ?>
            </a>
          </td>

          <!-- Author -->
          <td style="font-size:13px">
            <?= $e($t['first_name'] . ' ' . $t['last_name']) ?>
            <?php if (in_array($t['role_name'], ['admin','super_admin','manager'])): ?>
              <span class="badge bg-primary" style="font-size:9px">Staff</span>
            <?php endif; ?>
          </td>

          <!-- Reply count -->
          <td class="text-center" style="font-size:13px">
            <span class="badge bg-secondary"><?= (int)$t['reply_count'] ?></span>
          </td>

          <!-- Last activity -->
          <td class="text-muted" style="font-size:12.5px;white-space:nowrap">
            <?= date('d M Y H:i', strtotime($t['updated_at'])) ?>
          </td>

          <!-- Actions -->
          <td class="text-end">
            <div class="d-flex gap-1 justify-content-end">
              <a href="<?= $url('admin/forum/threads/' . $t['id']) ?>"
                 class="btn btn-xs btn-outline-primary" title="View thread">
                <i class="bi bi-eye"></i>
              </a>
              <button class="btn btn-xs btn-outline-warning btn-pin-thread"
                      title="<?= $t['is_pinned'] ? 'Unpin' : 'Pin' ?>"
                      data-id="<?= $t['id'] ?>" data-pin="<?= $t['is_pinned'] ? '0' : '1' ?>">
                <i class="bi bi-pin<?= $t['is_pinned'] ? '-angle-fill' : '-angle' ?>"></i>
              </button>
              <button class="btn btn-xs btn-outline-secondary btn-lock-thread"
                      title="<?= $t['is_locked'] ? 'Unlock' : 'Lock' ?>"
                      data-id="<?= $t['id'] ?>" data-lock="<?= $t['is_locked'] ? '0' : '1' ?>">
                <i class="bi bi-<?= $t['is_locked'] ? 'unlock' : 'lock' ?>"></i>
              </button>
              <button class="btn btn-xs btn-outline-danger btn-delete-thread"
                      title="Delete thread"
                      data-id="<?= $t['id'] ?>" data-title="<?= $e($t['title']) ?>">
                <i class="bi bi-trash3"></i>
              </button>
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
    <nav><ul class="pagination pagination-sm mb-0">
      <?php if ($page > 1): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&course_id=<?= $courseFilter ?>"><i class="bi bi-chevron-left"></i></a></li>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&course_id=<?= $courseFilter ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&course_id=<?= $courseFilter ?>"><i class="bi bi-chevron-right"></i></a></li>
      <?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- New Thread Modal -->
<div class="modal fade" id="newThreadModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>Post New Thread</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <div class="mb-3">
          <label class="form-label fw-semibold">Course</label>
          <select class="form-select" id="newThreadCourse">
            <option value="">— Select course —</option>
            <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (int)$courseFilter === (int)$c['id'] ? 'selected' : '' ?>>
              <?= $e($c['title']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Title</label>
          <input type="text" class="form-control" id="newThreadTitle" maxlength="255" placeholder="Thread title…">
        </div>
        <div>
          <label class="form-label fw-semibold">Body</label>
          <textarea class="form-control" id="newThreadBody" rows="5" placeholder="Thread content…"></textarea>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="submitNewThread">
          <i class="bi bi-send me-1"></i> Post Thread
        </button>
      </div>
    </div>
  </div>
</div>

<style>.btn-xs{padding:3px 7px;font-size:12px}</style>
<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">

<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';

// Live search
let st;
document.getElementById('forumSearch').addEventListener('input', function () {
  clearTimeout(st);
  st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
});

function applyCourseFilter() {
  const cid    = document.getElementById('courseFilterSel').value;
  const search = document.getElementById('forumSearch').value;
  window.location.href = BASE + '/admin/forum?course_id=' + cid + '&search=' + encodeURIComponent(search);
}

// ── Pin ───────────────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-pin-thread').forEach(btn => {
  btn.addEventListener('click', function () {
    const id  = this.dataset.id;
    const pin = this.dataset.pin;
    fetch(BASE + '/admin/forum/threads/' + id + '/pin', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&pin=' + pin,
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { LMS.toast('success', d.pinned ? 'Thread pinned.' : 'Thread unpinned.'); location.reload(); }
      else LMS.toast('error', d.message);
    });
  });
});

// ── Lock ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-lock-thread').forEach(btn => {
  btn.addEventListener('click', function () {
    const id   = this.dataset.id;
    const lock = this.dataset.lock;
    fetch(BASE + '/admin/forum/threads/' + id + '/lock', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&lock=' + lock,
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { LMS.toast('success', d.locked ? 'Thread locked.' : 'Thread unlocked.'); location.reload(); }
      else LMS.toast('error', d.message);
    });
  });
});

// ── Delete thread ─────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-thread').forEach(btn => {
  btn.addEventListener('click', function () {
    const id    = this.dataset.id;
    const title = this.dataset.title;
    LMS.confirm('Delete thread "' + title + '" and all its replies?', function () {
      fetch(BASE + '/admin/forum/threads/' + id + '/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) { document.getElementById('thread-row-' + id)?.remove(); LMS.toast('success', 'Thread deleted.'); }
        else LMS.toast('error', d.message);
      });
    });
  });
});

// ── New thread ────────────────────────────────────────────────────────────────
document.getElementById('submitNewThread').addEventListener('click', function () {
  const courseId = document.getElementById('newThreadCourse').value;
  const title    = document.getElementById('newThreadTitle').value.trim();
  const body     = document.getElementById('newThreadBody').value.trim();

  if (!courseId) { LMS.toast('error', 'Please select a course.'); return; }
  if (!title)    { LMS.toast('error', 'Thread title is required.'); return; }
  if (!body)     { LMS.toast('error', 'Thread body is required.'); return; }

  this.disabled = true;
  fetch(BASE + '/admin/forum/threads', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) +
          '&course_id=' + encodeURIComponent(courseId) +
          '&title='     + encodeURIComponent(title) +
          '&body='      + encodeURIComponent(body),
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      bootstrap.Modal.getInstance(document.getElementById('newThreadModal'))?.hide();
      LMS.toast('success', 'Thread posted.');
      setTimeout(() => window.location.href = BASE + '/admin/forum/threads/' + d.thread_id, 800);
    } else LMS.toast('error', d.message);
  })
  .finally(() => { this.disabled = false; });
});
</script>

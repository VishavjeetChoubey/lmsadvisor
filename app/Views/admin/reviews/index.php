<?php
use App\Core\View;
use App\Middleware\CsrfMiddleware;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$stars = fn(int $n): string => str_repeat('★', $n) . str_repeat('☆', 5 - $n);
?>

<!-- Stats + Rating Distribution -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon text-primary"><i class="bi bi-star-half"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $stats['avg_rating'] ?: '—' ?></div>
        <div class="stat-label">Avg Rating</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon text-success"><i class="bi bi-check-circle"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($stats['approved']) ?></div>
        <div class="stat-label">Approved</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <a href="<?= $url('admin/reviews?status=pending') ?>"
       class="stat-card text-decoration-none <?= $statusFilter==='pending'?'ring-active':'' ?>">
      <div class="stat-icon text-warning"><i class="bi bi-clock-history"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($stats['pending']) ?></div>
        <div class="stat-label">Pending</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon text-info"><i class="bi bi-chat-square-quote"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($stats['total']) ?></div>
        <div class="stat-label">Total Reviews</div>
      </div>
    </div>
  </div>
</div>

<!-- Rating distribution bar -->
<?php if ($stats['total'] > 0): ?>
<div class="card lms-card mb-4">
  <div class="card-body px-4 py-3">
    <h6 class="fw-semibold mb-3">Rating Distribution</h6>
    <div class="row g-2">
      <?php foreach ([5,4,3,2,1] as $star): ?>
      <?php
        $cnt  = $dist[$star] ?? 0;
        $pct  = $stats['total'] > 0 ? round($cnt / $stats['total'] * 100) : 0;
        $cls  = $star >= 4 ? 'success' : ($star >= 3 ? 'warning' : 'danger');
      ?>
      <div class="col-12">
        <div class="d-flex align-items-center gap-2" style="font-size:13px">
          <a href="<?= $url('admin/reviews?rating=' . $star) ?>"
             class="text-warning text-decoration-none" style="white-space:nowrap;width:72px">
            <?= $star ?>★ <?= $star===1?'star':'stars' ?>
          </a>
          <div class="progress flex-grow-1" style="height:10px;border-radius:5px">
            <div class="progress-bar bg-<?= $cls ?>" style="width:<?= $pct ?>%"></div>
          </div>
          <span class="text-muted" style="width:55px;text-align:right"><?= $cnt ?> (<?= $pct ?>%)</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Toolbar -->
<div class="card lms-card mb-4">
  <div class="card-body py-3 px-4">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-4">
        <form method="GET" action="<?= $url('admin/reviews') ?>" id="searchForm">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" class="form-control border-start-0 ps-0"
                   name="search" id="reviewSearch"
                   placeholder="Search reviewer, course…" value="<?= $e($search) ?>">
            <input type="hidden" name="status"    value="<?= $e($statusFilter) ?>">
            <input type="hidden" name="course_id" value="<?= $e($courseFilter) ?>">
            <input type="hidden" name="rating"    value="<?= $e($ratingFilter) ?>">
          </div>
        </form>
      </div>
      <div class="col-12 col-md-3 d-flex gap-2">
        <select class="form-select form-select-sm" onchange="applyFilter('status', this.value)">
          <option value="" <?= !$statusFilter ? 'selected' : '' ?>>All Status</option>
          <option value="pending"  <?= $statusFilter==='pending'  ? 'selected' : '' ?>>Pending</option>
          <option value="approved" <?= $statusFilter==='approved' ? 'selected' : '' ?>>Approved</option>
        </select>
        <select class="form-select form-select-sm" onchange="applyFilter('course_id', this.value)">
          <option value="">All Courses</option>
          <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (int)$courseFilter===(int)$c['id']?'selected':'' ?>><?= $e($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-5 d-flex gap-2 justify-content-md-end flex-wrap">
        <button class="btn btn-sm btn-success d-none" id="bulkApproveBtn" onclick="bulk('approve')">
          <i class="bi bi-check-all me-1"></i> Approve Selected
        </button>
        <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn" onclick="bulk('delete')">
          <i class="bi bi-trash3 me-1"></i> Delete Selected
        </button>
        <?php if ($statusFilter === 'pending' && $stats['pending'] > 0): ?>
        <button class="btn btn-sm btn-outline-success" id="approveAllPendingBtn">
          <i class="bi bi-check-all me-1"></i> Approve All Pending
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Reviews table -->
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="bi bi-star me-2"></i> Reviews
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <small class="text-muted">
      <?= number_format(($page-1)*$perPage+1) ?>–<?= number_format(min($page*$perPage,$total)) ?> of <?= number_format($total) ?>
    </small>
  </div>

  <?php if (empty($rows)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-star"></i></div>
      <h6 class="mt-3 text-muted">No reviews found</h6>
      <?php if ($search || $statusFilter || $courseFilter): ?>
        <a href="<?= $url('admin/reviews') ?>" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <form id="bulkForm">
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead>
        <tr>
          <th style="width:36px">
            <input type="checkbox" class="form-check-input" id="checkAll">
          </th>
          <th>Reviewer</th>
          <th>Course</th>
          <th>Rating</th>
          <th>Review</th>
          <th>Status</th>
          <th>Date</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr id="review-row-<?= $r['id'] ?>">
          <td>
            <input type="checkbox" class="form-check-input review-check"
                   value="<?= $r['id'] ?>" onchange="updateBulkButtons()">
          </td>
          <td>
            <div class="fw-semibold" style="font-size:13.5px">
              <?= $e($r['first_name'] . ' ' . $r['last_name']) ?>
            </div>
            <div class="text-muted" style="font-size:11.5px"><?= $e($r['email']) ?></div>
          </td>
          <td>
            <a href="<?= $url('admin/courses/' . $r['course_uuid'] . '/edit') ?>"
               class="text-decoration-none" style="font-size:13px">
              <?= $e($r['course_title']) ?>
            </a>
          </td>
          <td>
            <span class="star-display" style="color:#e3a008;font-size:15px">
              <?= $stars((int)$r['rating']) ?>
            </span>
            <div class="text-muted" style="font-size:11px"><?= $r['rating'] ?>/5</div>
          </td>
          <td style="max-width:300px">
            <?php if ($r['review']): ?>
              <div style="font-size:13px;color:var(--text-muted)">
                <?= $e(mb_strimwidth($r['review'], 0, 120, '…')) ?>
              </div>
              <?php if (mb_strlen($r['review']) > 120): ?>
                <a href="#" class="small" onclick="showFullReview(<?= $r['id'] ?>, this); return false">
                  Read more
                </a>
                <div id="full-review-<?= $r['id'] ?>" class="d-none" style="font-size:13px;margin-top:4px">
                  <?= $e($r['review']) ?>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted" style="font-size:12px"><em>No written review</em></span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge bg-<?= $r['is_approved'] ? 'success' : 'warning text-dark' ?>"
                  id="status-badge-<?= $r['id'] ?>">
              <?= $r['is_approved'] ? 'Approved' : 'Pending' ?>
            </span>
          </td>
          <td class="text-muted" style="font-size:12.5px;white-space:nowrap">
            <?= date('d M Y', strtotime($r['created_at'])) ?>
          </td>
          <td class="text-end">
            <div class="d-flex gap-1 justify-content-end">
              <?php if (!$r['is_approved']): ?>
              <button class="btn btn-xs btn-outline-success btn-approve"
                      data-id="<?= $r['id'] ?>" title="Approve">
                <i class="bi bi-check-lg"></i>
              </button>
              <?php else: ?>
              <button class="btn btn-xs btn-outline-warning btn-unapprove"
                      data-id="<?= $r['id'] ?>" title="Unapprove">
                <i class="bi bi-x-lg"></i>
              </button>
              <?php endif; ?>
              <button class="btn btn-xs btn-outline-danger btn-delete-review"
                      data-id="<?= $r['id'] ?>" title="Delete">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  </form>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center py-3 px-4">
    <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
    <nav><ul class="pagination pagination-sm mb-0">
      <?php if ($page > 1): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&course_id=<?= $courseFilter ?>&rating=<?= $ratingFilter ?>"><i class="bi bi-chevron-left"></i></a></li>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&course_id=<?= $courseFilter ?>&rating=<?= $ratingFilter ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&course_id=<?= $courseFilter ?>&rating=<?= $ratingFilter ?>"><i class="bi bi-chevron-right"></i></a></li>
      <?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<style>
.ring-active { box-shadow: 0 0 0 2px var(--primary) !important; }
.btn-xs { padding: 3px 7px; font-size: 12px; }
</style>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';

// ── Filter helpers ────────────────────────────────────────────────────────────
function applyFilter(key, val) {
  const url = new URL(window.location.href);
  url.searchParams.set(key, val);
  url.searchParams.set('page', 1);
  window.location.href = url.toString();
}

let st;
document.getElementById('reviewSearch').addEventListener('input', function () {
  clearTimeout(st);
  st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
});

// ── Read more toggle ──────────────────────────────────────────────────────────
function showFullReview(id, link) {
  document.getElementById('full-review-' + id).classList.remove('d-none');
  link.style.display = 'none';
}

// ── Check-all ─────────────────────────────────────────────────────────────────
document.getElementById('checkAll')?.addEventListener('change', function () {
  document.querySelectorAll('.review-check').forEach(cb => cb.checked = this.checked);
  updateBulkButtons();
});

function updateBulkButtons() {
  const checked = document.querySelectorAll('.review-check:checked').length;
  document.getElementById('bulkApproveBtn').classList.toggle('d-none', checked === 0);
  document.getElementById('bulkDeleteBtn').classList.toggle('d-none', checked === 0);
}

function getCheckedIds() {
  return [...document.querySelectorAll('.review-check:checked')].map(cb => cb.value);
}

// ── Bulk actions ──────────────────────────────────────────────────────────────
function bulk(action) {
  const ids = getCheckedIds();
  if (!ids.length) { LMS.toast('error', 'Select at least one review.'); return; }

  const label = action === 'approve' ? 'approve' : 'delete';
  LMS.confirm(`${label.charAt(0).toUpperCase()+label.slice(1)} ${ids.length} review(s)?`, function () {
    fetch(BASE + '/admin/reviews/bulk', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) +
            '&action=' + encodeURIComponent(action) +
            ids.map(id => '&ids[]=' + id).join(''),
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { LMS.toast('success', d.message); location.reload(); }
      else LMS.toast('error', d.message);
    });
  });
}

// ── Approve all pending ───────────────────────────────────────────────────────
document.getElementById('approveAllPendingBtn')?.addEventListener('click', function () {
  LMS.confirm('Approve all pending reviews?', function () {
    // Select all visible pending rows and bulk approve
    const ids = [...document.querySelectorAll('[id^="review-row-"]')]
      .filter(row => row.querySelector('.bg-warning'))
      .map(row => row.id.replace('review-row-', ''));

    if (!ids.length) { LMS.toast('info', 'No pending reviews on this page.'); return; }

    fetch(BASE + '/admin/reviews/bulk', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) +
            '&action=approve' +
            ids.map(id => '&ids[]=' + id).join(''),
    })
    .then(r => r.json())
    .then(d => { if (d.success) { LMS.toast('success', d.message); location.reload(); } });
  });
});

// ── Individual approve/unapprove/delete ───────────────────────────────────────
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-approve,.btn-unapprove,.btn-delete-review');
  if (!btn) return;
  e.preventDefault();

  const id = btn.dataset.id;

  if (btn.classList.contains('btn-delete-review')) {
    LMS.confirm('Delete this review permanently?', function () {
      fetch(BASE + '/admin/reviews/' + id + '/delete', {
        method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r=>r.json())
      .then(d => {
        if (d.success) { document.getElementById('review-row-' + id)?.remove(); LMS.toast('success','Review deleted.'); }
        else LMS.toast('error', d.message);
      });
    });
    return;
  }

  const endpoint = btn.classList.contains('btn-approve') ? 'approve' : 'unapprove';
  fetch(BASE + '/admin/reviews/' + id + '/' + endpoint, {
    method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF),
  })
  .then(r=>r.json())
  .then(d => {
    if (d.success) {
      const badge = document.getElementById('status-badge-' + id);
      if (badge) {
        badge.textContent = d.is_approved ? 'Approved' : 'Pending';
        badge.className   = 'badge bg-' + (d.is_approved ? 'success' : 'warning text-dark');
      }
      // Swap button
      const newBtn = document.createElement('button');
      newBtn.className = 'btn btn-xs btn-outline-' + (d.is_approved ? 'warning btn-unapprove' : 'success btn-approve');
      newBtn.dataset.id = id;
      newBtn.title = d.is_approved ? 'Unapprove' : 'Approve';
      newBtn.innerHTML = '<i class="bi bi-' + (d.is_approved ? 'x-lg' : 'check-lg') + '"></i>';
      btn.replaceWith(newBtn);
      LMS.toast('success', d.is_approved ? 'Review approved.' : 'Review unapproved.');
    } else LMS.toast('error', d.message);
  });
});
</script>

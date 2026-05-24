<?php
use App\Core\View;
use App\Middleware\CsrfMiddleware;
$e    = fn(mixed $v): string => View::e($v);
$url  = fn(string $p = ''): string => View::url($p);
$asset= fn(string $p): string => View::asset($p);

$statusColors = ['draft'=>'secondary','published'=>'success','archived'=>'warning'];
$levelColors  = ['beginner'=>'info','intermediate'=>'primary','advanced'=>'danger'];
?>

<!-- Status filter + stats row -->
<div class="row g-3 mb-4">
  <?php foreach (['draft'=>'Draft','published'=>'Published','archived'=>'Archived'] as $st => $lbl): ?>
  <div class="col-6 col-lg-4">
    <a href="<?= $url('admin/courses?status=' . $st . '&search=' . urlencode($search)) ?>"
       class="stat-card text-decoration-none <?= $statusFilter === $st ? 'ring-active' : '' ?>">
      <div class="stat-icon text-<?= $statusColors[$st] ?>">
        <i class="bi <?= $st==='published'?'bi-check-circle':($st==='draft'?'bi-file-earmark':'bi-archive') ?>"></i>
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
      <div class="col-12 col-md-4">
        <form method="GET" action="<?= $url('admin/courses') ?>" id="searchForm">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" class="form-control border-start-0 ps-0" name="search"
                   placeholder="Search courses…" value="<?= $e($search) ?>" id="courseSearch">
            <input type="hidden" name="status"   value="<?= $e($statusFilter) ?>">
            <input type="hidden" name="category" value="<?= $e($categoryId) ?>">
          </div>
        </form>
      </div>
      <div class="col-12 col-md-3">
        <select class="form-select form-select-sm" id="catFilter" onchange="applyFilters()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (int)$categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
            <?= $e($cat['label']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-5 d-flex gap-2 justify-content-md-end flex-wrap">
        <!-- Import -->
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
          <i class="bi bi-upload me-1"></i> Import
        </button>
        <!-- Manage categories -->
        <a href="<?= $url('admin/courses/categories') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-tags me-1"></i> Categories
        </a>
        <a href="<?= $url('admin/courses/create') ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-circle me-1"></i> New Course
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Courses table -->
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
    <h5 class="mb-0"><i class="bi bi-book me-2"></i> Courses
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <small class="text-muted">
      <?= number_format(($page - 1) * $perPage + 1) ?>–<?= number_format(min($page * $perPage, $total)) ?> of <?= number_format($total) ?>
    </small>
  </div>

  <?php if (empty($rows)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-book"></i></div>
      <h6 class="mt-3 text-muted">No courses found</h6>
      <?php if ($search || $statusFilter): ?>
        <a href="<?= $url('admin/courses') ?>" class="btn btn-sm btn-outline-primary mt-2">Clear filters</a>
      <?php else: ?>
        <a href="<?= $url('admin/courses/create') ?>" class="btn btn-sm btn-primary mt-2">
          <i class="bi bi-plus-circle me-1"></i> Create your first course
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead>
        <tr>
          <th style="width:60px"></th>
          <th>Title</th>
          <th>Category</th>
          <th>Level</th>
          <th>Status</th>
          <th>Sections</th>
          <th>Enrolled</th>
          <th>Rating</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $c): ?>
        <tr>
          <!-- Thumbnail -->
          <td>
            <?php if ($c['thumbnail']): ?>
              <img src="<?= $e(APP_URL . '/storage/uploads/' . $c['thumbnail']) ?>"
                   alt="" style="width:50px;height:36px;object-fit:cover;border-radius:6px">
            <?php else: ?>
              <div style="width:50px;height:36px;background:var(--content-bg);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--border-color)">
                <i class="bi bi-image"></i>
              </div>
            <?php endif; ?>
          </td>

          <!-- Title -->
          <td>
            <div class="fw-semibold" style="font-size:13.5px"><?= $e($c['title']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= $e($c['language']) ?><?= $c['is_rtl'] ? ' · <abbr title="Right-to-left">RTL</abbr>' : '' ?></div>
          </td>

          <!-- Category -->
          <td class="text-muted" style="font-size:13px"><?= $e($c['category_name'] ?? '—') ?></td>

          <!-- Level -->
          <td>
            <span class="badge bg-<?= $levelColors[$c['level']] ?? 'secondary' ?>-subtle text-<?= $levelColors[$c['level']] ?? 'secondary' ?>" style="font-size:11px;border-radius:20px;padding:4px 8px">
              <?= ucfirst($e($c['level'])) ?>
            </span>
          </td>

          <!-- Status with quick toggle -->
          <td>
            <div class="dropdown">
              <button class="badge border-0 bg-<?= $statusColors[$c['status']] ?? 'secondary' ?> dropdown-toggle"
                      style="cursor:pointer;font-size:11.5px;padding:5px 8px"
                      data-bs-toggle="dropdown">
                <?= ucfirst($e($c['status'])) ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-sm shadow-sm" style="min-width:130px;font-size:13px">
                <?php foreach (['draft'=>'Draft','published'=>'Published','archived'=>'Archived'] as $st=>$lbl): ?>
                <?php if ($st !== $c['status']): ?>
                <li>
                  <button class="dropdown-item btn-change-status"
                          data-uuid="<?= $e($c['uuid']) ?>"
                          data-status="<?= $st ?>">
                    <?= $lbl ?>
                  </button>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            </div>
          </td>

          <!-- Sections/Lessons -->
          <td style="font-size:13px">
            <?= $c['section_count'] ?> sections · <?= $c['lesson_count'] ?> lessons
          </td>

          <!-- Enrolled -->
          <td style="font-size:13px"><?= number_format($c['enrollment_count']) ?></td>

          <!-- Rating -->
          <td style="font-size:13px">
            <?php if ($c['avg_rating']): ?>
              <span class="text-warning">★</span> <?= $c['avg_rating'] ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>

          <!-- Actions -->
          <td class="text-end">
            <div class="d-flex gap-1 justify-content-end">
              <a href="<?= $url('admin/courses/' . $c['uuid'] . '/edit') ?>"
                 class="btn btn-sm btn-outline-primary" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <a href="<?= $url('admin/courses/' . $c['uuid'] . '/preview') ?>"
                 class="btn btn-sm btn-outline-secondary" title="Preview">
                <i class="bi bi-eye"></i>
              </a>
              <a href="<?= $url('admin/courses/' . $c['uuid'] . '/export') ?>"
                 class="btn btn-sm btn-outline-secondary" title="Export JSON">
                <i class="bi bi-download"></i>
              </a>
              <button class="btn btn-sm btn-outline-danger btn-delete-course"
                      title="Delete"
                      data-uuid="<?= $e($c['uuid']) ?>"
                      data-title="<?= $e($c['title']) ?>">
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
      <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><i class="bi bi-chevron-left"></i></a></li>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><i class="bi bi-chevron-right"></i></a></li>
      <?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Import modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?= $url('admin/courses/import') ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold"><i class="bi bi-upload me-2"></i>Import Course</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body px-4">
          <p class="text-muted small">Upload a course JSON file exported from LMSAdvisor. The course will be imported as a draft.</p>
          <input type="file" class="form-control" name="import_file" accept=".json" required>
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
.bg-info-subtle{background:#e0f7fa!important}.bg-primary-subtle{background:#ebf2ff!important}.bg-danger-subtle{background:#fde8e8!important}
.ring-active{box-shadow:0 0 0 2px var(--primary)!important}
</style>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';

// Live search
let st;
document.getElementById('courseSearch').addEventListener('input', function () {
  clearTimeout(st);
  st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
});

function applyFilters() {
  const cat    = document.getElementById('catFilter').value;
  const search = document.getElementById('courseSearch').value;
  const status = '<?= $e($statusFilter) ?>';
  window.location.href = BASE + '/admin/courses?category=' + cat + '&search=' + encodeURIComponent(search) + '&status=' + status;
}

// Delete
document.querySelectorAll('.btn-delete-course').forEach(btn => {
  btn.addEventListener('click', function () {
    const uuid  = this.dataset.uuid;
    const title = this.dataset.title;
    LMS.confirm('Delete course "' + title + '"? All sections, lessons, and enrollments will be permanently removed.', function () {
      fetch(BASE + '/admin/courses/' + uuid + '/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) { LMS.toast('success', 'Course deleted.'); window.location.reload(); }
        else LMS.toast('error', d.message || 'Delete failed.');
      });
    });
  });
});

// Status change
document.querySelectorAll('.btn-change-status').forEach(btn => {
  btn.addEventListener('click', function () {
    const uuid   = this.dataset.uuid;
    const status = this.dataset.status;
    fetch(BASE + '/admin/courses/' + uuid + '/toggle-status', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&status=' + encodeURIComponent(status),
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { LMS.toast('success', 'Status updated to ' + d.status + '.'); window.location.reload(); }
      else LMS.toast('error', d.message);
    });
  });
});
</script>

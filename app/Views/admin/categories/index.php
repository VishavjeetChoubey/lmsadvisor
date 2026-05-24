<?php
use App\Core\View;
$e    = fn(mixed $v): string => View::e($v);
$url  = fn(string $p = ''): string => View::url($p);
?>
<div class="row g-4">

  <!-- Add Category -->
  <div class="col-12 col-lg-4">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i> Add Category</h5>
      </div>
      <div class="card-body p-4">
        <div class="mb-3">
          <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="newCatName" placeholder="e.g. Web Development" maxlength="120">
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">Parent Category <small class="text-muted fw-normal">(optional)</small></label>
          <select class="form-select" id="newCatParent">
            <option value="">— None (top-level) —</option>
            <?php foreach ($roots as $r): ?>
            <option value="<?= $r['id'] ?>"><?= $e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary w-100" id="addCatBtn">
          <i class="bi bi-plus-circle me-1"></i> Add Category
        </button>
      </div>
    </div>
  </div>

  <!-- Category list -->
  <div class="col-12 col-lg-8">
    <div class="card lms-card">
      <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-tags me-2"></i> All Categories</h5>
        <span class="badge bg-secondary"><?= count($categories) ?></span>
      </div>
      <div id="catTableWrap">
        <?php if (empty($categories)): ?>
          <div class="card-body text-center py-5 text-muted">No categories yet. Add one to get started.</div>
        <?php else: ?>
        <table class="table lms-table mb-0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Slug</th>
              <th>Parent</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="catTableBody">
            <?php foreach ($categories as $cat): ?>
            <tr id="cat-row-<?= $cat['id'] ?>">
              <td class="fw-semibold"><?= $e($cat['name']) ?></td>
              <td><code style="font-size:12px"><?= $e($cat['slug']) ?></code></td>
              <td class="text-muted"><?= $e($cat['parent_name'] ?? '—') ?></td>
              <td class="text-end">
                <div class="d-flex gap-1 justify-content-end">
                  <button class="btn btn-sm btn-outline-primary btn-edit-cat"
                          data-id="<?= $cat['id'] ?>"
                          data-name="<?= $e($cat['name']) ?>"
                          data-parent="<?= (int)$cat['parent_id'] ?>">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger btn-delete-cat"
                          data-id="<?= $cat['id'] ?>"
                          data-name="<?= $e($cat['name']) ?>">
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit modal -->
<div class="modal fade" id="editCatModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Edit Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <input type="hidden" id="editCatId">
        <div class="mb-3">
          <label class="form-label fw-semibold">Name</label>
          <input type="text" class="form-control" id="editCatName" maxlength="120">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Parent</label>
          <select class="form-select" id="editCatParent">
            <option value="">— None —</option>
            <?php foreach ($roots as $r): ?>
            <option value="<?= $r['id'] ?>"><?= $e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveCatEdit">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">

<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';

function reloadPage() { window.location.reload(); }

// ── Add ───────────────────────────────────────────────────────────────────────
document.getElementById('addCatBtn').addEventListener('click', function () {
  const name   = document.getElementById('newCatName').value.trim();
  const parent = document.getElementById('newCatParent').value;
  if (!name) { LMS.toast('error', 'Category name is required.'); return; }

  fetch(BASE + '/admin/courses/categories', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) +
          '&name=' + encodeURIComponent(name) +
          '&parent_id=' + encodeURIComponent(parent),
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) { LMS.toast('success', 'Category added.'); reloadPage(); }
    else LMS.toast('error', d.message);
  });
});

// ── Edit ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-edit-cat').forEach(btn => {
  btn.addEventListener('click', function () {
    document.getElementById('editCatId').value    = this.dataset.id;
    document.getElementById('editCatName').value  = this.dataset.name;
    document.getElementById('editCatParent').value= this.dataset.parent;
    new bootstrap.Modal(document.getElementById('editCatModal')).show();
  });
});

document.getElementById('saveCatEdit').addEventListener('click', function () {
  const id     = document.getElementById('editCatId').value;
  const name   = document.getElementById('editCatName').value.trim();
  const parent = document.getElementById('editCatParent').value;

  fetch(BASE + '/admin/courses/categories/' + id, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) +
          '&name=' + encodeURIComponent(name) +
          '&parent_id=' + encodeURIComponent(parent),
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) { LMS.toast('success', 'Category updated.'); reloadPage(); }
    else LMS.toast('error', d.message);
  });
});

// ── Delete ────────────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-cat').forEach(btn => {
  btn.addEventListener('click', function () {
    const id   = this.dataset.id;
    const name = this.dataset.name;
    LMS.confirm('Delete category "' + name + '"?', function () {
      fetch(BASE + '/admin/courses/categories/' + id + '/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('cat-row-' + id)?.remove();
          LMS.toast('success', 'Category deleted.');
        } else LMS.toast('error', d.message);
      });
    });
  });
});
</script>

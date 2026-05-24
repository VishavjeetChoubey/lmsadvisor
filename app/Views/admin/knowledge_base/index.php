<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div class="d-flex gap-2 flex-wrap">
    <form method="GET" class="d-flex gap-2">
      <input type="text" class="form-control form-control-sm" name="search"
             value="<?= $e($search) ?>" placeholder="Search articles…" style="width:220px">
      <select class="form-select form-select-sm" name="category" style="width:160px">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $catFilter === $cat['id'] ? 'selected' : '' ?>><?= $e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-outline-secondary">Filter</button>
    </form>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newCatModal">
      <i class="bi bi-folder-plus me-1"></i> Category
    </button>
    <a href="<?= $url('admin/knowledge-base/create') ?>" class="btn btn-sm btn-primary">
      <i class="bi bi-plus-circle me-1"></i> New Article
    </a>
  </div>
</div>

<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-book me-2"></i>Articles
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
  </div>
  <?php if (empty($articles)): ?>
  <div class="card-body text-center py-5">
    <i class="bi bi-book" style="font-size:3rem;opacity:.2"></i>
    <h6 class="mt-3 text-muted">No articles yet</h6>
    <a href="<?= $url('admin/knowledge-base/create') ?>" class="btn btn-primary mt-2">
      <i class="bi bi-plus-circle me-1"></i> Create First Article
    </a>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead><tr>
        <th>Title</th><th>Category</th><th>Status</th><th>Views</th><th>Author</th><th>Updated</th><th></th>
      </tr></thead>
      <tbody>
        <?php foreach ($articles as $a): ?>
        <tr>
          <td>
            <a href="<?= $url('admin/knowledge-base/' . $a['uuid'] . '/edit') ?>" class="fw-semibold text-decoration-none">
              <?= $e(mb_strimwidth($a['title'],0,60,'…')) ?>
            </a>
            <div class="text-muted" style="font-size:11.5px;font-family:monospace">/<?= $e($a['slug']) ?></div>
          </td>
          <td class="text-muted" style="font-size:13px"><?= $e($a['cat_name'] ?? '—') ?></td>
          <td>
            <span class="badge bg-<?= $a['status']==='published'?'success':'secondary' ?>">
              <?= ucfirst($e($a['status'])) ?>
            </span>
          </td>
          <td class="text-muted"><?= number_format((int)$a['views']) ?></td>
          <td class="text-muted" style="font-size:12.5px"><?= $e($a['first_name'].' '.$a['last_name']) ?></td>
          <td class="text-muted" style="font-size:12px"><?= date('d M Y', strtotime($a['updated_at'])) ?></td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= $url('admin/knowledge-base/' . $a['uuid'] . '/edit') ?>" class="btn btn-xs btn-outline-primary">
                <i class="bi bi-pencil"></i>
              </a>
              <button class="btn btn-xs btn-outline-danger btn-delete-kb"
                      data-uuid="<?= $e($a['uuid']) ?>" data-title="<?= $e($a['title']) ?>">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center py-3 px-4">
    <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
    <nav><ul class="pagination pagination-sm mb-0">
      <?php for ($p = max(1,$page-2); $p<=min($totalPages,$page+2); $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?=$p?>&search=<?=urlencode($search)?>&category=<?=$catFilter?>"><?=$p?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- New Category Modal -->
<div class="modal fade" id="newCatModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0"><h5 class="modal-title fw-semibold">New Category</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4">
        <input type="text" class="form-control" id="newCatName" placeholder="Category name…">
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveCat">Save Category</button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF = '<?= $e($csrf_token) ?>';
const BASE = '<?= rtrim(APP_URL,'/') ?>';

document.getElementById('saveCat')?.addEventListener('click', function() {
  const name = document.getElementById('newCatName').value.trim();
  if (!name) return;
  fetch(BASE+'/admin/knowledge-base/categories',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token='+encodeURIComponent(CSRF)+'&name='+encodeURIComponent(name)
  }).then(r=>r.json()).then(d=>{if(d.success){LMS.toast('success','Category created.');location.reload();}else LMS.toast('error',d.message||'Failed.');});
});

document.querySelectorAll('.btn-delete-kb').forEach(btn=>{
  btn.addEventListener('click',function(){
    const uuid=this.dataset.uuid, title=this.dataset.title;
    LMS.confirm('Delete article "'+title+'"?',()=>{
      fetch(BASE+'/admin/knowledge-base/'+uuid+'/delete',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'csrf_token='+encodeURIComponent(CSRF)
      }).then(r=>r.json()).then(d=>{if(d.success){LMS.toast('success','Deleted.');location.reload();}else LMS.toast('error',d.message);});
    });
  });
});
</script>

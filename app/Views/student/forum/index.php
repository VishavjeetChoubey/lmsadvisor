<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>
<!-- Back to course + header -->
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
  <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn') ?>"
     class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to Course
  </a>
  <div class="flex-grow-1">
    <h4 class="fw-bold mb-0"><?= $e($course['title']) ?></h4>
    <p class="text-muted mb-0" style="font-size:13px">Discussion Forum · <?= number_format($total) ?> thread<?= $total !== 1 ? 's' : '' ?></p>
  </div>
  <?php if ($enrollment): ?>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newThreadModal">
    <i class="bi bi-plus-circle me-1"></i> New Thread
  </button>
  <?php endif; ?>
</div>

<!-- Search -->
<div class="mb-4">
  <form method="GET" action="<?= $url('learn/courses/' . $course['uuid'] . '/forum') ?>">
    <div class="input-group" style="max-width:420px">
      <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
      <input type="text" class="form-control border-start-0 ps-0" name="search"
             placeholder="Search threads…" value="<?= $e($search) ?>">
    </div>
  </form>
</div>

<!-- Thread list -->
<div class="card lms-card">
  <?php if (empty($rows)): ?>
    <div class="card-body text-center py-5">
      <i class="bi bi-chat-square" style="font-size:3rem;color:var(--border-color)"></i>
      <h6 class="mt-3 text-muted">No threads yet</h6>
      <?php if ($enrollment): ?>
        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#newThreadModal">
          <i class="bi bi-plus-circle me-1"></i> Start the first thread
        </button>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="list-group list-group-flush">
    <?php foreach ($rows as $t): ?>
    <a href="<?= $url('learn/courses/' . $course['uuid'] . '/forum/threads/' . $t['id']) ?>"
       class="list-group-item list-group-item-action py-3 px-4 text-decoration-none">
      <div class="d-flex align-items-start gap-3">
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:14px">
          <?= strtoupper(substr($t['first_name'], 0, 1)) ?>
        </div>
        <div class="flex-grow-1 min-width-0">
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <?php if ($t['is_pinned']): ?>
              <span class="badge bg-warning text-dark" style="font-size:10px"><i class="bi bi-pin-angle me-1"></i>Pinned</span>
            <?php endif; ?>
            <?php if ($t['is_locked']): ?>
              <span class="badge bg-danger" style="font-size:10px"><i class="bi bi-lock me-1"></i>Locked</span>
            <?php endif; ?>
            <span class="fw-semibold" style="font-size:14.5px;color:var(--text-primary)"><?= $e($t['title']) ?></span>
          </div>
          <div class="text-muted" style="font-size:12.5px">
            By <?= $e($t['first_name'] . ' ' . $t['last_name']) ?>
            · <?= date('d M Y', strtotime($t['created_at'])) ?>
          </div>
          <div class="text-muted mt-1" style="font-size:12px">
            <?= $e(mb_strimwidth(strip_tags($t['body']), 0, 100, '…')) ?>
          </div>
        </div>
        <div class="text-center flex-shrink-0" style="min-width:48px">
          <div class="fw-bold" style="font-size:17px;color:var(--primary)"><?= (int)$t['reply_count'] ?></div>
          <div style="font-size:11px;color:var(--text-muted)">replies</div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center py-3 px-4">
    <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
    <nav><ul class="pagination pagination-sm mb-0">
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?=$p?>&search=<?=urlencode($search)?>"><?=$p?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- New Thread Modal -->
<?php if ($enrollment): ?>
<div class="modal fade" id="newThreadModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>New Thread</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <div class="mb-3">
          <label class="form-label fw-semibold">Title</label>
          <input type="text" class="form-control" id="threadTitle" maxlength="255" placeholder="Thread title…">
        </div>
        <div>
          <label class="form-label fw-semibold">Body</label>
          <textarea class="form-control" id="threadBody" rows="5" placeholder="Describe your question or discussion topic…"></textarea>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="submitThread">
          <i class="bi bi-send me-1"></i> Post Thread
        </button>
      </div>
    </div>
  </div>
</div>
<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';
document.getElementById('submitThread')?.addEventListener('click', function() {
  const title = document.getElementById('threadTitle').value.trim();
  const body  = document.getElementById('threadBody').value.trim();
  if (!title) { LMS.toast('error','Thread title is required.'); return; }
  if (!body)  { LMS.toast('error','Thread body is required.'); return; }
  this.disabled = true;
  fetch(BASE + '/learn/courses/<?= $e($course['uuid']) ?>/forum/threads', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token='+encodeURIComponent(CSRF)+'&title='+encodeURIComponent(title)+'&body='+encodeURIComponent(body),
  }).then(r=>r.json()).then(d => {
    if (d.success) { LMS.toast('success','Thread posted!'); setTimeout(()=>location.reload(),600); }
    else { LMS.toast('error',d.message); this.disabled=false; }
  });
});
</script>
<?php endif; ?>

<?php
use App\Core\View;
use App\Services\AuthService;
$e        = fn(mixed $v): string => View::e($v);
$url      = fn(string $p = ''): string => View::url($p);
$authUser = AuthService::user();
?>
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
  <a href="<?= $url('learn/courses/' . $course['uuid'] . '/forum') ?>"
     class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to Forum
  </a>
  <div>
    <h4 class="fw-bold mb-0"><?= $e($thread['title']) ?></h4>
    <p class="text-muted mb-0" style="font-size:12.5px">
      <?= $e($thread['first_name'] . ' ' . $thread['last_name']) ?>
      · <?= date('d M Y H:i', strtotime($thread['created_at'])) ?>
      <?php if ($thread['is_pinned']): ?> · <span class="badge bg-warning text-dark">Pinned</span><?php endif; ?>
      <?php if ($thread['is_locked']): ?> · <span class="badge bg-danger">Locked</span><?php endif; ?>
    </p>
  </div>
</div>

<!-- Original post -->
<div class="card lms-card mb-4">
  <div class="card-body p-4">
    <div class="d-flex gap-3">
      <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700">
        <?= strtoupper(substr($thread['first_name'], 0, 1)) ?>
      </div>
      <div class="flex-grow-1">
        <div class="fw-semibold mb-1"><?= $e($thread['first_name'] . ' ' . $thread['last_name']) ?></div>
        <div style="font-size:14.5px;line-height:1.75;color:var(--text-primary)">
          <?= nl2br($e($thread['body'])) ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Replies -->
<h6 class="fw-semibold mb-3"><i class="bi bi-reply-all me-2"></i><?= count($replies) ?> Replies</h6>

<div id="repliesList">
  <?php foreach ($replies as $r):
    $isMe = (int)$r['user_id'] === (int)($authUser['id'] ?? 0);
  ?>
  <div class="card lms-card mb-3 <?= $r['is_solution'] ? 'border-success' : '' ?>">
    <div class="card-body p-4">
      <div class="d-flex gap-3 align-items-start">
        <div style="width:38px;height:38px;border-radius:50%;background:<?= $isMe ? '#6366f1' : '#0f172a'?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:13px">
          <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="fw-semibold" style="font-size:13.5px"><?= $e($r['first_name'] . ' ' . $r['last_name']) ?></span>
            <?php if ($isMe): ?><span class="badge bg-primary" style="font-size:10px">You</span><?php endif; ?>
            <?php if ($r['is_solution']): ?><span class="badge bg-success" style="font-size:10px"><i class="bi bi-check-circle me-1"></i>Solution</span><?php endif; ?>
            <span class="text-muted ms-auto" style="font-size:12px"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></span>
          </div>
          <div style="font-size:14px;line-height:1.7;color:var(--text-primary)">
            <?= nl2br($e($r['body'])) ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Reply form -->
<?php if ($enrollment && !$thread['is_locked']): ?>
<div class="card lms-card mt-4">
  <div class="card-header lms-card-header"><h6 class="mb-0"><i class="bi bi-reply me-2"></i>Post a Reply</h6></div>
  <div class="card-body p-4">
    <textarea class="form-control mb-3" id="replyBody" rows="4" placeholder="Write your reply…"></textarea>
    <button class="btn btn-primary" id="submitReply">
      <i class="bi bi-send me-1"></i> Post Reply
    </button>
  </div>
</div>
<?php elseif ($thread['is_locked']): ?>
<div class="alert alert-warning mt-4"><i class="bi bi-lock me-2"></i>This thread is locked.</div>
<?php endif; ?>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';
document.getElementById('submitReply')?.addEventListener('click', function() {
  const body = document.getElementById('replyBody').value.trim();
  if (!body) { LMS.toast('error','Reply cannot be empty.'); return; }
  this.disabled = true;
  fetch(BASE + '/learn/courses/<?= $e($course['uuid']) ?>/forum/threads/<?= $thread['id'] ?>/reply', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token='+encodeURIComponent(CSRF)+'&body='+encodeURIComponent(body),
  }).then(r=>r.json()).then(d => {
    if (d.success) {
      document.getElementById('replyBody').value = '';
      const html = `<div class="card lms-card mb-3"><div class="card-body p-4"><div class="d-flex gap-3">
        <div style="width:38px;height:38px;border-radius:50%;background:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:13px">${d.name.charAt(0).toUpperCase()}</div>
        <div class="flex-grow-1"><div class="d-flex gap-2 mb-1"><span class="fw-semibold" style="font-size:13.5px">${d.name}</span><span class="badge bg-primary" style="font-size:10px">You</span><span class="text-muted ms-auto" style="font-size:12px">Just now</span></div>
        <div style="font-size:14px;line-height:1.7">${d.body.replace(/\n/g,'<br>')}</div></div></div></div></div>`;
      document.getElementById('repliesList').insertAdjacentHTML('beforeend', html);
      LMS.toast('success','Reply posted!');
    } else LMS.toast('error', d.message);
  }).finally(() => { this.disabled = false; });
});
</script>

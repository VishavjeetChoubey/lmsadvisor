<?php
use App\Core\View;
use App\Services\AuthService;
$e        = fn(mixed $v): string => View::e($v);
$url      = fn(string $p = ''): string => View::url($p);
$authUser = AuthService::user();

$roleColors = ['super_admin'=>'warning','admin'=>'primary','manager'=>'info','student'=>'success'];
$roleIcons  = ['super_admin'=>'fa-crown','admin'=>'fa-shield-alt','manager'=>'fa-briefcase','student'=>'fa-graduation-cap'];
?>

<!-- Thread header card -->
<div class="card lms-card mb-4">
  <div class="card-body p-4">
    <div class="d-flex align-items-start gap-3 flex-wrap">
      <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
          <?php if ($thread['is_pinned']): ?>
            <span class="badge bg-warning text-dark"><i class="bi bi-pin-angle me-1"></i>Pinned</span>
          <?php endif; ?>
          <?php if ($thread['is_locked']): ?>
            <span class="badge bg-danger"><i class="bi bi-lock me-1"></i>Locked</span>
          <?php endif; ?>
          <a href="<?= $url('admin/courses/' . $thread['course_uuid'] . '/edit') ?>"
             class="badge bg-primary text-decoration-none">
            <?= $e($thread['course_title']) ?>
          </a>
        </div>
        <h2 class="fw-bold mb-2" style="font-size:1.4rem"><?= $e($thread['title']) ?></h2>
        <div class="text-muted" style="font-size:13px">
          <i class="bi bi-person-circle me-1"></i>
          <?= $e($thread['first_name'] . ' ' . $thread['last_name']) ?>
          &nbsp;·&nbsp; <?= date('d M Y H:i', strtotime($thread['created_at'])) ?>
          &nbsp;·&nbsp; <span class="badge bg-secondary"><?= (int)$thread['reply_count'] ?> replies</span>
        </div>
      </div>

      <!-- Moderation actions -->
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-sm btn-outline-warning" id="btnPin"
                data-id="<?= $thread['id'] ?>"
                data-pin="<?= $thread['is_pinned'] ? '0' : '1' ?>">
          <i class="bi bi-pin-angle me-1"></i><?= $thread['is_pinned'] ? 'Unpin' : 'Pin' ?>
        </button>
        <button class="btn btn-sm btn-outline-secondary" id="btnLock"
                data-id="<?= $thread['id'] ?>"
                data-lock="<?= $thread['is_locked'] ? '0' : '1' ?>">
          <i class="bi bi-<?= $thread['is_locked'] ? 'unlock' : 'lock' ?> me-1"></i>
          <?= $thread['is_locked'] ? 'Unlock' : 'Lock' ?>
        </button>
        <button class="btn btn-sm btn-outline-danger" id="btnDeleteThread"
                data-id="<?= $thread['id'] ?>">
          <i class="bi bi-trash3 me-1"></i>Delete Thread
        </button>
      </div>
    </div>

    <!-- Thread body -->
    <div class="forum-body mt-4 pt-4 border-top" style="line-height:1.7">
      <?= nl2br($e($thread['body'])) ?>
    </div>
  </div>
</div>

<!-- Replies -->
<h5 class="fw-semibold mb-3">
  <i class="bi bi-reply-all me-2"></i><?= count($replies) ?> Replies
</h5>

<?php if (empty($replies)): ?>
<div class="card lms-card mb-4">
  <div class="card-body text-center py-4 text-muted">
    <i class="bi bi-chat-square" style="font-size:2rem;opacity:.3"></i>
    <p class="mt-2 mb-0">No replies yet. Be the first to respond.</p>
  </div>
</div>
<?php else: ?>
<div id="repliesList">
  <?php foreach ($replies as $reply): ?>
  <?php
    $rName  = $reply['role_name'] ?? 'student';
    $color  = $roleColors[$rName] ?? 'secondary';
    $icon   = $roleIcons[$rName]  ?? 'fa-user';
  ?>
  <div class="card lms-card mb-3 reply-card <?= $reply['is_solution'] ? 'border-success' : '' ?>"
       id="reply-<?= $reply['id'] ?>">
    <div class="card-body p-4">
      <div class="d-flex align-items-start gap-3">
        <!-- Avatar -->
        <div class="reply-avatar text-<?= $color ?>">
          <i class="fas <?= $icon ?>"></i>
        </div>
        <!-- Content -->
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="fw-semibold" style="font-size:13.5px">
              <?= $e($reply['first_name'] . ' ' . $reply['last_name']) ?>
            </span>
            <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>" style="font-size:11px;border-radius:20px;padding:3px 8px">
              <?= ucfirst(str_replace('_',' ',$rName)) ?>
            </span>
            <?php if ($reply['is_solution']): ?>
              <span class="badge bg-success ms-1"><i class="bi bi-check-circle me-1"></i>Solution</span>
            <?php endif; ?>
            <span class="text-muted ms-auto" style="font-size:12px">
              <?= date('d M Y H:i', strtotime($reply['created_at'])) ?>
            </span>
          </div>
          <div class="forum-body" style="line-height:1.7">
            <?= nl2br($e($reply['body'])) ?>
          </div>
        </div>
        <!-- Reply actions -->
        <div class="d-flex flex-column gap-1">
          <button class="btn btn-xs btn-outline-success btn-mark-solution"
                  data-id="<?= $reply['id'] ?>"
                  data-solution="<?= $reply['is_solution'] ? '0' : '1' ?>"
                  title="<?= $reply['is_solution'] ? 'Unmark solution' : 'Mark as solution' ?>">
            <i class="bi bi-check-circle<?= $reply['is_solution'] ? '-fill' : '' ?>"></i>
          </button>
          <button class="btn btn-xs btn-outline-danger btn-delete-reply"
                  data-id="<?= $reply['id'] ?>"
                  title="Delete reply">
            <i class="bi bi-trash3"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Reply form -->
<?php if (!$thread['is_locked']): ?>
<div class="card lms-card mt-4">
  <div class="card-header lms-card-header">
    <h6 class="mb-0"><i class="bi bi-reply me-2"></i>Post a Reply</h6>
  </div>
  <div class="card-body p-4">
    <textarea class="form-control mb-3" id="replyBody" rows="4"
              placeholder="Write your reply…" style="resize:vertical"></textarea>
    <button class="btn btn-primary" id="submitReply"
            data-thread-id="<?= $thread['id'] ?>">
      <i class="bi bi-send me-1"></i> Post Reply
    </button>
  </div>
</div>
<?php else: ?>
<div class="alert alert-warning mt-4">
  <i class="bi bi-lock me-2"></i>This thread is locked. No new replies can be posted.
</div>
<?php endif; ?>

<style>
.reply-avatar {
  width:36px; height:36px; border-radius:50%;
  background:var(--primary-light);
  display:flex; align-items:center; justify-content:center;
  font-size:15px; flex-shrink:0;
}
.forum-body { font-size:14px; color:var(--text-primary); }
.reply-card.border-success { border-left:4px solid #0e9f6e !important; }
.btn-xs { padding:3px 7px; font-size:12px; }
.bg-primary-subtle{background:#ebf2ff!important}
.bg-success-subtle{background:#d1fae5!important}
.bg-warning-subtle{background:#fef9c3!important}
.bg-info-subtle{background:#e0f7fa!important}
</style>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<script>
const CSRF = document.getElementById('csrfToken').value;
const BASE = '<?= rtrim(APP_URL, '/') ?>';

// ── Pin ───────────────────────────────────────────────────────────────────────
document.getElementById('btnPin')?.addEventListener('click', function () {
  const id = this.dataset.id, pin = this.dataset.pin;
  fetch(BASE + '/admin/forum/threads/' + id + '/pin', {
    method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) + '&pin=' + pin,
  }).then(r=>r.json()).then(d => {
    if (d.success) { LMS.toast('success', d.pinned ? 'Pinned.' : 'Unpinned.'); location.reload(); }
    else LMS.toast('error', d.message);
  });
});

// ── Lock ──────────────────────────────────────────────────────────────────────
document.getElementById('btnLock')?.addEventListener('click', function () {
  const id = this.dataset.id, lock = this.dataset.lock;
  fetch(BASE + '/admin/forum/threads/' + id + '/lock', {
    method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) + '&lock=' + lock,
  }).then(r=>r.json()).then(d => {
    if (d.success) { LMS.toast('success', d.locked ? 'Thread locked.' : 'Unlocked.'); location.reload(); }
    else LMS.toast('error', d.message);
  });
});

// ── Delete thread ─────────────────────────────────────────────────────────────
document.getElementById('btnDeleteThread')?.addEventListener('click', function () {
  const id = this.dataset.id;
  LMS.confirm('Delete this entire thread and all replies? This cannot be undone.', function () {
    fetch(BASE + '/admin/forum/threads/' + id + '/delete', {
      method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF),
    }).then(r=>r.json()).then(d => {
      if (d.success) { LMS.toast('success', 'Thread deleted.'); setTimeout(() => window.location.href = BASE + '/admin/forum', 800); }
      else LMS.toast('error', d.message);
    });
  });
});

// ── Delete reply ──────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-reply').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id;
    LMS.confirm('Delete this reply?', function () {
      fetch(BASE + '/admin/forum/replies/' + id + '/delete', {
        method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      }).then(r=>r.json()).then(d => {
        if (d.success) { document.getElementById('reply-' + id)?.remove(); LMS.toast('success', 'Reply deleted.'); }
        else LMS.toast('error', d.message);
      });
    });
  });
});

// ── Mark solution ─────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-mark-solution').forEach(btn => {
  btn.addEventListener('click', function () {
    const id  = this.dataset.id;
    const sol = this.dataset.solution;
    fetch(BASE + '/admin/forum/replies/' + id + '/solution', {
      method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&is_solution=' + sol,
    }).then(r=>r.json()).then(d => {
      if (d.success) { LMS.toast('success', d.is_solution ? 'Marked as solution.' : 'Unmarked.'); location.reload(); }
      else LMS.toast('error', d.message);
    });
  });
});

// ── Post reply ────────────────────────────────────────────────────────────────
document.getElementById('submitReply')?.addEventListener('click', function () {
  const threadId = this.dataset.threadId;
  const body     = document.getElementById('replyBody').value.trim();
  if (!body) { LMS.toast('error', 'Reply cannot be empty.'); return; }

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Posting…';

  fetch(BASE + '/admin/forum/threads/' + threadId + '/reply', {
    method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) + '&body=' + encodeURIComponent(body),
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      document.getElementById('replyBody').value = '';
      LMS.toast('success', 'Reply posted.');
      // Append reply to list without page reload
      const r    = d.reply;
      const html = `<div class="card lms-card mb-3 reply-card" id="reply-${r.id}">
        <div class="card-body p-4">
          <div class="d-flex align-items-start gap-3">
            <div class="reply-avatar text-primary"><i class="fas fa-shield-alt"></i></div>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-semibold" style="font-size:13.5px">${r.first_name}</span>
                <span class="badge bg-primary-subtle text-primary" style="font-size:11px;border-radius:20px;padding:3px 8px">Staff</span>
                <span class="text-muted ms-auto" style="font-size:12px">Just now</span>
              </div>
              <div class="forum-body" style="line-height:1.7">${r.body.replace(/\n/g,'<br>')}</div>
            </div>
            <div class="d-flex flex-column gap-1">
              <button class="btn btn-xs btn-outline-danger btn-delete-reply" data-id="${r.id}"><i class="bi bi-trash3"></i></button>
            </div>
          </div>
        </div></div>`;

      const list = document.getElementById('repliesList');
      if (list) list.insertAdjacentHTML('beforeend', html);
      else document.getElementById('submitReply').closest('.card').insertAdjacentHTML('beforebegin',
        '<div id="repliesList">' + html + '</div>');

      // Re-bind delete button on new reply
      document.querySelector('#reply-' + r.id + ' .btn-delete-reply')
        ?.addEventListener('click', function () {
          LMS.confirm('Delete this reply?', () => {
            fetch(BASE + '/admin/forum/replies/' + r.id + '/delete', {
              method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
              body: 'csrf_token=' + encodeURIComponent(CSRF),
            }).then(res=>res.json()).then(dd => {
              if (dd.success) { document.getElementById('reply-' + r.id)?.remove(); LMS.toast('success','Reply deleted.'); }
            });
          });
        });
    } else LMS.toast('error', d.message);
  })
  .finally(() => {
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-send me-1"></i> Post Reply';
  });
});
</script>

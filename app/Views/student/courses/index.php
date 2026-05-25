<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$levelColors  = ['beginner'=>'info','intermediate'=>'primary','advanced'=>'danger'];
$statusColors = ['active'=>'success','completed'=>'primary','suspended'=>'warning','expired'=>'danger'];
?>

<?php if (empty($enrolled)): ?>
<!-- Empty state -->
<div class="text-center py-5 mt-4">
  <div style="width:100px;height:100px;border-radius:24px;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 32px rgba(99,102,241,.3)">
    <i class="bi bi-mortarboard" style="font-size:3rem;color:#fff"></i>
  </div>
  <h4 class="fw-bold mb-2" style="color:var(--text-primary)">No Courses Yet</h4>
  <p style="color:var(--text-muted);max-width:340px;margin:0 auto">
    You haven't been enrolled in any courses. Contact your administrator to get started.
  </p>
</div>

<?php else: ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0" style="color:var(--text-primary)">My Courses</h4>
    <p style="color:var(--text-muted);font-size:13.5px;margin-top:2px">
      <?= count($enrolled) ?> course<?= count($enrolled) !== 1 ? 's' : '' ?> enrolled
    </p>
  </div>
  <!-- Filter pills -->
  <div class="d-flex gap-2 flex-wrap">
    <button class="filter-pill active" data-filter="all">All</button>
    <button class="filter-pill" data-filter="active">In Progress</button>
    <button class="filter-pill" data-filter="completed">Completed</button>
  </div>
</div>

<!-- Course grid -->
<div class="row g-4" id="courseGrid">
  <?php foreach ($enrolled as $e2):
    $pct      = (int)($e2['progress_pct'] ?? 0);
    $isDone   = $e2['status'] === 'completed';
    $isSusp   = in_array($e2['status'], ['suspended','expired']);
    $btnLabel = $isDone ? 'View Course' : ($pct > 0 ? 'Resume' : 'Start Course');
    $btnIcon  = $isDone ? 'bi-award' : 'bi-play-fill';
  ?>
  <div class="col-12 col-md-6 col-xl-4 course-item" data-status="<?= $e($e2['status']) ?>">
    <?php $detailUrl = $url('learn/courses/' . $e2['course_uuid']); ?>
    <div class="sc-card" style="<?= $isSusp ? 'opacity:.65;pointer-events:none' : '' ?>">

      <!-- Thumbnail — clicking goes to course detail -->
      <a href="<?= $detailUrl ?>" class="sc-thumb text-decoration-none d-block">
        <?php if ($e2['thumbnail']): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $e2['thumbnail']) ?>"
               alt="<?= $e($e2['title']) ?>">
        <?php else: ?>
          <div class="sc-thumb-placeholder">
            <i class="bi bi-book-half"></i>
          </div>
        <?php endif; ?>

        <!-- Progress overlay badge -->
        <div class="sc-progress-badge <?= $isDone ? 'done' : '' ?>">
          <?php if ($isDone): ?>
            <i class="bi bi-check-circle-fill me-1"></i> Completed
          <?php elseif ($pct > 0): ?>
            <?= $pct ?>% done
          <?php else: ?>
            Not started
          <?php endif; ?>
        </div>
      </a><!-- /sc-thumb link -->

      <!-- Body -->
      <div class="sc-body">
        <!-- Badges -->
        <div class="sc-badges">
          <span class="sc-badge sc-badge-<?= $statusColors[$e2['status']] ?? 'secondary' ?>">
            <?= ucfirst($e($e2['status'])) ?>
          </span>
          <span class="sc-badge sc-badge-level">
            <?= ucfirst($e($e2['level'])) ?>
          </span>
          <?php if ($e2['category_name']): ?>
            <span class="sc-badge sc-badge-cat">
              <i class="bi bi-tag me-1"></i><?= $e($e2['category_name']) ?>
            </span>
          <?php endif; ?>
        </div>

        <!-- Title — clickable to detail page -->
        <a href="<?= $detailUrl ?>" class="text-decoration-none">
          <h5 class="sc-title" style="color:var(--text-primary)"><?= $e($e2['title']) ?></h5>
        </a>

        <!-- Meta -->
        <div class="sc-meta">
          <?php if ($e2['duration_hours']): ?>
            <span><i class="bi bi-clock me-1"></i><?= $e($e2['duration_hours']) ?>h total</span>
          <?php endif; ?>
          <span><i class="bi bi-calendar3 me-1"></i>Enrolled <?= date('d M Y', strtotime($e2['enrolled_at'])) ?></span>
        </div>

        <!-- Progress bar -->
        <div class="sc-progress-wrap">
          <div class="sc-progress-bar" style="width:<?= $pct ?>%;background:<?= $isDone ? '#0e9f6e' : '#6366f1' ?>"></div>
        </div>
        <div class="sc-progress-label">
          <?php if ($isDone): ?>
            <span style="color:#0e9f6e;font-weight:600"><i class="bi bi-check-circle me-1"></i>Course complete!</span>
          <?php else: ?>
            <span><?= $pct ?>% complete</span>
            <?php if ($e2['expires_at']): ?>
              <span class="<?= strtotime($e2['expires_at']) < strtotime('+7 days') ? 'text-danger fw-semibold' : '' ?>">
                Expires <?= date('d M Y', strtotime($e2['expires_at'])) ?>
              </span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Footer — START BUTTON -->
      <div class="sc-footer">
        <?php if ($isDone && $e2['certificate_enabled']): ?>
          <a href="<?= $url('learn/certificate/' . $e2['id']) ?>"
             class="sc-btn-secondary" title="View Certificate">
            <i class="bi bi-award-fill me-1" style="color:#e3a008"></i> Certificate
          </a>
        <?php else: ?>
          <div></div>
        <?php endif; ?>

        <?php
          $courseActionUrl = ($pct === 0 && !$isDone)
            ? $url('learn/courses/' . $e2['course_uuid'])            // Start → detail page
            : $url('learn/courses/' . $e2['course_uuid'] . '/learn'); // Resume/View → player
        ?>
        <a href="<?= $courseActionUrl ?>"
           class="sc-btn-start <?= $isDone ? 'done' : '' ?>">
          <i class="bi <?= $btnIcon ?>"></i>
          <?= $btnLabel ?>
        </a>
      </div>

    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
/* ── Filter pills ────────────────────────────────────────────────────────── */
.filter-pill {
  border: 1.5px solid var(--border-color);
  background: var(--card-bg);
  color: var(--text-muted);
  border-radius: 20px;
  padding: 5px 16px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all .15s;
}
.filter-pill.active,
.filter-pill:hover {
  border-color: #6366f1;
  background: #6366f1;
  color: #fff;
}

/* ── Student Course Card ─────────────────────────────────────────────────── */
.sc-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  height: 100%;
  transition: transform .2s, box-shadow .2s;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.sc-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(99,102,241,.15);
}

/* Thumbnail */
.sc-thumb {
  position: relative;
  height: 180px;
  flex-shrink: 0;
  overflow: hidden;
}
.sc-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.sc-thumb-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg,#6366f1 0%,#1a56db 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3.5rem;
  color: rgba(255,255,255,.45);
}
.sc-progress-badge {
  position: absolute;
  bottom: 10px;
  right: 10px;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(6px);
  color: #fff;
  font-size: 11.5px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
}
.sc-progress-badge.done {
  background: rgba(14,159,110,.85);
}

/* Body */
.sc-body { padding: 16px 18px 10px; flex-grow: 1; }

.sc-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
.sc-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
}
.sc-badge-success  { background: #d1fae5; color: #059669; }
.sc-badge-primary  { background: #ebf2ff; color: #1a56db; }
.sc-badge-warning  { background: #fef9c3; color: #ca8a04; }
.sc-badge-danger   { background: #fde8e8; color: #e02424; }
.sc-badge-level    { background: #f1f5f9; color: var(--text-muted); }
.sc-badge-cat      { background: #f1f5f9; color: var(--text-muted); }

.sc-title {
  font-size: 15.5px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 8px;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.sc-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

/* Progress */
.sc-progress-wrap {
  height: 7px;
  background: var(--border-color);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 6px;
}
.sc-progress-bar {
  height: 100%;
  border-radius: 4px;
  transition: width .4s ease;
}
.sc-progress-label {
  display: flex;
  justify-content: space-between;
  font-size: 11.5px;
  color: var(--text-muted);
  margin-bottom: 4px;
}

/* Footer / Start button */
.sc-footer {
  padding: 12px 18px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border-top: 1px solid var(--border-color);
  margin-top: 10px;
}

.sc-btn-start {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: linear-gradient(135deg,#6366f1,#1a56db);
  color: #fff !important;
  text-decoration: none !important;
  border: none;
  border-radius: 10px;
  padding: 10px 22px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: opacity .15s, transform .1s, box-shadow .15s;
  box-shadow: 0 4px 14px rgba(99,102,241,.35);
}
.sc-btn-start:hover {
  opacity: .92;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(99,102,241,.45);
}
.sc-btn-start:active { transform: translateY(0); }
.sc-btn-start.done {
  background: linear-gradient(135deg,#0e9f6e,#059669);
  box-shadow: 0 4px 14px rgba(14,159,110,.3);
}

.sc-btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #0e9f6e !important;
  text-decoration: none !important;
  font-size: 13px;
  font-weight: 600;
}
.sc-btn-secondary:hover { text-decoration: underline !important; }
</style>

<script>
// Filter pills
document.querySelectorAll('.filter-pill').forEach(pill => {
  pill.addEventListener('click', function () {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    this.classList.add('active');

    const filter = this.dataset.filter;
    document.querySelectorAll('.course-item').forEach(item => {
      if (filter === 'all') {
        item.style.display = '';
      } else if (filter === 'active') {
        item.style.display = item.dataset.status === 'active' ? '' : 'none';
      } else if (filter === 'completed') {
        item.style.display = item.dataset.status === 'completed' ? '' : 'none';
      }
    });
  });
});
</script>

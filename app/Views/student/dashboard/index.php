<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$levelClasses = ['beginner'=>'chip-beginner','intermediate'=>'chip-intermediate','advanced'=>'chip-advanced'];
?>

<!-- ── Stat Grid ───────────────────────────────────────────────── -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#ededff;color:#5b5ef6"><i class="bi bi-journals"></i></div>
    <div><div class="stat-num"><?= count($enrolled) ?></div><div class="stat-lbl">Enrolled</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#ecfdf5;color:#059669"><i class="bi bi-check-circle-fill"></i></div>
    <div><div class="stat-num"><?= (int)$completed ?></div><div class="stat-lbl">Completed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#fffbeb;color:#d97706"><i class="bi bi-trophy-fill"></i></div>
    <div><div class="stat-num"><?= number_format((int)$points) ?></div><div class="stat-lbl">Points</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#eff6ff;color:#2563eb"><i class="bi bi-arrow-right-circle-fill"></i></div>
    <div><div class="stat-num"><?= (int)$active ?></div><div class="stat-lbl">In Progress</div></div>
  </div>
</div>

<!-- ── Continue Learning ────────────────────────────────────────── -->
<?php
$inProgress = array_values(array_filter($enrolled, fn($e) => $e['status'] === 'active' && (int)($e['progress_pct'] ?? 0) > 0));
if (!empty($inProgress)):
  $latest = $inProgress[0];
  $pct    = (int)($latest['progress_pct'] ?? 0);
?>
<div class="continue-banner mb-5">
  <div class="continue-play-btn">
    <i class="bi bi-play-fill"></i>
  </div>
  <div class="flex-grow-1 min-w-0">
    <div class="txt-label mb-1">Continue Learning</div>
    <a href="<?= $url('learn/courses/' . ($latest['course_uuid'] ?? '')) ?>"
       class="text-decoration-none d-block mb-2">
      <div style="font-size:15.5px;font-weight:700;color:var(--text-1);line-height:1.4"><?= $e($latest['title']) ?></div>
    </a>
    <div class="d-flex align-items-center gap-2">
      <div class="course-progress-track" style="max-width:220px">
        <div class="course-progress-fill" style="width:<?= $pct ?>%;background:var(--primary)"></div>
      </div>
      <span style="font-size:12.5px;font-weight:600;color:var(--text-2)"><?= $pct ?>% complete</span>
    </div>
  </div>
  <a href="<?= $url('learn/courses/' . ($latest['course_uuid'] ?? '') . '/learn') ?>"
     class="btn-course-action btn-primary-action flex-shrink-0">
    <i class="bi bi-play-fill"></i> Resume Course
  </a>
</div>
<?php endif; ?>

<!-- ── My Courses ───────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 style="font-size:17px;font-weight:800;color:var(--text-1);display:flex;align-items:center;gap:8px">
    <i class="bi bi-journals" style="color:var(--primary)"></i> My Courses
  </h2>
  <a href="<?= $url('learn/courses') ?>" style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none">
    View All <i class="bi bi-arrow-right ms-1"></i>
  </a>
</div>

<?php if (empty($enrolled)): ?>
<div class="lms-surface text-center py-5 px-4">
  <div style="font-size:3.5rem;color:var(--border)"><i class="bi bi-mortarboard"></i></div>
  <div style="font-size:16px;font-weight:700;color:var(--text-1);margin-top:14px">No courses yet</div>
  <p style="font-size:14px;color:var(--text-2);margin-top:6px">Contact your administrator to get enrolled.</p>
</div>
<?php else: ?>
<div class="row g-4">
  <?php foreach (array_slice($enrolled, 0, 6) as $e2):
    $pct       = (int)($e2['progress_pct'] ?? 0);
    $isDone    = $e2['status'] === 'completed';
    $isActive  = $e2['status'] === 'active';
    $detailUrl = $url('learn/courses/' . $e2['course_uuid']);
    $playerUrl = $url('learn/courses/' . $e2['course_uuid'] . '/learn');
    $chipClass = $levelClasses[$e2['level']] ?? 'chip-beginner';
    $barColor  = $isDone ? '#12b76a' : 'var(--primary)';
    $statusBg  = $isDone ? '#ecfdf5' : '#eff6ff';
    $statusCl  = $isDone ? '#059669' : '#2563eb';
  ?>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="course-card">

      <!-- Thumbnail -->
      <a href="<?= $detailUrl ?>" class="course-thumb-wrap">
        <?php if ($e2['thumbnail']): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $e2['thumbnail']) ?>" alt="<?= $e($e2['title']) ?>">
        <?php else: ?>
          <div class="course-thumb-placeholder">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
        <?php endif; ?>
        <div class="course-progress-chip">
          <?= $isDone ? '✓ Done' : ($pct . '%') ?>
        </div>
      </a>

      <!-- Body -->
      <div class="course-body">
        <a href="<?= $detailUrl ?>" class="course-title-link">
          <div class="course-title-text"><?= $e($e2['title']) ?></div>
        </a>
        <div class="course-chips">
          <?php if ($e2['level']): ?><span class="course-chip <?= $chipClass ?>"><?= ucfirst($e($e2['level'])) ?></span><?php endif; ?>
          <?php if ($e2['category_name']): ?><span class="course-chip chip-category"><?= $e($e2['category_name']) ?></span><?php endif; ?>
        </div>
        <?php if ($e2['duration_hours']): ?>
        <div style="font-size:12px;color:var(--text-3);margin-top:2px">
          <i class="bi bi-clock me-1"></i><?= $e($e2['duration_hours']) ?>h total
        </div>
        <?php endif; ?>
        <div class="course-progress-row">
          <div class="course-progress-track">
            <div class="course-progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div>
          </div>
          <span class="course-progress-pct"><?= $pct ?>%</span>
        </div>
      </div>

      <!-- Footer -->
      <div class="course-footer">
        <span class="course-status-chip" style="background:<?= $statusBg ?>;color:<?= $statusCl ?>">
          <?= ucfirst($e($e2['status'])) ?>
        </span>
        <a href="<?= $playerUrl ?>" class="btn-course-action <?= $isDone ? 'btn-success-action' : 'btn-primary-action' ?>">
          <i class="bi <?= $isDone ? 'bi-eye-fill' : ($pct > 0 ? 'bi-play-fill' : 'bi-play-fill') ?>"></i>
          <?= $isDone ? 'View Course' : ($pct > 0 ? 'Resume' : 'Start Course') ?>
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

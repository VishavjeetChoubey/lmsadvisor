<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$levelClasses = ['beginner'=>'chip-beginner','intermediate'=>'chip-intermediate','advanced'=>'chip-advanced'];

$h        = (int)date('H');
$greeting = $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');

$enrolledCount  = count($enrolled);
$completedCount = (int)$completed;
$activeCount    = (int)$active;
$pointsTotal    = (int)$points;
$completePct    = $enrolledCount > 0 ? round($completedCount / $enrolledCount * 100) : 0;
?>

<!-- ── Greeting bar ────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4">
  <div>
    <h2 style="font-size:21px;font-weight:800;color:var(--text-1);margin:0 0 4px">
      <?= $greeting ?>, <?= $e($firstName ?? 'Learner') ?> 👋
    </h2>
    <p style="font-size:13.5px;color:var(--text-2);margin:0">
      <?php if ($activeCount > 0): ?>
        You have <strong><?= $activeCount ?></strong> course<?= $activeCount > 1 ? 's' : '' ?> in progress. Keep it up!
      <?php elseif ($completedCount > 0): ?>
        You've completed <strong><?= $completedCount ?></strong> course<?= $completedCount > 1 ? 's' : '' ?>. Excellent!
      <?php else: ?>
        Welcome! Start your first course today.
      <?php endif; ?>
    </p>
  </div>
  <div style="font-size:12.5px;color:var(--text-3);background:var(--card);border:1px solid var(--border);padding:5px 12px;border-radius:20px;white-space:nowrap">
    <i class="bi bi-calendar3 me-1"></i><?= date('d M Y') ?>
  </div>
</div>

<!-- ── Stats row ─────────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <?php
  $stats = [
    ['Enrolled',    $enrolledCount,                 'bi-journals',              '#5b5ef6','#ededff', null],
    ['Completed',   $completedCount,                'bi-patch-check-fill',      '#059669','#ecfdf5', $completePct > 0 ? $completePct.'% rate' : null],
    ['Grade Points',number_format($pointsTotal),    'bi-trophy-fill',           '#d97706','#fffbeb', null],
    ['In Progress', $activeCount,                   'bi-arrow-right-circle-fill','#2563eb','#eff6ff', null],
  ];
  foreach ($stats as [$label, $val, $icon, $color, $bg, $sub]):
  ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card h-100">
      <div class="stat-icon-wrap" style="background:<?= $bg ?>;color:<?= $color ?>">
        <i class="bi <?= $icon ?>"></i>
      </div>
      <div style="min-width:0">
        <div class="stat-num"><?= $val ?></div>
        <div class="stat-lbl"><?= $label ?></div>
        <?php if ($sub): ?>
          <div style="font-size:11px;color:var(--text-3);margin-top:2px;white-space:nowrap"><?= $sub ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Overall progress bar ──────────────────────────────────────────────────── -->
<?php if ($enrolledCount > 0): ?>
<div class="lms-surface p-3 mb-4 d-flex align-items-center gap-3" style="flex-wrap:wrap">
  <div style="flex:1;min-width:160px">
    <div class="d-flex justify-content-between mb-1">
      <span style="font-size:13px;font-weight:600;color:var(--text-2)">Overall Learning Progress</span>
      <span style="font-size:13px;font-weight:700;color:var(--primary)"><?= $completePct ?>%</span>
    </div>
    <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden">
      <div style="height:100%;width:<?= $completePct ?>%;background:linear-gradient(90deg,#5b5ef6,#3b82f6);border-radius:4px;transition:width .6s"></div>
    </div>
    <div style="font-size:11.5px;color:var(--text-3);margin-top:4px"><?= $completedCount ?> of <?= $enrolledCount ?> courses completed</div>
  </div>
  <div style="text-align:center;flex-shrink:0">
    <div style="font-size:30px;font-weight:800;color:var(--primary);line-height:1"><?= $completePct ?>%</div>
    <div style="font-size:11px;color:var(--text-3)">done</div>
  </div>
</div>
<?php endif; ?>

<!-- ── Continue Learning banner ──────────────────────────────────────────────── -->
<?php
$inProgress = array_values(array_filter($enrolled, fn($e) => $e['status'] === 'active' && (int)($e['progress_pct'] ?? 0) > 0));
if (!empty($inProgress)):
  $latest = $inProgress[0];
  $pct    = (int)($latest['progress_pct'] ?? 0);
?>
<div class="continue-banner mb-4">
  <div class="continue-play-btn flex-shrink-0">
    <i class="bi bi-play-fill"></i>
  </div>
  <div style="flex:1;min-width:0">
    <div style="font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-3);margin-bottom:3px">Continue Where You Left Off</div>
    <a href="<?= $url('learn/courses/' . ($latest['course_uuid'] ?? '')) ?>" class="text-decoration-none">
      <div style="font-size:14.5px;font-weight:700;color:var(--text-1);margin-bottom:6px;line-height:1.35;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?= $e($latest['title']) ?></div>
    </a>
    <div style="display:flex;align-items:center;gap:8px">
      <div style="flex:1;max-width:180px;height:5px;background:var(--border);border-radius:3px;overflow:hidden">
        <div style="height:100%;width:<?= $pct ?>%;background:var(--primary);border-radius:3px"></div>
      </div>
      <span style="font-size:12px;font-weight:600;color:var(--text-2);white-space:nowrap"><?= $pct ?>% complete</span>
    </div>
  </div>
  <a href="<?= $url('learn/courses/' . ($latest['course_uuid'] ?? '') . '/learn') ?>"
     class="btn-course-action btn-primary-action flex-shrink-0" style="white-space:nowrap">
    <i class="bi bi-play-fill"></i> Resume
  </a>
</div>
<?php endif; ?>

<!-- ── My Courses ──────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 style="font-size:16px;font-weight:800;color:var(--text-1);margin:0;display:flex;align-items:center;gap:7px">
    <i class="bi bi-journals" style="color:var(--primary)"></i> My Courses
  </h3>
  <a href="<?= $url('learn/courses') ?>" style="font-size:12.5px;font-weight:600;color:var(--primary);text-decoration:none">
    View All <i class="bi bi-arrow-right ms-1"></i>
  </a>
</div>
<?php if (empty($enrolled)): ?>
<div class="lms-surface text-center py-5 px-4">
  <div style="font-size:3rem;color:var(--border)"><i class="bi bi-mortarboard"></i></div>
  <div style="font-size:15px;font-weight:700;color:var(--text-1);margin-top:12px">No courses yet</div>
  <p style="font-size:13.5px;color:var(--text-2);margin-top:5px">Contact your administrator to get enrolled.</p>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach (array_slice($enrolled, 0, 6) as $e2):
    $pct       = (int)($e2['progress_pct'] ?? 0);
    $isDone    = $e2['status'] === 'completed';
    $detailUrl = $url('learn/courses/' . $e2['course_uuid']);
    $playerUrl = $url('learn/courses/' . $e2['course_uuid'] . '/learn');
    $chipClass = $levelClasses[$e2['level']] ?? 'chip-beginner';
    $barColor  = $isDone ? '#12b76a' : 'var(--primary)';
    $statusBg  = $isDone ? '#ecfdf5' : ($pct > 0 ? '#eff6ff' : 'var(--bg)');
    $statusCl  = $isDone ? '#059669' : ($pct > 0 ? '#2563eb' : 'var(--text-3)');
  ?>
  <div class="col-12 col-sm-6 col-xl-4">
    <div class="course-card h-100">
      <a href="<?= $detailUrl ?>" class="course-thumb-wrap" style="text-decoration:none">
        <?php if ($e2['thumbnail']): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $e2['thumbnail']) ?>" alt="<?= $e($e2['title']) ?>">
        <?php else: ?>
          <div class="course-thumb-placeholder">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
        <?php endif; ?>
        <?php if ($pct > 0 || $isDone): ?>
          <div class="course-progress-chip"><?= $isDone ? '✓ Done' : $pct . '%' ?></div>
        <?php endif; ?>
      </a>
      <div class="course-body">
        <a href="<?= $detailUrl ?>" class="course-title-link">
          <div class="course-title-text"><?= $e($e2['title']) ?></div>
        </a>
        <div class="course-chips">
          <?php if ($e2['level']): ?><span class="course-chip <?= $chipClass ?>"><?= ucfirst($e($e2['level'])) ?></span><?php endif; ?>
          <?php if ($e2['category_name']): ?><span class="course-chip chip-category"><?= $e($e2['category_name']) ?></span><?php endif; ?>
          <?php if ($e2['duration_hours']): ?><span style="font-size:11px;color:var(--text-3)"><i class="bi bi-clock me-1"></i><?= $e($e2['duration_hours']) ?>h</span><?php endif; ?>
        </div>
        <div class="course-progress-row">
          <div class="course-progress-track">
            <div class="course-progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div>
          </div>
          <span class="course-progress-pct"><?= $pct ?>%</span>
        </div>
      </div>
      <div class="course-footer">
        <span class="course-status-chip" style="background:<?= $statusBg ?>;color:<?= $statusCl ?>">
          <?= $isDone ? '✓ Completed' : ($pct > 0 ? '▶ Active' : '○ Not Started') ?>
        </span>
        <a href="<?= $playerUrl ?>" class="btn-course-action <?= $isDone ? 'btn-success-action' : 'btn-primary-action' ?>">
          <i class="bi <?= $isDone ? 'bi-eye-fill' : 'bi-play-fill' ?>"></i>
          <?= $isDone ? 'View' : ($pct > 0 ? 'Resume' : 'Start') ?>
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Recommended Courses ────────────────────────────────────────────────── -->
<?php
try {
  $recommendations = \App\Services\RecommendationService::getForUser((int)($authUser['id'] ?? 0), 4);
} catch (\Throwable $ex) {
  $recommendations = [];
}
?>
<?php if (!empty($recommendations)): ?>
<div class="d-flex align-items-center justify-content-between mb-3 mt-5">
  <h3 style="font-size:16px;font-weight:800;color:var(--text-1);margin:0;display:flex;align-items:center;gap:7px">
    <i class="bi bi-stars" style="color:#f59e0b"></i> Recommended For You
  </h3>
</div>
<div class="row g-3">
  <?php foreach ($recommendations as $rec):
    $rDetUrl = $url('learn/courses/' . ($rec['uuid'] ?? ''));
  ?>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="course-card h-100" style="border-top:3px solid #f59e0b">
      <a href="<?= $rDetUrl ?>" class="course-thumb-wrap" style="text-decoration:none">
        <?php if (!empty($rec['thumbnail'])): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $rec['thumbnail']) ?>" alt="<?= $e($rec['title']) ?>">
        <?php else: ?>
          <div class="course-thumb-placeholder"><i class="bi bi-journal-bookmark-fill"></i></div>
        <?php endif; ?>
        <div class="course-progress-chip" style="background:#f59e0b;color:#fff">
          <i class="bi bi-stars me-1"></i>Recommended
        </div>
      </a>
      <div class="course-body">
        <a href="<?= $rDetUrl ?>" class="course-title-link">
          <div class="course-title-text"><?= $e($rec['title']) ?></div>
        </a>
        <div class="course-chips">
          <?php if (!empty($rec['level'])): ?>
          <span class="course-chip chip-<?= $e($rec['level']) ?>"><?= ucfirst($e($rec['level'])) ?></span>
          <?php endif; ?>
          <?php if (!empty($rec['category_name'])): ?>
          <span class="course-chip chip-category"><?= $e($rec['category_name']) ?></span>
          <?php endif; ?>
        </div>
        <div style="font-size:12px;color:#d97706;margin-top:6px;font-weight:600">
          <i class="bi bi-info-circle me-1"></i><?= $e($rec['recommendation_reason'] ?? 'You might like this') ?>
        </div>
      </div>
      <div class="course-footer">
        <span></span>
        <a href="<?= $rDetUrl ?>" class="btn-course-action btn-primary-action" style="background:#f59e0b;border-color:#f59e0b">
          <i class="bi bi-eye-fill"></i> View
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

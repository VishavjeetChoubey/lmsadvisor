<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$levelClasses = ['beginner'=>'chip-beginner','intermediate'=>'chip-intermediate','advanced'=>'chip-advanced'];

// Greeting
$h   = (int)date('H');
$greeting = $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');

// Stats from passed variables
$enrolledCount  = count($enrolled);
$completedCount = (int)$completed;
$activeCount    = (int)$active;
$pointsTotal    = (int)$points;
$inProgressPct  = $enrolledCount > 0 ? round($activeCount / $enrolledCount * 100) : 0;
$completePct    = $enrolledCount > 0 ? round($completedCount / $enrolledCount * 100) : 0;
?>

<!-- ── Greeting ─────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
  <div>
    <h2 style="font-size:22px;font-weight:800;color:var(--text-1);margin:0 0 4px">
      <?= $greeting ?>, <?= $e($firstName ?? 'Learner') ?> 👋
    </h2>
    <p style="font-size:14px;color:var(--text-2);margin:0">
      <?php if ($activeCount > 0): ?>
        You have <strong><?= $activeCount ?></strong> course<?= $activeCount > 1 ? 's' : '' ?> in progress. Keep it up!
      <?php elseif ($completedCount > 0): ?>
        You've completed <strong><?= $completedCount ?></strong> course<?= $completedCount > 1 ? 's' : '' ?>. Excellent work!
      <?php else: ?>
        Welcome to LMSAdvisor. Start your learning journey today.
      <?php endif; ?>
    </p>
  </div>
  <div style="font-size:13px;color:var(--text-3);background:var(--card);border:1px solid var(--border);padding:6px 14px;border-radius:20px">
    <i class="bi bi-calendar3 me-1"></i><?= date('l, d F Y') ?>
  </div>
</div>

<!-- ── Stats ────────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <!-- Enrolled -->
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon-wrap" style="background:#ededff;color:#5b5ef6">
        <i class="bi bi-journals"></i>
      </div>
      <div>
        <div class="stat-num"><?= $enrolledCount ?></div>
        <div class="stat-lbl">Enrolled</div>
      </div>
    </div>
  </div>
  <!-- Completed -->
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon-wrap" style="background:#ecfdf5;color:#059669">
        <i class="bi bi-patch-check-fill"></i>
      </div>
      <div>
        <div class="stat-num"><?= $completedCount ?></div>
        <div class="stat-lbl">Completed</div>
        <?php if ($completePct > 0): ?>
          <div style="font-size:11px;color:var(--text-3);margin-top:2px"><?= $completePct ?>% completion rate</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <!-- Points -->
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon-wrap" style="background:#fffbeb;color:#d97706">
        <i class="bi bi-trophy-fill"></i>
      </div>
      <div>
        <div class="stat-num"><?= number_format($pointsTotal) ?></div>
        <div class="stat-lbl">Grade Points</div>
      </div>
    </div>
  </div>
  <!-- In Progress -->
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon-wrap" style="background:#eff6ff;color:#2563eb">
        <i class="bi bi-arrow-right-circle-fill"></i>
      </div>
      <div>
        <div class="stat-num"><?= $activeCount ?></div>
        <div class="stat-lbl">In Progress</div>
      </div>
    </div>
  </div>
</div>

<!-- ── Overall progress bar ──────────────────────────────────────────────── -->
<?php if ($enrolledCount > 0): ?>
<div class="lms-surface p-4 mb-4" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
  <div style="flex:1;min-width:200px">
    <div class="d-flex justify-content-between mb-1">
      <span style="font-size:13px;font-weight:600;color:var(--text-2)">Overall Learning Progress</span>
      <span style="font-size:13px;font-weight:700;color:var(--primary)"><?= $completePct ?>%</span>
    </div>
    <div style="height:10px;background:var(--border);border-radius:6px;overflow:hidden">
      <div style="height:100%;width:<?= $completePct ?>%;background:linear-gradient(90deg,#5b5ef6,#3b82f6);border-radius:6px;transition:width .6s"></div>
    </div>
    <div style="font-size:12px;color:var(--text-3);margin-top:5px"><?= $completedCount ?> of <?= $enrolledCount ?> courses completed</div>
  </div>
  <div style="text-align:center;flex-shrink:0">
    <div style="font-size:28px;font-weight:800;color:var(--primary)"><?= $completePct ?>%</div>
    <div style="font-size:12px;color:var(--text-3)">done</div>
  </div>
</div>
<?php endif; ?>

<!-- ── Continue Learning ──────────────────────────────────────────────────── -->
<?php
$inProgress = array_values(array_filter($enrolled, fn($e) => $e['status'] === 'active' && (int)($e['progress_pct'] ?? 0) > 0));
if (!empty($inProgress)):
  $latest = $inProgress[0];
  $pct    = (int)($latest['progress_pct'] ?? 0);
?>
<div class="continue-banner mb-4">
  <div class="continue-play-btn">
    <i class="bi bi-play-fill"></i>
  </div>
  <div style="flex:1;min-width:0">
    <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-3);margin-bottom:4px">Continue Where You Left Off</div>
    <a href="<?= $url('learn/courses/' . ($latest['course_uuid'] ?? '')) ?>" class="text-decoration-none">
      <div style="font-size:16px;font-weight:700;color:var(--text-1);margin-bottom:8px;line-height:1.3"><?= $e($latest['title']) ?></div>
    </a>
    <div style="display:flex;align-items:center;gap:10px">
      <div style="flex:1;max-width:220px;height:6px;background:var(--border);border-radius:3px;overflow:hidden">
        <div style="height:100%;width:<?= $pct ?>%;background:var(--primary);border-radius:3px"></div>
      </div>
      <span style="font-size:12.5px;font-weight:600;color:var(--text-2)"><?= $pct ?>% complete</span>
    </div>
  </div>
  <a href="<?= $url('learn/courses/' . ($latest['course_uuid'] ?? '') . '/learn') ?>"
     class="btn-course-action btn-primary-action flex-shrink-0">
    <i class="bi bi-play-fill"></i> Resume
  </a>
</div>
<?php endif; ?>

<!-- ── My Courses ─────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 style="font-size:17px;font-weight:800;color:var(--text-1);margin:0;display:flex;align-items:center;gap:8px">
    <i class="bi bi-journals" style="color:var(--primary)"></i> My Courses
  </h3>
  <a href="<?= $url('learn/courses') ?>"
     style="font-size:13px;font-weight:600;color:var(--primary);text-decoration:none">
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
    $detailUrl = $url('learn/courses/' . $e2['course_uuid']);
    $playerUrl = $url('learn/courses/' . $e2['course_uuid'] . '/learn');
    $chipClass = $levelClasses[$e2['level']] ?? 'chip-beginner';
    $barColor  = $isDone ? '#12b76a' : 'var(--primary)';
    $statusBg  = $isDone ? '#ecfdf5' : ($pct > 0 ? '#eff6ff' : '#f9fafb');
    $statusCl  = $isDone ? '#059669' : ($pct > 0 ? '#2563eb' : 'var(--text-3)');
  ?>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="course-card">

      <!-- Thumbnail → course detail -->
      <a href="<?= $detailUrl ?>" class="course-thumb-wrap">
        <?php if ($e2['thumbnail']): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $e2['thumbnail']) ?>"
               alt="<?= $e($e2['title']) ?>">
        <?php else: ?>
          <div class="course-thumb-placeholder">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
        <?php endif; ?>
        <?php if ($pct > 0 || $isDone): ?>
        <div class="course-progress-chip">
          <?= $isDone ? '✓ Done' : $pct . '%' ?>
        </div>
        <?php endif; ?>
      </a>

      <!-- Body -->
      <div class="course-body">
        <a href="<?= $detailUrl ?>" class="course-title-link">
          <div class="course-title-text"><?= $e($e2['title']) ?></div>
        </a>
        <div class="course-chips">
          <?php if ($e2['level']): ?>
            <span class="course-chip <?= $chipClass ?>"><?= ucfirst($e($e2['level'])) ?></span>
          <?php endif; ?>
          <?php if ($e2['category_name']): ?>
            <span class="course-chip chip-category"><?= $e($e2['category_name']) ?></span>
          <?php endif; ?>
          <?php if ($e2['duration_hours']): ?>
            <span style="font-size:11.5px;color:var(--text-3);margin-left:2px">
              <i class="bi bi-clock me-1"></i><?= $e($e2['duration_hours']) ?>h
            </span>
          <?php endif; ?>
        </div>
        <div class="course-progress-row">
          <div class="course-progress-track">
            <div class="course-progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div>
          </div>
          <span class="course-progress-pct"><?= $pct ?>%</span>
        </div>
      </div>

      <!-- Footer: status + action button → player -->
      <div class="course-footer">
        <span class="course-status-chip"
              style="background:<?= $statusBg ?>;color:<?= $statusCl ?>">
          <?= $isDone ? '✓ Completed' : ($pct > 0 ? '▶ Active' : '○ Not Started') ?>
        </span>
        <a href="<?= $playerUrl ?>"
           class="btn-course-action <?= $isDone ? 'btn-success-action' : 'btn-primary-action' ?>">
          <i class="bi <?= $isDone ? 'bi-eye-fill' : 'bi-play-fill' ?>"></i>
          <?= $isDone ? 'View' : ($pct > 0 ? 'Resume' : 'Start') ?>
        </a>
      </div>

    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

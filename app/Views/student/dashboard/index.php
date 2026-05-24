<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$levelColors = ['beginner'=>'info','intermediate'=>'primary','advanced'=>'danger'];
?>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="student-stat-card">
      <div class="student-stat-icon" style="color:#6366f1"><i class="bi bi-book-half"></i></div>
      <div class="student-stat-value"><?= count($enrolled) ?></div>
      <div class="student-stat-label">Enrolled</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="student-stat-card">
      <div class="student-stat-icon" style="color:#0e9f6e"><i class="bi bi-check-circle"></i></div>
      <div class="student-stat-value"><?= (int)$completed ?></div>
      <div class="student-stat-label">Completed</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="student-stat-card">
      <div class="student-stat-icon" style="color:#e3a008"><i class="bi bi-trophy"></i></div>
      <div class="student-stat-value"><?= number_format((int)$points) ?></div>
      <div class="student-stat-label">Points</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="student-stat-card">
      <div class="student-stat-icon" style="color:#1a56db"><i class="bi bi-person-running"></i></div>
      <div class="student-stat-value"><?= (int)$active ?></div>
      <div class="student-stat-label">In Progress</div>
    </div>
  </div>
</div>

<!-- Continue Learning -->
<?php
$inProgress = array_filter($enrolled, fn($e) => $e['status'] === 'active' && (int)($e['progress_pct'] ?? 0) > 0);
if (!empty($inProgress)):
  $latest = array_values($inProgress)[0];
  $pct    = (int)($latest['progress_pct'] ?? 0);
?>
<div class="card lms-card mb-4" style="border-left:4px solid #6366f1">
  <div class="card-body p-4 d-flex align-items-center gap-4 flex-wrap">
    <div style="font-size:2.5rem;color:#6366f1"><i class="bi bi-play-circle-fill"></i></div>
    <div class="flex-grow-1">
      <div class="text-muted mb-1" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Continue Learning</div>
      <h5 class="fw-bold mb-2"><?= $e($latest['title']) ?></h5>
      <div class="d-flex align-items-center gap-2">
        <div class="progress flex-grow-1" style="max-width:200px;height:8px;border-radius:4px">
          <div class="progress-bar" style="width:<?= $pct ?>%;background:#6366f1"></div>
        </div>
        <span style="font-size:13px;color:var(--text-muted)"><?= $pct ?>% complete</span>
      </div>
    </div>
    <a href="<?= $url('learn/courses') ?>"
       class="btn-start-course">
      <i class="bi bi-play-fill"></i> Resume Course
    </a>
  </div>
</div>
<?php endif; ?>

<!-- My Courses -->
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-book-half me-2"></i> My Courses</h5>
    <a href="<?= $url('learn/courses') ?>" class="btn btn-sm btn-outline-primary">View All</a>
  </div>

  <?php if (empty($enrolled)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3.5rem;color:var(--border-color)"><i class="bi bi-mortarboard"></i></div>
      <h6 class="mt-3 fw-semibold" style="color:var(--text-muted)">No courses enrolled yet</h6>
      <p class="small" style="color:var(--text-muted)">Contact your administrator to get enrolled in a course.</p>
    </div>
  <?php else: ?>
  <div class="row g-3 p-4">
    <?php foreach (array_slice($enrolled, 0, 6) as $e2):
      $pct       = (int)($e2['progress_pct'] ?? 0);
      $isDone    = $e2['status'] === 'completed';
      $btnLabel  = $isDone ? 'View Course' : ($pct > 0 ? 'Resume' : 'Start Course');
      $btnIcon   = $isDone ? 'bi-award' : ($pct > 0 ? 'bi-play-fill' : 'bi-play-fill');
      $btnClass  = 'btn-start-course' . ($isDone ? ' completed' : '');
    ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="course-card h-100 d-flex flex-column">
        <!-- Thumbnail -->
        <?php if ($e2['thumbnail']): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $e2['thumbnail']) ?>"
               class="course-thumb" alt="<?= $e($e2['title']) ?>">
        <?php else: ?>
          <div class="course-thumb-placeholder">
            <i class="bi bi-book-half" style="font-size:3rem;color:rgba(255,255,255,.5)"></i>
          </div>
        <?php endif; ?>

        <!-- Body -->
        <div class="course-body flex-grow-1">
          <div class="course-title"><?= $e($e2['title']) ?></div>
          <div class="course-meta d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-<?= $levelColors[$e2['level']] ?? 'secondary' ?>-subtle text-<?= $levelColors[$e2['level']] ?? 'secondary' ?>"
                  style="border-radius:20px;padding:2px 8px;font-size:11px">
              <?= ucfirst($e($e2['level'])) ?>
            </span>
            <?php if ($e2['category_name']): ?>
              <span><i class="bi bi-tag me-1"></i><?= $e($e2['category_name']) ?></span>
            <?php endif; ?>
            <?php if ($e2['duration_hours']): ?>
              <span><i class="bi bi-clock me-1"></i><?= $e($e2['duration_hours']) ?>h</span>
            <?php endif; ?>
          </div>

          <!-- Progress bar -->
          <div class="d-flex align-items-center gap-2 mt-2">
            <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
              <div class="progress-bar"
                   style="width:<?= $pct ?>%;background:<?= $isDone ? '#0e9f6e' : '#6366f1' ?>"></div>
            </div>
            <span style="font-size:11.5px;color:var(--text-muted);white-space:nowrap"><?= $pct ?>%</span>
          </div>
        </div>

        <!-- Footer with Start button -->
        <div class="course-footer">
          <span class="badge bg-<?= $isDone ? 'success' : 'primary' ?>-subtle text-<?= $isDone ? 'success' : 'primary' ?>"
                style="font-size:11px;border-radius:20px;padding:3px 10px">
            <?= ucfirst($e($e2['status'])) ?>
          </span>
          <a href="<?= $url('learn/courses') ?>" class="<?= $btnClass ?>">
            <i class="bi <?= $btnIcon ?>"></i>
            <?= $btnLabel ?>
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

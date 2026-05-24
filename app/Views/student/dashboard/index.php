<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$levelColors = ['beginner'=>'info','intermediate'=>'primary','advanced'=>'danger'];
?>
<!-- Stats -->
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

<!-- My Courses -->
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-book-half me-2"></i> My Courses</h5>
    <a href="<?= $url('learn/courses') ?>" class="btn btn-sm btn-outline-primary">View All</a>
  </div>
  <?php if (empty($enrolled)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-book"></i></div>
      <h6 class="mt-3" style="color:var(--text-muted)">No courses enrolled yet</h6>
      <p class="small" style="color:var(--text-muted)">Contact your administrator to get enrolled in a course.</p>
    </div>
  <?php else: ?>
  <div class="row g-3 p-4">
    <?php foreach (array_slice($enrolled, 0, 6) as $e2): ?>
    <?php $pct = (int)($e2['progress_pct'] ?? 0); ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100 border" style="border-radius:var(--radius)">
        <?php if ($e2['thumbnail']): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $e2['thumbnail']) ?>"
               class="card-img-top" alt=""
               style="height:140px;object-fit:cover;border-radius:var(--radius) var(--radius) 0 0">
        <?php else: ?>
          <div style="height:140px;background:linear-gradient(135deg,#6366f1,#1a56db);border-radius:var(--radius) var(--radius) 0 0;display:flex;align-items:center;justify-content:center">
            <i class="bi bi-book-half" style="font-size:2.5rem;color:rgba(255,255,255,.6)"></i>
          </div>
        <?php endif; ?>
        <div class="card-body pb-2">
          <h6 class="fw-semibold mb-1" style="font-size:14px"><?= $e($e2['title']) ?></h6>
          <div class="text-muted mb-2" style="font-size:11.5px">
            <span class="badge bg-<?= $levelColors[$e2['level']] ?? 'secondary' ?>-subtle text-<?= $levelColors[$e2['level']] ?? 'secondary' ?>" style="border-radius:20px;padding:2px 8px"><?= ucfirst($e($e2['level'])) ?></span>
            <?php if ($e2['category_name']): ?>
              <span class="ms-1"><?= $e($e2['category_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
              <div class="progress-bar bg-<?= $pct >= 100 ? 'success' : 'primary' ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <span style="font-size:11.5px;color:var(--text-muted)"><?= $pct ?>%</span>
          </div>
        </div>
        <div class="card-footer bg-transparent border-top py-2 px-3">
          <span class="badge bg-<?= $e2['status'] === 'completed' ? 'success' : 'primary' ?>" style="font-size:11px">
            <?= ucfirst($e($e2['status'])) ?>
          </span>
          <?php if ($e2['duration_hours']): ?>
            <span class="text-muted ms-2" style="font-size:11.5px"><i class="bi bi-clock me-1"></i><?= $e($e2['duration_hours']) ?>h</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

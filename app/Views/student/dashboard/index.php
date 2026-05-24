<?php
use App\Core\View;
$url = fn(string $p = ''): string => View::url($p);
?>
<div class="row g-4 mb-4">
  <?php
  $stats = [
    ['icon'=>'bi-book-half',     'color'=>'#6366f1', 'label'=>'Enrolled',      'value'=>'0'],
    ['icon'=>'bi-check-circle',  'color'=>'#0e9f6e', 'label'=>'Completed',     'value'=>'0'],
    ['icon'=>'bi-award',         'color'=>'#e3a008', 'label'=>'Certificates',  'value'=>'0'],
    ['icon'=>'bi-trophy',        'color'=>'#e02424', 'label'=>'Points',        'value'=>'0'],
  ];
  foreach ($stats as $s): ?>
  <div class="col-6 col-lg-3">
    <div class="student-stat-card">
      <div class="student-stat-icon" style="color:<?= htmlspecialchars($s['color']) ?>">
        <i class="bi <?= htmlspecialchars($s['icon']) ?>"></i>
      </div>
      <div class="student-stat-value"><?= htmlspecialchars($s['value']) ?></div>
      <div class="student-stat-label"><?= htmlspecialchars($s['label']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-12">
    <div class="card lms-card">
      <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-book-half me-2"></i> My Courses</h5>
        <a href="<?= $url('learn/courses') ?>" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body text-center py-5">
        <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-book"></i></div>
        <h6 class="mt-3" style="color:var(--text-muted)">No courses yet</h6>
        <p class="small" style="color:var(--text-muted)">Enroll in a course to get started. (Enrollment — Phase 7)</p>
      </div>
    </div>
  </div>
</div>

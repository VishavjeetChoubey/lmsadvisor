<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$levelColors = ['beginner'=>'info','intermediate'=>'primary','advanced'=>'danger'];
$statusColors = ['active'=>'primary','completed'=>'success','suspended'=>'warning','expired'=>'danger'];
?>
<div class="card lms-card">
  <div class="card-header lms-card-header">
    <h5 class="mb-0"><i class="bi bi-book-half me-2"></i> My Courses
      <span class="badge bg-secondary ms-1"><?= count($enrolled) ?></span>
    </h5>
  </div>
  <?php if (empty($enrolled)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-book"></i></div>
      <h6 class="mt-3 text-muted">No courses yet</h6>
      <p class="small text-muted">Contact your administrator to get enrolled.</p>
    </div>
  <?php else: ?>
  <div class="row g-4 p-4">
    <?php foreach ($enrolled as $e2): ?>
    <?php $pct = (int)($e2['progress_pct'] ?? 0); ?>
    <div class="col-12 col-md-6 col-xl-4">
      <div class="card h-100 border" style="border-radius:var(--radius);transition:box-shadow .2s" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow=''">
        <?php if ($e2['thumbnail']): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $e2['thumbnail']) ?>"
               class="card-img-top" alt=""
               style="height:160px;object-fit:cover;border-radius:var(--radius) var(--radius) 0 0">
        <?php else: ?>
          <div style="height:160px;background:linear-gradient(135deg,#6366f1,#1a56db);border-radius:var(--radius) var(--radius) 0 0;display:flex;align-items:center;justify-content:center">
            <i class="bi bi-book-half" style="font-size:3rem;color:rgba(255,255,255,.5)"></i>
          </div>
        <?php endif; ?>
        <div class="card-body">
          <div class="d-flex gap-2 mb-2 flex-wrap">
            <span class="badge bg-<?= $statusColors[$e2['status']] ?? 'secondary' ?>" style="font-size:11px"><?= ucfirst($e($e2['status'])) ?></span>
            <span class="badge bg-<?= $levelColors[$e2['level']] ?? 'secondary' ?>-subtle text-<?= $levelColors[$e2['level']] ?? 'secondary' ?>" style="font-size:11px;border-radius:20px"><?= ucfirst($e($e2['level'])) ?></span>
          </div>
          <h6 class="fw-semibold mb-2"><?= $e($e2['title']) ?></h6>
          <?php if ($e2['category_name']): ?>
            <div class="text-muted mb-2" style="font-size:12px"><i class="bi bi-tag me-1"></i><?= $e($e2['category_name']) ?></div>
          <?php endif; ?>
          <div class="d-flex align-items-center gap-2 mb-1">
            <div class="progress flex-grow-1" style="height:8px;border-radius:4px">
              <div class="progress-bar bg-<?= $pct >= 100 ? 'success' : 'primary' ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="fw-semibold" style="font-size:12.5px;color:var(--text-muted)"><?= $pct ?>%</span>
          </div>
          <div class="text-muted" style="font-size:11.5px">
            Enrolled: <?= date('d M Y', strtotime($e2['enrolled_at'])) ?>
            <?php if ($e2['duration_hours']): ?>
              · <?= $e($e2['duration_hours']) ?>h
            <?php endif; ?>
          </div>
        </div>
        <?php if ($pct >= 100 && $e2['certificate_enabled']): ?>
        <div class="card-footer bg-transparent border-top py-2 px-3">
          <span class="badge bg-success"><i class="bi bi-award me-1"></i>Certificate Available</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

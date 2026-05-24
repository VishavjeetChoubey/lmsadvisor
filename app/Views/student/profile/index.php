<?php
use App\Core\View;
use App\Middleware\CsrfMiddleware;
$e        = fn(mixed $v): string => View::e($v);
$url      = fn(string $p = ''): string => View::url($p);
$completed = count(array_filter($enrolled, fn($e) => $e['status'] === 'completed'));
$active    = count(array_filter($enrolled, fn($e) => $e['status'] === 'active'));
?>
<div class="row g-4">

  <!-- Profile card -->
  <div class="col-12 col-lg-4">
    <div class="card lms-card text-center p-4">
      <!-- Avatar -->
      <div class="mx-auto mb-3" style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center">
        <i class="fas fa-graduation-cap" style="font-size:2rem;color:#fff"></i>
      </div>
      <h5 class="fw-bold mb-0"><?= $e($auth_user['name'] ?? '') ?></h5>
      <div class="text-muted mb-3" style="font-size:13px"><?= $e($auth_user['email'] ?? '') ?></div>
      <span class="badge bg-primary px-3 py-2" style="font-size:12px">
        <?= $e($auth_user['role_display'] ?? ucfirst($auth_user['role'] ?? 'Student')) ?>
      </span>

      <hr class="my-4">

      <!-- Stats -->
      <div class="row g-3 text-center">
        <div class="col-4">
          <div class="fw-bold" style="font-size:22px;color:var(--primary)"><?= count($enrolled) ?></div>
          <div class="text-muted" style="font-size:11.5px">Enrolled</div>
        </div>
        <div class="col-4">
          <div class="fw-bold" style="font-size:22px;color:#0e9f6e"><?= $completed ?></div>
          <div class="text-muted" style="font-size:11.5px">Completed</div>
        </div>
        <div class="col-4">
          <div class="fw-bold" style="font-size:22px;color:#e3a008"><?= number_format((int)$points) ?></div>
          <div class="text-muted" style="font-size:11.5px">Points</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Activity -->
  <div class="col-12 col-lg-8">
    <div class="card lms-card mb-4">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Recent Courses</h5>
      </div>
      <?php if (empty($enrolled)): ?>
        <div class="card-body text-center py-4 text-muted">No courses yet.</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table lms-table mb-0">
          <thead>
            <tr>
              <th>Course</th>
              <th>Status</th>
              <th>Progress</th>
              <th>Enrolled</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($enrolled, 0, 8) as $e2):
              $pct = (int)($e2['progress_pct'] ?? 0);
              $statusColors = ['active'=>'primary','completed'=>'success','suspended'=>'warning','expired'=>'danger'];
            ?>
            <tr>
              <td class="fw-semibold" style="font-size:13px"><?= $e(mb_strimwidth($e2['title'], 0, 40, '…')) ?></td>
              <td>
                <span class="badge bg-<?= $statusColors[$e2['status']] ?? 'secondary' ?>" style="font-size:11px">
                  <?= ucfirst($e($e2['status'])) ?>
                </span>
              </td>
              <td style="min-width:100px">
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
                    <div class="progress-bar bg-<?= $pct>=100?'success':'primary' ?>" style="width:<?= $pct ?>%"></div>
                  </div>
                  <span style="font-size:11.5px;color:var(--text-muted)"><?= $pct ?>%</span>
                </div>
              </td>
              <td class="text-muted" style="font-size:12px;white-space:nowrap">
                <?= date('d M Y', strtotime($e2['enrolled_at'])) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Change Password -->
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-lock me-2"></i> Change Password</h5>
      </div>
      <div class="card-body p-4">
        <div class="alert alert-info small">
          <i class="bi bi-info-circle me-1"></i>
          Password change form will be enabled in Phase 13 (Student Portal). Contact your administrator to reset your password.
        </div>
      </div>
    </div>
  </div>
</div>

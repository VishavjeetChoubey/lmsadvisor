<?php
use App\Core\View;
$e = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>
<!-- Stat cards row -->
<div class="row g-4 mb-4">
  <?php
  $stats = [
    ['icon'=>'bi-people',        'color'=>'primary', 'label'=>'Total Users',    'value'=>'—', 'sub'=>'Phase 3'],
    ['icon'=>'bi-book',          'color'=>'success', 'label'=>'Total Courses',  'value'=>'—', 'sub'=>'Phase 5'],
    ['icon'=>'bi-person-check',  'color'=>'warning', 'label'=>'Enrollments',    'value'=>'—', 'sub'=>'Phase 7'],
    ['icon'=>'bi-award',         'color'=>'danger',  'label'=>'Certificates',   'value'=>'—', 'sub'=>'Phase 12'],
  ];
  foreach ($stats as $s): ?>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon text-<?= $e($s['color']) ?>">
        <i class="bi <?= $e($s['icon']) ?>"></i>
      </div>
      <div class="stat-body">
        <div class="stat-value"><?= $e($s['value']) ?></div>
        <div class="stat-label"><?= $e($s['label']) ?></div>
      </div>
      <div class="stat-badge text-<?= $e($s['color']) ?>"><?= $e($s['sub']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Phase status card -->
<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-kanban me-2"></i> Build Progress</h5>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0 lms-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Phase</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $phases = [
              [1,  'Project setup — MVC skeleton, router, autoloader', 'done'],
              [2,  'Auth — Login, sessions, reCAPTCHA, lockout',       'done'],
              [3,  'User management — CRUD, roles, impersonation',      'done'],
              [4,  'Settings — 8 tabs, SettingsService',               'done'],
              [5,  'Course engine — Builder, sections, lessons',        'done'],
              [6,  'Quiz builder',                                      'done'],
              [7,  'Enrollment + calendar sync',                       'done'],
              [8,  'Forum',                                             'done'],
              [9,  'Reviews',                                           'done'],
              [10, 'Leaderboard + grade points',                       'done'],
              [11, 'Reporting & analytics',                             'done'],
              [12, 'Certificates (mPDF)',                               'next'],
              [13, 'Student PWA portal',                                'pending'],
            ];
            foreach ($phases as [$n, $label, $status]):
              $badge = match($status) {
                'done'    => '<span class="badge bg-success">✓ Done</span>',
                'next'    => '<span class="badge bg-primary">→ Next</span>',
                default   => '<span class="badge bg-secondary">Pending</span>',
              };
            ?>
            <tr>
              <td class="text-muted fw-semibold"><?= $n ?></td>
              <td><?= htmlspecialchars($label) ?></td>
              <td><?= $badge ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card lms-card h-100">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Phase 1 Complete</h5>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">The MVC foundation is running. Everything below is live:</p>
        <ul class="list-unstyled small">
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Apache + .htaccess clean URLs</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Custom PSR-4 autoloader</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> PDO Database singleton</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Router with :param support</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Request / Response classes</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> View renderer + layouts</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Admin &amp; Student layouts</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Toast notification system</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Session management</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Security headers</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> DB migrations (SQL files)</li>
          <li><i class="bi bi-check-circle-fill text-success me-2"></i> API health endpoint</li>
        </ul>
        <a href="<?= $url('api/v1/health') ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100 mt-2">
          <i class="bi bi-heart-pulse me-1"></i> Check API Health
        </a>
      </div>
    </div>
  </div>
</div>

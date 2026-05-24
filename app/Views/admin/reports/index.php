<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$tabDefs = [
    'overview'    => ['Overview',    'bi-speedometer2'],
    'courses'     => ['Courses',     'bi-book'],
    'users'       => ['Users',       'bi-people'],
    'enrollments' => ['Enrollments', 'bi-person-check'],
    'audit'       => ['Audit Log',   'bi-shield-check'],
];
?>

<!-- Tab nav + export button -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <ul class="nav nav-pills gap-1" id="reportTabs">
    <?php foreach ($tabDefs as $key => [$label, $icon]): ?>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === $key ? 'active' : '' ?>"
         href="<?= $url('admin/reports?tab=' . $key) ?>">
        <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>

  <?php if (in_array($activeTab, ['courses','users','enrollments'], true)): ?>
  <a href="<?= $url('admin/reports/export/' . $activeTab . '?search=' . urlencode($search ?? '')) ?>"
     class="btn btn-sm btn-outline-success">
    <i class="bi bi-download me-1"></i> Export CSV
  </a>
  <?php endif; ?>
</div>

<?php
// ════════════════════════════════════════════════════════════════════════════
// TAB: OVERVIEW
// ════════════════════════════════════════════════════════════════════════════
if ($activeTab === 'overview'):
?>

<!-- KPI cards -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['Users',          $stats['total_users'],       'bi-people',        'primary'],
    ['Courses',        $stats['total_courses'],      'bi-book',          'success'],
    ['Enrollments',    $stats['total_enrollments'],  'bi-person-check',  'info'],
    ['Completions',    $stats['completed'],          'bi-award',         'warning'],
    ['Lessons',        $stats['total_lessons'],      'bi-file-play',     'secondary'],
    ['Quizzes',        $stats['total_quizzes'],      'bi-patch-question','danger'],
    ['Reviews',        $stats['total_reviews'],      'bi-star',          'warning'],
    ['Grade Points',   $stats['total_points'],       'bi-trophy',        'success'],
    ['Forum Threads',  $stats['forum_threads'],      'bi-chat-dots',     'info'],
    ['Avg Rating',     number_format((float)$stats['avg_rating'],1).'★', 'bi-star-half','primary'],
    ['Active Enrolls', $stats['active_enrollments'], 'bi-person-running','success'],
    ['Completion %',   $stats['total_enrollments'] > 0
        ? round($stats['completed'] / $stats['total_enrollments'] * 100) . '%'
        : '—', 'bi-percent', 'primary'],
  ];
  foreach ($kpis as [$label, $value, $icon, $color]):
  ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon text-<?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
      <div class="stat-body">
        <div class="stat-value" style="font-size:18px"><?= is_int($value) ? number_format($value) : $e($value) ?></div>
        <div class="stat-label"><?= $e($label) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Day range selector -->
<div class="d-flex gap-2 mb-4 flex-wrap">
  <span class="text-muted fw-semibold align-self-center" style="font-size:13.5px">Showing last:</span>
  <?php foreach ([7, 14, 30, 60, 90] as $d): ?>
  <a href="<?= $url('admin/reports?tab=overview&days=' . $d) ?>"
     class="btn btn-sm <?= (int)$days === $d ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <?= $d ?>d
  </a>
  <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-4 mb-4">
  <div class="col-12 col-lg-8">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i> Enrollment Trend (<?= $days ?> days)</h5>
      </div>
      <div class="card-body p-3">
        <canvas id="enrollChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-people me-2"></i> New Users (<?= $days ?> days)</h5>
      </div>
      <div class="card-body p-3">
        <canvas id="usersChart" height="240"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12 col-lg-6">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-patch-question me-2"></i> Quiz Attempts (<?= $days ?> days)</h5>
      </div>
      <div class="card-body p-3">
        <canvas id="quizChart" height="140"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i> Enrollment Status</h5>
      </div>
      <div class="card-body d-flex justify-content-center p-3">
        <canvas id="statusChart" style="max-width:260px;max-height:260px"></canvas>
      </div>
    </div>
  </div>
</div>

<?php
// ════════════════════════════════════════════════════════════════════════════
// TAB: COURSES
// ════════════════════════════════════════════════════════════════════════════
elseif ($activeTab === 'courses'):
?>

<?php include __DIR__ . '/_search_bar.php'; ?>

<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-book me-2"></i> Course Report
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <small class="text-muted"><?= number_format(($page-1)*25+1) ?>–<?= number_format(min($page*25,$total)) ?> of <?= number_format($total) ?></small>
  </div>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead>
        <tr>
          <th>Course</th>
          <th>Category</th>
          <th>Status</th>
          <th class="text-center">Enrollments</th>
          <th class="text-center">Completions</th>
          <th class="text-center">Completion%</th>
          <th class="text-center">Rating</th>
          <th class="text-center">Lessons</th>
          <th class="text-center">Avg Quiz</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <?php
          $compRate = $r['enrollments'] > 0
            ? round($r['completions'] / $r['enrollments'] * 100)
            : 0;
          $statusColors = ['draft'=>'secondary','published'=>'success','archived'=>'warning'];
        ?>
        <tr>
          <td>
            <a href="<?= $url('admin/courses/' . $r['slug'] . '/edit') ?>"
               class="fw-semibold text-decoration-none" style="font-size:13px">
              <?= $e(mb_strimwidth($r['title'], 0, 45, '…')) ?>
            </a>
            <div class="text-muted" style="font-size:11px"><?= $e($r['language']) ?> · <?= ucfirst($e($r['level'])) ?></div>
          </td>
          <td class="text-muted" style="font-size:12.5px"><?= $e($r['category'] ?? '—') ?></td>
          <td>
            <span class="badge bg-<?= $statusColors[$r['status']] ?? 'secondary' ?>" style="font-size:11px">
              <?= ucfirst($e($r['status'])) ?>
            </span>
          </td>
          <td class="text-center fw-semibold"><?= number_format((int)$r['enrollments']) ?></td>
          <td class="text-center"><?= number_format((int)$r['completions']) ?></td>
          <td class="text-center">
            <div class="d-flex align-items-center gap-1 justify-content-center">
              <div class="progress" style="width:50px;height:6px;border-radius:3px">
                <div class="progress-bar bg-<?= $compRate >= 70 ? 'success' : ($compRate >= 40 ? 'warning' : 'danger') ?>"
                     style="width:<?= $compRate ?>%"></div>
              </div>
              <span style="font-size:11.5px"><?= $compRate ?>%</span>
            </div>
          </td>
          <td class="text-center">
            <?php if ($r['avg_rating']): ?>
              <span class="text-warning">★</span> <?= $e($r['avg_rating']) ?>
              <span class="text-muted" style="font-size:11px">(<?= $r['review_count'] ?>)</span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="text-center"><?= (int)$r['lessons'] ?></td>
          <td class="text-center">
            <?= $r['avg_quiz_score'] ? $e($r['avg_quiz_score']) . '%' : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/_pagination.php'; ?>
</div>

<?php
// ════════════════════════════════════════════════════════════════════════════
// TAB: USERS
// ════════════════════════════════════════════════════════════════════════════
elseif ($activeTab === 'users'):
?>

<?php include __DIR__ . '/_search_bar.php'; ?>

<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-people me-2"></i> User Report
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <div class="d-flex gap-2 align-items-center">
      <select class="form-select form-select-sm" style="width:auto"
              onchange="window.location.href='<?= $url('admin/reports?tab=users&search=' . urlencode($search)) ?>&role='+this.value">
        <option value="">All Roles</option>
        <?php foreach (['student','manager','admin','super_admin'] as $r): ?>
        <option value="<?= $r ?>" <?= ($roleFilter ?? '') === $r ? 'selected' : '' ?>>
          <?= ucfirst(str_replace('_',' ',$r)) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
          <th>Status</th>
          <th class="text-center">Enrolled</th>
          <th class="text-center">Completed</th>
          <th class="text-center">Points</th>
          <th class="text-center">Forum</th>
          <th class="text-center">Quizzes</th>
          <th>Last Login</th>
          <th>Joined</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="fw-semibold" style="font-size:13px"><?= $e($r['first_name'] . ' ' . $r['last_name']) ?></div>
            <div class="text-muted" style="font-size:11.5px"><?= $e($r['email']) ?></div>
          </td>
          <td><span class="badge bg-secondary" style="font-size:11px"><?= $e($r['role_display']) ?></span></td>
          <td>
            <span class="badge <?= $r['is_active'] ? 'bg-success' : 'bg-danger' ?>" style="font-size:11px">
              <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td class="text-center"><?= (int)$r['enrollments'] ?></td>
          <td class="text-center"><?= (int)$r['completions'] ?></td>
          <td class="text-center fw-semibold text-primary"><?= number_format((int)$r['total_points']) ?></td>
          <td class="text-center"><?= (int)$r['forum_posts'] ?></td>
          <td class="text-center"><?= (int)$r['quiz_attempts'] ?></td>
          <td class="text-muted" style="font-size:12px">
            <?= $r['last_login_at'] ? date('d M Y', strtotime($r['last_login_at'])) : 'Never' ?>
          </td>
          <td class="text-muted" style="font-size:12px"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/_pagination.php'; ?>
</div>

<?php
// ════════════════════════════════════════════════════════════════════════════
// TAB: ENROLLMENTS
// ════════════════════════════════════════════════════════════════════════════
elseif ($activeTab === 'enrollments'):
?>

<?php include __DIR__ . '/_search_bar.php'; ?>

<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-person-check me-2"></i> Enrollment Report
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <select class="form-select form-select-sm" style="width:auto"
            onchange="window.location.href='<?= $url('admin/reports?tab=enrollments&search=' . urlencode($search)) ?>&status='+this.value">
      <option value="">All Status</option>
      <?php foreach (['active','completed','suspended','expired'] as $st): ?>
      <option value="<?= $st ?>" <?= ($statusFilter ?? '') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead>
        <tr>
          <th>Student</th>
          <th>Course</th>
          <th>Status</th>
          <th>Progress</th>
          <th>Enrolled</th>
          <th>Completed</th>
          <th>Expires</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <?php
          $pct = (int)($r['progress_pct'] ?? 0);
          $statusColors = ['active'=>'success','completed'=>'primary','suspended'=>'warning','expired'=>'danger'];
        ?>
        <tr>
          <td>
            <div class="fw-semibold" style="font-size:13px"><?= $e($r['first_name'] . ' ' . $r['last_name']) ?></div>
            <div class="text-muted" style="font-size:11.5px"><?= $e($r['email']) ?></div>
          </td>
          <td style="font-size:13px"><?= $e(mb_strimwidth($r['course_title'], 0, 40, '…')) ?></td>
          <td>
            <span class="badge bg-<?= $statusColors[$r['status']] ?? 'secondary' ?>" style="font-size:11px">
              <?= ucfirst($e($r['status'])) ?>
            </span>
          </td>
          <td style="min-width:120px">
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
                <div class="progress-bar bg-<?= $pct>=100?'success':'primary' ?>" style="width:<?= $pct ?>%"></div>
              </div>
              <span style="font-size:11.5px;color:var(--text-muted)"><?= $pct ?>%</span>
            </div>
          </td>
          <td class="text-muted" style="font-size:12px"><?= date('d M Y', strtotime($r['enrolled_at'])) ?></td>
          <td class="text-muted" style="font-size:12px"><?= $r['completed_at'] ? date('d M Y', strtotime($r['completed_at'])) : '—' ?></td>
          <td class="text-muted" style="font-size:12px">
            <?php if ($r['expires_at']): ?>
              <span class="<?= strtotime($r['expires_at']) < time() ? 'text-danger' : '' ?>">
                <?= date('d M Y', strtotime($r['expires_at'])) ?>
              </span>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/_pagination.php'; ?>
</div>

<?php
// ════════════════════════════════════════════════════════════════════════════
// TAB: AUDIT LOG
// ════════════════════════════════════════════════════════════════════════════
elseif ($activeTab === 'audit'):
?>

<?php include __DIR__ . '/_search_bar.php'; ?>

<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i> Audit Log
      <span class="badge bg-secondary ms-1"><?= number_format($total) ?></span>
    </h5>
    <select class="form-select form-select-sm" style="width:auto"
            onchange="window.location.href='<?= $url('admin/reports?tab=audit&search=' . urlencode($search)) ?>&action='+this.value">
      <option value="">All Actions</option>
      <?php foreach ($actionGroups as $grp): ?>
      <option value="<?= $e($grp) ?>" <?= ($actionFilter ?? '') === $grp ? 'selected' : '' ?>><?= ucfirst($e($grp)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead>
        <tr>
          <th>Action</th>
          <th>User</th>
          <th>Entity</th>
          <th>Changes</th>
          <th>IP</th>
          <th>When</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <?php
          [$prefix] = explode('.', $r['action'] . '.', 2);
          $badgeColor = match($prefix) {
            'user'        => 'primary',
            'course'      => 'success',
            'enrollment'  => 'info',
            'forum'       => 'secondary',
            'review'      => 'warning',
            'leaderboard' => 'warning',
            'settings'    => 'danger',
            default       => 'secondary',
          };
        ?>
        <tr>
          <td>
            <span class="badge bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?>"
                  style="font-size:11px;border-radius:20px;padding:3px 8px">
              <?= $e($r['action']) ?>
            </span>
          </td>
          <td style="font-size:13px">
            <?php if ($r['first_name']): ?>
              <?= $e($r['first_name'] . ' ' . $r['last_name']) ?>
              <div class="text-muted" style="font-size:11px"><?= $e($r['email'] ?? '') ?></div>
            <?php else: ?>
              <span class="text-muted">System</span>
            <?php endif; ?>
          </td>
          <td class="text-muted" style="font-size:12.5px">
            <?= $e($r['entity_type'] ?? '—') ?>
            <?php if ($r['entity_id']): ?>#<?= $r['entity_id'] ?><?php endif; ?>
          </td>
          <td style="font-size:12px;max-width:250px">
            <?php if ($r['new_value']): ?>
              <?php $new = json_decode($r['new_value'], true); ?>
              <span class="text-muted">
                <?= $e(mb_strimwidth(json_encode($new, JSON_UNESCAPED_UNICODE), 0, 80, '…')) ?>
              </span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="text-muted" style="font-size:12px"><?= $e($r['ip_address'] ?? '—') ?></td>
          <td class="text-muted" style="font-size:12px;white-space:nowrap">
            <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/_pagination.php'; ?>
</div>

<?php endif; ?>

<?php if ($activeTab === 'overview'): ?>
<script>
(function () {
  const initCharts = () => {
    if (typeof Chart === 'undefined') return;

    const enrollData  = <?= json_encode(array_column($enrollTrend, 'count')) ?>;
    const enrollDates = <?= json_encode(array_map(fn($r) => date('d M', strtotime($r['date'])), $enrollTrend)) ?>;
    const usersData   = <?= json_encode(array_column($usersTrend, 'count')) ?>;
    const quizData    = <?= json_encode(array_column($quizTrend, 'count')) ?>;
    const quizDates   = <?= json_encode(array_map(fn($r) => date('d M', strtotime($r['date'])), $quizTrend)) ?>;

    const gridColor   = '#e2e8f0';
    const font        = { size: 11 };

    // Enrollment line chart
    new Chart(document.getElementById('enrollChart'), {
      type: 'line',
      data: {
        labels: enrollDates,
        datasets: [{
          label: 'Enrollments',
          data: enrollData,
          borderColor: '#1a56db',
          backgroundColor: 'rgba(26,86,219,.08)',
          fill: true,
          tension: .35,
          pointRadius: 3,
          pointHoverRadius: 5,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: gridColor }, ticks: { font, maxTicksLimit: 10 } },
          y: { grid: { color: gridColor }, ticks: { font, precision: 0 }, beginAtZero: true },
        }
      }
    });

    // Users bar chart
    new Chart(document.getElementById('usersChart'), {
      type: 'bar',
      data: {
        labels: enrollDates,
        datasets: [{
          label: 'New Users',
          data: usersData,
          backgroundColor: '#0e9f6e',
          borderRadius: 4,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font, maxTicksLimit: 6 } },
          y: { grid: { color: gridColor }, ticks: { font, precision: 0 }, beginAtZero: true },
        }
      }
    });

    // Quiz attempts
    new Chart(document.getElementById('quizChart'), {
      type: 'bar',
      data: {
        labels: quizDates,
        datasets: [{
          label: 'Quiz Attempts',
          data: quizData,
          backgroundColor: '#7c3aed',
          borderRadius: 4,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font, maxTicksLimit: 10 } },
          y: { grid: { color: gridColor }, ticks: { font, precision: 0 }, beginAtZero: true },
        }
      }
    });

    // Enrollment status doughnut
    new Chart(document.getElementById('statusChart'), {
      type: 'doughnut',
      data: {
        labels: ['Active', 'Completed', 'Suspended', 'Expired'],
        datasets: [{
          data: [
            <?= (int)($stats['active_enrollments'] ?? 0) ?>,
            <?= (int)($stats['completed'] ?? 0) ?>,
            <?= (int)($stats['suspended'] ?? 0) ?>,
            <?= (int)($stats['expired'] ?? 0) ?>,
          ],
          backgroundColor: ['#0e9f6e','#1a56db','#e3a008','#e02424'],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        cutout: '68%',
        plugins: {
          legend: { position: 'bottom', labels: { font, padding: 14 } },
        }
      }
    });
  };

  if (typeof Chart !== 'undefined') {
    initCharts();
  } else {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
    s.onload = initCharts;
    document.head.appendChild(s);
  }
})();
</script>
<?php endif; ?>

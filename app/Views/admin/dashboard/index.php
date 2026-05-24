<?php
use App\Core\View;
use App\Services\AuthService;
use App\Services\ReportService;
$e        = fn(mixed $v): string => View::e($v);
$url      = fn(string $p = ''): string => View::url($p);
$authUser = AuthService::user();

// Load real stats
$stats = ReportService::overviewStats();
$trend = ReportService::enrollmentsTrend(30);
$usersTrend = ReportService::usersTrend(7);

// Recent enrollments
$pdo = \App\Core\Database::getInstance();
$recentEnrollments = $pdo->query(
  'SELECT e.enrolled_at, u.first_name, u.last_name, c.title AS course_title, e.status
   FROM enrollments e
   JOIN users u ON u.id = e.user_id
   JOIN courses c ON c.id = e.course_id
   ORDER BY e.enrolled_at DESC LIMIT 6'
)->fetchAll();

// Top courses by enrollment
$topCourses = $pdo->query(
  'SELECT c.title, c.uuid, COUNT(e.id) AS count, c.status,
          ROUND(AVG(r.rating),1) AS avg_rating
   FROM courses c
   LEFT JOIN enrollments e ON e.course_id = c.id
   LEFT JOIN course_reviews r ON r.course_id = c.id AND r.is_approved = 1
   WHERE c.status = "published"
   GROUP BY c.id ORDER BY count DESC LIMIT 5'
)->fetchAll();

// Quick actions links
$isAdmin = AuthService::isAdmin() || AuthService::isSuperAdmin();
?>

<!-- Greeting -->
<div class="dash-greeting">
  <div>
    <h3 class="dash-welcome">
      <?php
        $h = (int)date('H');
        echo $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');
      ?>, <?= $e(explode(' ', $authUser['name'] ?? 'Admin')[0]) ?> 👋
    </h3>
    <p class="dash-subtitle">Here's what's happening in your LMS today.</p>
  </div>
  <div class="dash-date">
    <i class="bi bi-calendar3 me-1"></i><?= date('l, d F Y') ?>
  </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['icon'=>'bi-people-fill',     'color'=>'#6366f1','bg'=>'#ebf2ff','label'=>'Total Users',       'value'=>$stats['total_users'],       'sub'=>'Registered learners',      'link'=>'/admin/users'],
    ['icon'=>'bi-book-fill',       'color'=>'#0e9f6e','bg'=>'#d1fae5','label'=>'Published Courses',  'value'=>$stats['total_courses'],      'sub'=>'Active course library',    'link'=>'/admin/courses'],
    ['icon'=>'bi-person-check-fill','color'=>'#1a56db','bg'=>'#dbeafe','label'=>'Total Enrollments', 'value'=>$stats['total_enrollments'],  'sub'=>$stats['active_enrollments'].' active now','link'=>'/admin/enrollments'],
    ['icon'=>'bi-award-fill',      'color'=>'#e3a008','bg'=>'#fef9c3','label'=>'Completions',        'value'=>$stats['completed'],          'sub'=>($stats['total_enrollments']>0 ? round($stats['completed']/$stats['total_enrollments']*100).'% completion rate' : '—'),'link'=>'/admin/enrollments?status=completed'],
    ['icon'=>'bi-star-fill',       'color'=>'#f97316','bg'=>'#ffedd5','label'=>'Avg Rating',         'value'=>$stats['avg_rating'] ? number_format((float)$stats['avg_rating'],1).'★' : '—','sub'=>$stats['total_reviews'].' approved reviews','link'=>'/admin/reviews'],
    ['icon'=>'bi-trophy-fill',     'color'=>'#7c3aed','bg'=>'#ede9fe','label'=>'Grade Points Given', 'value'=>number_format($stats['total_points']),'sub'=>'Total points awarded','link'=>'/admin/leaderboard'],
  ];
  foreach ($kpis as $k):
  ?>
  <div class="col-6 col-lg-4 col-xl-2">
    <a href="<?= $url(ltrim($k['link'],'/')) ?>" class="kpi-card text-decoration-none">
      <div class="kpi-icon" style="background:<?= $k['bg'] ?>;color:<?= $k['color'] ?>">
        <i class="bi <?= $k['icon'] ?>"></i>
      </div>
      <div class="kpi-value"><?= $k['value'] ?></div>
      <div class="kpi-label"><?= $k['label'] ?></div>
      <div class="kpi-sub"><?= $k['sub'] ?></div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts row -->
<div class="row g-4 mb-4">

  <!-- Enrollment trend -->
  <div class="col-12 col-xl-8">
    <div class="card lms-card h-100">
      <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Enrollment Trend <span class="text-muted fw-normal" style="font-size:13px">(last 30 days)</span></h5>
        <a href="<?= $url('admin/reports?tab=overview') ?>" class="btn btn-sm btn-outline-secondary">
          Full Report <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="card-body p-3">
        <canvas id="enrollChart" height="90"></canvas>
      </div>
    </div>
  </div>

  <!-- Enrollment status donut -->
  <div class="col-12 col-xl-4">
    <div class="card lms-card h-100">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-pie-chart me-2 text-success"></i>Enrollment Status</h5>
      </div>
      <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
        <canvas id="statusChart" style="max-width:200px;max-height:200px"></canvas>
        <div class="d-flex flex-wrap gap-3 mt-3 justify-content-center" style="font-size:12.5px">
          <?php foreach ([['Active','#0e9f6e',$stats['active_enrollments']],['Completed','#6366f1',$stats['completed']],['Others','#e3a008',$stats['total_enrollments']-$stats['active_enrollments']-$stats['completed']]] as [$label,$color,$val]): ?>
          <div class="d-flex align-items-center gap-1">
            <span style="width:10px;height:10px;border-radius:50%;background:<?=$color?>;display:inline-block"></span>
            <span class="text-muted"><?=$label?>:</span>
            <span class="fw-semibold"><?=number_format(max(0,$val))?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bottom row -->
<div class="row g-4">

  <!-- Recent enrollments -->
  <div class="col-12 col-lg-7">
    <div class="card lms-card">
      <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-info"></i>Recent Enrollments</h5>
        <a href="<?= $url('admin/enrollments') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <?php if (empty($recentEnrollments)): ?>
        <div class="card-body text-center py-4 text-muted">No enrollments yet.</div>
      <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($recentEnrollments as $r): ?>
        <div class="list-group-item d-flex align-items-center gap-3 py-3">
          <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:13px">
            <?= strtoupper(substr($r['first_name'],0,1)) ?>
          </div>
          <div class="flex-grow-1" style="min-width:0">
            <div class="fw-semibold" style="font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= $e($r['first_name'].' '.$r['last_name']) ?>
            </div>
            <div class="text-muted" style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= $e($r['course_title']) ?>
            </div>
          </div>
          <div class="text-end flex-shrink-0">
            <span class="badge bg-<?= $r['status']==='completed'?'success':($r['status']==='active'?'primary':'secondary') ?>" style="font-size:11px">
              <?= ucfirst($r['status']) ?>
            </span>
            <div class="text-muted mt-1" style="font-size:11px"><?= date('d M', strtotime($r['enrolled_at'])) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top courses + Quick actions -->
  <div class="col-12 col-lg-5">

    <!-- Top courses -->
    <div class="card lms-card mb-4">
      <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-fire me-2 text-warning"></i>Top Courses</h5>
        <a href="<?= $url('admin/courses') ?>" class="btn btn-sm btn-outline-secondary">All Courses</a>
      </div>
      <?php if (empty($topCourses)): ?>
        <div class="card-body text-center py-4 text-muted">No published courses yet.</div>
      <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($topCourses as $i => $c): ?>
        <a href="<?= $url('admin/courses/'.$c['uuid'].'/edit') ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 px-3">
          <span style="width:24px;height:24px;border-radius:50%;background:<?= $i===0?'#fef9c3':($i===1?'#f1f5f9':($i===2?'#ffedd5':'#f8fafc'))?>;color:<?=$i===0?'#ca8a04':($i===1?'#64748b':'#9ca3af')?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0">
            <?= $i+1 ?>
          </span>
          <div class="flex-grow-1" style="min-width:0">
            <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= $e($c['title']) ?>
            </div>
          </div>
          <div class="text-end flex-shrink-0">
            <div class="fw-bold" style="font-size:13px;color:var(--primary)"><?= (int)$c['count'] ?></div>
            <div style="font-size:11px;color:var(--text-muted)">enrolled</div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Quick actions -->
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-lightning-fill me-2 text-warning"></i>Quick Actions</h5>
      </div>
      <div class="card-body p-3">
        <div class="row g-2">
          <?php
          $actions = [
            ['bi-plus-circle','Create Course','admin/courses/create','primary'],
            ['bi-person-plus','Add User','admin/users/create','success'],
            ['bi-person-check','Enroll Student','admin/enrollments','info'],
            ['bi-bar-chart-line','View Reports','admin/reports','warning'],
            ['bi-star','Reviews','admin/reviews?status=pending','danger'],
            ['bi-trophy','Leaderboard','admin/leaderboard','secondary'],
          ];
          foreach ($actions as [$icon,$label,$link,$color]):
          ?>
          <div class="col-6">
            <a href="<?= $url($link) ?>"
               class="quick-action-btn text-decoration-none d-flex align-items-center gap-2 p-3 rounded-3 border"
               style="background:var(--content-bg);transition:all .15s;font-size:13px;font-weight:600;color:var(--text-primary)">
              <i class="bi <?= $icon ?> text-<?= $color ?>" style="font-size:16px"></i>
              <?= $label ?>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* ── Dashboard styles ──────────────────────────────────────────────────────── */
.dash-greeting {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 24px; flex-wrap: wrap; gap: 8px;
}
.dash-welcome { font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 0; }
.dash-subtitle { color: var(--text-muted); font-size: 14px; margin: 4px 0 0; }
.dash-date { font-size: 13.5px; color: var(--text-muted); background: var(--content-bg); padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border-color); }

/* KPI Cards */
.kpi-card {
  display: flex; flex-direction: column; align-items: flex-start;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 16px; padding: 18px 16px;
  transition: transform .2s, box-shadow .2s;
  height: 100%;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.kpi-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; margin-bottom: 12px;
}
.kpi-value { font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; margin-bottom: 4px; }
.kpi-label { font-size: 12.5px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.kpi-sub   { font-size: 11.5px; color: var(--text-muted); }

/* Quick action hover */
.quick-action-btn:hover { border-color: var(--primary) !important; background: #ebf2ff !important; }
</style>

<script>
(function() {
  const loadChart = () => {
    if (typeof Chart === 'undefined') {
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
      s.onload = renderCharts;
      document.head.appendChild(s);
    } else renderCharts();
  };

  function renderCharts() {
    const grid = '#e2e8f0';
    const font = { size: 11 };

    // Enrollment trend
    const trend   = <?= json_encode(array_column($trend, 'count')) ?>;
    const labels  = <?= json_encode(array_map(fn($r) => date('d M', strtotime($r['date'])), $trend)) ?>;
    new Chart(document.getElementById('enrollChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Enrollments',
          data: trend,
          borderColor: '#6366f1',
          backgroundColor: 'rgba(99,102,241,.08)',
          fill: true,
          tension: .4,
          pointRadius: 3,
          pointHoverRadius: 5,
          pointBackgroundColor: '#6366f1',
          borderWidth: 2.5,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid:{ color:grid }, ticks:{ font, maxTicksLimit:10 } },
          y: { grid:{ color:grid }, ticks:{ font, precision:0 }, beginAtZero:true },
        }
      }
    });

    // Status donut
    new Chart(document.getElementById('statusChart'), {
      type: 'doughnut',
      data: {
        labels: ['Active','Completed','Other'],
        datasets: [{
          data: [<?= (int)$stats['active_enrollments'] ?>,<?= (int)$stats['completed'] ?>,<?= max(0,(int)$stats['total_enrollments']-(int)$stats['active_enrollments']-(int)$stats['completed']) ?>],
          backgroundColor: ['#0e9f6e','#6366f1','#e3a008'],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        cutout: '70%',
        plugins: { legend:{ display:false } },
      }
    });
  }

  document.addEventListener('DOMContentLoaded', loadChart);
})();
</script>

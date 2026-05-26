<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$totalPv      = (int)($overview['total_pageviews'] ?? 0);
$uniqueVis    = (int)($overview['unique_visitors'] ?? 0);
$uniqueIps    = (int)($overview['unique_ips'] ?? 0);
$avgDur       = (int)($overview['avg_duration'] ?? 0);
$avgDurFmt    = $avgDur > 0 ? gmdate('i:s', $avgDur) : '—';

// Trend data for chart
$trendDates    = array_column($overview['trend'] ?? [], 'd');
$trendVis      = array_column($overview['trend'] ?? [], 'visitors');
$trendPv       = array_column($overview['trend'] ?? [], 'pageviews');

// Device totals
$totalDevices  = max(1, array_sum(array_column($devices, 'visitors')));
$deviceColors  = ['desktop'=>'#5b5ef6','mobile'=>'#12b76a','tablet'=>'#f79009','bot'=>'#e5e7eb','unknown'=>'#d1d5db'];

// Country flag emoji helper
$flagEmoji = function(string $cc): string {
    if (!$cc || strlen($cc) !== 2) return '🌍';
    $offset = 127397;
    $chars  = mb_strtoupper($cc);
    return mb_convert_encoding('&#' . (ord($chars[0]) + $offset) . ';&#' . (ord($chars[1]) + $offset) . ';', 'UTF-8', 'HTML-ENTITIES');
};

// Heatmap grid
$heatGrid = [];
foreach ($heatmap as $row) {
    $heatGrid[$row['dow']][$row['hr']] = (int)$row['visitors'];
}
$maxHeat = 1;
foreach ($heatGrid as $day) foreach ($day as $v) if ($v > $maxHeat) $maxHeat = $v;
$days_labels = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
?>

<!-- Period selector -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
  <div class="d-flex gap-2">
    <?php foreach ([7=>'7d',30=>'30d',60=>'60d',90=>'90d'] as $d => $lbl): ?>
    <a href="?days=<?= $d ?>"
       class="btn btn-sm <?= $days === $d ? 'btn-primary' : 'btn-outline-secondary' ?>"
       style="border-radius:8px;font-weight:600">
      <?= $lbl ?>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="d-flex align-items-center gap-3">
    <div class="badge bg-success-subtle text-success px-3 py-2" style="font-size:12px">
      <i class="bi bi-shield-check me-1"></i>SOC2 Compliant — No raw IPs or PII stored
    </div>
    <button class="btn btn-sm btn-outline-danger" id="purgeBtn">
      <i class="bi bi-trash3 me-1"></i>Purge Old Data
    </button>
  </div>
</div>

<!-- ── KPI Cards ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['Page Views',       $totalPv,                   'bi-eye-fill',           '#5b5ef6','#ededff'],
    ['Unique Visitors',  $uniqueVis,                  'bi-people-fill',        '#059669','#ecfdf5'],
    ['Unique IPs',       $uniqueIps,                  'bi-geo-alt-fill',       '#d97706','#fffbeb'],
    ['Avg. Time on Page',$avgDurFmt,                  'bi-clock-fill',         '#2563eb','#eff6ff'],
  ];
  foreach ($kpis as [$label, $val, $icon, $color, $bg]):
  ?>
  <div class="col-6 col-lg-3">
    <div class="card lms-card h-100" style="border-radius:16px">
      <div class="card-body p-4 d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:14px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi <?= $icon ?>" style="font-size:20px;color:<?= $color ?>"></i>
        </div>
        <div>
          <div style="font-size:26px;font-weight:800;color:var(--text-primary);line-height:1"><?= is_int($val) ? number_format($val) : $val ?></div>
          <div style="font-size:12.5px;font-weight:600;color:var(--text-muted);margin-top:3px"><?= $label ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Visitor Trend Chart ────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-12 col-xl-8">
    <div class="card lms-card" style="border-radius:16px">
      <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Visitor Trend <span class="text-muted fw-normal" style="font-size:13px">(last <?= $days ?> days)</span></h5>
      </div>
      <div class="card-body p-3" style="height:260px">
        <canvas id="trendChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Devices Donut -->
  <div class="col-12 col-xl-4">
    <div class="card lms-card" style="border-radius:16px;height:100%">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-laptop me-2 text-success"></i>Devices</h5>
      </div>
      <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
        <?php if (empty($devices)): ?>
          <p class="text-muted">No data yet</p>
        <?php else: ?>
        <canvas id="deviceChart" style="max-width:180px;max-height:180px"></canvas>
        <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
          <?php foreach ($devices as $d): ?>
          <div class="d-flex align-items-center gap-1" style="font-size:12.5px">
            <span style="width:9px;height:9px;border-radius:50%;background:<?= $deviceColors[$d['device_type']] ?? '#ccc' ?>;display:inline-block"></span>
            <span style="text-transform:capitalize"><?= $e($d['device_type']) ?></span>
            <strong><?= number_format((int)$d['visitors']) ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Countries + Browsers + User Roles ─────────────────────────────────── -->
<div class="row g-4 mb-4">

  <!-- Countries -->
  <div class="col-12 col-lg-5">
    <div class="card lms-card" style="border-radius:16px;height:100%">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-globe2 me-2 text-warning"></i>Top Countries</h5>
      </div>
      <?php if (empty($countries)): ?>
        <div class="card-body text-center py-4 text-muted">No geo data yet (visits from local/private IPs are excluded)</div>
      <?php else: ?>
      <div class="list-group list-group-flush">
        <?php
        $maxCountry = max(1, (int)($countries[0]['visitors'] ?? 1));
        foreach ($countries as $c):
          $pct = round((int)$c['visitors'] / $maxCountry * 100);
        ?>
        <div class="list-group-item py-2 px-4">
          <div class="d-flex align-items-center gap-3">
            <span style="font-size:20px"><?= $flagEmoji($c['country_code']) ?></span>
            <div class="flex-grow-1" style="min-width:0">
              <div class="d-flex justify-content-between mb-1">
                <span style="font-size:13px;font-weight:600"><?= $e($c['country_name']) ?></span>
                <span style="font-size:12.5px;font-weight:700;color:var(--primary)"><?= number_format((int)$c['visitors']) ?></span>
              </div>
              <div style="height:4px;background:var(--border-color);border-radius:2px">
                <div style="height:100%;width:<?= $pct ?>%;background:var(--primary);border-radius:2px"></div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Browsers + Roles -->
  <div class="col-12 col-lg-7">
    <div class="row g-4 h-100">

      <!-- Browsers -->
      <div class="col-12 col-md-6">
        <div class="card lms-card" style="border-radius:16px;height:100%">
          <div class="card-header lms-card-header">
            <h5 class="mb-0"><i class="bi bi-browser-safari me-2 text-info"></i>Browsers</h5>
          </div>
          <?php if (empty($browsers)): ?>
            <div class="card-body text-center py-4 text-muted">No data yet</div>
          <?php else: ?>
          <div class="card-body p-3">
            <canvas id="browserChart" style="max-height:220px"></canvas>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- User types -->
      <div class="col-12 col-md-6">
        <div class="card lms-card" style="border-radius:16px;height:100%">
          <div class="card-header lms-card-header">
            <h5 class="mb-0"><i class="bi bi-person-badge me-2" style="color:#7c3aed"></i>User Types</h5>
          </div>
          <div class="card-body p-3">
            <?php if (empty($roles)): ?>
              <p class="text-muted text-center py-3">No data yet</p>
            <?php else: ?>
            <?php
            $totalRoles = max(1, array_sum(array_column($roles, 'visitors')));
            $roleColors = ['admin'=>'#5b5ef6','super_admin'=>'#7c3aed','manager'=>'#2563eb','student'=>'#059669','guest'=>'#9ca3af'];
            foreach ($roles as $r):
              $rpct = round($r['visitors'] / $totalRoles * 100);
            ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span style="font-size:13px;font-weight:600;text-transform:capitalize"><?= $e(str_replace('_',' ',$r['user_role'])) ?></span>
                <span style="font-size:12.5px;font-weight:700"><?= number_format((int)$r['visitors']) ?> <span style="color:var(--text-muted)">(<?= $rpct ?>%)</span></span>
              </div>
              <div style="height:7px;background:var(--border-color);border-radius:4px">
                <div style="height:100%;width:<?= $rpct ?>%;background:<?= $roleColors[$r['user_role']] ?? '#9ca3af' ?>;border-radius:4px"></div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Events -->
      <div class="col-12">
        <div class="card lms-card" style="border-radius:16px">
          <div class="card-header lms-card-header">
            <h5 class="mb-0"><i class="bi bi-lightning-fill me-2 text-warning"></i>LMS Events</h5>
          </div>
          <div class="card-body p-3">
            <?php if (empty($events)): ?>
              <p class="text-muted text-center">No events tracked yet</p>
            <?php else: ?>
            <div class="d-flex flex-wrap gap-2">
              <?php
              $evColors = ['login'=>'primary','logout'=>'secondary','enroll'=>'success',
                          'complete'=>'warning','quiz_pass'=>'success','quiz_fail'=>'danger',
                          'video_play'=>'info'];
              foreach ($events as $ev):
                $ec = $evColors[$ev['event_type']] ?? 'secondary';
              ?>
              <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3"
                   style="background:var(--content-bg);border:1px solid var(--border-color)">
                <span class="badge bg-<?= $ec ?>" style="font-size:10.5px"><?= $e($ev['event_type']) ?></span>
                <span style="font-size:14px;font-weight:800"><?= number_format((int)$ev['total']) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Heatmap ────────────────────────────────────────────────────────────── -->
<div class="card lms-card mb-4" style="border-radius:16px">
  <div class="card-header lms-card-header">
    <h5 class="mb-0"><i class="bi bi-calendar-week me-2" style="color:#db2777"></i>Activity Heatmap <span class="text-muted fw-normal" style="font-size:13px">(visitors by day &amp; hour)</span></h5>
  </div>
  <div class="card-body p-4" style="overflow-x:auto">
    <div style="display:grid;grid-template-columns:40px repeat(24,1fr);gap:3px;min-width:700px">
      <!-- Header row: hours -->
      <div></div>
      <?php for ($h = 0; $h < 24; $h++): ?>
        <div style="text-align:center;font-size:10px;color:var(--text-muted);font-weight:600">
          <?= $h === 0 ? '12a' : ($h < 12 ? $h . 'a' : ($h === 12 ? '12p' : ($h-12) . 'p')) ?>
        </div>
      <?php endfor; ?>
      <!-- Data rows: days -->
      <?php for ($d = 0; $d < 7; $d++): ?>
        <div style="font-size:11px;font-weight:600;color:var(--text-muted);display:flex;align-items:center;padding-right:4px">
          <?= $days_labels[$d] ?>
        </div>
        <?php for ($h = 0; $h < 24; $h++):
          $v    = $heatGrid[$d][$h] ?? 0;
          $intens = $maxHeat > 0 ? $v / $maxHeat : 0;
          $alpha = round($intens * 0.85 + ($v > 0 ? 0.1 : 0), 2);
          $bg   = $v > 0 ? "rgba(91,94,246,{$alpha})" : 'var(--border-color)';
          $tc   = $intens > 0.5 ? '#fff' : ($v > 0 ? '#5b5ef6' : 'transparent');
        ?>
        <div style="height:28px;border-radius:4px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;cursor:default"
             title="<?= $days_labels[$d] ?> <?= $h ?>:00 — <?= $v ?> visitors">
          <?php if ($v > 0): ?>
            <span style="font-size:9px;font-weight:700;color:<?= $tc ?>"><?= $v ?></span>
          <?php endif; ?>
        </div>
        <?php endfor; ?>
      <?php endfor; ?>
    </div>
    <div class="d-flex align-items-center gap-2 mt-3" style="font-size:11px;color:var(--text-muted)">
      <span>Less</span>
      <?php foreach ([0.05,0.2,0.4,0.65,0.85] as $a): ?>
      <div style="width:14px;height:14px;border-radius:3px;background:rgba(91,94,246,<?= $a ?>)"></div>
      <?php endforeach; ?>
      <span>More</span>
    </div>
  </div>
</div>

<!-- ── Top Pages ─────────────────────────────────────────────────────────── -->
<div class="card lms-card" style="border-radius:16px">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2 text-secondary"></i>Top Pages</h5>
  </div>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead><tr>
        <th>Path</th><th>Page Views</th><th>Unique Visitors</th><th style="width:160px">Traffic</th>
      </tr></thead>
      <tbody>
        <?php if (empty($top_pages)): ?>
        <tr><td colspan="4" class="text-center py-4 text-muted">No page view data yet. Traffic will appear here after the first visit.</td></tr>
        <?php else: ?>
        <?php
        $maxPv = max(1, (int)($top_pages[0]['pageviews'] ?? 1));
        foreach ($top_pages as $pg):
          $ppct = round($pg['pageviews'] / $maxPv * 100);
        ?>
        <tr>
          <td>
            <div style="font-family:monospace;font-size:13px;font-weight:600"><?= $e($pg['path']) ?></div>
            <?php if ($pg['page_title']): ?><div style="font-size:11.5px;color:var(--text-muted)"><?= $e($pg['page_title']) ?></div><?php endif; ?>
          </td>
          <td style="font-weight:700"><?= number_format((int)$pg['pageviews']) ?></td>
          <td><?= number_format((int)$pg['unique_visitors']) ?></td>
          <td>
            <div style="height:6px;background:var(--border-color);border-radius:3px">
              <div style="height:100%;width:<?= $ppct ?>%;background:var(--primary);border-radius:3px"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- SOC2 notice -->
<div class="mt-4 p-3 rounded-3 d-flex align-items-start gap-3" style="background:#f0fdf4;border:1px solid #bbf7d0">
  <i class="bi bi-shield-check text-success mt-1" style="font-size:1.1rem;flex-shrink:0"></i>
  <div style="font-size:13px;color:#166534">
    <strong>SOC2 Privacy Compliance:</strong>
    All visitor IP addresses are stored as SHA-256 hashes — the raw IP is never written to disk.
    No user names, emails, or user IDs are stored in analytics tables.
    Geographic data is limited to country and city.
    Data is automatically purged after <strong><?= (int)\App\Models\Setting::get('analytics_retention_days', 90) ?> days</strong> per your retention policy.
  </div>
</div>

<script>
// ── Charts ──────────────────────────────────────────────────────────────────
(function() {
  function loadChartJs(cb) {
    if (typeof Chart !== 'undefined') { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
    s.onload = cb;
    document.head.appendChild(s);
  }

  loadChartJs(function() {
    var gridColor = '#e2e8f0';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6b7280';

    // Trend chart
    var trendDates = <?= json_encode($trendDates) ?>;
    var trendVis   = <?= json_encode($trendVis) ?>;
    var trendPv    = <?= json_encode($trendPv) ?>;

    if (document.getElementById('trendChart') && trendDates.length) {
      new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
          labels: trendDates,
          datasets: [
            {
              label: 'Unique Visitors',
              data: trendVis,
              borderColor: '#5b5ef6', backgroundColor: 'rgba(91,94,246,.08)',
              fill: true, tension: .4, pointRadius: 2, borderWidth: 2.5,
            },
            {
              label: 'Page Views',
              data: trendPv,
              borderColor: '#12b76a', backgroundColor: 'rgba(18,183,106,.06)',
              fill: true, tension: .4, pointRadius: 2, borderWidth: 2, borderDash: [4,3],
            },
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 16 } } },
          scales: {
            x: { grid: { color: gridColor }, ticks: { maxTicksLimit: 10 } },
            y: { grid: { color: gridColor }, beginAtZero: true, ticks: { precision: 0 } },
          }
        }
      });
    }

    // Device donut
    var devices = <?= json_encode(array_map(fn($d) => ['label'=>ucfirst($d['device_type']),'val'=>(int)$d['visitors']], $devices)) ?>;
    var devColors = ['#5b5ef6','#12b76a','#f79009','#e5e7eb','#d1d5db'];
    if (document.getElementById('deviceChart') && devices.length) {
      new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
          labels: devices.map(function(d){ return d.label; }),
          datasets: [{ data: devices.map(function(d){ return d.val; }), backgroundColor: devColors, borderWidth: 0 }]
        },
        options: { cutout: '68%', plugins: { legend: { display: false } } }
      });
    }

    // Browsers bar
    var browsers = <?= json_encode(array_map(fn($b) => ['label'=>$b['browser'],'val'=>(int)$b['visitors']], $browsers)) ?>;
    if (document.getElementById('browserChart') && browsers.length) {
      new Chart(document.getElementById('browserChart'), {
        type: 'bar',
        data: {
          labels: browsers.map(function(b){ return b.label; }),
          datasets: [{
            label: 'Visitors',
            data: browsers.map(function(b){ return b.val; }),
            backgroundColor: '#5b5ef6', borderRadius: 6, borderSkipped: false,
          }]
        },
        options: {
          indexAxis: 'y', responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { x: { grid: { color: gridColor }, beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }
        }
      });
    }
  });

  // Purge button
  document.getElementById('purgeBtn')?.addEventListener('click', function() {
    var BASE = (window.LMS && window.LMS.BASE) || '';
    var CSRF = '<?= $e($csrf_token) ?>';
    if (!confirm('Purge analytics data older than the retention period?')) return;
    this.disabled = true;
    this.textContent = 'Purging…';
    fetch(BASE + '/admin/analytics/purge', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF),
    }).then(function(r){ return r.json(); }).then(function(d) {
      LMS.toast('success', 'Purged ' + d.purged + ' old records.');
      setTimeout(function(){ location.reload(); }, 1000);
    }).catch(function() {
      document.getElementById('purgeBtn').disabled = false;
      document.getElementById('purgeBtn').innerHTML = '<i class="bi bi-trash3 me-1"></i>Purge Old Data';
    });
  });
})();
</script>

<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>

<div class="adm-page-header mb-4">
  <h2 class="adm-page-title">📊 Executive Reports</h2>
  <p class="adm-page-sub">Platform-wide analytics — enrollments, completions, cohort retention, student LTV.</p>
</div>

<!-- ── KPI cards ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
<?php $kpiCards = [
  ['bi-people-fill',    'Total Students',       number_format($kpis['total_users']),          '#6366f1'],
  ['bi-person-plus',    'New (30d)',             '+'.number_format($kpis['new_users_30d']),    '#059669'],
  ['bi-journal-check',  'Enrollments',          number_format($kpis['total_enrollments']),    '#0891b2'],
  ['bi-check-circle',   'Completions',          number_format($kpis['total_completions']),    '#7c3aed'],
  ['bi-percent',        'Completion Rate',       $kpis['completion_rate'].'%',                '#d97706'],
  ['bi-bar-chart-line', 'Avg Progress',          round((float)$kpis['avg_progress'],1).'%',   '#be185d'],
  ['bi-lightning',      'Active (7d)',            number_format($kpis['active_learners_7d']),  '#0f766e'],
  ['bi-award',          'Certificates Issued',   number_format($kpis['certificates_issued']), '#b45309'],
];
foreach($kpiCards as [$ico,$lbl,$val,$clr]): ?>
<div class="col-6 col-xl-3">
  <div class="card border-0 shadow-sm h-100">
    <div class="card-body d-flex align-items-center gap-3 py-3">
      <div style="width:40px;height:40px;border-radius:12px;background:<?=$clr?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="bi <?=$ico?>" style="color:<?=$clr?>;font-size:17px"></i>
      </div>
      <div>
        <div style="font-size:19px;font-weight:800;color:var(--bs-body-color);line-height:1"><?=$e((string)$val)?></div>
        <div class="text-muted" style="font-size:11.5px;margin-top:2px"><?=$e($lbl)?></div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- ── Charts row ────────────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
        <span class="fw-bold"><i class="bi bi-graph-up me-2"></i>Enrollment & Completion Trend (60 days)</span>
      </div>
      <div class="card-body">
        <canvas id="trendChart" height="220"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-diagram-3 me-2"></i>Category Breakdown</div>
      <div class="card-body">
        <canvas id="categoryChart" height="220"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ── Cohort Retention ───────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-transparent fw-bold py-3">
    <i class="bi bi-people me-2"></i>Cohort Retention
    <span class="text-muted fw-normal" style="font-size:13px;margin-left:6px">% of monthly cohort still active after 1, 2, 3 months</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:13.5px">
      <thead class="table-light">
        <tr>
          <th>Cohort</th><th>Size</th>
          <th>Month 0</th><th>Month 1</th><th>Month 2</th><th>Month 3</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($cohorts)): ?>
        <tr><td colspan="6" class="text-center py-4 text-muted">Not enough data yet — check back after students have been active for 1+ months.</td></tr>
        <?php else: foreach($cohorts as $c): ?>
        <tr>
          <td class="fw-semibold"><?=$e($c['cohort'])?></td>
          <td><?=number_format((int)$c['size'])?></td>
          <?php foreach($c['retention'] as $idx=>$pct):
            $clr = $pct>=70?'#059669':($pct>=40?'#d97706':'#dc2626');
            $bg  = $pct>=70?'#ecfdf5':($pct>=40?'#fffbeb':'#fef2f2');
          ?>
          <td>
            <span style="background:<?=$bg?>;color:<?=$clr?>;padding:3px 10px;border-radius:20px;font-weight:700;font-size:12px">
              <?=$pct?>%
            </span>
          </td>
          <?php endforeach;
          // Pad missing months
          for($pad=count($c['retention']);$pad<4;$pad++): ?>
          <td><span class="text-muted">—</span></td>
          <?php endfor; ?>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Top Courses + LTV ──────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">
  <!-- Top courses -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-trophy me-2"></i>Top Courses by Enrollment</div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:13px">
          <thead class="table-light"><tr><th>#</th><th>Course</th><th>Level</th><th>Enrolled</th><th>Completion %</th><th>Rating</th></tr></thead>
          <tbody>
            <?php if(empty($topCourses)): ?>
            <tr><td colspan="6" class="text-center py-3 text-muted">No data yet.</td></tr>
            <?php else: foreach($topCourses as $i=>$c):
              $cr = (float)$c['completion_rate'];
              $crClr = $cr>=70?'#059669':($cr>=40?'#d97706':'#dc2626');
            ?>
            <tr>
              <td class="text-muted fw-bold"><?=$i+1?></td>
              <td>
                <a href="<?=$url('admin/courses/'.$c['uuid'].'/edit')?>" style="font-weight:600;text-decoration:none;font-size:13px">
                  <?=$e(mb_substr($c['title'],0,50))?>
                </a>
              </td>
              <td><span class="badge bg-secondary-subtle text-secondary" style="font-size:11px"><?=ucfirst($e($c['level']??''))?></span></td>
              <td class="fw-bold"><?=number_format((int)$c['enrollments'])?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div style="flex:1;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden">
                    <div style="width:<?=min(100,$cr)?>%;height:100%;background:<?=$crClr?>;border-radius:3px"></div>
                  </div>
                  <span style="font-size:12px;font-weight:700;color:<?=$crClr?>;white-space:nowrap"><?=$cr?>%</span>
                </div>
              </td>
              <td>
                <?php if((float)$c['avg_rating']>0): ?>
                <span style="color:#d97706;font-weight:700;font-size:12px">★ <?=number_format((float)$c['avg_rating'],1)?></span>
                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Student LTV -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-person-hearts me-2"></i>Student Lifetime Value</div>
      <div class="card-body">
        <?php $ltvItems=[
          ['bi-journals','Avg Courses per Student',     number_format((float)$ltv['avg_courses_per_student'],1)],
          ['bi-check-all','Avg Completions per Student',number_format((float)$ltv['avg_completion_per_student'],1)],
          ['bi-trophy','Avg Grade Points Earned',       number_format((float)$ltv['avg_grade_points'],0).'pts'],
          ['bi-lightning-fill','Power Learners (3+ completions)',(int)$ltv['power_learners'].' students'],
        ];
        foreach($ltvItems as [$ico,$lbl,$val]): ?>
        <div class="d-flex align-items-center gap-3 py-2 border-bottom" style="font-size:13.5px">
          <i class="bi <?=$ico?>" style="color:#6366f1;font-size:16px;width:20px;flex-shrink:0"></i>
          <span style="flex:1;color:var(--bs-secondary-color)"><?=$e($lbl)?></span>
          <strong><?=$e((string)$val)?></strong>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Category breakdown -->
    <?php if(!empty($categories)): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-tags me-2"></i>By Category</div>
      <div class="card-body p-0">
        <?php foreach(array_slice($categories,0,5) as $cat): ?>
        <div style="padding:10px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px">
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600"><?=$e($cat['category']??'Uncategorised')?></div>
            <div style="font-size:11.5px;color:var(--bs-secondary-color)"><?=(int)$cat['courses']?> courses · <?=(int)$cat['enrollments']?> enrollments</div>
          </div>
          <span style="font-size:12px;font-weight:700;color:#059669;white-space:nowrap"><?=(float)$cat['completion_rate']?>%</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
  // ── Trend chart ────────────────────────────────────────────────────────────
  var enrollData = <?=json_encode($enrollment_trend)?>;
  var compData   = <?=json_encode($completion_trend)?>;

  // Build date range for last 60 days
  var labels = [], enMap = {}, compMap = {};
  enrollData.forEach(function(d) { enMap[d.date] = d.count; });
  compData.forEach(function(d)   { compMap[d.date] = d.count; });

  for (var i=59; i>=0; i--) {
    var d = new Date(); d.setDate(d.getDate()-i);
    var key = d.toISOString().slice(0,10);
    labels.push(d.toLocaleDateString('en-GB',{day:'2-digit',month:'short'}));
    enMap[key] = enMap[key] || 0;
    compMap[key] = compMap[key] || 0;
  }
  var dateKeys = Object.keys(enMap).sort();

  new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        { label:'Enrollments', data: dateKeys.map(k=>enMap[k]||0),
          borderColor:'#6366f1', backgroundColor:'rgba(99,102,241,.08)',
          fill:true, tension:.4, pointRadius:0, borderWidth:2 },
        { label:'Completions', data: dateKeys.map(k=>compMap[k]||0),
          borderColor:'#059669', backgroundColor:'rgba(5,150,105,.06)',
          fill:true, tension:.4, pointRadius:0, borderWidth:2 }
      ]
    },
    options: {
      responsive:true, interaction:{mode:'index',intersect:false},
      plugins:{ legend:{position:'top',labels:{font:{size:12}}} },
      scales:{
        x:{ grid:{display:false}, ticks:{font:{size:11}, maxTicksLimit:10} },
        y:{ beginAtZero:true, grid:{color:'rgba(0,0,0,.05)'}, ticks:{font:{size:11}} }
      }
    }
  });

  // ── Category doughnut ──────────────────────────────────────────────────────
  var catData = <?=json_encode($categories)?>;
  if (catData.length) {
    new Chart(document.getElementById('categoryChart'), {
      type: 'doughnut',
      data: {
        labels: catData.map(c => c.category || 'Other'),
        datasets: [{
          data: catData.map(c => parseInt(c.enrollments)||0),
          backgroundColor: ['#6366f1','#059669','#0891b2','#d97706','#dc2626','#7c3aed','#be185d'],
          borderWidth: 2, borderColor: '#fff',
        }]
      },
      options: {
        responsive:true, cutout:'65%',
        plugins:{ legend:{position:'bottom',labels:{font:{size:11},padding:10}} }
      }
    });
  }
})();
</script>

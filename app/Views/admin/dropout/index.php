<?php use App\Core\View; $e=$v=fn($x)=>View::e($x); $url=fn($p='')=>View::url($p); ?>

<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">⚠️ Drop-out Predictor</h2>
    <p class="adm-page-sub">Students at risk of abandoning their course before completion.</p>
  </div>
  <div class="d-flex gap-2">
    <!-- Filter -->
    <div class="btn-group">
      <?php foreach(['medium'=>'All Risk','high'=>'High+','critical'=>'Critical'] as $val=>$lbl): ?>
      <a href="?level=<?=$val?>" class="btn btn-sm btn-<?=$level===$val?'primary':'outline-secondary'?>">
        <?=$lbl?>
      </a>
      <?php endforeach; ?>
    </div>
    <!-- Recalculate -->
    <form method="POST" action="<?=$url('admin/dropout/recalculate')?>">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(\App\Middleware\CsrfMiddleware::token())?>">
      <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-clockwise me-1"></i>Recalculate</button>
    </form>
  </div>
</div>

<?php if($flash): ?>
<div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-4"><?=$e($flash['message'])?></div>
<?php endif; ?>

<!-- Summary cards -->
<div class="row g-3 mb-4">
  <?php
  $counts = ['critical'=>0,'high'=>0,'medium'=>0,'low'=>0];
  foreach($atRisk as $r) { if(isset($counts[$r['risk_level']])) $counts[$r['risk_level']]++; }
  $cards = [
    ['critical','🔴','Critical',    '#fef2f2','#b91c1c'],
    ['high',    '🟠','High Risk',   '#fff7ed','#c2410c'],
    ['medium',  '🟡','Medium Risk', '#fffbeb','#b45309'],
  ];
  foreach($cards as [$key,$icon,$lbl,$bg,$col]): ?>
  <div class="col-4">
    <div class="card border-0 shadow-sm" style="background:<?=$bg?>">
      <div class="card-body text-center py-3">
        <div style="font-size:28px"><?=$icon?></div>
        <div style="font-size:22px;font-weight:800;color:<?=$col?>"><?=$counts[$key]?></div>
        <div style="font-size:12px;color:<?=$col?>"><?=$lbl?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- At-risk students table -->
<?php if(empty($atRisk)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5">
    <div style="font-size:3rem">✅</div>
    <div class="fw-bold mt-2">No students at risk</div>
    <div class="text-muted">Click Recalculate to refresh scores.</div>
  </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:13.5px">
      <thead class="table-light">
        <tr>
          <th>Student</th>
          <th>Course</th>
          <th style="width:80px">Risk</th>
          <th style="width:80px">Score</th>
          <th>Factors</th>
          <th style="width:80px">Alert</th>
          <th style="width:130px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($atRisk as $r):
          $riskColors = ['critical'=>['#fef2f2','#b91c1c'],'high'=>['#fff7ed','#c2410c'],'medium'=>['#fffbeb','#b45309'],'low'=>['#f0fdf4','#166534']];
          [$rbg,$rcl] = $riskColors[$r['risk_level']] ?? ['#f3f4f6','#374151'];
        ?>
        <tr>
          <td>
            <div class="fw-semibold"><?=$e($r['first_name'].' '.$r['last_name'])?></div>
            <div style="font-size:12px;color:var(--bs-secondary-color)"><?=$e($r['email'])?></div>
          </td>
          <td>
            <a href="<?=$url('admin/courses')?>" style="font-size:13px;text-decoration:none">
              <?=$e($r['course_title'])?>
            </a>
          </td>
          <td>
            <span class="badge" style="background:<?=$rbg?>;color:<?=$rcl?>;border:1px solid <?=$rcl?>30">
              <?=ucfirst($e($r['risk_level']))?>
            </span>
          </td>
          <td>
            <div style="font-weight:700;color:<?=$rcl?>"><?=number_format((float)$r['risk_score'],0)?>%</div>
          </td>
          <td>
            <div style="font-size:12px;color:var(--bs-secondary-color)">
              <?php foreach($r['factors']['alerts'] ?? [] as $alert): ?>
                <div>• <?=$e($alert)?></div>
              <?php endforeach; ?>
              <?php if(isset($r['factors']['days_since_login'])): ?>
                <div style="color:#9ca3af">Last login: <?=$r['factors']['days_since_login']?> days ago · Progress: <?=$r['factors']['progress_pct'] ?? 0?>%</div>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <?php if($r['alert_sent']): ?>
              <span class="badge bg-success-subtle text-success">Sent</span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary">—</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-warning lmsa-send-alert"
                    data-id="<?=(int)$r['enrollment_id']?>"
                    data-name="<?=$e($r['first_name'])?>"
                    data-csrf="<?=htmlspecialchars(\App\Middleware\CsrfMiddleware::token())?>">
              <i class="bi bi-envelope"></i> Alert
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.lmsa-send-alert').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id   = this.dataset.id;
    var name = this.dataset.name;
    var csrf = this.dataset.csrf;
    if (!confirm('Send re-engagement email to ' + name + '?')) return;
    this.disabled = true;
    var self = this;
    fetch('<?=$url('admin/dropout/alert')?>', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'csrf_token='+encodeURIComponent(csrf)+'&enrollment_id='+id
    }).then(r=>r.json()).then(function(d) {
      if(d.success) { self.innerHTML='<i class="bi bi-check2"></i> Sent'; self.className='btn btn-sm btn-success'; }
      else           { alert(d.message); self.disabled=false; }
    });
  });
});
</script>

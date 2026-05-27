<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>

<!-- Period + Course selector -->
<div class="d-flex align-items-center flex-wrap gap-3 mb-4">
  <div class="d-flex gap-2">
    <?php foreach([7=>'7d',30=>'30d',60=>'60d'] as $d=>$l):?>
    <a href="?days=<?=$d?>" class="btn btn-sm <?=$days===$d?'btn-primary':'btn-outline-secondary'?>" style="border-radius:8px;font-weight:600"><?=$l?></a>
    <?php endforeach;?>
  </div>
  <div class="ms-auto">
    <select class="form-select form-select-sm" onchange="if(this.value)window.location='/admin/learner-analytics/course/'+this.value" style="min-width:220px">
      <option value="">📊 Drill into a course…</option>
      <?php foreach($courses as $c):?><option value="<?=$e($c['uuid'])?>"><?=$e($c['title'])?></option><?php endforeach;?>
    </select>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- At-Risk Students -->
  <div class="col-12 col-lg-5">
    <div class="card lms-card h-100">
      <div class="card-header lms-card-header d-flex align-items-center gap-2">
        <span style="width:10px;height:10px;border-radius:50%;background:#dc2626;display:inline-block"></span>
        <h5 class="mb-0">⚠️ At-Risk Students <small class="text-muted fw-normal" style="font-size:13px">(inactive 14+ days)</small></h5>
      </div>
      <?php if(empty($at_risk)):?>
      <div class="card-body text-center py-4 text-muted"><i class="bi bi-check-circle-fill text-success" style="font-size:2rem"></i><br>No at-risk students right now!</div>
      <?php else:?>
      <div style="max-height:380px;overflow-y:auto">
        <table class="table table-hover lms-table mb-0">
          <thead><tr><th>Student</th><th>Courses</th><th>Inactive</th><th></th></tr></thead>
          <tbody>
          <?php foreach($at_risk as $r):?>
          <tr>
            <td>
              <div class="fw-semibold" style="font-size:13px"><?=$e($r['first_name'].' '.$r['last_name'])?></div>
              <div class="text-muted" style="font-size:11.5px"><?=$e($r['email'])?></div>
            </td>
            <td style="font-size:13px"><?=$r['active_courses']?></td>
            <td><span class="badge bg-<?=$r['days_inactive']>30?'danger':'warning'?>-subtle text-<?=$r['days_inactive']>30?'danger':'warning'?>"><?=$r['days_inactive']?>d</span></td>
            <td><a href="mailto:<?=$e($r['email'])?>" class="btn btn-xs btn-outline-primary"><i class="bi bi-envelope"></i></a></td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <?php endif;?>
    </div>
  </div>

  <!-- Engagement Scores -->
  <div class="col-12 col-lg-7">
    <div class="card lms-card h-100">
      <div class="card-header lms-card-header"><h5 class="mb-0">🔥 Learner Engagement <small class="text-muted fw-normal" style="font-size:13px">(last <?=$days?> days)</small></h5></div>
      <?php if(empty($engagement)):?>
      <div class="card-body text-center py-4 text-muted">No activity data yet.</div>
      <?php else:?>
      <div style="max-height:400px;overflow-y:auto">
        <table class="table table-hover lms-table mb-0">
          <thead><tr><th>Student</th><th>Score</th><th>Logins</th><th>Lessons</th><th>Quizzes</th></tr></thead>
          <tbody>
          <?php foreach(array_slice($engagement,0,20) as $r):
            $sc=$r['score'];
            $color=$sc>=70?'#059669':($sc>=40?'#d97706':'#dc2626');
          ?>
          <tr>
            <td>
              <div class="fw-semibold" style="font-size:13px"><?=$e($r['first_name'].' '.$r['last_name'])?></div>
              <div class="text-muted" style="font-size:11.5px"><?=$e($r['email'])?></div>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div style="flex:1;height:6px;background:var(--border-color);border-radius:3px"><div style="height:100%;width:<?=$sc?>%;background:<?=$color?>;border-radius:3px"></div></div>
                <span style="font-size:13px;font-weight:700;color:<?=$color?>;min-width:36px"><?=$sc?></span>
              </div>
            </td>
            <td style="font-size:13px"><?=$r['logins']?></td>
            <td style="font-size:13px"><?=$r['lessons_done']?></td>
            <td style="font-size:13px"><?=$r['quiz_attempts']?></td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <?php endif;?>
    </div>
  </div>
</div>

<!-- Course selector prompt -->
<div class="lms-surface p-4 text-center" style="border:2px dashed var(--border-color);border-radius:14px">
  <i class="bi bi-bar-chart-line" style="font-size:2.5rem;opacity:.3"></i>
  <h5 class="mt-3 fw-bold" style="color:var(--text-2)">Select a course above to see the completion funnel, quiz heatmap, and grade book</h5>
</div>

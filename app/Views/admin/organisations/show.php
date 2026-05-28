<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">🏭 <?=$e($org['name'])?></h2>
    <p class="adm-page-sub"><?=$e((string)$org['seats_used'])?>/<?=$e((string)$org['seat_limit'])?> seats used</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?=$url('admin/organisations/'.$org['uuid'].'/export')?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-download me-1"></i> Export CSV
    </a>
  </div>
</div>
<?php if($flash): ?><div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-4"><?=$e($flash['message'])?></div><?php endif; ?>

<div class="row g-4">
  <!-- Members + Compliance -->
  <div class="col-lg-8">
    <!-- Compliance Report -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clipboard-check me-2"></i>Compliance Report</h6>
        <span class="badge bg-secondary"><?=count($members)?> members</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:13px">
          <thead class="table-light">
            <tr><th>Employee</th><th>Department</th>
              <?php $allCourses=array_unique(array_map(fn($m)=>array_column($m['courses'],'course_title'),$report));
                    $courseNames = !empty($report) ? array_column($report[0]['courses'],'course_title') : [];
                    foreach($courseNames as $cn): ?><th><?=$e($cn)?></th><?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($report as $m): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?=$e($m['name'])?></div>
                <div style="font-size:11px;color:var(--bs-secondary-color)"><?=$e($m['email'])?></div>
              </td>
              <td style="color:var(--bs-secondary-color)"><?=$e($m['department']??'—')?></td>
              <?php foreach($m['courses'] as $c):
                $dot = match($c['status']) { 'completed'=>['#059669','✓'],'active'=>['#2563eb','▶'],'not_enrolled'=>['#9ca3af','—'],default=>['#d97706','⏳'] };
              ?>
              <td>
                <span title="<?=$e($c['status'])?>" style="color:<?=$dot[0]?>;font-weight:700"><?=$dot[1]?></span>
                <?php if($c['overdue']): ?><span class="badge bg-danger-subtle text-danger" style="font-size:10px">Overdue</span><?php endif; ?>
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Sidebar: Assign course -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-journal-plus me-2"></i>Assign Course</div>
      <div class="card-body">
        <div id="assignResult" class="mb-2"></div>
        <div class="mb-2">
          <label class="form-label fw-semibold" style="font-size:13px">Course</label>
          <select class="form-select form-select-sm" id="assignCourse">
            <?php foreach($courses as $c): ?>
            <option value="<?=(int)$c['id']?>" <?=in_array($c['id'],$assignedIds)?'disabled':''?>>
              <?=$e($c['title'])?><?=in_array($c['id'],$assignedIds)?' (assigned)':''?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold" style="font-size:13px">Due Date</label>
          <input type="date" class="form-control form-control-sm" id="assignDue">
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="assignMandatory" checked>
          <label class="form-check-label" style="font-size:13px">Mandatory</label>
        </div>
        <button class="btn btn-primary btn-sm w-100" id="assignBtn">
          <i class="bi bi-plus-circle me-1"></i> Assign & Enroll All
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('assignBtn').addEventListener('click', function() {
  var $res = document.getElementById('assignResult');
  this.disabled = true;
  fetch('<?=$url('admin/organisations/'.$org['uuid'].'/assign')?>', {
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token=<?=urlencode(\App\Middleware\CsrfMiddleware::token())?>'
        +'&course_id='+document.getElementById('assignCourse').value
        +'&due_date='+document.getElementById('assignDue').value
        +'&mandatory='+(document.getElementById('assignMandatory').checked?'1':'0')
  }).then(r=>r.json()).then(d=>{
    $res.innerHTML='<div class="alert alert-'+(d.success?'success':'danger')+' py-2 mb-0" style="font-size:13px">'+d.message+'</div>';
    if(d.success) setTimeout(()=>location.reload(),1200);
    document.getElementById('assignBtn').disabled=false;
  });
});
</script>

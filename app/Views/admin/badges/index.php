<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$ruleLabels=['courses_completed'=>'Courses Completed','quiz_score'=>'Quiz Score %','login_streak'=>'Login Streak (days)','grade_points'=>'Grade Points','manual'=>'Manual Award'];
?>
<!-- Create badge -->
<div class="card lms-card mb-4">
  <div class="card-header lms-card-header"><h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Badge</h5></div>
  <form method="POST" action="<?=$url('admin/badges')?>">
  <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
  <div class="card-body p-4">
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label fw-semibold">Name</label><input name="name" class="form-control" required placeholder="Badge name"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Description</label><input name="description" class="form-control" placeholder="What earns this badge?"></div>
      <div class="col-md-2"><label class="form-label fw-semibold">Icon (BI class)</label><input name="icon" class="form-control" value="bi-award-fill" placeholder="bi-award-fill"></div>
      <div class="col-md-1"><label class="form-label fw-semibold">Color</label><input type="color" name="color" class="form-control form-control-color" value="#5b5ef6"></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Rule Type</label>
        <select name="rule_type" class="form-select">
          <?php foreach($ruleLabels as $k=>$v):?><option value="<?=$k?>"><?=$v?></option><?php endforeach;?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label fw-semibold">Value</label><input type="number" name="rule_value" class="form-control" value="1" min="0"></div>
      <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Create</button></div>
    </div>
  </div>
  </form>
</div>

<!-- Badge list -->
<div class="row g-3">
<?php foreach($badges as $b):?>
<div class="col-12 col-md-6 col-xl-4">
  <div class="card lms-card">
    <div class="card-body p-3 d-flex align-items-center gap-3">
      <div style="width:48px;height:48px;border-radius:14px;background:<?=$e($b['color'])?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="bi <?=$e($b['icon'])?>" style="font-size:22px;color:<?=$e($b['color'])?>"></i>
      </div>
      <div class="flex-grow-1">
        <div class="fw-bold"><?=$e($b['name'])?></div>
        <div class="text-muted" style="font-size:12.5px"><?=$e($b['description']??'')?></div>
        <div class="mt-1">
          <span class="badge bg-secondary-subtle text-secondary" style="font-size:11px"><?=$ruleLabels[$b['rule_type']]??$b['rule_type']?></span>
          <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:11px">≥ <?=$b['rule_value']?></span>
          <span class="badge bg-success-subtle text-success ms-1" style="font-size:11px"><?=$b['earned_count']?> earned</span>
        </div>
      </div>
      <div class="d-flex flex-column gap-1">
        <span class="badge bg-<?=$b['is_active']?'success':'secondary'?>-subtle"><?=$b['is_active']?'Active':'Off'?></span>
        <button type="button" class="btn btn-xs btn-outline-danger delete-badge" data-id="<?=$b['id']?>"><i class="bi bi-trash3"></i></button>
      </div>
    </div>
  </div>
</div>
<?php endforeach;?>
</div>
<script>
var BASE=(window.LMS&&window.LMS.BASE)||'', CSRF='<?=$e($csrf_token)?>';
document.querySelectorAll('.delete-badge').forEach(b=>b.addEventListener('click',function(){
  if(!confirm('Delete this badge?'))return;
  fetch(BASE+'/admin/badges/'+this.dataset.id+'/delete',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token='+encodeURIComponent(CSRF)})
    .then(r=>r.json()).then(d=>{if(d.success)this.closest('.col-12').remove();});
}));
</script>

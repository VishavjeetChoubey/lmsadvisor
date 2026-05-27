<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$enrolled = !empty($path['enrollment_status']);
$done     = $path['enrollment_status']==='completed';
?>
<div class="row g-4">
  <div class="col-12 col-lg-4">
    <div class="lms-surface p-4 mb-4">
      <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#5b5ef6,#7c3aed);display:flex;align-items:center;justify-content:center;margin-bottom:16px">
        <i class="bi bi-signpost-2-fill" style="font-size:28px;color:#fff"></i>
      </div>
      <h2 style="font-size:20px;font-weight:800;color:var(--text-1);margin-bottom:8px"><?=$e($path['title'])?></h2>
      <?php if($path['description']):?>
      <p style="font-size:14px;color:var(--text-2);line-height:1.7"><?=$e($path['description'])?></p>
      <?php endif;?>
      <div style="font-size:13px;color:var(--text-3);margin:12px 0">
        <i class="bi bi-journals me-1"></i><?=count($path['courses'])?> courses
      </div>
      <?php if($enrolled):?>
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-1" style="font-size:13px;font-weight:600">
          <span>Your progress</span><span style="color:var(--primary)"><?=$pct?>%</span>
        </div>
        <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden">
          <div style="height:100%;width:<?=$pct?>%;background:var(--primary);border-radius:4px"></div>
        </div>
      </div>
      <?php else:?>
      <button class="btn-course-action btn-primary-action w-100 justify-content-center" id="enrollPathBtn">
        <i class="bi bi-play-fill me-2"></i>Enroll in Path
      </button>
      <?php endif;?>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <h3 style="font-size:17px;font-weight:700;color:var(--text-1);margin-bottom:20px">
      <i class="bi bi-list-ol me-2" style="color:var(--primary)"></i>Course Sequence
    </h3>
    <div class="d-flex flex-column gap-3">
    <?php foreach($path['courses'] as $i => $c):
      $cDone  = $c['enrollment_status']==='completed';
      $cActive= !$cDone && ($c['enrollment_status']==='active');
      $locked = !$enrolled && $i > 0;
    ?>
    <div class="lms-surface p-3 d-flex align-items-center gap-3" style="<?=$locked?'opacity:.6':''?>">
      <div style="width:36px;height:36px;border-radius:50%;background:<?=$cDone?'#059669':($cActive?'#5b5ef6':'var(--border)')?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:14px;color:<?=$cDone?'#059669':($cActive?'#5b5ef6':'var(--text-3)')?>">
        <?=$cDone?'✓':($i+1)?>
      </div>
      <div class="flex-grow-1">
        <div style="font-size:14.5px;font-weight:600;color:var(--text-1)"><?=$e($c['title'])?></div>
        <div style="font-size:12px;color:var(--text-3)"><?=$cDone?'Completed':($cActive?(($c['progress_pct']??0).'% complete'):'Not started')?></div>
      </div>
      <?php if($enrolled && !$locked):?>
      <a href="<?=$url('learn/courses/'.$c['uuid'].($cActive?'/learn':''))?>" class="btn btn-sm <?=$cDone?'btn-outline-success':'btn-primary'?>">
        <?=$cDone?'Review':($cActive?'Resume':'Start')?>
      </a>
      <?php endif;?>
    </div>
    <?php if($i < count($path['courses'])-1):?>
    <div style="margin-left:18px;width:2px;height:16px;background:var(--border);border-radius:1px"></div>
    <?php endif;?>
    <?php endforeach;?>
    </div>
  </div>
</div>

<script>
document.getElementById('enrollPathBtn')?.addEventListener('click',function(){
  var BASE=(window.LMS&&window.LMS.BASE)||'';
  this.disabled=true; this.textContent='Enrolling…';
  fetch(BASE+'/learn/paths/<?=$e($path['uuid'])?>/enroll',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token=<?=$e($csrf_token??"")?>'
  }).then(r=>r.json()).then(d=>{
    if(d.success){LMS.toast('success',d.message);setTimeout(()=>location.reload(),800);}
    else{LMS.toast('error',d.message);this.disabled=false;}
  });
});
</script>

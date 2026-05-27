<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="mb-4">
  <h2 style="font-size:20px;font-weight:800;color:var(--text-1)">🗺️ Learning Paths</h2>
  <p style="color:var(--text-2)">Structured curricula — complete courses in a guided sequence.</p>
</div>
<?php if(empty($paths)):?>
<div class="lms-surface text-center py-5">
  <div style="font-size:3rem;color:var(--border)"><i class="bi bi-signpost-2"></i></div>
  <h5 class="mt-3 fw-bold">No learning paths available yet</h5>
  <p style="color:var(--text-2)">Check back soon — your instructor is building paths for you.</p>
</div>
<?php else:?>
<div class="row g-4">
<?php foreach($paths as $path):
  $enrolled = !empty($path['enrollment_status']);
  $done     = $path['enrollment_status']==='completed';
?>
<div class="col-12 col-md-6 col-xl-4">
  <a href="<?=$url('learn/paths/'.$path['uuid'])?>" class="text-decoration-none d-block h-100">
    <div class="course-card h-100" style="transition:transform .2s,box-shadow .2s">
      <div style="height:130px;background:linear-gradient(135deg,#5b5ef6,#7c3aed);display:flex;align-items:center;justify-content:center;position:relative">
        <i class="bi bi-signpost-2-fill" style="font-size:3.5rem;color:rgba(255,255,255,.3)"></i>
        <?php if($done):?>
        <span class="badge bg-success position-absolute" style="top:10px;right:10px">✓ Completed</span>
        <?php elseif($enrolled):?>
        <span class="badge bg-primary position-absolute" style="top:10px;right:10px">▶ In Progress</span>
        <?php endif;?>
      </div>
      <div class="course-body">
        <div class="course-title-text" style="color:var(--text-1);font-weight:700"><?=$e($path['title'])?></div>
        <?php if($path['description']):?>
        <p style="font-size:13px;color:var(--text-2);margin-top:4px"><?=$e(mb_strimwidth($path['description'],0,90,'…'))?></p>
        <?php endif;?>
        <div style="font-size:12.5px;color:var(--text-3);margin-top:6px">
          <i class="bi bi-journals me-1"></i><?=(int)$path['course_count']?> courses
        </div>
      </div>
      <div class="course-footer">
        <span style="font-size:13px;color:var(--text-2)"><?=$enrolled?($done?'Completed':'Enrolled'):'Not enrolled'?></span>
        <span class="btn-course-action btn-primary-action" style="font-size:13px">
          <?=$enrolled?'View Path →':'Start Path →'?>
        </span>
      </div>
    </div>
  </a>
</div>
<?php endforeach;?>
</div>
<?php endif;?>

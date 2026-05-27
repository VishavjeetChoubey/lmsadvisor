<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="d-flex gap-3 mb-4">
  <button class="btn btn-sm fw-semibold btn-primary" id="showTpl">📧 Templates</button>
  <button class="btn btn-sm fw-semibold btn-outline-secondary" id="showLog">📋 Queue Log</button>
</div>

<div id="tplTab">
<div class="row g-4">
<?php
$icons =['enrollment_confirmation'=>'bi-person-check-fill','course_completion'=>'bi-patch-check-fill',
         'quiz_result'=>'bi-clipboard-check','webinar_reminder'=>'bi-camera-video-fill','certificate_ready'=>'bi-award-fill'];
$colors=['enrollment_confirmation'=>'#5b5ef6','course_completion'=>'#059669',
         'quiz_result'=>'#d97706','webinar_reminder'=>'#7c3aed','certificate_ready'=>'#b45309'];
foreach ($templates as $tpl):
  $ic=$icons[$tpl['slug']]??'bi-envelope'; $cl=$colors[$tpl['slug']]??'#374151';
?>
<div class="col-12 col-md-6">
  <div class="card lms-card h-100">
    <div class="card-body p-4 d-flex gap-3 align-items-start">
      <div style="width:46px;height:46px;border-radius:12px;background:<?=$e($cl)?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="bi <?=$e($ic)?>" style="font-size:20px;color:<?=$e($cl)?>"></i>
      </div>
      <div class="flex-grow-1">
        <div class="fw-bold mb-1"><?=$e($tpl['name'])?></div>
        <div class="text-muted mb-2" style="font-size:13px"><?=$e($tpl['subject'])?></div>
        <span class="badge bg-<?=$tpl['is_enabled']?'success':'secondary'?>-subtle text-<?=$tpl['is_enabled']?'success':'secondary'?>"><?=$tpl['is_enabled']?'Enabled':'Disabled'?></span>
      </div>
    </div>
    <div class="card-footer d-flex gap-2">
      <a href="<?=$url('admin/email/templates/'.$tpl['slug'].'/edit')?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
      <button class="btn btn-sm btn-outline-secondary test-send-btn" data-slug="<?=$e($tpl['slug'])?>"><i class="bi bi-send me-1"></i>Test</button>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<div class="card lms-card mt-4">
  <div class="card-header lms-card-header"><h5 class="mb-0"><i class="bi bi-send me-2"></i>Send Test Email</h5></div>
  <div class="card-body p-4"><div class="row g-3 align-items-end">
    <div class="col-md-5"><label class="form-label fw-semibold">To Email</label><input type="email" id="testEmail" class="form-control" placeholder="you@example.com"></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Template</label>
      <select id="testSlug" class="form-select"><?php foreach($templates as $t):?><option value="<?=$e($t['slug'])?>"><?=$e($t['name'])?></option><?php endforeach;?></select>
    </div>
    <div class="col-md-3"><button class="btn btn-primary w-100" id="sendTestBtn"><i class="bi bi-send me-1"></i>Send Test</button></div>
  </div></div>
</div>
</div>

<div id="logTab" class="d-none">
  <div class="card lms-card">
    <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Queue Log (last 50)</h5>
      <button class="btn btn-sm btn-primary" id="processQueueBtn"><i class="bi bi-play me-1"></i>Process Now</button>
    </div>
    <div class="table-responsive"><table class="table table-hover lms-table mb-0">
      <thead><tr><th>To</th><th>Template</th><th>Status</th><th>Attempts</th><th>Scheduled</th><th>Sent</th></tr></thead>
      <tbody><?php foreach($queue as $q):?>
      <tr>
        <td style="font-size:13px"><?=$e($q['to_email'])?></td>
        <td><span class="badge bg-secondary-subtle text-secondary" style="font-size:11px"><?=$e($q['template']??'')?></span></td>
        <td><span class="badge bg-<?=['pending'=>'warning','sent'=>'success','failed'=>'danger'][$q['status']]??'secondary'?>-subtle"><?=$q['status']?></span></td>
        <td><?=$q['attempts']?></td>
        <td style="font-size:12px"><?=date('d M H:i',strtotime($q['scheduled_at']))?></td>
        <td style="font-size:12px"><?=$q['sent_at']?date('d M H:i',strtotime($q['sent_at'])):'—'?></td>
      </tr>
      <?php endforeach;?></tbody>
    </table></div>
  </div>
</div>

<script>
var BASE=(window.LMS&&window.LMS.BASE)||'', CSRF='<?=$e($csrf_token)?>';
document.getElementById('showTpl').addEventListener('click',function(){document.getElementById('tplTab').classList.remove('d-none');document.getElementById('logTab').classList.add('d-none');});
document.getElementById('showLog').addEventListener('click',function(){document.getElementById('logTab').classList.remove('d-none');document.getElementById('tplTab').classList.add('d-none');});
document.querySelectorAll('.test-send-btn').forEach(b=>b.addEventListener('click',function(){
  document.getElementById('testSlug').value=this.dataset.slug;
  document.getElementById('testEmail').focus();
}));
document.getElementById('sendTestBtn')?.addEventListener('click',function(){
  var em=document.getElementById('testEmail').value, sl=document.getElementById('testSlug').value;
  if(!em){LMS.toast('error','Enter an email');return;}
  this.disabled=true;
  fetch(BASE+'/admin/email/test',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token='+encodeURIComponent(CSRF)+'&test_to='+encodeURIComponent(em)+'&template_slug='+encodeURIComponent(sl)})
    .then(r=>r.json()).then(d=>{LMS.toast(d.success?'success':'error',d.message);this.disabled=false;});
});
document.getElementById('processQueueBtn')?.addEventListener('click',function(){
  this.disabled=true;this.textContent='Processing…';
  fetch(BASE+'/admin/email/process-queue',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token='+encodeURIComponent(CSRF)})
    .then(r=>r.json()).then(d=>{LMS.toast('success','Sent '+d.sent+', Failed '+d.failed);this.disabled=false;this.innerHTML='<i class="bi bi-play me-1"></i>Process Now';});
});
</script>

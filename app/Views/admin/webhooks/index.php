<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$allEvents = ['enroll','complete','quiz_pass','quiz_fail','grade','badge','*'];
?>

<div class="row g-4">
  <!-- Create webhook -->
  <div class="col-12 col-lg-5">
    <div class="card lms-card">
      <div class="card-header lms-card-header"><h5 class="mb-0"><i class="bi bi-plug me-2"></i>New Webhook</h5></div>
      <form method="POST" action="<?=$url('admin/webhooks')?>">
        <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
        <div class="card-body p-4">
          <div class="mb-3"><label class="form-label fw-semibold">Name</label>
            <input name="name" class="form-control" required placeholder="e.g. Slack Enrollment Alert"></div>
          <div class="mb-3"><label class="form-label fw-semibold">Endpoint URL</label>
            <input name="url" class="form-control" type="url" required placeholder="https://hooks.example.com/lms"></div>
          <div class="mb-3"><label class="form-label fw-semibold">Secret <small class="text-muted fw-normal">(leave blank to auto-generate)</small></label>
            <input name="secret" class="form-control" placeholder="Auto-generated HMAC-SHA256 secret"></div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Events to Fire</label>
            <div class="p-3 rounded-2" style="background:var(--content-bg);border:1px solid var(--border-color)">
              <?php foreach($allEvents as $ev):?>
              <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" name="events[]" value="<?=$ev?>" id="ev_<?=$ev?>" <?=$ev==='*'?'':'checked'?>>
                <label class="form-check-label" for="ev_<?=$ev?>">
                  <?=$ev==='*'?'<strong>* — All events</strong>':$ev?>
                </label>
              </div>
              <?php endforeach;?>
            </div>
          </div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus me-1"></i>Create Webhook</button></div>
      </form>
    </div>

    <!-- Slack integration card -->
    <div class="card lms-card mt-4">
      <div class="card-header lms-card-header d-flex align-items-center gap-2">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z" fill="#E01E5A"/></svg>
        <h5 class="mb-0">Slack Notifications</h5>
      </div>
      <div class="card-body p-4">
        <p class="text-muted" style="font-size:13.5px">Post enrollment, completion, and badge events to a Slack channel using an <a href="https://api.slack.com/messaging/webhooks" target="_blank">Incoming Webhook URL</a>.</p>
        <p class="text-muted" style="font-size:13px">Configure the Slack Webhook URL and enable in <a href="<?=$url('admin/settings?tab=general')?>">Settings → General</a>.</p>
      </div>
    </div>
  </div>

  <!-- Webhook list -->
  <div class="col-12 col-lg-7">
    <div class="card lms-card">
      <div class="card-header lms-card-header"><h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Active Webhooks (<?=count($webhooks)?>)</h5></div>
      <?php if(empty($webhooks)):?>
      <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-plug" style="font-size:3rem;opacity:.2"></i>
        <h5 class="mt-3">No webhooks yet</h5>
        <p>Create your first webhook to start receiving LMS events.</p>
      </div>
      <?php else:?>
      <div class="list-group list-group-flush">
        <?php foreach($webhooks as $wh):
          $events = json_decode($wh['events']??'[]',true);
        ?>
        <div class="list-group-item p-4">
          <div class="d-flex align-items-start gap-3">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-bold" style="font-size:14.5px"><?=$e($wh['name'])?></span>
                <span class="badge bg-<?=$wh['is_active']?'success':'secondary'?>-subtle text-<?=$wh['is_active']?'success':'secondary'?>"><?=$wh['is_active']?'Active':'Paused'?></span>
                <?php if($wh['fail_count']>0):?>
                <span class="badge bg-danger-subtle text-danger"><?=$wh['fail_count']?> failures</span>
                <?php endif;?>
              </div>
              <div style="font-size:12.5px;color:var(--text-muted);font-family:monospace"><?=$e($wh['url'])?></div>
              <div class="d-flex flex-wrap gap-1 mt-2">
                <?php foreach($events as $ev):?>
                <span class="badge bg-primary-subtle text-primary" style="font-size:10.5px"><?=$e($ev)?></span>
                <?php endforeach;?>
              </div>
              <?php if($wh['last_fired']):?>
              <div style="font-size:11.5px;color:var(--text-muted);margin-top:4px">Last fired: <?=date('d M Y H:i',strtotime($wh['last_fired']))?></div>
              <?php endif;?>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
              <button class="btn btn-xs btn-outline-primary test-btn" data-id="<?=$wh['id']?>" title="Send test payload"><i class="bi bi-play"></i></button>
              <button class="btn btn-xs btn-outline-secondary logs-btn" data-id="<?=$wh['id']?>" title="View delivery logs"><i class="bi bi-list-ul"></i></button>
              <button class="btn btn-xs btn-outline-warning rotate-btn" data-id="<?=$wh['id']?>" title="Rotate secret"><i class="bi bi-arrow-repeat"></i></button>
              <button class="btn btn-xs btn-outline-danger delete-btn" data-id="<?=$wh['id']?>" title="Delete"><i class="bi bi-trash3"></i></button>
            </div>
          </div>
          <!-- Delivery logs (hidden) -->
          <div class="wh-logs d-none mt-3" id="logs_<?=$wh['id']?>"></div>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>
    </div>
  </div>
</div>

<script>
var BASE=(window.LMS&&window.LMS.BASE)||'', CSRF='<?=$e($csrf_token)?>';
function whPost(url,cb){fetch(BASE+url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token='+encodeURIComponent(CSRF)}).then(r=>r.json()).then(cb);}

document.querySelectorAll('.test-btn').forEach(b=>b.addEventListener('click',function(){
  this.disabled=true;
  whPost('/admin/webhooks/'+this.dataset.id+'/test',d=>{LMS.toast(d.success?'success':'error',d.message);this.disabled=false;});
}));

document.querySelectorAll('.delete-btn').forEach(b=>b.addEventListener('click',function(){
  if(!confirm('Delete this webhook?'))return;
  var btn=this;
  whPost('/admin/webhooks/'+this.dataset.id+'/delete',d=>{if(d.success)btn.closest('.list-group-item').remove();});
}));

document.querySelectorAll('.rotate-btn').forEach(b=>b.addEventListener('click',function(){
  if(!confirm('Rotate secret? All existing signatures will become invalid.'))return;
  whPost('/admin/webhooks/'+this.dataset.id+'/rotate-secret',d=>{
    if(d.success){LMS.toast('success','New secret: '+d.secret);navigator.clipboard?.writeText(d.secret);}
  });
}));

document.querySelectorAll('.logs-btn').forEach(b=>b.addEventListener('click',function(){
  var id=this.dataset.id, el=document.getElementById('logs_'+id);
  if(!el.classList.contains('d-none')){el.classList.add('d-none');return;}
  fetch(BASE+'/admin/webhooks/'+id+'/logs').then(r=>r.json()).then(d=>{
    if(!d.logs||!d.logs.length){el.innerHTML='<p class="text-muted" style="font-size:13px">No deliveries yet.</p>';}
    else{el.innerHTML='<div class="table-responsive"><table class="table table-sm mb-0 lms-table"><thead><tr><th>Event</th><th>Status</th><th>Code</th><th>Time</th><th>ms</th></tr></thead><tbody>'+
      d.logs.map(l=>'<tr><td style="font-size:12px">'+l.event_type+'</td><td><span class="badge bg-'+(l.success?'success':'danger')+'-subtle">'+(l.success?'OK':'FAIL')+'</span></td><td>'+l.response_code+'</td><td style="font-size:11px">'+new Date(l.fired_at).toLocaleString()+'</td><td>'+l.duration_ms+'</td></tr>').join('')+
      '</tbody></table></div>';}
    el.classList.remove('d-none');
  });
}));
</script>

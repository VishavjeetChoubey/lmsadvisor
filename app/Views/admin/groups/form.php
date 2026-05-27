<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$isEdit = !empty($group);
$action = $isEdit ? $url('admin/groups/'.$group['id'].'/update') : $url('admin/groups');
$memberIds = array_column($members??[], 'user_id');
?>
<form method="POST" action="<?=$action?>">
<input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
<div class="row g-4">
  <div class="col-12 col-lg-7">
    <div class="card lms-card">
      <div class="card-header lms-card-header"><h5 class="mb-0">Group Details</h5></div>
      <div class="card-body p-4">
        <div class="mb-3"><label class="form-label fw-semibold">Group Name <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="<?=$e($group['name']??'')?>" required placeholder="e.g. Sales Team Q2 2026"></div>
        <div class="mb-3"><label class="form-label fw-semibold">Description</label>
          <textarea name="description" class="form-control" rows="3"><?=$e($group['description']??'')?></textarea></div>
        <div class="mb-0"><label class="form-label fw-semibold">Manager (optional)</label>
          <select name="manager_id" class="form-select">
            <option value="">— No manager —</option>
            <?php foreach($users as $u):?>
            <option value="<?=$u['id']?>" <?=($group['manager_id']??0)==$u['id']?'selected':''?>><?=$e($u['first_name'].' '.$u['last_name'])?> (<?=$e($u['email'])?>)</option>
            <?php endforeach;?>
          </select>
        </div>
      </div>
      <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i><?=$isEdit?'Save Changes':'Create Group'?></button>
        <a href="<?=$url('admin/groups')?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </div>

    <?php if($isEdit && !empty($group_courses)):?>
    <div class="card lms-card mt-4">
      <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-journals me-2"></i>Assigned Courses</h5>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach($group_courses as $gc):?>
        <div class="list-group-item d-flex align-items-center justify-content-between">
          <span style="font-size:14px"><?=$e($gc['title'])?></span>
          <button type="button" class="btn btn-xs btn-outline-danger remove-course" data-id="<?=$gc['id']?>"><i class="bi bi-x"></i></button>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php endif;?>
  </div>

  <?php if($isEdit):?>
  <div class="col-12 col-lg-5">
    <!-- Members -->
    <div class="card lms-card">
      <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-people me-2"></i>Members (<?=count($members)?>)</h5>
      </div>
      <div style="max-height:300px;overflow-y:auto">
        <div class="list-group list-group-flush" id="memberList">
          <?php foreach($members as $m):?>
          <div class="list-group-item d-flex align-items-center justify-content-between py-2">
            <div style="font-size:13.5px"><strong><?=$e($m['first_name'].' '.$m['last_name'])?></strong><br><span class="text-muted" style="font-size:12px"><?=$e($m['email'])?></span></div>
            <button type="button" class="btn btn-xs btn-outline-danger rm-member" data-id="<?=$m['user_id']?>"><i class="bi bi-x"></i></button>
          </div>
          <?php endforeach;?>
        </div>
      </div>
      <div class="card-footer p-3">
        <div class="d-flex gap-2">
          <select class="form-select form-select-sm" id="addUserSel">
            <option value="">Add member…</option>
            <?php foreach($users as $u): if(in_array($u['id'],array_column($members,'user_id')))continue;?>
            <option value="<?=$u['id']?>"><?=$e($u['first_name'].' '.$u['last_name'])?> (<?=$e($u['email'])?>)</option>
            <?php endforeach;?>
          </select>
          <button type="button" class="btn btn-sm btn-primary" id="addMemberBtn"><i class="bi bi-plus"></i></button>
        </div>
      </div>
    </div>
    <!-- Assign course -->
    <div class="card lms-card mt-3">
      <div class="card-header lms-card-header"><h5 class="mb-0"><i class="bi bi-journals me-2"></i>Assign Course</h5></div>
      <div class="card-body p-3">
        <div class="d-flex gap-2">
          <select class="form-select form-select-sm" id="addCourseSel">
            <option value="">Select course…</option>
            <?php foreach($courses??[] as $c):?>
            <option value="<?=$c['id']?>"><?=$e($c['title'])?></option>
            <?php endforeach;?>
          </select>
          <button type="button" class="btn btn-sm btn-success" id="assignCourseBtn"><i class="bi bi-plus"></i></button>
        </div>
        <div class="form-text mt-1">Assigning a course will auto-enroll all current members.</div>
      </div>
    </div>
  </div>
  <?php endif;?>
</div>
</form>

<?php if($isEdit):?>
<script>
var BASE=(window.LMS&&window.LMS.BASE)||'', CSRF='<?=$e($csrf_token)?>', GID=<?=$group['id']?>;
document.getElementById('addMemberBtn')?.addEventListener('click',function(){
  var sel=document.getElementById('addUserSel'), uid=sel.value;
  if(!uid)return;
  fetch(BASE+'/admin/groups/'+GID+'/members',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token='+encodeURIComponent(CSRF)+'&user_id='+uid})
    .then(r=>r.json()).then(d=>{if(d.success){LMS.toast('success','Member added');location.reload();}});
});
document.querySelectorAll('.rm-member').forEach(b=>b.addEventListener('click',function(){
  var uid=this.dataset.id;
  fetch(BASE+'/admin/groups/'+GID+'/members/remove',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token='+encodeURIComponent(CSRF)+'&user_id='+uid})
    .then(r=>r.json()).then(d=>{if(d.success)this.closest('.list-group-item').remove();});
}));
document.getElementById('assignCourseBtn')?.addEventListener('click',function(){
  var cid=document.getElementById('addCourseSel').value;
  if(!cid)return;
  fetch(BASE+'/admin/groups/'+GID+'/courses',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token='+encodeURIComponent(CSRF)+'&course_id='+cid})
    .then(r=>r.json()).then(d=>LMS.toast(d.success?'success':'error',d.message));
});
document.querySelectorAll('.remove-course').forEach(b=>b.addEventListener('click',function(){
  var cid=this.dataset.id;
  fetch(BASE+'/admin/groups/'+GID+'/courses/remove',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token='+encodeURIComponent(CSRF)+'&course_id='+cid})
    .then(r=>r.json()).then(d=>{if(d.success)this.closest('.list-group-item').remove();});
}));
</script>
<?php endif;?>

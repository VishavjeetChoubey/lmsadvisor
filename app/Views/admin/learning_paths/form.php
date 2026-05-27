<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$action=$path ? $url('admin/learning-paths/'.$path['uuid'].'/update') : $url('admin/learning-paths'); ?>
<form method="POST" action="<?=$action?>">
<input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card lms-card">
      <div class="card-body p-4">
        <div class="mb-3"><label class="form-label fw-semibold">Path Title <span class="text-danger">*</span></label>
          <input name="title" class="form-control form-control-lg" value="<?=$e($path['title']??'')?>" required placeholder="e.g. Full Stack Web Development Path">
        </div>
        <div class="mb-3"><label class="form-label fw-semibold">Description</label>
          <textarea name="description" rows="4" class="form-control" placeholder="What will students achieve on this path?"><?=$e($path['description']??'')?></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_published" value="1" <?=($path['is_published']??0)?'checked':''?>>
          <label class="form-check-label fw-semibold">Published (visible to students)</label>
        </div>
      </div>
    </div>

    <!-- Course ordering -->
    <div class="card lms-card mt-4">
      <div class="card-header lms-card-header"><h5 class="mb-0"><i class="bi bi-journals me-2"></i>Courses in Path <span class="text-muted fw-normal" style="font-size:13px">(drag to reorder)</span></h5></div>
      <div class="card-body p-3">
        <div id="pathCourseList" style="min-height:60px">
          <?php foreach($path_courses as $cid): $c=array_values(array_filter($courses,fn($x)=>(int)$x['id']===(int)$cid))[0]??null; if(!$c)continue;?>
          <div class="d-flex align-items-center gap-3 p-2 mb-2 rounded-2" style="background:var(--content-bg);border:1px solid var(--border-color)">
            <i class="bi bi-grip-vertical text-muted" style="cursor:grab"></i>
            <span class="flex-grow-1" style="font-size:14px"><?=$e($c['title'])?></span>
            <input type="hidden" name="course_ids[]" value="<?=$cid?>">
            <button type="button" class="btn btn-xs btn-outline-danger remove-path-course"><i class="bi bi-x"></i></button>
          </div>
          <?php endforeach;?>
        </div>
        <div class="mt-3">
          <label class="form-label fw-semibold">Add Course</label>
          <select id="addCourseSelect" class="form-select">
            <option value="">— Select course to add —</option>
            <?php foreach($courses as $c):?>
            <option value="<?=$c['id']?>" data-title="<?=$e($c['title'])?>"><?=$e($c['title'])?></option>
            <?php endforeach;?>
          </select>
          <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addCourseBtn"><i class="bi bi-plus me-1"></i>Add to Path</button>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card lms-card">
      <div class="card-header lms-card-header"><h6 class="mb-0">About Learning Paths</h6></div>
      <div class="card-body p-3 text-muted" style="font-size:13.5px">
        <p>Students enroll in the entire path at once. Each course is completed in order.</p>
        <p>Courses with prerequisites will be automatically enforced — students must complete earlier courses first.</p>
      </div>
    </div>
    <div class="mt-3 d-flex flex-column gap-2">
      <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-1"></i><?=$path?'Save Changes':'Create Path'?></button>
      <a href="<?=$url('admin/learning-paths')?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </div>
</div>
</form>
<script>
document.getElementById('addCourseBtn')?.addEventListener('click',function(){
  var sel=document.getElementById('addCourseSelect');
  if(!sel.value)return;
  var id=sel.value, title=sel.options[sel.selectedIndex].dataset.title;
  var list=document.getElementById('pathCourseList');
  var div=document.createElement('div');
  div.className='d-flex align-items-center gap-3 p-2 mb-2 rounded-2';
  div.style='background:var(--content-bg);border:1px solid var(--border-color)';
  div.innerHTML='<i class="bi bi-grip-vertical text-muted" style="cursor:grab"></i><span class="flex-grow-1" style="font-size:14px">'+title+'</span><input type="hidden" name="course_ids[]" value="'+id+'"><button type="button" class="btn btn-xs btn-outline-danger remove-path-course"><i class="bi bi-x"></i></button>';
  list.appendChild(div);
  sel.value='';
});
document.getElementById('pathCourseList').addEventListener('click',function(e){
  if(e.target.closest('.remove-path-course')) e.target.closest('.d-flex').remove();
});
</script>

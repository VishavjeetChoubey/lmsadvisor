<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <p class="text-muted mb-0">Guided curriculum sequences — ordered lists of courses students complete in sequence.</p>
  <a href="<?=$url('admin/learning-paths/create')?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Path</a>
</div>
<?php if(empty($paths)):?>
<div class="card lms-card text-center py-5"><div class="card-body">
  <i class="bi bi-signpost-2" style="font-size:3rem;opacity:.2"></i>
  <h5 class="mt-3 fw-bold">No learning paths yet</h5>
  <p class="text-muted">Create your first structured curriculum path.</p>
  <a href="<?=$url('admin/learning-paths/create')?>" class="btn btn-primary">Create Learning Path</a>
</div></div>
<?php else:?>
<div class="row g-4">
<?php foreach($paths as $path):?>
<div class="col-12 col-md-6 col-xl-4">
  <div class="card lms-card h-100">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width:44px;height:44px;border-radius:12px;background:#ededff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi bi-signpost-2-fill" style="font-size:20px;color:#5b5ef6"></i>
        </div>
        <div>
          <div class="fw-bold"><?=$e($path['title'])?></div>
          <span class="badge bg-<?=$path['is_published']?'success':'secondary'?>-subtle text-<?=$path['is_published']?'success':'secondary'?>" style="font-size:11px"><?=$path['is_published']?'Published':'Draft'?></span>
        </div>
      </div>
      <div class="d-flex gap-3 text-muted" style="font-size:13px">
        <span><i class="bi bi-journals me-1"></i><?=(int)$path['course_count']?> courses</span>
        <span><i class="bi bi-people me-1"></i>Enrolled: —</span>
      </div>
    </div>
    <div class="card-footer d-flex gap-2">
      <a href="<?=$url('admin/learning-paths/'.$path['uuid'].'/edit')?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
      <form method="POST" action="<?=$url('admin/learning-paths/'.$path['uuid'].'/delete')?>" onsubmit="return confirm('Delete this path?')">
        <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
      </form>
    </div>
  </div>
</div>
<?php endforeach;?>
</div>
<?php endif;?>

<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <p class="text-muted mb-0">Manage teams, departments, and cohorts. Bulk-enroll groups in courses.</p>
  <a href="<?=$url('admin/groups/create')?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Group</a>
</div>
<?php if(empty($groups)):?>
<div class="card lms-card text-center py-5"><div class="card-body">
  <i class="bi bi-people" style="font-size:3rem;opacity:.2"></i>
  <h5 class="mt-3 fw-bold">No groups yet</h5>
  <p class="text-muted">Create your first team or cohort to manage learners together.</p>
  <a href="<?=$url('admin/groups/create')?>" class="btn btn-primary">Create Group</a>
</div></div>
<?php else:?>
<div class="row g-4">
<?php foreach($groups as $g):?>
<div class="col-12 col-md-6 col-xl-4">
  <div class="card lms-card h-100">
    <div class="card-body p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width:44px;height:44px;border-radius:12px;background:#ecfdf5;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi bi-people-fill" style="font-size:20px;color:#059669"></i>
        </div>
        <div>
          <div class="fw-bold"><?=$e($g['name'])?></div>
          <?php if($g['manager_name']): ?><div class="text-muted" style="font-size:12px">Manager: <?=$e($g['manager_name'])?></div><?php endif;?>
        </div>
      </div>
      <div class="d-flex gap-4 text-muted" style="font-size:13px">
        <span><i class="bi bi-person me-1"></i><?=(int)$g['member_count']?> members</span>
        <span><i class="bi bi-journals me-1"></i><?=(int)$g['course_count']?> courses</span>
      </div>
      <?php if($g['description']):?><p class="mt-2 text-muted" style="font-size:13px"><?=$e(mb_strimwidth($g['description'],0,80,'…'))?></p><?php endif;?>
    </div>
    <div class="card-footer d-flex gap-2 flex-wrap">
      <a href="<?=$url('admin/groups/'.$g['id'].'/edit')?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Manage</a>
      <a href="<?=$url('admin/groups/'.$g['id'].'/report')?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-bar-chart me-1"></i>Report</a>
      <form method="POST" action="<?=$url('admin/groups/'.$g['id'].'/delete')?>" onsubmit="return confirm('Delete this group?')">
        <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
      </form>
    </div>
  </div>
</div>
<?php endforeach;?>
</div>
<?php endif;?>

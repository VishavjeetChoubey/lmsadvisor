<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between">
  <div>
    <h2 class="adm-page-title">📋 Assignments</h2>
    <p class="adm-page-sub">Courses with assignment lessons — click a course to review and grade submissions.</p>
  </div>
</div>

<?php if(!empty($error)): ?>
<div class="alert alert-warning d-flex align-items-center gap-2">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <?=$e($error)?>
  <a href="<?=$url('admin/database')?>" class="btn btn-sm btn-warning ms-auto">Run Migrations</a>
</div>
<?php elseif(empty($courses)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5">
    <i class="bi bi-clipboard-x" style="font-size:3rem;opacity:.3"></i>
    <div class="fw-bold mt-3">No assignment lessons found</div>
    <p class="text-muted">Add a lesson with type <strong>Assignment</strong> to a published course first.</p>
    <a href="<?=$url('admin/courses')?>" class="btn btn-primary btn-sm mt-2">Go to Courses</a>
  </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:13.5px">
      <thead class="table-light">
        <tr>
          <th>Course</th>
          <th class="text-center">Assignment Lessons</th>
          <th class="text-center">Submissions</th>
          <th class="text-center">Pending Review</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($courses as $c): ?>
        <tr>
          <td class="fw-semibold"><?=$e($c['title'])?></td>
          <td class="text-center"><?=(int)$c['assignment_count']?></td>
          <td class="text-center"><?=(int)$c['submission_count']?></td>
          <td class="text-center">
            <?php if($c['pending_count'] > 0): ?>
              <span class="badge bg-warning text-dark"><?=(int)$c['pending_count']?> pending</span>
            <?php else: ?>
              <span class="badge bg-success-subtle text-success">All graded</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?=$url('admin/courses/'.$c['uuid'].'/assignments')?>" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-eye me-1"></i> Review
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

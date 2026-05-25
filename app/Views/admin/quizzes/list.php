<?php use App\Core\View; $e=$c=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-patch-question me-2"></i>All Quizzes</h5>
    <span class="badge bg-secondary"><?= count($quizzes) ?></span>
  </div>
  <?php if (empty($quizzes)): ?>
    <div class="card-body text-center py-5 text-muted">
      <i class="bi bi-patch-question" style="font-size:3rem;opacity:.2"></i>
      <h6 class="mt-3">No quizzes yet</h6>
      <p class="small">Create quiz lessons inside a course to see them here.</p>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead><tr><th>Quiz</th><th>Course</th><th>Pass %</th><th>Max Attempts</th><th>Time Limit</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($quizzes as $q): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= $e($q['title']) ?></div>
            <div class="text-muted" style="font-size:12px">Lesson: <?= $e($q['lesson_title']) ?></div>
          </td>
          <td><a href="<?= $url('admin/courses/'.$q['course_uuid'].'/edit') ?>" class="text-decoration-none" style="font-size:13px"><?= $e($q['course_title']) ?></a></td>
          <td><?= (int)$q['pass_percentage'] ?>%</td>
          <td><?= (int)$q['max_attempts'] ?>x</td>
          <td><?= $q['time_limit_sec'] ? gmdate('i:s', (int)$q['time_limit_sec']) : '—' ?></td>
          <td>
            <a href="<?= $url('admin/quizzes/'.$q['id'].'/preview') ?>" class="btn btn-xs btn-outline-primary">
              <i class="bi bi-eye"></i> Preview
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

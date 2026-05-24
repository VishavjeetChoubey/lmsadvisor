<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>
<div class="row justify-content-center">
  <div class="col-12 col-xl-9">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i> New Course</h5>
      </div>
      <div class="card-body p-4">
        <form action="<?= $url('admin/courses/create') ?>" method="POST"
              enctype="multipart/form-data" id="createCourseForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">

          <?php include __DIR__ . '/_form_fields.php'; ?>

          <hr class="my-4">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4" id="createBtn">
              <i class="bi bi-check-circle me-1"></i> Create Course & Add Content
            </button>
            <a href="<?= $url('admin/courses') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('createCourseForm').addEventListener('submit', function () {
  const btn = document.getElementById('createBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating…';
});
</script>

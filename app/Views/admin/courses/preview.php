<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$levelColors = ['beginner'=>'info','intermediate'=>'primary','advanced'=>'danger'];
$typeIcons   = ['text'=>'bi-file-text','video'=>'bi-play-circle','document'=>'bi-file-pdf','scorm'=>'bi-box-seam','quiz'=>'bi-patch-question'];
$typeColors  = ['text'=>'secondary','video'=>'danger','document'=>'warning','scorm'=>'info','quiz'=>'success'];
?>

<!-- Preview banner -->
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-eye-fill fs-5"></i>
  <div>
    <strong>Admin Preview Mode</strong> — Viewing as a student would. Progress is not recorded.
  </div>
  <a href="<?= $url('admin/courses/' . $course['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-dark ms-auto">
    <i class="bi bi-pencil me-1"></i> Back to Editor
  </a>
</div>

<div class="row g-4">

  <!-- Course info -->
  <div class="col-12 col-lg-8">
    <div class="card lms-card mb-4">
      <?php if ($course['thumbnail']): ?>
        <img src="<?= $e(APP_URL . '/storage/uploads/' . $course['thumbnail']) ?>"
             class="card-img-top" alt="Thumbnail"
             style="max-height:300px;object-fit:cover">
      <?php endif; ?>
      <div class="card-body p-4">
        <div class="d-flex gap-2 mb-3 flex-wrap">
          <?php if ($course['category_name']): ?>
            <span class="badge bg-primary"><?= $e($course['category_name']) ?></span>
          <?php endif; ?>
          <span class="badge bg-<?= $levelColors[$course['level']] ?? 'secondary' ?>">
            <?= ucfirst($e($course['level'])) ?>
          </span>
          <?php if ($course['is_rtl']): ?>
            <span class="badge bg-info">RTL</span>
          <?php endif; ?>
          <?php if ($course['certificate_enabled']): ?>
            <span class="badge bg-success"><i class="bi bi-award me-1"></i>Certificate</span>
          <?php endif; ?>
        </div>

        <h2 class="fw-bold mb-3"><?= $e($course['title']) ?></h2>

        <?php if ($course['short_description']): ?>
          <p class="lead text-muted" style="font-size:15px"><?= $e($course['short_description']) ?></p>
        <?php endif; ?>

        <?php if ($course['description']): ?>
          <div class="mt-3 course-description">
            <?= $course['description'] /* Already HTML from Quill */ ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar meta -->
  <div class="col-12 col-lg-4">
    <div class="card lms-card mb-3">
      <div class="card-body p-3">
        <dl class="row mb-0 small">
          <?php if ($course['duration_hours']): ?>
          <dt class="col-6 text-muted">Duration</dt>
          <dd class="col-6"><?= $e($course['duration_hours']) ?> hours</dd>
          <?php endif; ?>
          <dt class="col-6 text-muted">Language</dt>
          <dd class="col-6"><?= $e($course['language']) ?></dd>
          <dt class="col-6 text-muted">Pass Score</dt>
          <dd class="col-6"><?= $e($course['pass_percentage']) ?>%</dd>
          <?php if ($course['grade_points']): ?>
          <dt class="col-6 text-muted">Grade Points</dt>
          <dd class="col-6"><?= $e($course['grade_points']) ?></dd>
          <?php endif; ?>
          <?php if ($course['end_date']): ?>
          <dt class="col-6 text-muted">End Date</dt>
          <dd class="col-6 text-danger"><?= date('d M Y', strtotime($course['end_date'])) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>

    <!-- Curriculum -->
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h6 class="mb-0"><i class="bi bi-list-nested me-2"></i>Curriculum</h6>
      </div>
      <div class="card-body p-0">
        <?php foreach ($sections as $i => $sec): ?>
        <div class="border-bottom">
          <div class="px-3 py-2 d-flex justify-content-between align-items-center"
               style="background:var(--content-bg);cursor:pointer"
               data-bs-toggle="collapse"
               data-bs-target="#secPreview<?= $i ?>">
            <span class="fw-semibold" style="font-size:13.5px">
              <i class="bi bi-collection text-primary me-2"></i><?= $e($sec['title']) ?>
            </span>
            <span class="text-muted" style="font-size:12px"><?= count($sec['lessons']) ?> lessons</span>
          </div>
          <div class="collapse show" id="secPreview<?= $i ?>">
            <?php foreach ($sec['lessons'] as $les): ?>
            <?php $icon = $typeIcons[$les['type']] ?? 'bi-file'; $color = $typeColors[$les['type']] ?? 'secondary'; ?>
            <div class="d-flex align-items-center gap-2 px-3 py-2 border-top"
                 style="font-size:13px">
              <i class="bi <?= $icon ?> text-<?= $color ?>"></i>
              <span class="flex-grow-1"><?= $e($les['title']) ?></span>
              <?php if ($les['is_previewable']): ?>
                <span class="badge bg-success" style="font-size:10px">Free</span>
              <?php else: ?>
                <i class="bi bi-lock text-muted" style="font-size:11px"></i>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

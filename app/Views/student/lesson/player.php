<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$typeIcons = ['text'=>'bi-file-text','video'=>'bi-play-circle','document'=>'bi-file-pdf','scorm'=>'bi-box-seam','quiz'=>'bi-patch-question'];
?>

<div class="row g-0 lesson-player-wrap">

  <!-- LEFT: Curriculum sidebar -->
  <div class="col-12 col-lg-3 curriculum-sidebar" id="curriculumSidebar">
    <div class="curriculum-header">
      <div class="curriculum-course-title"><?= $e($course['title']) ?></div>
      <div class="curriculum-progress-wrap">
        <div class="d-flex justify-content-between mb-1" style="font-size:12px">
          <span style="color:rgba(255,255,255,.6)">Course Progress</span>
          <span style="color:#fff;font-weight:600"><?= $courseProgress ?>%</span>
        </div>
        <div style="height:5px;background:rgba(255,255,255,.15);border-radius:3px">
          <div style="height:5px;background:#6366f1;border-radius:3px;width:<?= $courseProgress ?>%"></div>
        </div>
      </div>
    </div>

    <div class="curriculum-body">
      <?php foreach ($sections as $si => $sec): ?>
      <div class="curriculum-section">
        <div class="curriculum-section-title">
          <i class="bi bi-collection me-2 opacity-50"></i>
          <?= $e($sec['title']) ?>
          <span class="ms-auto opacity-50" style="font-size:11px"><?= count($sec['lessons']) ?></span>
        </div>
        <?php foreach ($sec['lessons'] as $les): ?>
        <?php
          $icon      = $typeIcons[$les['type']] ?? 'bi-circle';
          $isActive  = (int)$les['id'] === (int)($currentLesson['id'] ?? 0);
          $isDone    = !empty($lessonProgress[$les['id']]) && $lessonProgress[$les['id']]['status'] === 'completed';
        ?>
        <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $les['id']) ?>"
           class="curriculum-lesson <?= $isActive ? 'active' : '' ?> <?= $isDone ? 'done' : '' ?>">
          <span class="lesson-status-dot">
            <?php if ($isDone): ?>
              <i class="bi bi-check-circle-fill" style="color:#0e9f6e"></i>
            <?php elseif ($isActive): ?>
              <i class="bi bi-play-circle-fill" style="color:#6366f1"></i>
            <?php else: ?>
              <i class="bi bi-circle" style="color:rgba(255,255,255,.25)"></i>
            <?php endif; ?>
          </span>
          <span class="lesson-title"><?= $e($les['title']) ?></span>
          <i class="bi <?= $icon ?> lesson-type-icon"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RIGHT: Lesson content area -->
  <div class="col-12 col-lg-9 lesson-content-area">

    <!-- Lesson topbar -->
    <div class="lesson-topbar">
      <button class="lesson-topbar-btn" id="toggleCurriculum" title="Toggle curriculum">
        <i class="bi bi-layout-sidebar-inset"></i>
      </button>
      <div class="lesson-topbar-title"><?= $e($currentLesson['title'] ?? 'Select a lesson') ?></div>
      <div class="ms-auto d-flex gap-2">
        <?php if ($prevLesson): ?>
        <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $prevLesson['id']) ?>"
           class="lesson-topbar-btn" title="Previous lesson">
          <i class="bi bi-arrow-left"></i>
        </a>
        <?php endif; ?>
        <?php if ($nextLesson): ?>
        <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $nextLesson['id']) ?>"
           class="lesson-topbar-btn" title="Next lesson">
          <i class="bi bi-arrow-right"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Lesson body -->
    <div class="lesson-body" id="lessonBody">
      <?php if (!$currentLesson): ?>
        <div class="text-center py-5 mt-4">
          <i class="bi bi-play-circle" style="font-size:4rem;color:var(--border-color)"></i>
          <h5 class="mt-3 fw-semibold" style="color:var(--text-muted)">Select a lesson to begin</h5>
          <p style="color:var(--text-muted);font-size:14px">Choose any lesson from the curriculum on the left.</p>
        </div>
      <?php else: ?>
        <?php $type = $currentLesson['type']; ?>

        <!-- VIDEO -->
        <?php if ($type === 'video'): ?>
          <?php $content = $currentLesson['content'] ?? ''; ?>
          <?php if ($currentLesson['video_type'] === 'youtube' && $content): ?>
            <?php preg_match('/(?:v=|youtu\.be\/)([^&\s]+)/', $content, $m); $vid = $m[1] ?? ''; ?>
            <div class="lesson-video-wrap">
              <iframe src="https://www.youtube.com/embed/<?= $e($vid) ?>?rel=0"
                      frameborder="0" allowfullscreen allow="autoplay; encrypted-media"></iframe>
            </div>
          <?php elseif ($currentLesson['video_type'] === 'vimeo' && $content): ?>
            <?php preg_match('/vimeo\.com\/(\d+)/', $content, $m); $vid = $m[1] ?? ''; ?>
            <div class="lesson-video-wrap">
              <iframe src="https://player.vimeo.com/video/<?= $e($vid) ?>"
                      frameborder="0" allowfullscreen></iframe>
            </div>
          <?php elseif ($currentLesson['file_path']): ?>
            <div class="lesson-video-wrap">
              <video controls style="width:100%;border-radius:12px">
                <source src="<?= $e(APP_URL . '/storage/uploads/' . $currentLesson['file_path']) ?>">
              </video>
            </div>
          <?php else: ?>
            <div class="lesson-placeholder"><i class="bi bi-play-circle"></i><p>Video not available</p></div>
          <?php endif; ?>

        <!-- TEXT -->
        <?php elseif ($type === 'text'): ?>
          <div class="lesson-text-content">
            <?= $currentLesson['content'] ?: '<p class="text-muted">No content yet.</p>' ?>
          </div>

        <!-- DOCUMENT -->
        <?php elseif ($type === 'document'): ?>
          <?php if ($currentLesson['file_path']): ?>
          <div class="text-center py-4">
            <i class="bi bi-file-pdf" style="font-size:4rem;color:#e02424"></i>
            <h5 class="mt-3 fw-semibold"><?= $e($currentLesson['title']) ?></h5>
            <p class="text-muted mb-4">Click below to view or download the document.</p>
            <a href="<?= $e(APP_URL . '/storage/uploads/' . $currentLesson['file_path']) ?>"
               target="_blank" class="sc-btn-start" style="display:inline-flex">
              <i class="bi bi-download me-1"></i> Open Document
            </a>
          </div>
          <?php else: ?>
            <div class="lesson-placeholder"><i class="bi bi-file-pdf"></i><p>Document not available</p></div>
          <?php endif; ?>

        <!-- QUIZ -->
        <?php elseif ($type === 'quiz'): ?>
          <div class="text-center py-5">
            <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 8px 24px rgba(99,102,241,.3)">
              <i class="bi bi-patch-question-fill" style="font-size:2.5rem;color:#fff"></i>
            </div>
            <h4 class="fw-bold mb-2"><?= $e($currentLesson['title']) ?></h4>
            <p class="text-muted mb-4">Ready to test your knowledge? This lesson has a quiz.</p>
            <a href="<?= $url('learn/courses/' . $course['uuid'] . '/quiz/' . $currentLesson['id']) ?>"
               class="sc-btn-start" style="display:inline-flex">
              <i class="bi bi-play-fill me-1"></i> Start Quiz
            </a>
          </div>

        <!-- SCORM -->
        <?php elseif ($type === 'scorm'): ?>
          <div class="text-center py-5">
            <i class="bi bi-box-seam" style="font-size:4rem;color:#0891b2"></i>
            <h5 class="mt-3 fw-semibold">SCORM Package</h5>
            <p class="text-muted">SCORM player will be available in the next update.</p>
          </div>
        <?php endif; ?>

        <!-- Mark Complete button -->
        <?php
          $isCompleted = !empty($lessonProgress[$currentLesson['id']]) &&
                         $lessonProgress[$currentLesson['id']]['status'] === 'completed';
        ?>
        <div class="lesson-actions">
          <?php if (!$isCompleted): ?>
          <form method="POST" action="<?= $url('learn/courses/' . $course['uuid'] . '/complete-lesson') ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="lesson_id"  value="<?= (int)$currentLesson['id'] ?>">
            <button type="submit" class="sc-btn-start">
              <i class="bi bi-check-circle me-1"></i> Mark as Complete
            </button>
          </form>
          <?php else: ?>
            <span style="color:#0e9f6e;font-weight:600;font-size:14px">
              <i class="bi bi-check-circle-fill me-1"></i> Lesson completed
            </span>
          <?php endif; ?>

          <?php if ($nextLesson): ?>
          <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $nextLesson['id']) ?>"
             class="sc-btn-start" style="background:linear-gradient(135deg,#0e9f6e,#059669)">
            Next Lesson <i class="bi bi-arrow-right ms-1"></i>
          </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
/* ── Lesson Player Layout ──────────────────────────────────────────────── */
.lesson-player-wrap {
  margin: -24px -20px;
  min-height: calc(100vh - 60px);
}

/* Curriculum sidebar */
.curriculum-sidebar {
  background: #0f172a;
  display: flex;
  flex-direction: column;
  min-height: calc(100vh - 60px);
  position: sticky;
  top: 60px;
  overflow-y: auto;
}
.curriculum-header {
  padding: 20px 16px 16px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}
.curriculum-course-title {
  color: #fff;
  font-weight: 700;
  font-size: 14.5px;
  line-height: 1.4;
  margin-bottom: 12px;
}
.curriculum-progress-wrap { margin-top: 8px; }
.curriculum-body { flex: 1; overflow-y: auto; padding: 8px 0 80px; }
.curriculum-section { margin-bottom: 4px; }
.curriculum-section-title {
  display: flex;
  align-items: center;
  padding: 10px 16px;
  color: rgba(255,255,255,.5);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .7px;
}
.curriculum-lesson {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  color: rgba(255,255,255,.65);
  text-decoration: none !important;
  font-size: 13.5px;
  transition: background .15s, color .15s;
  border-left: 3px solid transparent;
}
.curriculum-lesson:hover { background: rgba(255,255,255,.06); color: #fff; }
.curriculum-lesson.active { background: rgba(99,102,241,.2); color: #fff; border-left-color: #6366f1; }
.curriculum-lesson.done { color: rgba(255,255,255,.5); }
.lesson-status-dot { flex-shrink: 0; font-size: 15px; line-height: 1; }
.lesson-title { flex: 1; line-height: 1.35; }
.lesson-type-icon { font-size: 12px; opacity: .4; flex-shrink: 0; }

/* Lesson content */
.lesson-content-area { display: flex; flex-direction: column; }
.lesson-topbar {
  height: 52px;
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  padding: 0 16px;
  gap: 12px;
  position: sticky;
  top: 60px;
  z-index: 100;
}
.lesson-topbar-btn {
  width: 34px; height: 34px;
  border-radius: 8px;
  background: none;
  border: 1px solid var(--border-color);
  color: var(--text-muted);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 16px;
  text-decoration: none;
  transition: background .15s, color .15s;
}
.lesson-topbar-btn:hover { background: var(--content-bg); color: var(--primary); }
.lesson-topbar-title {
  font-weight: 600;
  font-size: 14.5px;
  color: var(--text-primary);
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.lesson-body { flex: 1; padding: 28px; }

/* Video */
.lesson-video-wrap {
  position: relative;
  padding-bottom: 56.25%;
  border-radius: 16px;
  overflow: hidden;
  background: #000;
  margin-bottom: 24px;
  box-shadow: 0 8px 32px rgba(0,0,0,.2);
}
.lesson-video-wrap iframe,
.lesson-video-wrap video {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
}

/* Text content */
.lesson-text-content {
  max-width: 760px;
  font-size: 15px;
  line-height: 1.75;
  color: var(--text-primary);
}
.lesson-text-content h1,.lesson-text-content h2,.lesson-text-content h3 { margin-top: 24px; margin-bottom: 12px; font-weight: 700; }
.lesson-text-content p { margin-bottom: 14px; }
.lesson-text-content ul,.lesson-text-content ol { padding-left: 24px; margin-bottom: 14px; }

/* Placeholder */
.lesson-placeholder {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-muted);
}
.lesson-placeholder i { font-size: 4rem; opacity: .3; }
.lesson-placeholder p { margin-top: 12px; font-size: 15px; }

/* Actions bar */
.lesson-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid var(--border-color);
}

/* SC start button (reuse from courses) */
.sc-btn-start {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: linear-gradient(135deg,#6366f1,#1a56db);
  color: #fff !important;
  text-decoration: none !important;
  border: none;
  border-radius: 10px;
  padding: 11px 24px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(99,102,241,.3);
  transition: opacity .15s, transform .1s;
}
.sc-btn-start:hover { opacity: .9; transform: translateY(-1px); }

@media (max-width: 991px) {
  .lesson-player-wrap { margin: -24px -20px; }
  .curriculum-sidebar {
    min-height: auto;
    position: static;
    max-height: 280px;
  }
  .lesson-body { padding: 16px; }
}
</style>

<script>
document.getElementById('toggleCurriculum')?.addEventListener('click', function () {
  const sidebar = document.getElementById('curriculumSidebar');
  sidebar.classList.toggle('d-none');
});
</script>

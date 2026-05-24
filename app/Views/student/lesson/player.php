<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$typeIcons   = ['text'=>'bi-file-text','video'=>'bi-play-circle-fill','document'=>'bi-file-pdf','scorm'=>'bi-box-seam','quiz'=>'bi-patch-question-fill'];
$typeColors  = ['text'=>'rgba(255,255,255,.5)','video'=>'#f87171','document'=>'#fbbf24','scorm'=>'#38bdf8','quiz'=>'#a78bfa'];
?>

<!-- Full player shell — overrides student layout padding -->
<div class="lp-shell" id="lpShell">

  <!-- ═══════════════════════════════════════════════════
       LEFT: Curriculum Sidebar (collapsible)
  ════════════════════════════════════════════════════ -->
  <aside class="lp-sidebar" id="lpSidebar">

    <!-- Header -->
    <div class="lp-sidebar-header">
      <div class="lp-course-title"><?= $e($course['title']) ?></div>
      <!-- Progress bar -->
      <div class="lp-progress-wrap">
        <div class="lp-progress-labels">
          <span>Progress</span>
          <span><?= $courseProgress ?>%</span>
        </div>
        <div class="lp-progress-track">
          <div class="lp-progress-fill" style="width:<?= $courseProgress ?>%"></div>
        </div>
      </div>
    </div>

    <!-- Sections accordion -->
    <div class="lp-sections" id="lpSections">
      <?php foreach ($sections as $si => $sec):
        $secLessons    = $sec['lessons'];
        $secTotal      = count($secLessons);
        $secCompleted  = count(array_filter($secLessons, fn($l) => !empty($lessonProgress[$l['id']]) && $lessonProgress[$l['id']]['status'] === 'completed'));
        $secHasActive  = in_array((int)($currentLesson['id'] ?? 0), array_column($secLessons, 'id'));
        $secOpen       = $secHasActive || $si === 0;
      ?>
      <div class="lp-section" id="lpSec<?= $si ?>">

        <!-- Section header (accordion trigger) -->
        <button class="lp-section-head <?= $secOpen ? 'open' : '' ?>"
                onclick="toggleSection(<?= $si ?>)"
                aria-expanded="<?= $secOpen ? 'true' : 'false' ?>">
          <div class="lp-sec-left">
            <i class="bi bi-chevron-right lp-sec-chevron" id="lpChevron<?= $si ?>"></i>
            <span class="lp-sec-title"><?= $e($sec['title']) ?></span>
          </div>
          <div class="lp-sec-right">
            <span class="lp-sec-count">
              <?= $secCompleted ?>/<?= $secTotal ?>
            </span>
            <?php if ($secCompleted === $secTotal && $secTotal > 0): ?>
              <i class="bi bi-check-circle-fill" style="color:#0e9f6e;font-size:13px"></i>
            <?php endif; ?>
          </div>
        </button>

        <!-- Section progress micro-bar -->
        <div class="lp-sec-bar">
          <div class="lp-sec-bar-fill"
               style="width:<?= $secTotal > 0 ? round($secCompleted/$secTotal*100) : 0 ?>%"></div>
        </div>

        <!-- Lessons list (collapsible) -->
        <div class="lp-lessons-wrap <?= $secOpen ? 'open' : '' ?>" id="lpLessons<?= $si ?>">
          <?php foreach ($secLessons as $les):
            $icon     = $typeIcons[$les['type']] ?? 'bi-circle';
            $iconClr  = $typeColors[$les['type']] ?? 'rgba(255,255,255,.4)';
            $isActive = (int)$les['id'] === (int)($currentLesson['id'] ?? 0);
            $isDone   = !empty($lessonProgress[$les['id']]) && $lessonProgress[$les['id']]['status'] === 'completed';
          ?>
          <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $les['id']) ?>"
             class="lp-lesson <?= $isActive ? 'active' : '' ?> <?= $isDone ? 'done' : '' ?>">

            <!-- Status icon -->
            <span class="lp-lesson-status">
              <?php if ($isDone): ?>
                <i class="bi bi-check-circle-fill" style="color:#0e9f6e"></i>
              <?php elseif ($isActive): ?>
                <span class="lp-playing-dot"></span>
              <?php else: ?>
                <i class="bi bi-circle" style="color:rgba(255,255,255,.2)"></i>
              <?php endif; ?>
            </span>

            <!-- Lesson info -->
            <div class="lp-lesson-info">
              <span class="lp-lesson-title"><?= $e($les['title']) ?></span>
              <?php if ($les['duration_sec']): ?>
                <span class="lp-lesson-dur">
                  <?= gmdate('i:s', (int)$les['duration_sec']) ?>
                </span>
              <?php endif; ?>
            </div>

            <!-- Type icon -->
            <i class="bi <?= $icon ?>" style="color:<?= $iconClr ?>;font-size:13px;flex-shrink:0"></i>
          </a>
          <?php endforeach; ?>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

  </aside>

  <!-- ═══════════════════════════════════════════════════
       RIGHT: Lesson content
  ════════════════════════════════════════════════════ -->
  <div class="lp-content" id="lpContent">

    <!-- Player topbar -->
    <div class="lp-topbar" id="lpTopbar">

      <!-- Sidebar toggle -->
      <button class="lp-topbar-btn" id="lpSidebarToggle" title="Toggle curriculum">
        <i class="bi bi-layout-sidebar-inset"></i>
      </button>

      <!-- Lesson title -->
      <div class="lp-topbar-title">
        <?= $e($currentLesson['title'] ?? 'Select a lesson') ?>
      </div>

      <div class="lp-topbar-right">
        <!-- Forum link (if enabled) -->
        <?php if ($course['forum_enabled']): ?>
        <a href="<?= $url('learn/courses/' . $course['uuid'] . '/forum') ?>"
           class="lp-topbar-btn" title="Course Forum">
          <i class="bi bi-chat-dots"></i>
        </a>
        <?php endif; ?>

        <!-- Prev lesson -->
        <?php if ($prevLesson): ?>
        <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $prevLesson['id']) ?>"
           class="lp-topbar-btn lp-prev-btn" title="Previous: <?= $e($prevLesson['title']) ?>">
          <i class="bi bi-skip-backward-fill"></i>
        </a>
        <?php endif; ?>

        <!-- Next lesson -->
        <?php if ($nextLesson): ?>
        <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $nextLesson['id']) ?>"
           class="lp-topbar-btn lp-next-btn" title="Next: <?= $e($nextLesson['title']) ?>">
          <i class="bi bi-skip-forward-fill"></i>
        </a>
        <?php endif; ?>

        <!-- Fullscreen toggle -->
        <button class="lp-topbar-btn lp-fullscreen-btn" id="lpFullscreenBtn" title="Fullscreen">
          <i class="bi bi-fullscreen" id="lpFsIcon"></i>
        </button>

        <!-- Exit player -->
        <a href="<?= $url('learn/courses') ?>" class="lp-topbar-btn" title="Back to courses">
          <i class="bi bi-x-lg"></i>
        </a>
      </div>
    </div>

    <!-- Lesson body -->
    <div class="lp-body" id="lpBody">
      <?php if (!$currentLesson): ?>
        <!-- No lesson selected -->
        <div class="lp-empty">
          <i class="bi bi-collection-play"></i>
          <h5>Select a lesson to begin</h5>
          <p>Choose any lesson from the curriculum on the left.</p>
        </div>

      <?php else:
        $type = $currentLesson['type'];
        $isCompleted = !empty($lessonProgress[$currentLesson['id']]) &&
                       $lessonProgress[$currentLesson['id']]['status'] === 'completed';
      ?>

        <!-- ── VIDEO ── -->
        <?php if ($type === 'video'): ?>
          <?php $content = $currentLesson['content'] ?? ''; ?>
          <?php if ($currentLesson['video_type'] === 'youtube' && $content): ?>
            <?php preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $content, $m); $vid = $m[1] ?? ''; ?>
            <div class="lp-video-wrap">
              <iframe src="https://www.youtube.com/embed/<?= $e($vid) ?>?rel=0&modestbranding=1"
                      frameborder="0" allowfullscreen
                      allow="autoplay; encrypted-media; picture-in-picture"></iframe>
            </div>
          <?php elseif ($currentLesson['video_type'] === 'vimeo' && $content): ?>
            <?php preg_match('/vimeo\.com\/(\d+)/', $content, $m); $vid = $m[1] ?? ''; ?>
            <div class="lp-video-wrap">
              <iframe src="https://player.vimeo.com/video/<?= $e($vid) ?>?color=6366f1&byline=0"
                      frameborder="0" allowfullscreen></iframe>
            </div>
          <?php elseif ($currentLesson['file_path']): ?>
            <div class="lp-video-wrap" style="padding-bottom:0;height:auto">
              <video controls style="width:100%;border-radius:12px;max-height:70vh">
                <source src="<?= $e(APP_URL . '/storage/uploads/' . $currentLesson['file_path']) ?>">
                Your browser does not support video.
              </video>
            </div>
          <?php else: ?>
            <div class="lp-empty"><i class="bi bi-camera-video-off"></i><p>Video not configured.</p></div>
          <?php endif; ?>

        <!-- ── TEXT ── -->
        <?php elseif ($type === 'text'): ?>
          <div class="lp-text-content">
            <?= $currentLesson['content'] ?: '<p class="text-muted">No content added yet.</p>' ?>
          </div>

        <!-- ── DOCUMENT ── -->
        <?php elseif ($type === 'document'): ?>
          <?php if ($currentLesson['file_path']): ?>
          <div class="lp-doc-wrap">
            <i class="bi bi-file-pdf-fill lp-doc-icon"></i>
            <h4><?= $e($currentLesson['title']) ?></h4>
            <p>Click below to open the document.</p>
            <a href="<?= $e(APP_URL . '/storage/uploads/' . $currentLesson['file_path']) ?>"
               target="_blank" class="lp-cta-btn">
              <i class="bi bi-box-arrow-up-right me-2"></i> Open Document
            </a>
          </div>
          <?php else: ?>
            <div class="lp-empty"><i class="bi bi-file-earmark-x"></i><p>Document not uploaded yet.</p></div>
          <?php endif; ?>

        <!-- ── QUIZ ── -->
        <?php elseif ($type === 'quiz'): ?>
          <div class="lp-doc-wrap">
            <div class="lp-quiz-icon"><i class="bi bi-patch-question-fill"></i></div>
            <h4><?= $e($currentLesson['title']) ?></h4>
            <p>Test your knowledge with this quiz.</p>
            <a href="<?= $url('learn/courses/' . $course['uuid'] . '/quiz/' . $currentLesson['id']) ?>"
               class="lp-cta-btn">
              <i class="bi bi-play-fill me-2"></i> Start Quiz
            </a>
          </div>

        <!-- ── SCORM ── -->
        <?php elseif ($type === 'scorm'): ?>
          <div class="lp-empty">
            <i class="bi bi-box-seam" style="color:#38bdf8"></i>
            <h5>SCORM Package</h5>
            <p>SCORM player coming soon.</p>
          </div>
        <?php endif; ?>

        <!-- ── Actions bar ── -->
        <div class="lp-actions">

          <!-- Mark complete -->
          <?php if (!$isCompleted): ?>
          <form method="POST"
                action="<?= $url('learn/courses/' . $course['uuid'] . '/complete-lesson') ?>">
            <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
            <input type="hidden" name="lesson_id"  value="<?= (int)$currentLesson['id'] ?>">
            <button type="submit" class="lp-cta-btn">
              <i class="bi bi-check-circle me-2"></i> Mark as Complete
            </button>
          </form>
          <?php else: ?>
            <div class="lp-completed-badge">
              <i class="bi bi-check-circle-fill me-2"></i> Lesson Completed
            </div>
          <?php endif; ?>

          <!-- Next lesson shortcut -->
          <?php if ($nextLesson): ?>
          <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $nextLesson['id']) ?>"
             class="lp-cta-btn lp-cta-next">
            Next: <?= $e(mb_strimwidth($nextLesson['title'], 0, 32, '…')) ?>
            <i class="bi bi-arrow-right ms-2"></i>
          </a>
          <?php endif; ?>
        </div>

      <?php endif; ?>
    </div><!-- /.lp-body -->
  </div><!-- /.lp-content -->
</div><!-- /.lp-shell -->

<style>
/* ══════════════════════════════════════════════════════════
   LESSON PLAYER — Full shell
══════════════════════════════════════════════════════════ */
.lp-shell {
  display: flex;
  height: 100%; /* fills st-content in player-mode */
  overflow: hidden;
  background: var(--content-bg);
}

/* ── Sidebar ─────────────────────────────────────────────── */
.lp-sidebar {
  width: 300px;
  min-width: 300px;
  background: #0f172a;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: width .25s ease, min-width .25s ease;
  flex-shrink: 0;
}
.lp-shell.sidebar-hidden .lp-sidebar {
  width: 0;
  min-width: 0;
}

.lp-sidebar-header {
  padding: 18px 16px 14px;
  border-bottom: 1px solid rgba(255,255,255,.07);
  flex-shrink: 0;
}
.lp-course-title {
  color: #fff;
  font-weight: 700;
  font-size: 13.5px;
  line-height: 1.4;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.lp-progress-labels {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: rgba(255,255,255,.5);
  margin-bottom: 5px;
}
.lp-progress-labels span:last-child { color: #fff; font-weight: 700; }
.lp-progress-track {
  height: 5px;
  background: rgba(255,255,255,.1);
  border-radius: 3px;
  overflow: hidden;
}
.lp-progress-fill {
  height: 100%;
  background: linear-gradient(90deg,#6366f1,#1a56db);
  border-radius: 3px;
  transition: width .4s ease;
}

/* ── Sections ────────────────────────────────────────────── */
.lp-sections {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 24px;
}
.lp-sections::-webkit-scrollbar { width: 4px; }
.lp-sections::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 2px; }

.lp-section { border-bottom: 1px solid rgba(255,255,255,.06); }

/* Section accordion header */
.lp-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 12px 16px;
  background: none;
  border: none;
  cursor: pointer;
  transition: background .15s;
}
.lp-section-head:hover { background: rgba(255,255,255,.04); }
.lp-section-head.open { background: rgba(99,102,241,.08); }

.lp-sec-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
.lp-sec-chevron {
  color: rgba(255,255,255,.4);
  font-size: 11px;
  transition: transform .2s ease;
  flex-shrink: 0;
}
.lp-section-head.open .lp-sec-chevron { transform: rotate(90deg); color: #6366f1; }
.lp-sec-title {
  color: rgba(255,255,255,.8);
  font-size: 12.5px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.lp-section-head.open .lp-sec-title { color: #fff; }

.lp-sec-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.lp-sec-count { font-size: 11px; color: rgba(255,255,255,.35); }

/* Section progress micro-bar */
.lp-sec-bar {
  height: 2px;
  background: rgba(255,255,255,.06);
}
.lp-sec-bar-fill {
  height: 100%;
  background: #6366f1;
  transition: width .3s ease;
}

/* Lessons collapsible panel */
.lp-lessons-wrap {
  max-height: 0;
  overflow: hidden;
  transition: max-height .3s ease;
}
.lp-lessons-wrap.open { max-height: 1000px; }

/* Lesson row */
.lp-lesson {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px 10px 28px;
  color: rgba(255,255,255,.55);
  text-decoration: none !important;
  font-size: 13px;
  border-left: 2px solid transparent;
  transition: background .15s, color .15s, border-color .15s;
}
.lp-lesson:hover { background: rgba(255,255,255,.05); color: rgba(255,255,255,.85); }
.lp-lesson.active {
  background: rgba(99,102,241,.18);
  color: #fff;
  border-left-color: #6366f1;
}
.lp-lesson.done { color: rgba(255,255,255,.45); }
.lp-lesson.done:hover { color: rgba(255,255,255,.75); }

.lp-lesson-status { width: 18px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }

/* Animated playing dot */
.lp-playing-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: #6366f1;
  animation: lpPulse 1.4s ease-in-out infinite;
  display: inline-block;
}
@keyframes lpPulse {
  0%,100% { opacity: 1; transform: scale(1); }
  50% { opacity: .5; transform: scale(.75); }
}

.lp-lesson-info { flex: 1; min-width: 0; }
.lp-lesson-title { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lp-lesson-dur { display: block; font-size: 11px; color: rgba(255,255,255,.3); margin-top: 2px; }

/* ── Content area ────────────────────────────────────────── */
.lp-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
}

/* Topbar */
.lp-topbar {
  height: 52px;
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  padding: 0 14px;
  gap: 8px;
  flex-shrink: 0;
}
.lp-topbar-title {
  flex: 1;
  font-weight: 600;
  font-size: 14px;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 0 8px;
}
.lp-topbar-right { display: flex; align-items: center; gap: 4px; }

.lp-topbar-btn {
  width: 34px; height: 34px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: transparent;
  color: var(--text-muted);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  font-size: 15px;
  text-decoration: none;
  transition: background .15s, color .15s, border-color .15s;
  flex-shrink: 0;
}
.lp-topbar-btn:hover { background: var(--content-bg); color: var(--primary); border-color: var(--primary); }

/* Fullscreen button — highlight when active */
.lp-shell.fullscreen .lp-fullscreen-btn {
  background: #6366f1;
  color: #fff !important;
  border-color: #6366f1;
}

/* Lesson body */
.lp-body {
  flex: 1;
  overflow-y: auto;
  padding: 28px 32px;
}
.lp-body::-webkit-scrollbar { width: 6px; }
.lp-body::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }

/* Video */
.lp-video-wrap {
  position: relative;
  padding-bottom: 56.25%;
  border-radius: 14px;
  overflow: hidden;
  background: #000;
  margin-bottom: 24px;
  box-shadow: 0 8px 32px rgba(0,0,0,.2);
}
.lp-video-wrap iframe {
  position: absolute; top:0; left:0; width:100%; height:100%;
}

/* Text content */
.lp-text-content {
  max-width: 720px;
  font-size: 15.5px;
  line-height: 1.8;
  color: var(--text-primary);
}
.lp-text-content h1,.lp-text-content h2,.lp-text-content h3 { font-weight:700; margin:24px 0 12px; }
.lp-text-content p { margin-bottom:14px; }
.lp-text-content ul,.lp-text-content ol { padding-left:24px; margin-bottom:14px; }
.lp-text-content code { background:var(--content-bg); padding:2px 6px; border-radius:4px; font-size:13.5px; }
.lp-text-content pre { background:var(--content-bg); padding:16px; border-radius:8px; overflow-x:auto; }

/* Document / Quiz centered cards */
.lp-doc-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 60px 24px;
}
.lp-doc-icon { font-size: 5rem; color: #e02424; margin-bottom: 16px; }
.lp-quiz-icon {
  width: 80px; height: 80px;
  border-radius: 20px;
  background: linear-gradient(135deg,#6366f1,#1a56db);
  display: flex; align-items: center; justify-content: center;
  font-size: 2.5rem; color: #fff; margin: 0 auto 16px;
  box-shadow: 0 8px 24px rgba(99,102,241,.3);
}
.lp-doc-wrap h4 { font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
.lp-doc-wrap p  { color: var(--text-muted); margin-bottom: 24px; }

/* Empty state */
.lp-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 80px 24px;
  color: var(--text-muted);
}
.lp-empty i { font-size: 4rem; opacity: .25; margin-bottom: 14px; }
.lp-empty h5 { font-weight: 600; margin-bottom: 6px; }
.lp-empty p  { font-size: 14px; }

/* CTA buttons */
.lp-cta-btn {
  display: inline-flex;
  align-items: center;
  background: linear-gradient(135deg,#6366f1,#1a56db);
  color: #fff !important;
  text-decoration: none !important;
  border: none;
  border-radius: 10px;
  padding: 11px 26px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(99,102,241,.3);
  transition: opacity .15s, transform .1s;
}
.lp-cta-btn:hover { opacity: .9; transform: translateY(-1px); }
.lp-cta-next {
  background: linear-gradient(135deg,#0f172a,#1e293b);
  box-shadow: 0 4px 14px rgba(0,0,0,.2);
}

/* Actions bar */
.lp-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid var(--border-color);
}
.lp-completed-badge {
  display: inline-flex;
  align-items: center;
  color: #0e9f6e;
  font-weight: 700;
  font-size: 14px;
  padding: 10px 20px;
  background: #d1fae5;
  border-radius: 10px;
}

/* ── Fullscreen mode ─────────────────────────────────────── */
.lp-shell.fullscreen {
  position: fixed !important;
  top: 0 !important; left: 0 !important;
  right: 0 !important; bottom: 0 !important;
  z-index: 9999 !important;
  height: 100vh !important;
  margin: 0 !important;
  border-radius: 0 !important;
}

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 991px) {
  .lp-shell { height: calc(100vh - 60px - 64px); /* minus bottom nav */ }
  .lp-sidebar {
    position: fixed;
    top: 60px; left: 0; bottom: 64px;
    z-index: 800;
    transform: translateX(-100%);
    transition: transform .25s ease;
    width: 280px !important;
    min-width: 280px !important;
  }
  .lp-shell.sidebar-visible .lp-sidebar {
    transform: translateX(0);
    box-shadow: 4px 0 20px rgba(0,0,0,.3);
  }
  .lp-body { padding: 16px; }
}
</style>

<script>
// ── Section accordion ─────────────────────────────────────────────────────────
function toggleSection(idx) {
  const head    = document.getElementById('lpSec' + idx).querySelector('.lp-section-head');
  const lessons = document.getElementById('lpLessons' + idx);
  const isOpen  = head.classList.contains('open');

  // Toggle this section
  head.classList.toggle('open', !isOpen);
  lessons.classList.toggle('open', !isOpen);
  head.setAttribute('aria-expanded', String(!isOpen));
}

// ── Sidebar toggle ────────────────────────────────────────────────────────────
const lpShell   = document.getElementById('lpShell');
const isMobile  = () => window.innerWidth < 992;

document.getElementById('lpSidebarToggle').addEventListener('click', function () {
  if (isMobile()) {
    lpShell.classList.toggle('sidebar-visible');
  } else {
    lpShell.classList.toggle('sidebar-hidden');
  }
});

// Close mobile sidebar when clicking lesson link
document.querySelectorAll('.lp-lesson').forEach(a => {
  a.addEventListener('click', () => {
    if (isMobile()) lpShell.classList.remove('sidebar-visible');
  });
});

// Close mobile sidebar clicking outside
document.addEventListener('click', function (e) {
  if (!isMobile()) return;
  if (!e.target.closest('#lpSidebar') && !e.target.closest('#lpSidebarToggle')) {
    lpShell.classList.remove('sidebar-visible');
  }
});

// ── Fullscreen toggle ─────────────────────────────────────────────────────────
const fsBtn  = document.getElementById('lpFullscreenBtn');
const fsIcon = document.getElementById('lpFsIcon');

function enterFullscreen() {
  document.getElementById('stTopbar')?.style.setProperty('display','none');
window.__topbarHidden = true;
  document.getElementById('bottomNav')?.style.setProperty('display','none');
  
  lpShell.style.height = '100vh';
  lpShell.classList.add('fullscreen');
  fsIcon.className = 'bi bi-fullscreen-exit';
  sessionStorage.setItem('lp_fullscreen', '1');
}

function exitFullscreen() {
  document.getElementById('stTopbar')?.style.removeProperty('display');
window.__topbarHidden = false;
  document.getElementById('bottomNav')?.style.removeProperty('display');
  
  lpShell.style.height = '';
  lpShell.classList.remove('fullscreen');
  fsIcon.className = 'bi bi-fullscreen';
  sessionStorage.removeItem('lp_fullscreen');
}

// Restore fullscreen state on page load
if (sessionStorage.getItem('lp_fullscreen') === '1') {
  // Small delay so DOM is ready
  setTimeout(enterFullscreen, 50);
}

fsBtn.addEventListener('click', function () {
  lpShell.classList.contains('fullscreen') ? exitFullscreen() : enterFullscreen();
});

// Escape key exits
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape' && lpShell.classList.contains('fullscreen')) exitFullscreen();
});
</script>

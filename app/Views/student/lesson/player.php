<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$typeIcons   = ['text'=>'bi-file-text','video'=>'bi-play-circle-fill','document'=>'bi-file-pdf','scorm'=>'bi-box-seam','quiz'=>'bi-patch-question-fill'];
$typeColors  = ['text'=>'rgba(255,255,255,.5)','video'=>'#f87171','document'=>'#fbbf24','scorm'=>'#38bdf8','quiz'=>'#a78bfa'];
?>

<!-- Full player shell — overrides student layout padding -->
<div class="lp-shell" id="lpShell" data-lesson-id="<?= $currentLesson['id'] ?>" data-course-id="<?= $course['id'] ?>">

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
          <?php
            $scormExtracted = \App\Services\ScormService::isExtracted((int)$currentLesson['id']);
            $scormEntry     = '';
            if ($scormExtracted) {
              try {
                $scormEntry = \App\Services\ScormService::findEntryPoint(
                  STORE_PATH . '/scorm_packages/' . $currentLesson['id'] . '/',
                  (int)$currentLesson['id']
                );
              } catch (\Throwable $e) { $scormEntry = ''; }
            }
            $scormSrc = $scormExtracted && $scormEntry
              ? (APP_URL . '/scorm/' . $currentLesson['id'] . '/' . ltrim($scormEntry, '/'))
              : '';
          ?>

          <?php if ($scormSrc): ?>
          <!-- SCORM player shell -->
          <div class="scorm-player-wrap" id="scormPlayerWrap">
            <!-- Toolbar -->
            <div class="scorm-toolbar">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-info me-1"></i>
                <span class="fw-semibold" style="font-size:13.5px"><?= $e($currentLesson['title']) ?></span>
                <span class="badge bg-info-subtle text-info ms-1" style="font-size:11px">SCORM</span>
              </div>
              <div class="scorm-toolbar-right">
                <span id="scormStatusBadge" class="badge bg-secondary" style="font-size:11.5px">Loading…</span>
                <button class="scorm-toolbar-btn" onclick="document.getElementById('scormFrame').requestFullscreen()" title="Fullscreen content">
                  <i class="bi bi-fullscreen"></i>
                </button>
                <button class="scorm-toolbar-btn" onclick="reloadScorm()" title="Restart">
                  <i class="bi bi-arrow-clockwise"></i>
                </button>
              </div>
            </div>
            <!-- iframe -->
            <iframe
              id="scormFrame"
              src="<?= $e($scormSrc) ?>"
              class="scorm-frame"
              allow="fullscreen"
              allowfullscreen
            ></iframe>
          </div>

          <style>
          .scorm-player-wrap {
            display: flex; flex-direction: column;
            border: 1px solid var(--border-color);
            border-radius: 14px; overflow: hidden;
            background: #000;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,.15);
          }
          .scorm-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
          }
          .scorm-toolbar-right { display: flex; align-items: center; gap: 8px; }
          .scorm-toolbar-btn {
            width: 30px; height: 30px; border-radius: 7px;
            border: 1px solid var(--border-color); background: transparent;
            color: var(--text-muted); cursor: pointer; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
            transition: all .15s;
          }
          .scorm-toolbar-btn:hover { background: var(--content-bg); color: var(--primary); }
          .scorm-frame {
            width: 100%; border: none;
            height: 600px; min-height: 500px;
            background: #fff;
          }
          .bg-info-subtle { background: #e0f7fa !important; }
          </style>

          <script>
          (function () {
            const LESSON_ID    = <?= (int)$currentLesson['id'] ?>;
            const ENROLLMENT_ID= <?= (int)($enrollment['id'] ?? 0) ?>;
            const CSRF         = document.getElementById('csrfToken')?.value || '<?= $e($csrf_token) ?>';
            const API_URL      = '<?= APP_URL ?>/scorm/api/' + LESSON_ID;
            const frame        = document.getElementById('scormFrame');
            const statusBadge  = document.getElementById('scormStatusBadge');

            // ── In-memory SCORM data store ──────────────────────────────────
            let scormData = {};
            let saveTimer = null;
            let isCompleted = false;

            // ── Status badge helper ────────────────────────────────────────
            function setStatus(text, cls) {
              if (!statusBadge) return;
              statusBadge.textContent  = text;
              statusBadge.className    = 'badge ' + cls;
            }

            // ── Persist to server ──────────────────────────────────────────
            function persistData() {
              fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=' + encodeURIComponent(CSRF)
                    + '&action=save'
                    + '&data=' + encodeURIComponent(JSON.stringify(scormData)),
              }).then(r => r.json()).then(d => {
                if (d.success && isCompleted) {
                  setStatus('Completed ✓', 'bg-success');
                }
              }).catch(() => {});
            }

            // Load saved progress from server
            function loadProgress() {
              fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=' + encodeURIComponent(CSRF) + '&action=load',
              }).then(r => r.json()).then(d => {
                if (d.success && d.data) scormData = d.data;
              }).catch(() => {});
            }

            // ── SCORM 1.2 API (window.API) ────────────────────────────────
            function makeApi12() {
              return {
                LMSInitialize: function () {
                  loadProgress();
                  setStatus('In Progress', 'bg-primary');
                  return 'true';
                },
                LMSFinish: function () {
                  persistData();
                  return 'true';
                },
                LMSGetValue: function (key) {
                  return scormData[key] !== undefined ? String(scormData[key]) : '';
                },
                LMSSetValue: function (key, value) {
                  scormData[key] = value;
                  // Track status
                  const s = scormData['cmi.core.lesson_status'] || '';
                  if (s === 'passed' || s === 'completed') {
                    isCompleted = true;
                    setStatus('Completed ✓', 'bg-success');
                  } else if (s === 'failed') {
                    setStatus('Failed', 'bg-danger');
                  } else if (s === 'incomplete') {
                    setStatus('In Progress', 'bg-warning');
                  }
                  // Debounced save
                  clearTimeout(saveTimer);
                  saveTimer = setTimeout(persistData, 5000);
                  return 'true';
                },
                LMSCommit: function () { persistData(); return 'true'; },
                LMSGetLastError:   function () { return '0'; },
                LMSGetErrorString: function () { return ''; },
                LMSGetDiagnostic:  function () { return ''; },
              };
            }

            // ── SCORM 2004 API (window.API_1484_11) ───────────────────────
            function makeApi2004() {
              return {
                Initialize: function () {
                  loadProgress();
                  setStatus('In Progress', 'bg-primary');
                  return 'true';
                },
                Terminate: function () {
                  persistData();
                  return 'true';
                },
                GetValue: function (key) {
                  return scormData[key] !== undefined ? String(scormData[key]) : '';
                },
                SetValue: function (key, value) {
                  scormData[key] = value;
                  const completion = scormData['cmi.completion_status'] || '';
                  const success    = scormData['cmi.success_status'] || '';
                  if (completion === 'completed' || success === 'passed') {
                    isCompleted = true;
                    setStatus('Completed ✓', 'bg-success');
                  } else if (completion === 'incomplete') {
                    setStatus('In Progress', 'bg-warning');
                  }
                  clearTimeout(saveTimer);
                  saveTimer = setTimeout(persistData, 5000);
                  return 'true';
                },
                Commit:           function () { persistData(); return 'true'; },
                GetLastError:     function () { return '0'; },
                GetErrorString:   function () { return ''; },
                GetDiagnostic:    function () { return ''; },
              };
            }

            // ── Inject API into iframe when it loads ─────────────────────
            frame.addEventListener('load', function () {
              try {
                const w = frame.contentWindow;
                if (!w) return;
                // Inject both SCORM 1.2 and 2004 APIs
                w.API         = makeApi12();
                w.API_1484_11 = makeApi2004();
                // Also make the frame's parent chain see our API
                setStatus('Running', 'bg-primary');
              } catch (e) {
                // Cross-origin — APIs already injected via same-origin serving
                console.warn('SCORM API injection:', e.message);
              }
            });

            // Also inject at global level so SCORM content can find API
            // by traversing window.parent chain
            window.API         = makeApi12();
            window.API_1484_11 = makeApi2004();

            // ── Save before page unload ───────────────────────────────────
            window.addEventListener('beforeunload', function () {
              persistData();
            });

            // ── Reload helper ─────────────────────────────────────────────
            window.reloadScorm = function () {
              scormData = {};
              frame.src = frame.src;
              setStatus('Restarted', 'bg-secondary');
            };

          })();
          </script>

          <?php else: ?>
          <!-- Package not extracted yet -->
          <div class="lp-doc-wrap">
            <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,#0891b2,#0e7490);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 8px 24px rgba(8,145,178,.3)">
              <i class="bi bi-box-seam" style="font-size:2.5rem;color:#fff"></i>
            </div>
            <h4>SCORM Package</h4>
            <p style="color:var(--text-muted)">
              This SCORM package is being processed. Please save the lesson again<br>
              or contact your administrator if this persists.
            </p>
            <?php if ($currentLesson['file_path']): ?>
            <p class="text-muted small">Package: <?= $e(basename($currentLesson['file_path'])) ?></p>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        <?php endif; ?>

        <!-- ── Actions bar ── -->
        <div class="lp-actions">

          <!-- Mark complete -->
          <?php if (!$isCompleted): ?>
          <form method="POST"
                action="<?= $url('learn/courses/' . $course['uuid'] . '/complete-lesson') ?>">
            <input type="hidden" name="csrf_token" id="csrfToken" value="<?= $e($csrf_token) ?>">
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



          <!-- ── Notes & Comments panel ─────────────── -->
          <div class="lp-collab-panel" id="lpCollabPanel"
               <?= !($featureNotes ?? true) ? 'style="display:none!important;width:0;min-width:0;overflow:hidden"' : '' ?>>
            <div class="lp-collab-tabs">
              <button class="lp-collab-tab active" data-panel="notes"><i class="bi bi-journal-text"></i><span>Notes</span></button>
              <button class="lp-collab-tab" data-panel="comments"><i class="bi bi-chat-dots"></i><span>Comments</span></button>
              <button class="lp-collab-tab" data-panel="qa"><i class="bi bi-question-circle"></i><span>Ask</span></button>
              <button class="lp-collab-tab lp-collab-close-btn" id="collabCloseBtn" title="Hide panel"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="lp-collab-content" id="notesPanel">
              <div class="lp-note-form">
                <textarea id="noteInput" placeholder="Add a note about this lesson…" rows="3"></textarea>
                <div class="d-flex gap-2 mt-2">
                  <button class="btn btn-sm btn-primary flex-grow-1" id="saveNoteBtn"><i class="bi bi-save me-1"></i>Save</button>
                  <a href="<?= APP_URL ?>/api/lessons/<?= $currentLesson['id'] ?>/notes/export/<?= $course['id'] ?>" class="btn btn-sm btn-outline-secondary" download title="Export notes"><i class="bi bi-download"></i></a>
                </div>
              </div>
              <div id="notesList" class="lp-notes-list mt-2"></div>
            </div>
            <div class="lp-collab-content d-none" id="commentsPanel">
              <div id="commentsList" class="lp-comments-list"></div>
              <div class="lp-comment-form mt-2">
                <textarea id="commentInput" placeholder="Comment on this lesson…" rows="2"></textarea>
                <button class="btn btn-sm btn-primary w-100 mt-2" id="postCommentBtn"><i class="bi bi-send me-1"></i>Post</button>
              </div>
            </div>
            <div class="lp-collab-content d-none" id="qaPanel">
              <p style="font-size:12.5px;color:rgba(255,255,255,.5)">Your question posts to the course forum for instructors and peers.</p>
              <input type="text" id="qaTitle" class="form-control mb-2" placeholder="Question title…" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1);color:#f1f5f9">
              <textarea id="qaBody" class="form-control mb-2" rows="3" placeholder="Details…" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1);color:#f1f5f9"></textarea>
              <button class="btn btn-sm btn-primary w-100" id="askQuestionBtn"><i class="bi bi-question-circle me-1"></i>Post Question</button>
            </div>
          </div>
</div><!-- /.lp-shell -->

<!-- ═══════════════════════════════════════════════════════════════
     AI TUTOR — Instructor Avatar Panel
     Features: voice input, TTS output, animated avatar, text chat
════════════════════════════════════════════════════════════════ -->
<div class="lp-ai-panel" id="lpAiPanel">

  <!-- ── Avatar section ─────────────────────────────────────────── -->
  <div class="lp-ai-avatar-section" id="aiAvatarSection">
    <div class="lp-ai-avatar-wrap" id="aiAvatarWrap">
      <?php
        $instrPhoto = $instructor['profile_photo'] ?? null;
        $instrName  = trim(($instructor['first_name'] ?? 'AI') . ' ' . ($instructor['last_name'] ?? 'Tutor'));
      ?>
      <?php if ($instrPhoto): ?>
        <img src="<?= APP_URL ?>/storage/uploads/<?= $e($instrPhoto) ?>"
             alt="<?= $e($instrName) ?>"
             class="lp-ai-instructor-img" id="aiInstructorImg">
      <?php else: ?>
        <div class="lp-ai-instructor-initials" id="aiInstructorImg">
          <?= $e(mb_strtoupper(mb_substr($instructor['first_name'] ?? 'A', 0, 1) . mb_substr($instructor['last_name'] ?? 'T', 0, 1))) ?>
        </div>
      <?php endif; ?>
      <!-- Speaking rings — pulse when AI is talking -->
      <div class="lp-ai-ring lp-ai-ring-1" id="aiRing1"></div>
      <div class="lp-ai-ring lp-ai-ring-2" id="aiRing2"></div>
      <div class="lp-ai-ring lp-ai-ring-3" id="aiRing3"></div>
    </div>

    <div class="lp-ai-tutor-name"><?= $e($instrName) ?></div>
    <div class="lp-ai-tutor-role">AI Tutor · <span class="lp-ai-dot-inline"></span> Online</div>

    <!-- Speaking waveform (shown when TTS is playing) -->
    <div class="lp-ai-waveform" id="aiWaveform">
      <?php for ($w = 0; $w < 20; $w++): ?>
        <div class="lp-ai-wave-bar" style="animation-delay:<?= $w * 0.07 ?>s"></div>
      <?php endfor; ?>
    </div>

    <!-- Status text under avatar -->
    <div class="lp-ai-status-text" id="aiStatusText">Ask me anything about this lesson</div>
  </div>

  <!-- ── Header controls ────────────────────────────────────────── -->
  <div class="lp-ai-controls-bar">
    <button class="lp-ai-ctrl-btn" id="aiMinimiseBtn" title="Minimise">
      <i class="bi bi-dash-lg"></i>
    </button>
    <button class="lp-ai-ctrl-btn" id="aiSoundToggle" title="Toggle voice output">
      <i class="bi bi-volume-up-fill" id="aiSoundIcon"></i>
    </button>
    <button class="lp-ai-ctrl-btn" onclick="document.getElementById('lpAiPanel').classList.remove('open')" title="Close">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- ── Quick actions ──────────────────────────────────────────── -->
  <div class="lp-ai-quick-row" id="aiQuickRow">
    <button class="lp-ai-chip" id="aiSummaryBtn">
      <i class="bi bi-list-stars"></i> Summarise
    </button>
    <button class="lp-ai-chip" id="aiQuizMeBtn">
      <i class="bi bi-patch-question"></i> Quiz me
    </button>
    <button class="lp-ai-chip" id="aiTranslateBtn">
      <i class="bi bi-translate"></i> Translate
    </button>
  </div>

  <!-- ── Chat log ───────────────────────────────────────────────── -->
  <div class="lp-ai-chat" id="aiChatLog"></div>

  <!-- ── Input bar ──────────────────────────────────────────────── -->
  <div class="lp-ai-input-bar">
    <!-- Voice input button -->
    <button class="lp-ai-voice-btn" id="aiVoiceBtn" title="Voice input">
      <i class="bi bi-mic-fill" id="aiMicIcon"></i>
    </button>
    <div class="lp-ai-input-wrap">
      <input type="text" id="aiChatInput"
             placeholder="Type or press mic to speak…"
             autocomplete="off">
    </div>
    <button class="lp-ai-send-btn" id="aiChatSend">
      <i class="bi bi-send-fill"></i>
    </button>
  </div>

  <!-- Translate language picker -->
  <div id="aiLangPicker" class="lp-ai-lang-picker" style="display:none">
    <select id="aiLangSelect">
      <option value="Hindi">Hindi</option>
      <option value="Spanish">Spanish</option>
      <option value="French">French</option>
      <option value="Arabic">Arabic</option>
      <option value="German">German</option>
      <option value="Portuguese">Portuguese</option>
      <option value="Chinese (Simplified)">Chinese</option>
      <option value="Japanese">Japanese</option>
    </select>
    <button id="aiDoTranslateBtn"><i class="bi bi-check2"></i> Translate</button>
  </div>

<?php if ($featureAiTutor ?? true): ?>
</div><!-- /.lp-ai-panel -->

<!-- AI Tutor FAB -->
<button class="lp-ai-fab" id="aiTutorFab" title="AI Tutor">
  <i class="bi bi-stars"></i>
</button>
<?php else: ?>
</div><!-- /.lp-ai-panel (hidden by setting) -->
<?php endif; ?>

<?php if ($featureCollabFab ?? true): ?>
<button class="lp-collab-fab" id="collabOpenBtn" title="Notes & Comments" style="display:none">
  <i class="bi bi-journal-text"></i>
</button>
<?php endif; ?>

<style>
/* ═══════════════════════════════════════════════════════
   AI TUTOR PANEL — Full redesign
═══════════════════════════════════════════════════════ */
.lp-ai-panel {
  position: fixed;
  bottom: 96px; right: 20px;
  width: 360px;
  max-height: 680px;
  background: linear-gradient(180deg, #1a0f2e 0%, #110a22 100%);
  border: 1px solid rgba(167,139,250,.2);
  border-radius: 20px;
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 1300;
  box-shadow: 0 24px 64px rgba(0,0,0,.7), 0 0 0 1px rgba(167,139,250,.1);
  transition: right .25s ease;
}
/* When collab panel is open (not collapsed), push AI panel left of it */
.lp-shell:not(.collab-hidden) ~ .lp-ai-panel,
body:not(.collab-panel-hidden) .lp-ai-panel {
  right: 00px;
}
.lp-ai-panel.open { display: flex; }

/* ── Controls bar ── */
.lp-ai-controls-bar {
  position: absolute;
  top: 10px; right: 10px;
  display: flex; gap: 4px; z-index: 2;
}
.lp-ai-ctrl-btn {
  width: 28px; height: 28px; border-radius: 50%;
  background: rgba(255,255,255,.08); border: none; color: rgba(255,255,255,.5);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; cursor: pointer; transition: background .15s, color .15s;
}
.lp-ai-ctrl-btn:hover { background: rgba(255,255,255,.15); color: #fff; }

/* ── Avatar section ── */
.lp-ai-avatar-section {
  display: flex; flex-direction: column; align-items: center;
  padding: 28px 20px 16px;
  background: linear-gradient(180deg, rgba(124,58,237,.2) 0%, transparent 100%);
  position: relative;
}
.lp-ai-avatar-wrap {
  position: relative;
  width: 90px; height: 90px;
  margin-bottom: 12px;
}
.lp-ai-instructor-img {
  width: 90px; height: 90px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid rgba(167,139,250,.5);
  position: relative; z-index: 1;
  transition: transform .3s;
}
.lp-ai-panel.speaking .lp-ai-instructor-img { transform: scale(1.05); }
.lp-ai-instructor-initials {
  width: 90px; height: 90px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7c3aed, #5b5ef6);
  border: 3px solid rgba(167,139,250,.5);
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; font-weight: 700; color: #fff;
  position: relative; z-index: 1;
  transition: transform .3s;
}
.lp-ai-panel.speaking .lp-ai-instructor-initials { transform: scale(1.05); }

/* Speaking rings */
.lp-ai-ring {
  position: absolute; inset: -4px; border-radius: 50%;
  border: 2px solid rgba(167,139,250,0);
  transition: all .3s;
}
.lp-ai-panel.speaking .lp-ai-ring-1 {
  border-color: rgba(167,139,250,.5);
  animation: aiRing 1.2s ease-out infinite;
}
.lp-ai-panel.speaking .lp-ai-ring-2 {
  inset: -12px;
  border-color: rgba(167,139,250,.3);
  animation: aiRing 1.2s ease-out .3s infinite;
}
.lp-ai-panel.speaking .lp-ai-ring-3 {
  inset: -20px;
  border-color: rgba(167,139,250,.15);
  animation: aiRing 1.2s ease-out .6s infinite;
}
@keyframes aiRing {
  0%   { transform: scale(.9); opacity: 0; }
  50%  { opacity: 1; }
  100% { transform: scale(1.15); opacity: 0; }
}

/* Tutor name / role */
.lp-ai-tutor-name {
  font-size: 15px; font-weight: 700; color: #f3e8ff; letter-spacing: .01em;
}
.lp-ai-tutor-role {
  font-size: 12px; color: rgba(255,255,255,.45); margin-top: 3px;
  display: flex; align-items: center; gap: 5px;
}
.lp-ai-dot-inline {
  width: 6px; height: 6px; border-radius: 50%;
  background: #4ade80; box-shadow: 0 0 6px #4ade80;
  display: inline-block;
}

/* Waveform */
.lp-ai-waveform {
  display: flex; align-items: center; gap: 2px;
  height: 24px; margin-top: 10px;
  opacity: 0; transition: opacity .3s;
}
.lp-ai-panel.speaking .lp-ai-waveform { opacity: 1; }
.lp-ai-wave-bar {
  width: 3px; border-radius: 2px;
  background: #a78bfa;
  height: 4px;
}
.lp-ai-panel.speaking .lp-ai-wave-bar {
  animation: aiWave 0.8s ease-in-out infinite alternate;
}
@keyframes aiWave {
  from { height: 4px; }
  to   { height: 20px; }
}

/* Status text */
.lp-ai-status-text {
  font-size: 12px; color: rgba(255,255,255,.35);
  margin-top: 8px; text-align: center; min-height: 16px;
  transition: color .3s;
}
.lp-ai-panel.speaking .lp-ai-status-text { color: #c4b5fd; }

/* ── Quick chips ── */
.lp-ai-quick-row {
  display: flex; gap: 6px; padding: 8px 14px;
  flex-wrap: wrap;
}
.lp-ai-chip {
  background: rgba(167,139,250,.1);
  border: 1px solid rgba(167,139,250,.2);
  color: #c4b5fd;
  border-radius: 20px; padding: 5px 12px;
  font-size: 12px; font-weight: 600;
  cursor: pointer; display: flex; align-items: center; gap: 5px;
  transition: background .15s, border-color .15s, transform .1s;
  white-space: nowrap;
}
.lp-ai-chip:hover { background: rgba(167,139,250,.2); border-color: rgba(167,139,250,.4); }
.lp-ai-chip:active { transform: scale(.96); }

/* ── Chat log ── */
.lp-ai-chat {
  flex: 1; overflow-y: auto; padding: 10px 14px;
  display: flex; flex-direction: column; gap: 10px;
  min-height: 80px; max-height: 240px;
  scroll-behavior: smooth;
}
.lp-ai-chat::-webkit-scrollbar { width: 3px; }
.lp-ai-chat::-webkit-scrollbar-thumb { background: rgba(167,139,250,.2); border-radius: 2px; }

.lp-ai-msg {
  max-width: 88%; padding: 9px 13px;
  border-radius: 14px; font-size: 13.5px;
  line-height: 1.55; word-break: break-word;
  position: relative;
}
.lp-ai-msg.user {
  align-self: flex-end;
  background: #5b5ef6;
  color: #fff;
  border-bottom-right-radius: 4px;
}
.lp-ai-msg.ai {
  align-self: flex-start;
  background: rgba(255,255,255,.07);
  color: #f1f5f9;
  border-bottom-left-radius: 4px;
}
.lp-ai-msg.ai .lp-ai-speak-btn {
  position: absolute; top: 6px; right: 8px;
  background: none; border: none; color: rgba(255,255,255,.3);
  font-size: 12px; cursor: pointer; padding: 2px;
  transition: color .15s;
}
.lp-ai-msg.ai .lp-ai-speak-btn:hover { color: #a78bfa; }
.lp-ai-msg.thinking { color: rgba(255,255,255,.4); font-style: italic; }

/* Typing dots */
.lp-ai-typing { display: flex; gap: 4px; padding: 12px 14px; }
.lp-ai-typing span {
  width: 6px; height: 6px; border-radius: 50%;
  background: rgba(167,139,250,.5);
  animation: aiTyping 1.2s ease-in-out infinite;
}
.lp-ai-typing span:nth-child(2) { animation-delay: .2s; }
.lp-ai-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes aiTyping {
  0%,80%,100% { transform: scale(.7); opacity: .4; }
  40%          { transform: scale(1); opacity: 1; }
}

/* ── Input bar ── */
.lp-ai-input-bar {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 12px;
  background: rgba(0,0,0,.3);
  border-top: 1px solid rgba(255,255,255,.06);
  flex-shrink: 0;
}
.lp-ai-input-wrap { flex: 1; }
.lp-ai-input-wrap input {
  width: 100%; background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.1); color: #f1f5f9;
  border-radius: 24px; padding: 9px 14px;
  font-size: 13px; outline: none; font-family: inherit;
  transition: border-color .15s;
}
.lp-ai-input-wrap input:focus { border-color: rgba(124,58,237,.5); }
.lp-ai-input-wrap input::placeholder { color: rgba(255,255,255,.3); }

.lp-ai-voice-btn {
  width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
  background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
  color: rgba(255,255,255,.5); font-size: 15px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .15s;
}
.lp-ai-voice-btn:hover { background: rgba(255,255,255,.12); color: #fff; }
.lp-ai-voice-btn.recording {
  background: rgba(239,68,68,.3);
  border-color: rgba(239,68,68,.5);
  color: #fca5a5;
  animation: aiPulse .6s ease-in-out infinite alternate;
}
@keyframes aiPulse { from { box-shadow: 0 0 0 0 rgba(239,68,68,.4); } to { box-shadow: 0 0 0 8px rgba(239,68,68,0); } }

.lp-ai-send-btn {
  width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
  background: #7c3aed; border: none; color: #fff; font-size: 15px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .15s, transform .1s;
}
.lp-ai-send-btn:hover { background: #6d28d9; transform: scale(1.05); }

/* Language picker */
.lp-ai-lang-picker {
  display: flex; gap: 8px; padding: 8px 12px;
  background: rgba(0,0,0,.2);
  border-top: 1px solid rgba(255,255,255,.05);
}
.lp-ai-lang-picker select {
  flex: 1; background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.1); color: #f1f5f9;
  border-radius: 8px; padding: 6px 10px; font-size: 13px; outline: none;
}
.lp-ai-lang-picker button {
  background: #7c3aed; border: none; color: #fff;
  border-radius: 8px; padding: 6px 14px; font-size: 13px;
  cursor: pointer; font-weight: 600; white-space: nowrap;
  display: flex; align-items: center; gap: 5px;
}

/* FAB */
.lp-ai-fab {
  position: fixed; bottom: 28px; right: 20px;
  width: 56px; height: 56px; border-radius: 50%;
  background: linear-gradient(135deg, #7c3aed, #5b5ef6);
  border: none; color: #fff; font-size: 24px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; z-index: 1300;
  box-shadow: 0 6px 24px rgba(124,58,237,.5);
  transition: transform .2s, box-shadow .2s;
}
.lp-ai-fab:hover { transform: scale(1.08); box-shadow: 0 8px 28px rgba(124,58,237,.65); }

/* Mobile */
@media (max-width: 480px) {
  .lp-ai-panel { width: calc(100vw - 24px); right: 12px; bottom: 88px; max-height: 70vh; }
}
</style>

<style>
/* ── AI Header branding ──────────────────────────────────────────────────── */
.lp-ai-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg,#7c3aed,#5b5ef6);
  display: flex; align-items: center; justify-content: center;
  font-size: 17px; color: #fff; flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(124,58,237,.4);
}
.lp-ai-identity { flex: 1; min-width: 0; }
.lp-ai-name { font-size: 13.5px; font-weight: 700; color: #f3e8ff; }
.lp-ai-status { font-size: 11px; color: rgba(255,255,255,.5); display: flex; align-items: center; gap: 4px; margin-top: 1px; }
.lp-ai-dot { width: 6px; height: 6px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 4px #4ade80; }

/* ── Collab close/open buttons ──────────────────────────────────────────── */
.lp-collab-close-btn {
  flex: none !important;
  width: 36px !important;
  padding: 0 !important;
  border-left: 1px solid rgba(255,255,255,.07);
  font-size: 14px !important;
}
.lp-collab-fab {
  position: fixed;
  bottom: 90px; right: 24px;
  width: 44px; height: 44px;
  border-radius: 50%;
  background: rgba(99,102,241,.85);
  border: 1px solid rgba(165,180,252,.3);
  color: #fff; font-size: 18px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; z-index: 1290;
  box-shadow: 0 4px 16px rgba(99,102,241,.4);
  backdrop-filter: blur(8px);
  transition: transform .2s, background .2s;
}
.lp-collab-fab:hover { transform: scale(1.08); background: rgba(99,102,241,1); }

/* ── Fullscreen ─────────────────────────────────────────────────────────── */
.lp-shell:fullscreen,
.lp-shell:-webkit-full-screen,
.lp-shell:-moz-full-screen {
  height: 100vh !important;
  max-height: 100vh !important;
  border-radius: 0;
  /* Force the page theme — browsers reset color-scheme in fullscreen */
  background: var(--page-bg, #f1f5f9) !important;
  color: var(--text-primary, #0f172a) !important;
  color-scheme: light !important;
}
/* Keep dark mode working if user had it on before going fullscreen */
[data-theme="dark"] .lp-shell:fullscreen,
[data-theme="dark"] .lp-shell:-webkit-full-screen,
[data-theme="dark"] .lp-shell:-moz-full-screen {
  background: var(--page-bg, #0f172a) !important;
  color: var(--text-primary, #f1f5f9) !important;
  color-scheme: dark !important;
}
</style>

<style>
/* ══════════════════════════════════════════════════════════
   NOTES & COMMENTS PANEL (right sidebar in lp-shell)
══════════════════════════════════════════════════════════ */
.lp-collab-panel {
  width: 280px;
  min-width: 280px;
  background: #0d1117;
  border-left: 0px solid rgba(255,255,255,.07);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  flex-shrink: 0;
  height: 100%;
}
button#collabCloseBtn {
    display: flex;
    align-items: center;
    justify-content: center;
    align-content: center;
    color: #fff;
    background: #999;
}
.lp-collab-tabs {
  display: flex;
  border-bottom: 1px solid rgba(255,255,255,.1);
  flex-shrink: 0;
  background: #0a0d13;
}
.lp-collab-tab {
  flex: 1;
  padding: 10px 4px;
  background: none;
  border: none;
  color: rgba(255,255,255,.4);
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  transition: color .15s, border-color .15s;
}
.lp-collab-tab i { font-size: 16px; }
.lp-collab-tab span { font-size: 10px; }
.lp-collab-tab:hover { color: rgba(255,255,255,.7); }
.lp-collab-tab.active { color: #a5b4fc; border-bottom-color: #6366f1; }

.lp-collab-content {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-height: 0;
}
.lp-collab-content.d-none { display: none !important; }
.lp-collab-content::-webkit-scrollbar { width: 4px; }
.lp-collab-content::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }

/* Note form */
.lp-note-form textarea,
.lp-comment-form textarea,
.lp-collab-content input[type="text"],
.lp-collab-content textarea {
  background: rgba(255,255,255,.06) !important;
  border: 1px solid rgba(255,255,255,.12) !important;
  color: #f1f5f9 !important;
  border-radius: 8px;
  width: 100%;
  padding: 9px 11px;
  font-size: 13px;
  resize: vertical;
  font-family: inherit;
  outline: none;
  transition: border-color .15s;
}
.lp-note-form textarea:focus,
.lp-comment-form textarea:focus,
.lp-collab-content input[type="text"]:focus,
.lp-collab-content textarea:focus {
  border-color: rgba(99,102,241,.5) !important;
}
.lp-note-form textarea::placeholder,
.lp-comment-form textarea::placeholder,
.lp-collab-content input[type="text"]::placeholder,
.lp-collab-content textarea::placeholder { color: rgba(255,255,255,.3); }

/* Notes list */
.lp-notes-list,
.lp-comments-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  overflow-y: auto;
}
.lp-note-item {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 8px;
  padding: 10px 32px 10px 10px;
  font-size: 13px;
  color: rgba(255,255,255,.8);
  line-height: 1.5;
  position: relative;
  word-break: break-word;
}
.lp-note-del {
  position: absolute;
  top: 6px; right: 6px;
  background: none; border: none;
  color: rgba(255,255,255,.3);
  cursor: pointer; font-size: 14px;
  padding: 2px; line-height: 1;
}
.lp-note-del:hover { color: #f87171; }

/* Comments */
.lp-comment-item {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.07);
  border-radius: 8px;
  padding: 10px;
  font-size: 13px;
  color: rgba(255,255,255,.8);
  word-break: break-word;
}
.lp-comment-author {
  font-size: 11.5px;
  font-weight: 700;
  color: #a5b4fc;
  margin-bottom: 4px;
}
.lp-reply-btn {
  background: none; border: none;
  font-size: 11px; color: rgba(255,255,255,.35);
  cursor: pointer; margin-top: 6px; padding: 0;
}
.lp-reply-btn:hover { color: #a5b4fc; }
.lp-replies {
  margin-top: 8px; margin-left: 8px;
  padding-left: 10px;
  border-left: 2px solid rgba(255,255,255,.08);
}

/* ══════════════════════════════════════════════════════════
   AI TUTOR PANEL — fixed floating overlay
══════════════════════════════════════════════════════════ */
.lp-ai-fab {
  position: fixed;
  bottom: 28px; right: 24px;
  width: 54px; height: 54px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7c3aed, #5b5ef6);
  border: none; color: #fff; font-size: 24px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; z-index: 1300;
  box-shadow: 0 6px 24px rgba(124,58,237,.5);
  transition: transform .2s, box-shadow .2s;
}
.lp-ai-fab:hover { transform: scale(1.08); box-shadow: 0 8px 28px rgba(124,58,237,.65); }

.lp-ai-panel {
  position: fixed;
  bottom: 96px; right: 24px;
  width: 340px;
  max-height: 540px;
  background: #1a1628;
  border: 1px solid rgba(167,139,250,.2);
  border-radius: 16px;
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 1300;
  box-shadow: 0 20px 60px rgba(0,0,0,.6), 0 0 0 1px rgba(167,139,250,.1);
}
.lp-ai-panel.open { display: flex; }

.lp-ai-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 13px 14px;
  background: rgba(124,58,237,.2);
  border-bottom: 1px solid rgba(167,139,250,.15);
  font-size: 14px;
  font-weight: 700;
  color: #e9d5ff;
  flex-shrink: 0;
}
.lp-ai-header span { flex: 1; }
.lp-ai-close {
  background: none; border: none;
  color: rgba(255,255,255,.4);
  cursor: pointer; font-size: 18px;
  line-height: 1; padding: 0;
  transition: color .15s;
}
.lp-ai-close:hover { color: #fff; }

.lp-ai-body {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-height: 0;
}
.lp-ai-body::-webkit-scrollbar { width: 4px; }
.lp-ai-body::-webkit-scrollbar-thumb { background: rgba(167,139,250,.2); border-radius: 2px; }

.lp-ai-chat {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 200px;
  overflow-y: auto;
  min-height: 40px;
}
.lp-ai-msg {
  padding: 8px 12px;
  border-radius: 10px;
  font-size: 13px;
  line-height: 1.5;
  max-width: 90%;
  word-break: break-word;
  white-space: pre-wrap;
}
.lp-ai-msg.user {
  background: rgba(91,94,246,.3);
  color: #e0e7ff;
  align-self: flex-end;
  border-bottom-right-radius: 3px;
}
.lp-ai-msg.ai {
  background: rgba(255,255,255,.07);
  color: #f1f5f9;
  align-self: flex-start;
  border-bottom-left-radius: 3px;
}
.lp-ai-thinking {
  color: rgba(255,255,255,.4) !important;
  font-style: italic;
}

.lp-ai-input-row {
  display: flex;
  gap: 6px;
  margin-top: 4px;
}
.lp-ai-input-row input {
  flex: 1;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.12);
  color: #f1f5f9;
  border-radius: 8px;
  padding: 8px 11px;
  font-size: 13px;
  outline: none;
  font-family: inherit;
}
.lp-ai-input-row input:focus { border-color: rgba(124,58,237,.5); }
.lp-ai-input-row input::placeholder { color: rgba(255,255,255,.3); }
.lp-ai-input-row button {
  background: #7c3aed; border: none; color: #fff;
  border-radius: 8px; padding: 8px 13px;
  cursor: pointer; font-size: 16px;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s;
}
.lp-ai-input-row button:hover { background: #6d28d9; }

.lp-ai-quick {
  background: rgba(167,139,250,.1);
  border: 1px solid rgba(167,139,250,.2);
  color: #c4b5fd;
  border-radius: 8px;
  padding: 7px 12px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  text-align: left;
  transition: background .15s, border-color .15s;
  width: 100%;
}
.lp-ai-quick:hover { background: rgba(167,139,250,.2); border-color: rgba(167,139,250,.35); }

.lp-ai-result {
  background: rgba(167,139,250,.08);
  border: 1px solid rgba(167,139,250,.15);
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 13px;
  color: #e9d5ff;
  line-height: 1.6;
}
.lp-ai-bullet {
  display: flex;
  gap: 8px;
  margin-bottom: 6px;
  line-height: 1.5;
}
.lp-ai-bullet::before {
  content: "✦";
  color: #a78bfa;
  flex-shrink: 0;
  margin-top: 1px;
}
</style>

<style>
/* ══════════════════════════════════════════════════════════
   LESSON PLAYER — Full shell
══════════════════════════════════════════════════════════ */
.lp-shell {
  display: flex;
  height: 100%;
  max-height: 100%;
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
  height: 100%;
  height: 93vh;
  align-self: stretch;
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
  overflow-x: hidden;
  padding-bottom: 24px;
  min-height: 0; /* critical: allows flex child to shrink and scroll */
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
  var head    = document.getElementById('lpSec' + idx).querySelector('.lp-section-head');
  var lessons = document.getElementById('lpLessons' + idx);
  var isOpen  = head.classList.contains('open');
  head.classList.toggle('open', !isOpen);
  lessons.classList.toggle('open', !isOpen);
  head.setAttribute('aria-expanded', String(!isOpen));
}

// ── Fullscreen ─────────────────────────────────────────────────────────────────
(function() {
  var btn   = document.getElementById('lpFullscreenBtn');
  var shell = document.getElementById('lpShell');
  var icon  = document.getElementById('lpFsIcon');
  if (!btn || !shell) return;

  btn.addEventListener('click', function() {
    var isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement);
    if (isFs) {
      (document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen).call(document);
    } else {
      var req = shell.requestFullscreen || shell.webkitRequestFullscreen || shell.mozRequestFullScreen;
      if (req) req.call(shell);
    }
  });

  function onFsChange() {
    var isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement);
    if (icon) {
      icon.className = isFs ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    }
    if (shell) {
      shell.classList.toggle('fullscreen', isFs);
    }
    // Re-stamp data-theme on <html> — browsers can reset color-scheme in fullscreen
    var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
  }
  document.addEventListener('fullscreenchange',       onFsChange);
  document.addEventListener('webkitfullscreenchange', onFsChange);
  document.addEventListener('mozfullscreenchange',    onFsChange);
})();

// ── Collab Panel open/close toggle ────────────────────────────────────────────
(function() {
  var panel    = document.getElementById('lpCollabPanel');
  var closeBtn = document.getElementById('collabCloseBtn');
  var openBtn  = document.getElementById('collabOpenBtn');
  if (!panel) return;

  function collapsePanel() {
    panel.style.width     = '0';
    panel.style.minWidth  = '0';
    panel.style.overflow  = 'hidden';
    if (openBtn) openBtn.style.display = 'flex';
    document.body.classList.add('collab-panel-hidden');
    localStorage.setItem('lp_collab', 'hidden');
  }
  function expandPanel() {
    panel.style.width     = '';
    panel.style.minWidth  = '';
    panel.style.overflow  = '';
    if (openBtn) openBtn.style.display = 'none';
    document.body.classList.remove('collab-panel-hidden');
    localStorage.setItem('lp_collab', 'visible');
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      var isHidden = panel.style.width === '0px' || panel.style.width === '0';
      if (isHidden) { expandPanel(); this.querySelector('i').className = 'bi bi-chevron-right'; }
      else           { collapsePanel(); this.querySelector('i').className = 'bi bi-chevron-left'; }
    });
  }
  if (openBtn) {
    openBtn.addEventListener('click', function() {
      expandPanel();
      if (closeBtn) closeBtn.querySelector('i').className = 'bi bi-chevron-right';
    });
  }

  // Restore saved state
  if (localStorage.getItem('lp_collab') === 'hidden') {
    collapsePanel();
    if (closeBtn) closeBtn.querySelector('i').className = 'bi bi-chevron-left';
  }
})();

// ── Sidebar toggle ─────────────────────────────────────────────────────────────
(function() {
  var btn = document.getElementById('lpSidebarToggle');
  if (!btn) return;
  btn.addEventListener('click', function() {
    var shell = document.getElementById('lpShell');
    if (!shell) return;
    var hidden = shell.classList.toggle('sidebar-hidden');
    shell.classList.toggle('sidebar-visible', !hidden);
    localStorage.setItem('lp_sidebar', hidden ? 'hidden' : 'visible');
  });
  var saved = localStorage.getItem('lp_sidebar');
  var shell = document.getElementById('lpShell');
  if (shell && saved === 'hidden') {
    shell.classList.add('sidebar-hidden');
    shell.classList.remove('sidebar-visible');
  }
})();

// ── Mark lesson complete ───────────────────────────────────────────────────────
(function() {
  var BASE = (window.LMS && window.LMS.BASE) || '';
  var csrf = document.getElementById('csrfToken') ? document.getElementById('csrfToken').value : '<?= $e($csrf_token) ?>';

  var markBtn = document.getElementById('lpMarkComplete');
  if (!markBtn) return;
  markBtn.addEventListener('click', function() {
    var lessonId     = this.dataset.lessonId;
    var enrollmentId = this.dataset.enrollmentId;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';
    var self = this;
    fetch(BASE + '/learn/lessons/' + lessonId + '/complete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(csrf) + '&enrollment_id=' + encodeURIComponent(enrollmentId)
    }).then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.success) {
          self.classList.remove('btn-primary');
          self.classList.add('btn-success');
          self.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Completed!';
          if (d.next_lesson_id) {
            var nextBtn = document.getElementById('lpNextBtn');
            if (nextBtn) nextBtn.href = BASE + nextBtn.dataset.baseUrl + '?lesson=' + d.next_lesson_id;
          }
          if (d.course_completed) {
            setTimeout(function() { location.reload(); }, 1200);
          }
          // Update sidebar check
          var check = document.querySelector('.lp-lesson-item[data-lesson-id="' + lessonId + '"] .lp-check-icon');
          if (check) { check.className = 'bi bi-check-circle-fill lp-check-icon text-success'; }
        } else {
          LMS.toast('error', d.message || 'Could not save progress.');
          self.disabled = false;
          self.innerHTML = '<i class="bi bi-check-circle me-1"></i>Mark as Complete';
        }
      }).catch(function() {
        self.disabled = false;
        self.innerHTML = '<i class="bi bi-check-circle me-1"></i>Mark as Complete';
      });
  });
})();

// ── Collaboration Panel (Notes, Comments, Ask) ────────────────────────────────
(function() {
  var BASE = (window.LMS && window.LMS.BASE) || '';
  var shell = document.getElementById('lpShell');
  var LID   = shell ? shell.dataset.lessonId : null;
  var CID   = shell ? shell.dataset.courseId : null;
  var csrf  = document.getElementById('csrfToken') ? document.getElementById('csrfToken').value : '<?= $e($csrf_token) ?>';

  // Tab switch
  document.querySelectorAll('.lp-collab-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.lp-collab-tab').forEach(function(b) { b.classList.remove('active'); });
      document.querySelectorAll('.lp-collab-content').forEach(function(p) { p.classList.add('d-none'); });
      btn.classList.add('active');
      var id = btn.dataset.panel + 'Panel';
      var panel = document.getElementById(id);
      if (panel) panel.classList.remove('d-none');
      if (btn.dataset.panel === 'comments') loadComments();
    });
  });

  // ── Notes ────────────────────────────────────────────────────────────────────
  function loadNotes() {
    if (!LID) return;
    fetch(BASE + '/api/lessons/' + LID + '/notes')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        var el = document.getElementById('notesList');
        if (!el) return;
        if (!d.notes || !d.notes.length) {
          el.innerHTML = '<p style="font-size:12px;color:rgba(255,255,255,.3);text-align:center;padding:12px 0">No notes yet.</p>';
          return;
        }
        el.innerHTML = d.notes.map(function(n) {
          return '<div class="lp-note-item">' +
            '<button class="lp-note-del" onclick="delNote(' + n.id + ')"><i class="bi bi-x"></i></button>' +
            n.note.replace(/&/g,'&amp;').replace(/</g,'&lt;') +
            '</div>';
        }).join('');
      }).catch(function() {});
  }

  document.getElementById('saveNoteBtn') && document.getElementById('saveNoteBtn').addEventListener('click', function() {
    var note = (document.getElementById('noteInput') ? document.getElementById('noteInput').value : '').trim();
    if (!note || !LID) return;
    fetch(BASE + '/api/lessons/' + LID + '/notes', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf) + '&note=' + encodeURIComponent(note) + '&course_id=' + CID
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.success) {
        document.getElementById('noteInput').value = '';
        loadNotes();
        LMS.toast('success', 'Note saved!');
      }
    }).catch(function() {});
  });

  window.delNote = function(id) {
    fetch(BASE + '/api/notes/' + id + '/delete', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf)
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.success) loadNotes();
    }).catch(function() {});
  };

  // ── Comments ─────────────────────────────────────────────────────────────────
  var replyTo = null;
  function loadComments() {
    if (!LID) return;
    fetch(BASE + '/api/lessons/' + LID + '/comments')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        var el = document.getElementById('commentsList');
        if (!el) return;
        if (!d.comments || !d.comments.length) {
          el.innerHTML = '<p style="font-size:12px;color:rgba(255,255,255,.3);text-align:center;padding:12px 0">No comments yet.</p>';
          return;
        }
        el.innerHTML = d.comments.map(function(c) {
          var reps = (c.replies || []).map(function(r) {
            return '<div class="lp-comment-item" style="margin-top:6px"><div class="lp-comment-author">' +
              r.author_name + '</div>' + r.body.replace(/</g,'&lt;') + '</div>';
          }).join('');
          return '<div class="lp-comment-item"><div class="lp-comment-author">' +
            c.author_name + (c.is_pinned ? ' 📌' : '') + '</div>' +
            c.body.replace(/</g,'&lt;') +
            (reps ? '<div class="lp-replies">' + reps + '</div>' : '') +
            '<button class="lp-reply-btn" onclick="setReply(' + c.id + ')">↩ Reply</button></div>';
        }).join('');
      }).catch(function() {});
  }

  window.setReply = function(id) {
    replyTo = id;
    var inp = document.getElementById('commentInput');
    if (inp) inp.focus();
  };

  document.getElementById('postCommentBtn') && document.getElementById('postCommentBtn').addEventListener('click', function() {
    var body = (document.getElementById('commentInput') ? document.getElementById('commentInput').value : '').trim();
    if (!body || !LID) return;
    fetch(BASE + '/api/lessons/' + LID + '/comments', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf) + '&body=' + encodeURIComponent(body) +
            (replyTo ? '&parent_id=' + replyTo : '')
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.success) {
        document.getElementById('commentInput').value = '';
        replyTo = null;
        loadComments();
      }
    }).catch(function() {});
  });

  // ── Ask a question ───────────────────────────────────────────────────────────
  document.getElementById('askQuestionBtn') && document.getElementById('askQuestionBtn').addEventListener('click', function() {
    var title = (document.getElementById('qaTitle') ? document.getElementById('qaTitle').value : '').trim();
    var body  = (document.getElementById('qaBody')  ? document.getElementById('qaBody').value  : '').trim();
    if (!title || !LID) { LMS.toast('error', 'Enter a question title.'); return; }
    this.disabled = true;
    var self = this;
    fetch(BASE + '/api/lessons/' + LID + '/ask', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf) + '&title=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(body)
    }).then(function(r) { return r.json(); }).then(function(d) {
      LMS.toast(d.success ? 'success' : 'error', d.message);
      if (d.success) {
        if (document.getElementById('qaTitle')) document.getElementById('qaTitle').value = '';
        if (document.getElementById('qaBody'))  document.getElementById('qaBody').value  = '';
      }
      self.disabled = false;
    }).catch(function() { self.disabled = false; });
  });

  loadNotes();
})();

// ── AI Tutor Panel ────────────────────────────────────────────────────────────
(function() {
  var BASE  = (window.LMS && window.LMS.BASE) || '';
  var shell = document.getElementById('lpShell');
  var LID   = shell ? shell.dataset.lessonId : null;
  var CID   = shell ? shell.dataset.courseId : null;
  var csrf  = document.getElementById('csrfToken') ? document.getElementById('csrfToken').value : '<?= $e($csrf_token) ?>';
  var panel = document.getElementById('lpAiPanel');
  var chatLog = document.getElementById('aiChatLog');

  // ── State ──────────────────────────────────────────────────────
  var soundEnabled  = true;
  var isRecording   = false;
  var isSpeaking    = false;
  var currentUtter  = null;
  var recognition   = null;
  var hasSpeechAPI  = ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window);
  var hasTTSAPI     = ('speechSynthesis' in window);

  // ── FAB open/close ─────────────────────────────────────────────
  document.getElementById('aiTutorFab') && document.getElementById('aiTutorFab').addEventListener('click', function() {
    panel.classList.toggle('open');
  });
  document.getElementById('aiMinimiseBtn') && document.getElementById('aiMinimiseBtn').addEventListener('click', function() {
    panel.classList.remove('open');
  });

  // ── Sound toggle ───────────────────────────────────────────────
  document.getElementById('aiSoundToggle') && document.getElementById('aiSoundToggle').addEventListener('click', function() {
    soundEnabled = !soundEnabled;
    var icon = document.getElementById('aiSoundIcon');
    if (icon) icon.className = soundEnabled ? 'bi bi-volume-up-fill' : 'bi bi-volume-mute-fill';
    if (!soundEnabled && currentUtter) { window.speechSynthesis.cancel(); setSpeaking(false); }
  });

  // ── Speaking state ─────────────────────────────────────────────
  function setSpeaking(on) {
    isSpeaking = on;
    if (panel) panel.classList.toggle('speaking', on);
    var st = document.getElementById('aiStatusText');
    if (st) st.textContent = on ? 'Speaking…' : 'Ask me anything about this lesson';
  }

  // ── TTS ────────────────────────────────────────────────────────
  function speak(text) {
    if (!hasTTSAPI || !soundEnabled) return;
    window.speechSynthesis.cancel();
    // Strip markdown-ish syntax from spoken text
    var clean = text.replace(/\*\*/g,'').replace(/\*/g,'').replace(/#{1,6}\s/g,'').replace(/`[^`]+`/g, function(m){ return m.replace(/`/g,''); });
    currentUtter = new SpeechSynthesisUtterance(clean);
    currentUtter.rate  = 1.0;
    currentUtter.pitch = 1.0;
    currentUtter.volume = 0.9;
    // Prefer a natural voice
    var voices = window.speechSynthesis.getVoices();
    var preferred = voices.find(function(v) {
      return /Google|Natural|Premium|Enhanced/i.test(v.name) && /en/i.test(v.lang);
    }) || voices.find(function(v) { return /en/i.test(v.lang); });
    if (preferred) currentUtter.voice = preferred;
    currentUtter.onstart = function() { setSpeaking(true); };
    currentUtter.onend   = function() { setSpeaking(false); };
    currentUtter.onerror = function() { setSpeaking(false); };
    window.speechSynthesis.speak(currentUtter);
  }

  // ── Voice input ────────────────────────────────────────────────
  if (!hasSpeechAPI) {
    var voiceBtn = document.getElementById('aiVoiceBtn');
    if (voiceBtn) { voiceBtn.style.opacity = '.3'; voiceBtn.title = 'Voice input not supported in this browser'; }
  }

  document.getElementById('aiVoiceBtn') && document.getElementById('aiVoiceBtn').addEventListener('click', function() {
    if (!hasSpeechAPI) { addMsg('ai', 'Voice input isn\'t supported in this browser. Please use Chrome or Edge.'); return; }
    if (isRecording) { recognition && recognition.stop(); return; }

    var SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRec();
    recognition.lang = 'en-US';
    recognition.continuous = false;
    recognition.interimResults = false;

    recognition.onstart = function() {
      isRecording = true;
      var btn = document.getElementById('aiVoiceBtn');
      var icon = document.getElementById('aiMicIcon');
      if (btn) btn.classList.add('recording');
      if (icon) icon.className = 'bi bi-record-circle-fill';
      var st = document.getElementById('aiStatusText');
      if (st) st.textContent = 'Listening…';
    };
    recognition.onresult = function(e) {
      var transcript = e.results[0][0].transcript;
      var input = document.getElementById('aiChatInput');
      if (input) { input.value = transcript; }
      // Auto-send
      setTimeout(sendChat, 150);
    };
    recognition.onerror = function(e) {
      addMsg('ai', 'Couldn\'t hear you — please try again or type your question.');
    };
    recognition.onend = function() {
      isRecording = false;
      var btn = document.getElementById('aiVoiceBtn');
      var icon = document.getElementById('aiMicIcon');
      if (btn) btn.classList.remove('recording');
      if (icon) icon.className = 'bi bi-mic-fill';
      var st = document.getElementById('aiStatusText');
      if (st) st.textContent = 'Ask me anything about this lesson';
    };
    recognition.start();
  });

  // ── Chat message helpers ───────────────────────────────────────
  function addMsg(role, text, withSpeakBtn) {
    var div = document.createElement('div');
    div.className = 'lp-ai-msg ' + role;

    // Render simple markdown
    var html = text
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
      .replace(/\*(.+?)\*/g,'<em>$1</em>')
      .replace(/`(.+?)`/g,'<code style="background:rgba(255,255,255,.1);padding:1px 5px;border-radius:4px;font-size:12px">$1</code>')
      .replace(/\n/g,'<br>');
    div.innerHTML = html;

    // Add speaker button on AI messages
    if (role === 'ai' && hasTTSAPI && withSpeakBtn !== false) {
      var speakBtn = document.createElement('button');
      speakBtn.className = 'lp-ai-speak-btn';
      speakBtn.title = 'Listen';
      speakBtn.innerHTML = '<i class="bi bi-play-circle"></i>';
      speakBtn.addEventListener('click', function() {
        if (isSpeaking) { window.speechSynthesis.cancel(); setSpeaking(false); speakBtn.innerHTML='<i class="bi bi-play-circle"></i>'; }
        else            { speak(text); speakBtn.innerHTML='<i class="bi bi-stop-circle"></i>'; }
      });
      div.appendChild(speakBtn);
    }

    if (chatLog) { chatLog.appendChild(div); chatLog.scrollTop = chatLog.scrollHeight; }
    return div;
  }

  function showTyping() {
    var d = document.createElement('div');
    d.className = 'lp-ai-typing'; d.id = 'aiTypingDots';
    d.innerHTML = '<span></span><span></span><span></span>';
    if (chatLog) { chatLog.appendChild(d); chatLog.scrollTop = chatLog.scrollHeight; }
  }
  function hideTyping() {
    var d = document.getElementById('aiTypingDots');
    if (d) d.remove();
  }

  function setStatus(text) {
    var st = document.getElementById('aiStatusText');
    if (st) st.textContent = text;
  }

  // ── Send chat ──────────────────────────────────────────────────
  function sendChat() {
    var input = document.getElementById('aiChatInput');
    var q = input ? input.value.trim() : '';
    if (!q) return;
    addMsg('user', q);
    if (input) input.value = '';
    showTyping();
    setStatus('Thinking…');

    fetch(BASE + '/api/ai/chat', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf) +
            '&course_id=' + CID + '&lesson_id=' + LID +
            '&question=' + encodeURIComponent(q)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      hideTyping();
      setStatus('Ask me anything about this lesson');
      var answer = d.success ? d.answer : (d.message || 'Something went wrong.');
      addMsg('ai', answer);
      if (d.success && soundEnabled) speak(answer);
    })
    .catch(function() {
      hideTyping();
      setStatus('Ask me anything about this lesson');
      addMsg('ai', 'Connection error. Please try again.');
    });
  }

  // Send button + Enter key
  document.getElementById('aiChatSend') && document.getElementById('aiChatSend').addEventListener('click', sendChat);
  document.getElementById('aiChatInput') && document.getElementById('aiChatInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); }
  });

  // ── Summarise ──────────────────────────────────────────────────
  document.getElementById('aiSummaryBtn') && document.getElementById('aiSummaryBtn').addEventListener('click', function() {
    showTyping(); setStatus('Summarising lesson…');
    fetch(BASE + '/api/ai/summarise', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf) + '&lesson_id=' + LID
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      hideTyping(); setStatus('Ask me anything about this lesson');
      if (d.success && d.bullets && d.bullets.length) {
        var text = 'Here\'s a summary:\n' + d.bullets.map(function(b){ return '• ' + b; }).join('\n');
        addMsg('ai', text);
        if (soundEnabled) speak(text);
      } else {
        addMsg('ai', d.message || 'Could not summarise this lesson.');
      }
    })
    .catch(function() { hideTyping(); addMsg('ai', 'Connection error.'); });
  });

  // ── Quiz me ────────────────────────────────────────────────────
  document.getElementById('aiQuizMeBtn') && document.getElementById('aiQuizMeBtn').addEventListener('click', function() {
    showTyping(); setStatus('Generating a question…');
    fetch(BASE + '/api/ai/chat', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf) +
            '&course_id=' + CID + '&lesson_id=' + LID +
            '&question=' + encodeURIComponent('Give me one quiz question about this lesson to test my understanding. Wait for my answer before revealing the correct answer.')
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      hideTyping(); setStatus('Waiting for your answer…');
      var q = d.success ? d.answer : 'Could not generate a question.';
      addMsg('ai', q);
      if (soundEnabled) speak(q);
    })
    .catch(function() { hideTyping(); addMsg('ai', 'Connection error.'); });
  });

  // ── Translate ──────────────────────────────────────────────────
  document.getElementById('aiTranslateBtn') && document.getElementById('aiTranslateBtn').addEventListener('click', function() {
    var picker = document.getElementById('aiLangPicker');
    if (picker) picker.style.display = picker.style.display === 'none' ? 'flex' : 'none';
  });

  document.getElementById('aiDoTranslateBtn') && document.getElementById('aiDoTranslateBtn').addEventListener('click', function() {
    var lang = document.getElementById('aiLangSelect') ? document.getElementById('aiLangSelect').value : 'Hindi';
    var picker = document.getElementById('aiLangPicker');
    if (picker) picker.style.display = 'none';
    showTyping(); setStatus('Translating to ' + lang + '…');
    fetch(BASE + '/api/ai/translate', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf) + '&lesson_id=' + LID + '&language=' + encodeURIComponent(lang)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      hideTyping(); setStatus('Ask me anything about this lesson');
      if (d.success) {
        addMsg('ai', 'Translation to ' + lang + ':\n\n' + d.content);
      } else {
        addMsg('ai', d.message || 'Translation failed.');
      }
    })
    .catch(function() { hideTyping(); addMsg('ai', 'Connection error.'); });
  });

  // Load voices on first interaction (browsers lazy-load voice list)
  if (hasTTSAPI) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = function() { window.speechSynthesis.getVoices(); };
  }

})();

// ── Review form (course completion) ─────────────────────────────────────────
(function() {
  var BASE = (window.LMS && window.LMS.BASE) || '';
  var csrf = document.getElementById('csrfToken') ? document.getElementById('csrfToken').value : '<?= $e($csrf_token) ?>';
  var reviewForm = document.getElementById('lpReviewForm');
  if (!reviewForm) return;

  document.getElementById('lpReviewSubmit') && document.getElementById('lpReviewSubmit').addEventListener('click', function() {
    var rating = 0;
    reviewForm.querySelectorAll('.lp-star input').forEach(function(s) {
      if (s.checked) rating = parseInt(s.value);
    });
    if (!rating) { LMS.toast('error', 'Please select a rating.'); return; }
    var comment = (document.getElementById('lpReviewText') ? document.getElementById('lpReviewText').value : '').trim();
    this.disabled = true;
    this.textContent = 'Submitting…';
    var self = this;
    var courseUuid = '<?= isset($course) ? $e($course["uuid"]) : "" ?>';
    fetch(BASE + '/learn/courses/' + courseUuid + '/review', {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf) + '&rating=' + rating + '&comment=' + encodeURIComponent(comment)
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.success) {
        reviewForm.innerHTML = '<div style="padding:12px;background:#ecfdf5;border-radius:10px;font-size:13.5px;color:#059669"><i class="bi bi-check-circle-fill me-1"></i>' + d.message + '</div>';
        LMS.toast('success', d.message);
      } else {
        LMS.toast('error', d.message || 'Failed to submit review.');
        self.disabled = false;
        self.innerHTML = '<i class="bi bi-send-fill me-1"></i>Submit Review';
      }
    }).catch(function() {
      self.disabled = false;
      self.innerHTML = '<i class="bi bi-send-fill me-1"></i>Submit Review';
    });
  });
})();
</script>
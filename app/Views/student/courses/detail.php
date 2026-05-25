<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$pct      = (int)($progressPct ?? 0);
$isDone   = ($enrollment['status'] ?? '') === 'completed';
$btnLabel = $isDone ? 'View Course' : ($pct > 0 ? 'Resume Course' : 'Start Course');
$btnIcon  = $isDone ? 'bi-award' : 'bi-play-fill';
$playerUrl= $url('learn/courses/' . $course['uuid'] . '/learn' . ($resumeLessonId ? '?lesson=' . $resumeLessonId : ''));

$levelColors = ['beginner'=>'#0e9f6e','intermediate'=>'#1a56db','advanced'=>'#e02424'];
$levelColor  = $levelColors[$course['level'] ?? 'beginner'] ?? '#6366f1';

$typeIcons = ['text'=>'bi-file-text','video'=>'bi-play-circle-fill','document'=>'bi-file-pdf','scorm'=>'bi-box-seam','quiz'=>'bi-patch-question-fill'];
$typeColors= ['text'=>'secondary','video'=>'danger','document'=>'warning','scorm'=>'info','quiz'=>'success'];
?>

<div class="course-detail-wrap">

  <!-- ═══════════════════════════════════════════════════
       LEFT — Course outline sidebar
  ════════════════════════════════════════════════════ -->
  <aside class="cd-sidebar">
    <div class="cd-sidebar-inner">

      <!-- Sticky top: CTA -->
      <div class="cd-cta-box">
        <!-- Thumbnail or gradient -->
        <div class="cd-thumb">
          <?php if ($course['thumbnail']): ?>
            <img src="<?= $e(APP_URL . '/storage/uploads/' . $course['thumbnail']) ?>" alt="">
          <?php else: ?>
            <div class="cd-thumb-placeholder">
              <i class="bi bi-book-half"></i>
            </div>
          <?php endif; ?>
          <!-- Overlay play button if video -->
          <?php if ($course['preview_video']): ?>
          <a href="<?= $playerUrl ?>" class="cd-play-overlay">
            <i class="bi bi-play-circle-fill"></i>
          </a>
          <?php endif; ?>
        </div>

        <!-- Progress -->
        <?php if ($pct > 0): ?>
        <div class="cd-progress-wrap">
          <div class="d-flex justify-content-between mb-1" style="font-size:12px">
            <span style="color:var(--text-muted)">Your progress</span>
            <span class="fw-bold"><?= $pct ?>%</span>
          </div>
          <div style="height:7px;background:var(--border-color);border-radius:4px;overflow:hidden">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $isDone ? '#0e9f6e' : '#6366f1' ?>;border-radius:4px;transition:width .4s"></div>
          </div>
          <div class="text-muted mt-1" style="font-size:12px">
            <?= $completedCount ?>/<?= $totalLessons ?> lessons completed
          </div>
        </div>
        <?php endif; ?>

        <!-- Main CTA button -->
        <a href="<?= $playerUrl ?>" class="cd-btn-start <?= $isDone ? 'done' : '' ?>">
          <i class="bi <?= $btnIcon ?> me-2"></i><?= $btnLabel ?>
        </a>

        <!-- Meta info -->
        <div class="cd-meta-list">
          <?php if ($course['level']): ?>
          <div class="cd-meta-item">
            <i class="bi bi-bar-chart"></i>
            <span><?= ucfirst($e($course['level'])) ?> level</span>
          </div>
          <?php endif; ?>
          <?php if ($course['duration_hours']): ?>
          <div class="cd-meta-item">
            <i class="bi bi-clock"></i>
            <span><?= $e($course['duration_hours']) ?> hours total</span>
          </div>
          <?php endif; ?>
          <?php if ($course['language']): ?>
          <div class="cd-meta-item">
            <i class="bi bi-translate"></i>
            <span><?= $e($course['language']) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($course['certificate_enabled']): ?>
          <div class="cd-meta-item">
            <i class="bi bi-award text-warning"></i>
            <span>Certificate on completion</span>
          </div>
          <?php endif; ?>
          <?php if ($course['grade_points']): ?>
          <div class="cd-meta-item">
            <i class="bi bi-trophy text-warning"></i>
            <span><?= (int)$course['grade_points'] ?> grade points</span>
          </div>
          <?php endif; ?>
          <?php if ($course['forum_enabled']): ?>
          <div class="cd-meta-item">
            <i class="bi bi-chat-dots text-primary"></i>
            <a href="<?= $url('learn/courses/' . $course['uuid'] . '/forum') ?>" class="text-decoration-none">Discussion Forum</a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Course outline -->
      <div class="cd-outline">
        <div class="cd-outline-title">
          <i class="bi bi-list-nested me-2"></i>Course Outline
          <span class="ms-auto"><?= $totalLessons ?> lessons</span>
        </div>
        <div class="cd-outline-scroll">
          <?php foreach ($sections as $si => $sec):
          $secLessons   = $sec['lessons'];
          $secTotal     = count($secLessons);
          $secCompleted = count(array_filter($secLessons, fn($l) => ($lessonProgress[$l['id']] ?? '') === 'completed'));
          $secPct       = $secTotal > 0 ? round($secCompleted / $secTotal * 100) : 0;
        ?>
        <div class="cd-section open">
          <div class="cd-section-head">
            <i class="bi bi-chevron-right cd-sec-chevron"></i>
            <span class="cd-sec-title"><?= $e($sec['title']) ?></span>
            <span class="cd-sec-count"><?= $secCompleted ?>/<?= $secTotal ?></span>
          </div>
          <div class="cd-section-lessons">
            <?php foreach ($secLessons as $les):
              $done    = ($lessonProgress[$les['id']] ?? '') === 'completed';
              $icon    = $typeIcons[$les['type']] ?? 'bi-circle';
              $clr     = $typeColors[$les['type']] ?? 'secondary';
            ?>
            <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $les['id']) ?>"
               class="cd-lesson <?= $done ? 'done' : '' ?>">
              <span class="cd-lesson-check">
                <?php if ($done): ?>
                  <i class="bi bi-check-circle-fill" style="color:#0e9f6e"></i>
                <?php else: ?>
                  <i class="bi bi-circle" style="color:var(--border-color)"></i>
                <?php endif; ?>
              </span>
              <span class="cd-lesson-title"><?= $e($les['title']) ?></span>
              <span class="ms-auto d-flex align-items-center gap-1" style="flex-shrink:0">
                <i class="bi <?= $icon ?> text-<?= $clr ?>" style="font-size:12px"></i>
                <?php if ($les['duration_sec']): ?>
                  <span style="font-size:11px;color:var(--text-muted)"><?= gmdate('i:s', (int)$les['duration_sec']) ?></span>
                <?php endif; ?>
              </span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
        </div><!-- /cd-outline-scroll -->
      </div><!-- /cd-outline -->

    </div><!-- /cd-sidebar-inner -->
  </aside><!-- /cd-sidebar -->

  <!-- ═══════════════════════════════════════════════════
       RIGHT — Course details
  ════════════════════════════════════════════════════ -->
  <div class="cd-main">

    <!-- Hero header -->
    <div class="cd-hero">
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php if ($course['category_name']): ?>
          <span class="badge bg-primary-subtle text-primary"><?= $e($course['category_name']) ?></span>
        <?php endif; ?>
        <span class="badge" style="background:<?= $levelColor ?>20;color:<?= $levelColor ?>">
          <?= ucfirst($e($course['level'])) ?>
        </span>
        <?php if ($avgRating): ?>
        <span class="badge bg-warning text-dark">
          ★ <?= number_format($avgRating, 1) ?> (<?= $reviewCount ?> reviews)
        </span>
        <?php endif; ?>
      </div>
      <h1 class="cd-hero-title"><?= $e($course['title']) ?></h1>
      <?php if ($course['short_description']): ?>
        <p class="cd-hero-desc"><?= $e($course['short_description']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div class="cd-tabs-wrap">
      <ul class="cd-tabs">
        <li><a class="cd-tab active" href="#" data-tab="overview">Overview</a></li>
        <?php if (!empty($instructors)): ?>
        <li><a class="cd-tab" href="#" data-tab="instructor">Instructor</a></li>
        <?php endif; ?>
        <?php if (!empty($reviewList)): ?>
        <li><a class="cd-tab" href="#" data-tab="reviews">Reviews (<?= $reviewCount ?>)</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- ── Overview tab ── -->
    <div class="cd-tab-panel active" id="cdTab-overview">
      <?php if ($course['description']): ?>
      <div class="cd-section-block">
        <h5 class="cd-section-label">About this course</h5>
        <div class="cd-rich-content"><?= $course['description'] ?></div>
      </div>
      <?php endif; ?>

      <!-- What you'll learn (parsed from description if lists exist, else show outcome) -->
      <div class="cd-section-block">
        <h5 class="cd-section-label">Course Details</h5>
        <div class="row g-3">
          <?php $details = [
            ['bi-collection','Sections', count($sections) . ' sections'],
            ['bi-file-play','Lessons', $totalLessons . ' lessons'],
            ['bi-clock','Duration', $course['duration_hours'] ? $course['duration_hours'] . ' hours' : '—'],
            ['bi-bar-chart','Level', ucfirst($course['level'] ?? '—')],
            ['bi-translate','Language', $course['language'] ?? 'English'],
            ['bi-trophy','Grade Points', $course['grade_points'] ? (int)$course['grade_points'] . ' pts on completion' : '—'],
            ['bi-award','Certificate', $course['certificate_enabled'] ? 'Issued on completion' : 'Not available'],
            ['bi-chat-dots','Forum', $course['forum_enabled'] ? 'Available' : 'Not available'],
          ];
          foreach ($details as [$ico, $lbl, $val]): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="cd-detail-card">
              <i class="bi <?= $ico ?> cd-detail-icon"></i>
              <div class="cd-detail-label"><?= $lbl ?></div>
              <div class="cd-detail-value"><?= $val ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($isDone && $course['certificate_enabled']): ?>
      <div class="cd-section-block">
        <div class="cd-cert-banner">
          <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#e3a008,#f59e0b);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi bi-award-fill" style="font-size:1.5rem;color:#fff"></i>
          </div>
          <div class="flex-grow-1">
            <div class="fw-bold mb-1">🎉 You completed this course!</div>
            <div class="text-muted" style="font-size:13.5px">Your certificate is ready to download.</div>
          </div>
          <a href="<?= $url('learn/certificate/' . $enrollment['id']) ?>" class="cd-btn-cert" target="_blank">
            <i class="bi bi-download me-1"></i> Certificate
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── Instructor tab ── -->
    <?php if (!empty($instructors)): ?>
    <div class="cd-tab-panel d-none" id="cdTab-instructor">
      <div class="cd-section-block">
        <h5 class="cd-section-label">Instructors</h5>
        <?php foreach ($instructors as $instr): ?>
        <div class="cd-instructor-card">
          <div class="cd-instr-avatar">
            <?= strtoupper(substr($instr['first_name'], 0, 1)) ?>
          </div>
          <div>
            <div class="fw-semibold"><?= $e($instr['first_name'] . ' ' . $instr['last_name']) ?></div>
            <div class="text-muted" style="font-size:13px"><?= $e($instr['email']) ?></div>
            <span class="badge bg-<?= $instr['cm_role'] === 'manager' ? 'success' : 'primary' ?> mt-1" style="font-size:11px">
              <?= ucfirst($e($instr['cm_role'])) ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Reviews tab ── -->
    <?php if (!empty($reviewList)): ?>
    <div class="cd-tab-panel d-none" id="cdTab-reviews">
      <div class="cd-section-block">
        <!-- Rating summary -->
        <div class="d-flex align-items-center gap-4 mb-4">
          <div class="text-center">
            <div style="font-size:3.5rem;font-weight:800;line-height:1;color:var(--text-primary)"><?= number_format($avgRating, 1) ?></div>
            <div style="color:#e3a008;font-size:18px"><?= str_repeat('★', round($avgRating)) ?><?= str_repeat('☆', 5 - round($avgRating)) ?></div>
            <div class="text-muted" style="font-size:12.5px"><?= $reviewCount ?> ratings</div>
          </div>
        </div>
        <!-- Review list -->
        <?php foreach ($reviewList as $rv): ?>
        <div class="cd-review-item">
          <div class="cd-review-head">
            <div class="cd-review-avatar"><?= strtoupper(substr($rv['first_name'], 0, 1)) ?></div>
            <div>
              <div class="fw-semibold" style="font-size:14px"><?= $e($rv['first_name'] . ' ' . $rv['last_name']) ?></div>
              <div style="color:#e3a008;font-size:13px"><?= str_repeat('★', (int)$rv['rating']) ?><span style="color:var(--border-color)"><?= str_repeat('★', 5 - (int)$rv['rating']) ?></span></div>
            </div>
            <div class="ms-auto text-muted" style="font-size:12px"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
          </div>
          <?php if ($rv['comment']): ?>
          <p class="cd-review-body"><?= $e($rv['comment']) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /.cd-main -->
</div><!-- /.course-detail-wrap -->

<style>
/* ── Course Detail Page ───────────────────────────────────────────────────── */
.course-detail-wrap {
  margin: -28px -28px 0;
  min-height: calc(100vh - 60px);
  display: flex;
}

/* Sidebar — white background, fixed height, scrollable */
.cd-sidebar {
  width: 320px;
  min-width: 320px;
  background: #fff;
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  height: calc(100vh - 60px);
  position: sticky;
  top: 60px;
  overflow: hidden;
  flex-shrink: 0;
}
.cd-sidebar-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}
.cd-cta-box {
  padding: 20px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.cd-progress-wrap { margin-bottom: 14px; }
.cd-btn-start {
  display: flex; align-items: center; justify-content: center;
  width: 100%; padding: 12px;
  background: linear-gradient(135deg,#5b5ef6,#3b82f6);
  color: #fff !important; text-decoration: none !important;
  border-radius: 10px; font-size: 14.5px; font-weight: 700;
  box-shadow: 0 4px 14px rgba(91,94,246,.3);
  transition: opacity .15s, transform .1s;
  margin-bottom: 16px; gap: 8px;
}
.cd-btn-start:hover { opacity: .92; transform: translateY(-1px); }
.cd-btn-start.done { background: linear-gradient(135deg,#12b76a,#059669); box-shadow: 0 4px 14px rgba(18,183,106,.3); }
.cd-meta-list { display: flex; flex-direction: column; gap: 9px; }
.cd-meta-item { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--text-2); }
.cd-meta-item i { width: 16px; text-align: center; flex-shrink: 0; }
.cd-meta-item a { color: var(--primary); text-decoration: none; }

/* Outline — scrollable area */
.cd-outline {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.cd-outline-title {
  display: flex; align-items: center;
  padding: 12px 20px;
  font-size: 12px; font-weight: 700;
  color: var(--text-2);
  text-transform: uppercase; letter-spacing: .06em;
  border-bottom: 1px solid var(--border);
  background: #f8f9fb;
  flex-shrink: 0;
}
.cd-outline-scroll {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
}
.cd-outline-scroll::-webkit-scrollbar { width: 4px; }
.cd-outline-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

/* Section */
.cd-section { border-bottom: 1px solid var(--border); }
.cd-section-head {
  display: flex; align-items: center; gap: 8px;
  padding: 11px 20px; cursor: pointer;
  font-size: 13px; font-weight: 600; color: var(--text-1);
  background: #fff; transition: background .1s;
  user-select: none;
}
.cd-section-head:hover { background: #f5f5ff; }
.cd-sec-chevron { font-size: 11px; color: var(--text-3); transition: transform .2s; flex-shrink: 0; }
.cd-section.open .cd-sec-chevron { transform: rotate(90deg); }
.cd-sec-title { flex: 1; }
.cd-sec-count { font-size: 11px; color: var(--text-3); }
.cd-section-lessons { display: none; background: #fafbff; }
.cd-section.open .cd-section-lessons { display: block; }
.cd-lesson {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 20px 9px 28px;
  font-size: 12.5px; color: var(--text-2);
  text-decoration: none !important;
  border-left: 2px solid transparent;
  transition: background .1s, color .1s, border-color .1s;
}
.cd-lesson:hover { background: #ededff; color: var(--primary); border-left-color: var(--primary); }
.cd-lesson.done { color: var(--text-3); }
.cd-lesson-check { flex-shrink: 0; font-size: 14px; line-height: 1; }
.cd-lesson-title { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Main content area */
.cd-main {
  flex: 1;
  padding: 32px 36px 60px;
  overflow-y: auto;
  min-width: 0;
}
.cd-hero { margin-bottom: 28px; }
.cd-hero-title { font-size: clamp(20px,2.5vw,28px); font-weight: 800; color: var(--text-1); margin-bottom: 10px; line-height: 1.3; }
.cd-hero-desc { font-size: 15px; color: var(--text-2); line-height: 1.7; }

/* Tabs */
.cd-tabs-wrap { border-bottom: 2px solid var(--border); margin-bottom: 28px; }
.cd-tabs { display: flex; list-style: none; margin: 0; padding: 0; gap: 0; }
.cd-tabs li { display: flex; }
.cd-tab {
  padding: 12px 22px; font-size: 14px; font-weight: 600;
  color: var(--text-2); text-decoration: none !important;
  border-bottom: 2px solid transparent; margin-bottom: -2px;
  transition: color .15s, border-color .15s;
}
.cd-tab:hover { color: var(--primary); }
.cd-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
.cd-tab-panel { }
.cd-tab-panel.d-none { display: none !important; }
.cd-section-block { margin-bottom: 32px; }
.cd-section-label { font-size: 18px; font-weight: 700; color: var(--text-1); margin-bottom: 16px; }
.cd-rich-content { font-size: 15px; line-height: 1.8; color: var(--text-1); }
.cd-rich-content h2, .cd-rich-content h3 { font-weight: 700; margin: 24px 0 10px; }
.cd-rich-content ul, .cd-rich-content ol { padding-left: 20px; margin-bottom: 14px; }
.cd-rich-content p { margin-bottom: 12px; }

/* Detail cards */
.cd-detail-card { background: #f8f9fb; border: 1px solid var(--border); border-radius: 10px; padding: 16px; text-align: center; }
.cd-detail-icon { font-size: 1.4rem; color: var(--primary); display: block; margin-bottom: 6px; }
.cd-detail-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); margin-bottom: 3px; font-weight: 600; }
.cd-detail-value { font-size: 13.5px; font-weight: 700; color: var(--text-1); }

/* Certificate banner */
.cd-cert-banner { display: flex; align-items: center; gap: 16px; padding: 20px; border-radius: 14px; background: linear-gradient(135deg,#fffbeb,#fef9c3); border: 1px solid #fde68a; }
.cd-btn-cert { padding: 9px 22px; border-radius: 9px; font-size: 13.5px; font-weight: 700; background: #f79009; color: #fff !important; text-decoration: none !important; white-space: nowrap; }
.cd-btn-cert:hover { opacity: .9; }

/* Instructor */
.cd-instructor-card { display: flex; align-items: center; gap: 14px; padding: 16px; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 12px; background: #f8f9fb; }
.cd-instr-avatar { width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0; background: linear-gradient(135deg,#5b5ef6,#3b82f6); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; color: #fff; }

/* Reviews */
.cd-review-item { padding: 18px 0; border-bottom: 1px solid var(--border); }
.cd-review-item:last-child { border-bottom: none; }
.cd-review-head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.cd-review-avatar { width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 700; }
.cd-review-body { font-size: 14px; color: var(--text-2); line-height: 1.6; margin: 0; }

[data-theme="dark"] .cd-sidebar { background: #18181c; border-color: #26262e; }
[data-theme="dark"] .cd-section-head { background: #18181c; color: #f1f1f5; }
[data-theme="dark"] .cd-section-head:hover { background: #26262e; }
[data-theme="dark"] .cd-section-lessons { background: #131316; }
[data-theme="dark"] .cd-outline-title { background: #0f0f12; }
[data-theme="dark"] .cd-main { background: #0f0f12; }
[data-theme="dark"] .cd-detail-card { background: #1a1a1f; }
[data-theme="dark"] .cd-tabs-wrap { border-color: #26262e; }

@media (max-width: 991px) {
  .course-detail-wrap { flex-direction: column; }
  .cd-sidebar { width: 100%; min-width: 0; height: auto; position: static; }
  .cd-outline { height: 340px; }
  .cd-main { padding: 20px; }
}

/* Sidebar */
.cd-sidebar {
  border-right: 1px solid var(--border-color);
  background: var(--card-bg);
}
.cd-sidebar-inner {
  position: sticky;
  top: 60px;
  max-height: calc(100vh - 60px);
  overflow-y: auto;
}
.cd-sidebar-inner::-webkit-scrollbar { width: 4px; }
.cd-sidebar-inner::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 2px; }

.cd-cta-box { padding: 20px; border-bottom: 1px solid var(--border-color); }

/* Thumbnail */
.cd-thumb {
  width: 100%; height: 160px;
  border-radius: 12px; overflow: hidden;
  background: linear-gradient(135deg,#6366f1,#1a56db);
  position: relative; margin-bottom: 14px;
}
.cd-thumb img { width:100%;height:100%;object-fit:cover;display:block; }
.cd-thumb-placeholder { width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;color:rgba(255,255,255,.4); }
.cd-play-overlay {
  position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  background:rgba(0,0,0,.35);color:#fff;font-size:3rem;
  transition:background .15s;
}
.cd-play-overlay:hover { background:rgba(0,0,0,.5);color:#fff; }

/* Progress */
.cd-progress-wrap { margin-bottom:14px; }

/* CTA button */
.cd-btn-start {
  display: flex;align-items:center;justify-content:center;
  width:100%;padding:12px;
  background:linear-gradient(135deg,#6366f1,#1a56db);
  color:#fff!important;text-decoration:none!important;
  border-radius:10px;font-size:14.5px;font-weight:700;
  box-shadow:0 4px 14px rgba(99,102,241,.3);
  transition:opacity .15s,transform .1s;
  margin-bottom:16px;
}
.cd-btn-start:hover { opacity:.92;transform:translateY(-1px); }
.cd-btn-start.done { background:linear-gradient(135deg,#0e9f6e,#059669);box-shadow:0 4px 14px rgba(14,159,110,.3); }

/* Meta list */
.cd-meta-list { display:flex;flex-direction:column;gap:8px; }
.cd-meta-item { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted); }
.cd-meta-item i { width:16px;text-align:center;flex-shrink:0; }
.cd-meta-item a { color:var(--primary); }

/* Outline */
.cd-outline { }
.cd-outline-title {
  display:flex;align-items:center;
  padding:14px 20px;
  font-size:13px;font-weight:700;color:var(--text-primary);
  border-bottom:1px solid var(--border-color);
  background:var(--content-bg);
  position:sticky;top:0;z-index:10;
}
.cd-section { border-bottom:1px solid var(--border-color); }
.cd-section-head {
  display:flex;align-items:center;gap:8px;
  padding:12px 20px;cursor:pointer;
  font-size:13px;font-weight:600;color:var(--text-primary);
  transition:background .1s;
}
.cd-section-head:hover { background:var(--content-bg); }
.cd-sec-chevron { font-size:11px;color:var(--text-muted);transition:transform .2s;flex-shrink:0; }
.cd-section.open .cd-sec-chevron { transform:rotate(90deg); }
.cd-sec-title { flex:1;font-size:13px; }
.cd-sec-count { font-size:11px;color:var(--text-muted);flex-shrink:0; }
.cd-section-lessons { display:none;background:var(--content-bg); }
.cd-section.open .cd-section-lessons { display:block; }
.cd-lesson {
  display:flex;align-items:center;gap:8px;
  padding:9px 20px 9px 32px;
  font-size:12.5px;color:var(--text-muted);
  text-decoration:none!important;
  border-left:2px solid transparent;
  transition:background .1s,color .1s;
}
.cd-lesson:hover { background:var(--card-bg);color:var(--text-primary); }
.cd-lesson.done { color:var(--text-muted); }
.cd-lesson-check { flex-shrink:0;font-size:14px;line-height:1; }
.cd-lesson-title { flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }

/* Main content */
.cd-main { padding:32px 32px 48px; }

.cd-hero { margin-bottom:24px; }
.cd-hero-title { font-size:24px;font-weight:800;color:var(--text-primary);margin-bottom:10px;line-height:1.3; }
.cd-hero-desc { font-size:15px;color:var(--text-muted);line-height:1.6;margin-bottom:0; }

/* Tabs */
.cd-tabs-wrap { border-bottom:1px solid var(--border-color);margin-bottom:28px; }
.cd-tabs { display:flex;list-style:none;margin:0;padding:0;gap:0; }
.cd-tabs li { display:flex; }
.cd-tab {
  padding:12px 20px;font-size:14px;font-weight:500;
  color:var(--text-muted);text-decoration:none!important;
  border-bottom:2px solid transparent;transition:color .15s,border-color .15s;
}
.cd-tab:hover { color:var(--primary); }
.cd-tab.active { color:var(--primary);border-bottom-color:var(--primary);font-weight:600; }

.cd-tab-panel { display:block; }
.cd-tab-panel.d-none { display:none!important; }
.cd-section-block { margin-bottom:32px; }
.cd-section-label { font-size:17px;font-weight:700;color:var(--text-primary);margin-bottom:16px; }

.cd-rich-content { font-size:15px;line-height:1.8;color:var(--text-primary); }
.cd-rich-content h2,.cd-rich-content h3 { font-weight:700;margin:20px 0 10px; }
.cd-rich-content ul,.cd-rich-content ol { padding-left:20px;margin-bottom:14px; }
.cd-rich-content p { margin-bottom:12px; }

/* Detail cards */
.cd-detail-card {
  background:var(--content-bg);border:1px solid var(--border-color);
  border-radius:12px;padding:16px;text-align:center;
}
.cd-detail-icon { font-size:1.5rem;color:#6366f1;display:block;margin-bottom:6px; }
.cd-detail-label { font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:3px; }
.cd-detail-value { font-size:13.5px;font-weight:600;color:var(--text-primary); }

/* Certificate banner */
.cd-cert-banner {
  display:flex;align-items:center;gap:16px;
  padding:20px;border-radius:14px;
  background:linear-gradient(135deg,#fef9c3,#fef3c7);
  border:1px solid #fde68a;
}
.cd-btn-cert {
  padding:9px 20px;border-radius:9px;font-size:13.5px;font-weight:700;
  background:#e3a008;color:#fff!important;text-decoration:none!important;
  white-space:nowrap;transition:opacity .15s;
}
.cd-btn-cert:hover { opacity:.9; }

/* Instructor */
.cd-instructor-card {
  display:flex;align-items:center;gap:14px;
  padding:16px;border:1px solid var(--border-color);
  border-radius:12px;margin-bottom:12px;
  background:var(--content-bg);
}
.cd-instr-avatar {
  width:52px;height:52px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,#6366f1,#1a56db);
  display:flex;align-items:center;justify-content:center;
  font-size:20px;font-weight:700;color:#fff;
}

/* Reviews */
.cd-review-item { padding:18px 0;border-bottom:1px solid var(--border-color); }
.cd-review-item:last-child { border-bottom:none; }
.cd-review-head { display:flex;align-items:center;gap:12px;margin-bottom:10px; }
.cd-review-avatar {
  width:38px;height:38px;border-radius:50%;flex-shrink:0;
  background:#6366f1;color:#fff;display:flex;align-items:center;justify-content:center;
  font-size:15px;font-weight:700;
}
.cd-review-body { font-size:14px;color:var(--text-muted);line-height:1.6;margin:0; }

@media (max-width: 991px) {
  .course-detail-wrap { margin:-28px -24px 0; }
  .cd-sidebar { border-right:none;border-bottom:1px solid var(--border-color); }
  .cd-sidebar-inner { position:static;max-height:none; }
  .cd-main { padding:20px; }
  .cd-hero-title { font-size:20px; }
}
</style>

<script>
// ── Tab switching ──────────────────────────────────────────────────────────
document.querySelectorAll('.cd-tab').forEach(tab => {
  tab.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('.cd-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.cd-tab-panel').forEach(p => p.classList.add('d-none'));
    this.classList.add('active');
    const panel = document.getElementById('cdTab-' + this.dataset.tab);
    if (panel) panel.classList.remove('d-none');
  });
});

// ── Section accordion — click on header toggles open/close ─────────────────
document.querySelectorAll('.cd-section-head').forEach(head => {
  head.addEventListener('click', function() {
    this.closest('.cd-section').classList.toggle('open');
  });
});
// All start open — already set in PHP (class="cd-section open")
</script>

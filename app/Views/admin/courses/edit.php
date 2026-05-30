<?php
use App\Core\View;
$e    = fn(mixed $v): string => View::e($v);
$url  = fn(string $p = ''): string => View::url($p);
$asset= fn(string $p): string => View::asset($p);

$lessonTypeIcons  = ['text'=>'bi-file-text','video'=>'bi-play-circle-fill','document'=>'bi-file-pdf','scorm'=>'bi-box-seam','quiz'=>'bi-patch-question-fill','assignment'=>'bi-clipboard-check-fill'];
$lessonTypeColors = ['text'=>'secondary','video'=>'danger','document'=>'warning','scorm'=>'info','quiz'=>'success','assignment'=>'primary'];
$statusColors     = ['draft'=>'secondary','published'=>'success','archived'=>'warning'];
?>

<!-- ── Top header bar ──────────────────────────────────────────────────────── -->
<div class="ce-header">
  <div class="ce-header-left">
    <a href="<?= $url('admin/courses') ?>" class="ce-back-btn">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h4 class="ce-course-name"><?= $e($course['title']) ?></h4>
      <div class="ce-course-meta">
        <span class="badge bg-<?= $statusColors[$course['status']] ?? 'secondary' ?>"><?= ucfirst($e($course['status'])) ?></span>
        <span class="text-muted ms-2" style="font-size:12px"><?= $e($course['language']) ?> · <?= ucfirst($e($course['level'])) ?></span>
      </div>
    </div>
  </div>
  <div class="ce-header-actions">
    <a href="<?= $url('admin/courses/' . $course['uuid'] . '/preview') ?>" target="_blank"
       class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-eye me-1"></i> Preview
    </a>
    <a href="<?= $url('admin/courses/' . $course['uuid'] . '/export') ?>"
       class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-download me-1"></i> Export
    </a>
    <a href="<?= $url('admin/courses/' . $course['uuid'] . '/enrollments') ?>"
       class="btn btn-sm btn-outline-success">
      <i class="bi bi-people me-1"></i> Students
      <span class="badge bg-success ms-1"><?= (int)($course['enrollment_count'] ?? 0) ?></span>
    </a>
    <!-- Quick status toggle -->
    <div class="dropdown">
      <button class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
        Publish
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
        <?php foreach (['draft'=>'Set as Draft','published'=>'Publish Now','archived'=>'Archive'] as $st=>$lbl): ?>
        <?php if ($st !== $course['status']): ?>
        <li>
          <button class="dropdown-item btn-change-status" data-uuid="<?= $e($course['uuid']) ?>" data-status="<?= $st ?>">
            <?= $lbl ?>
          </button>
        </li>
        <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<!-- ── Main tabs ───────────────────────────────────────────────────────────── -->
<div class="ce-tabs-wrap">
  <ul class="ce-tabs" id="courseEditTabs">
    <li><a class="ce-tab active" href="#" data-tab="builder">
      <i class="bi bi-layout-text-sidebar me-1"></i> Content Builder
    </a></li>
    <li><a class="ce-tab" href="#" data-tab="details">
      <i class="bi bi-info-circle me-1"></i> Course Details
    </a></li>
    <li><a class="ce-tab" href="#" data-tab="settings">
      <i class="bi bi-sliders me-1"></i> Settings
    </a></li>
    <li><a class="ce-tab" href="#" data-tab="media">
      <i class="bi bi-image me-1"></i> Media
    </a></li>
    <li><a class="ce-tab" href="#" data-tab="instructors">
      <i class="bi bi-person-badge me-1"></i> Instructors
    </a></li>
  </ul>
</div>

<!-- ═══════════════════════════════════════════════════
     TAB: CONTENT BUILDER
════════════════════════════════════════════════════ -->
<div class="ce-tab-content active" id="tab-builder">
  <div class="builder-toolbar">
    <div class="builder-toolbar-left">
      <i class="bi bi-list-nested text-muted me-2"></i>
      <span style="font-weight:600;font-size:14px"><?= count($sections) ?> section<?= count($sections) !== 1 ? 's' : '' ?></span>
      <span class="text-muted ms-2" style="font-size:13px">
        · <?= array_sum(array_map(fn($s) => count($s['lessons']), $sections)) ?> lesson<?= array_sum(array_map(fn($s) => count($s['lessons']), $sections)) !== 1 ? 's' : '' ?>
      </span>
    </div>
    <button class="btn btn-primary btn-sm" id="addSectionBtn">
      <i class="bi bi-plus-circle me-1"></i> Add Section
    </button>
  </div>

  <?php if (empty($sections)): ?>
  <div class="builder-empty" id="builderEmpty">
    <div class="builder-empty-icon"><i class="bi bi-collection-play"></i></div>
    <h5>No content yet</h5>
    <p>Start building your course by adding a section.</p>
    <button class="btn btn-primary mt-2" id="addSectionBtnEmpty">
      <i class="bi bi-plus-circle me-1"></i> Add First Section
    </button>
  </div>
  <?php endif; ?>

  <!-- Sections list -->
  <div id="sectionsList" class="sections-list">
    <?php foreach ($sections as $sec): ?>
    <div class="section-card" data-section-id="<?= $sec['id'] ?>">
      <!-- Section header -->
      <div class="section-head">
        <div class="drag-handle section-drag-handle">
          <i class="bi bi-grip-vertical"></i>
        </div>
        <div class="section-icon"><i class="bi bi-collection"></i></div>
        <div class="section-title-wrap" onclick="toggleSectionBody(<?= $sec['id'] ?>)">
          <span class="section-title" id="sec-title-<?= $sec['id'] ?>"><?= $e($sec['title']) ?></span>
          <span class="section-meta text-muted">
            <?= count($sec['lessons']) ?> lesson<?= count($sec['lessons']) !== 1 ? 's' : '' ?>
            <?php if ($sec['drip_days']): ?>
              · <i class="bi bi-clock ms-1"></i> +<?= $sec['drip_days'] ?>d drip
            <?php endif; ?>
          </span>
        </div>
        <div class="section-actions">
          <button class="sec-btn sec-btn-add btn-add-lesson" data-section-id="<?= $sec['id'] ?>" title="Add lesson">
            <i class="bi bi-plus"></i> Add Lesson
          </button>
          <button class="sec-btn btn-edit-section"
                  data-id="<?= $sec['id'] ?>"
                  data-title="<?= $e($sec['title']) ?>"
                  data-description="<?= $e($sec['description'] ?? '') ?>"
                  data-drip="<?= $e($sec['drip_days'] ?? '') ?>"
                  title="Edit section">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="sec-btn sec-btn-del btn-delete-section"
                  data-id="<?= $sec['id'] ?>" data-title="<?= $e($sec['title']) ?>" title="Delete section">
            <i class="bi bi-trash3"></i>
          </button>
          <button class="sec-btn sec-btn-toggle" onclick="toggleSectionBody(<?= $sec['id'] ?>)" title="Toggle">
            <i class="bi bi-chevron-down" id="sec-chevron-<?= $sec['id'] ?>"></i>
          </button>
        </div>
      </div>

      <!-- Section lessons body -->
      <div class="section-body open" id="section-body-<?= $sec['id'] ?>">
        <div class="lessons-list" id="lessons-<?= $sec['id'] ?>">
          <?php foreach ($sec['lessons'] as $les): ?>
          <?php
            $icon  = $lessonTypeIcons[$les['type']] ?? 'bi-file';
            $color = $lessonTypeColors[$les['type']] ?? 'secondary';
          ?>
          <div class="lesson-row" data-lesson-id="<?= $les['id'] ?>">
            <div class="drag-handle lesson-drag-handle"><i class="bi bi-grip-vertical"></i></div>
            <div class="lesson-type-badge lesson-type-<?= $les['type'] ?>">
              <i class="bi <?= $icon ?>"></i>
            </div>
            <span class="lesson-title-text"><?= $e($les['title']) ?></span>
            <?php if ($les['is_previewable']): ?>
              <span class="badge bg-success-subtle text-success ms-2" style="font-size:10px">Free Preview</span>
            <?php endif; ?>
            <?php if ($les['drip_days']): ?>
              <span class="badge bg-info-subtle text-info ms-1" style="font-size:10px">+<?= $les['drip_days'] ?>d</span>
            <?php endif; ?>
            <div class="lesson-actions ms-auto">
              <?php if ($les['type'] === 'quiz'): ?>
              <a href="<?= $url('admin/quizzes/lesson/' . $les['id']) ?>"
                 class="les-btn les-btn-quiz" title="Open Quiz Builder" target="_blank">
                <i class="bi bi-patch-question"></i> Quiz Builder
              </a>
              <?php endif; ?>
              <button class="les-btn btn-edit-lesson"
                      data-id="<?= $les['id'] ?>"
                      data-title="<?= $e($les['title']) ?>"
                      data-type="<?= $e($les['type']) ?>"
                      data-video-type="<?= $e($les['video_type'] ?? '') ?>"
                      data-content="<?= $e($les['content'] ?? '') ?>"
                      data-file="<?= $e($les['file_path'] ?? '') ?>"
                      data-duration="<?= $e($les['duration_sec'] ?? '') ?>"
                      data-drip="<?= $e($les['drip_days'] ?? '') ?>"
                      data-previewable="<?= $les['is_previewable'] ?>"
                      data-mandatory="<?= $les['is_mandatory'] ?>"
                      title="Edit lesson">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="les-btn les-btn-del btn-delete-lesson"
                      data-id="<?= $les['id'] ?>" data-title="<?= $e($les['title']) ?>" title="Delete">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($sec['lessons'])): ?>
          <div class="lessons-empty">
            <i class="bi bi-plus-circle me-1"></i> No lessons yet — click "Add Lesson" above
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     TAB: COURSE DETAILS
════════════════════════════════════════════════════ -->
<div class="ce-tab-content" id="tab-details">
  <div class="ce-form-wrap">
    <form action="<?= $url('admin/courses/' . $course['uuid'] . '/edit') ?>"
          method="POST" enctype="multipart/form-data" id="editCourseForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
      <?php include __DIR__ . '/_form_fields.php'; ?>
      <div class="ce-form-actions">
        <button type="submit" class="btn btn-primary px-5" id="saveEditBtn">
          <i class="bi bi-check-circle me-1"></i> Save Changes
        </button>
        <a href="<?= $url('admin/courses') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<!-- Settings and Media tabs rendered via _form_fields.php tabs, but we expose them separately -->
<div class="ce-tab-content" id="tab-settings">
  <div class="ce-form-wrap">
    <form action="<?= $url('admin/courses/' . $course['uuid'] . '/edit') ?>"
          method="POST" id="editCourseFormSettings" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
      <?php include __DIR__ . '/_settings_fields.php'; ?>
      <div class="ce-form-actions">
        <button type="submit" class="btn btn-primary px-5">
          <i class="bi bi-check-circle me-1"></i> Save Settings
        </button>
      </div>
    </form>
  </div>
</div>

<div class="ce-tab-content" id="tab-media">
  <div class="ce-form-wrap">
    <form action="<?= $url('admin/courses/' . $course['uuid'] . '/thumbnail') ?>"
          method="POST" enctype="multipart/form-data" id="editCourseFormMedia" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
      <?php include __DIR__ . '/_media_fields.php'; ?>
      <div class="ce-form-actions">
        <button type="submit" class="btn btn-primary px-5">
          <i class="bi bi-check-circle me-1"></i> Save Media
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     TAB: INSTRUCTORS
════════════════════════════════════════════════════ -->
<div class="ce-tab-content" id="tab-instructors">
  <div class="ce-form-wrap">
    <div class="row g-4">
      <div class="col-12 col-lg-5">
        <div class="card lms-card">
          <div class="card-header lms-card-header">
            <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i> Assign Instructor / Manager</h5>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label class="form-label fw-semibold">Search User</label>
              <input type="text" class="form-control" id="instrUserSearch"
                     placeholder="Type name or email…" autocomplete="off">
              <div id="instrSearchResults" class="list-group mt-1" style="max-height:200px;overflow-y:auto"></div>
              <input type="hidden" id="instrUserId">
              <div id="instrUserDisplay" class="mt-2 p-2 rounded d-none"
                   style="background:var(--content-bg);font-size:13px"></div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Role</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="instrRole" id="roleInstructor" value="instructor" checked>
                  <label class="form-check-label" for="roleInstructor">
                    <i class="bi bi-person-video3 me-1 text-primary"></i> Instructor
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="instrRole" id="roleManager" value="manager">
                  <label class="form-check-label" for="roleManager">
                    <i class="bi bi-briefcase me-1 text-success"></i> Manager
                  </label>
                </div>
              </div>
            </div>
            <button class="btn btn-primary w-100" id="assignInstrBtn">
              <i class="bi bi-plus-circle me-1"></i> Assign
            </button>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-7">
        <div class="card lms-card">
          <div class="card-header lms-card-header">
            <h5 class="mb-0"><i class="bi bi-people me-2"></i> Assigned Users</h5>
          </div>
          <div id="assignedList">
            <div class="card-body text-center py-4 text-muted" id="assignedEmpty">
              <i class="bi bi-person-slash" style="font-size:2rem;opacity:.3"></i>
              <p class="mt-2 mb-0 small">No instructors assigned yet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Modals ──────────────────────────────────────────────────────────────── -->

<!-- Edit Section Modal -->
<div class="modal fade" id="editSectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-collection me-2"></i>Edit Section</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <input type="hidden" id="editSecId">
        <div class="mb-3">
          <label class="form-label fw-semibold">Section Title</label>
          <input type="text" class="form-control" id="editSecTitle" maxlength="255">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Description <small class="text-muted fw-normal">(optional)</small></label>
          <textarea class="form-control" id="editSecDesc" rows="2"></textarea>
        </div>
        <div>
          <label class="form-label fw-semibold">Drip Days <small class="text-muted fw-normal">(0 = available immediately)</small></label>
          <input type="number" class="form-control" id="editSecDrip" min="0" placeholder="e.g. 7">
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveSecEdit"><i class="bi bi-check-circle me-1"></i>Save Section</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Lesson Type Picker -->
<div class="modal fade" id="addLessonModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>Add Lesson</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 pb-4">
        <input type="hidden" id="addLessonSectionId">
        <p class="text-muted small mb-3">Select a lesson type:</p>
        <div class="row g-2">
          <?php foreach ([
            ['text',       'Text / Article',  'bi-file-text',           'secondary'],
            ['video',      'Video Lesson',     'bi-play-circle-fill',    'danger'],
            ['document',   'PDF Document',     'bi-file-pdf',            'warning'],
            ['scorm',      'SCORM Package',    'bi-box-seam',            'info'],
            ['quiz',       'Quiz',             'bi-patch-question-fill', 'success'],
            ['assignment', 'Assignment',       'bi-clipboard-check-fill','primary'],
          ] as [$type, $label, $icon, $color]): ?>
          <div class="col-6">
            <button class="lesson-type-picker btn-pick-type w-100" data-type="<?= $type ?>">
              <i class="bi <?= $icon ?> lesson-type-pick-icon text-<?= $color ?>"></i>
              <span><?= $label ?></span>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Lesson Modal -->
<div class="modal fade" id="editLessonModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Edit Lesson</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <input type="hidden" id="editLesId">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold">Lesson Title</label>
            <input type="text" class="form-control" id="editLesTitle" maxlength="255">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Type</label>
            <select class="form-select" id="editLesType" disabled>
              <option value="text">Text</option>
              <option value="video">Video</option>
              <option value="document">Document</option>
              <option value="scorm">SCORM</option>
              <option value="quiz">Quiz</option>
            </select>
          </div>
          <!-- Video source -->
          <div class="col-12" id="videoTypeWrap" style="display:none">
            <label class="form-label fw-semibold">Video Source</label>
            <select class="form-select" id="editLesVideoType">
              <option value="upload">Upload File</option>
              <option value="youtube">YouTube URL</option>
              <option value="vimeo">Vimeo URL</option>
            </select>
          </div>
          <!-- Content -->
          <div class="col-12" id="contentWrap" style="display:none">
            <label class="form-label fw-semibold" id="contentLabel">Content</label>
            <div id="lessonTextEditorWrap" style="display:none">
              <div id="lessonTextEditor"></div>
              <textarea id="editLesContent" class="d-none"></textarea>
            </div>
            <div id="lessonUrlWrap" style="display:none">
              <input type="text" class="form-control" id="editLesUrl" placeholder="https://youtube.com/watch?v=…">
            </div>
          </div>
          <!-- File upload -->
          <div class="col-12" id="fileUploadWrap" style="display:none">
            <label class="form-label fw-semibold">Upload File</label>
            <input type="file" class="form-control" id="editLesFile">
            <div class="form-text" id="currentFile"></div>
          </div>
          <!-- Duration / Drip -->
          <div class="col-md-4">
            <label class="form-label fw-semibold">Duration (seconds)</label>
            <input type="number" class="form-control" id="editLesDuration" min="0" placeholder="e.g. 600">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Drip Days</label>
            <input type="number" class="form-control" id="editLesDrip" min="0" placeholder="0 = immediate">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="editLesPreviewable">
                <label class="form-check-label fw-semibold" for="editLesPreviewable">Free Preview</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="editLesMandatory" checked>
                <label class="form-check-label fw-semibold" for="editLesMandatory">Mandatory</label>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <a href="#" class="btn btn-outline-success d-none me-auto" id="openQuizBuilder" target="_blank">
          <i class="bi bi-patch-question me-1"></i> Open Quiz Builder
        </a>
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveLesEdit"><i class="bi bi-check-circle me-1"></i>Save Lesson</button>
      </div>
    </div>
  </div>
</div>

<style>
/* ── Course Editor Layout ─────────────────────────────────────────────────── */
.ce-header {
  display: flex; align-items: center; justify-content: space-between;
  gap: 16px; flex-wrap: wrap;
  padding: 0 0 20px;
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 0;
}
.ce-header-left { display: flex; align-items: center; gap: 14px; }
.ce-back-btn {
  width: 36px; height: 36px; border-radius: 10px;
  border: 1px solid var(--border-color);
  background: var(--card-bg); color: var(--text-muted);
  display: flex; align-items: center; justify-content: center;
  text-decoration: none; font-size: 16px;
  transition: all .15s;
}
.ce-back-btn:hover { border-color: var(--primary); color: var(--primary); }
.ce-course-name { font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0; }
.ce-course-meta { margin-top: 2px; }
.ce-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.ce-tabs-wrap {
  border-bottom: 1px solid var(--border-color);
  margin: 0 -20px;
  padding: 0 20px;
  background: var(--card-bg);
  position: sticky; top: 60px; z-index: 200;
}
.ce-tabs {
  display: flex; list-style: none; margin: 0; padding: 0; gap: 0;
}
.ce-tabs li { display: flex; }
.ce-tab {
  display: flex; align-items: center; gap: 6px;
  padding: 14px 20px;
  font-size: 13.5px; font-weight: 500;
  color: var(--text-muted);
  text-decoration: none !important;
  border-bottom: 2px solid transparent;
  white-space: nowrap;
  transition: color .15s, border-color .15s;
}
.ce-tab:hover { color: var(--primary); }
.ce-tab.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 600; }

/* ── Tab content ──────────────────────────────────────────────────────────── */
.ce-tab-content { display: none; padding-top: 24px; }
.ce-tab-content.active { display: block; }
.ce-form-wrap { max-width: 860px; }
.ce-form-actions { padding-top: 24px; border-top: 1px solid var(--border-color); margin-top: 32px; display: flex; gap: 10px; }

/* ── Builder toolbar ──────────────────────────────────────────────────────── */
.builder-toolbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius) var(--radius) 0 0;
  margin-bottom: 0;
}
.builder-toolbar-left { display: flex; align-items: center; }

/* ── Builder empty state ──────────────────────────────────────────────────── */
.builder-empty {
  text-align: center; padding: 60px 24px;
  border: 2px dashed var(--border-color);
  border-top: none;
  border-radius: 0 0 var(--radius) var(--radius);
  color: var(--text-muted);
}
.builder-empty-icon { font-size: 3.5rem; opacity: .2; margin-bottom: 12px; }

/* ── Section card ─────────────────────────────────────────────────────────── */
.sections-list { border: 1px solid var(--border-color); border-top: none; border-radius: 0 0 var(--radius) var(--radius); }
.section-card { border-bottom: 1px solid var(--border-color); }
.section-card:last-child { border-bottom: none; border-radius: 0 0 var(--radius) var(--radius); }

.section-head {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 16px;
  background: var(--content-bg);
  cursor: default;
}
.section-icon { color: #6366f1; font-size: 16px; flex-shrink: 0; }
.section-title-wrap { flex: 1; min-width: 0; cursor: pointer; }
.section-title { display: block; font-weight: 600; font-size: 14.5px; color: var(--text-primary); }
.section-meta  { display: block; font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.section-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

.sec-btn {
  padding: 5px 10px; border-radius: 7px; font-size: 12.5px; font-weight: 500;
  border: 1px solid var(--border-color); background: var(--card-bg);
  color: var(--text-muted); cursor: pointer; transition: all .15s;
  display: flex; align-items: center; gap: 4px;
}
.sec-btn:hover { border-color: var(--primary); color: var(--primary); background: #ebf2ff; }
.sec-btn-add  { color: #6366f1; border-color: #6366f1; background: #ebf2ff; }
.sec-btn-add:hover { background: #6366f1; color: #fff; }
.sec-btn-del:hover { border-color: #e02424; color: #e02424; background: #fde8e8; }
.sec-btn-toggle { padding: 5px 8px; }

/* ── Section body ─────────────────────────────────────────────────────────── */
.section-body { padding: 0; }
.section-body:not(.open) { display: none; }
#sec-chevron-* { transition: transform .2s; }

/* ── Lesson row ───────────────────────────────────────────────────────────── */
.lessons-list { padding: 6px 0; }
.lesson-row {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 16px 10px 40px;
  border-bottom: 1px solid var(--border-color);
  background: var(--card-bg);
  transition: background .1s;
}
.lesson-row:last-child { border-bottom: none; }
.lesson-row:hover { background: var(--content-bg); }
.lesson-title-text { font-size: 13.5px; color: var(--text-primary); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.lessons-empty { padding: 16px 40px; color: var(--text-muted); font-size: 13px; font-style: italic; }

/* Lesson type badge */
.lesson-type-badge {
  width: 26px; height: 26px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; flex-shrink: 0;
}
.lesson-type-text     { background:#f1f5f9; color:#64748b; }
.lesson-type-video    { background:#fde8e8; color:#e02424; }
.lesson-type-document { background:#fef9c3; color:#ca8a04; }
.lesson-type-scorm    { background:#e0f7fa; color:#0891b2; }
.lesson-type-quiz     { background:#d1fae5; color:#059669; }

.lesson-actions { display: flex; align-items: center; gap: 4px; }
.les-btn {
  padding: 4px 8px; border-radius: 6px; font-size: 12px;
  border: 1px solid var(--border-color); background: transparent;
  color: var(--text-muted); cursor: pointer; transition: all .15s;
  display: inline-flex; align-items: center; gap: 4px; text-decoration: none;
}
.les-btn:hover { border-color: var(--primary); color: var(--primary); }
.les-btn-del:hover  { border-color: #e02424; color: #e02424; }
.les-btn-quiz { color: #059669; border-color: #059669; }
.les-btn-quiz:hover { background: #d1fae5; color: #059669; }

/* ── Drag handle ──────────────────────────────────────────────────────────── */
.drag-handle { cursor: grab; color: var(--border-color); font-size: 16px; flex-shrink: 0; }
.drag-handle:hover { color: var(--text-muted); }
.drag-handle:active { cursor: grabbing; }
.sortable-ghost { opacity: .4; background: #ebf2ff !important; }

/* ── Lesson type picker modal ─────────────────────────────────────────────── */
.lesson-type-picker {
  padding: 16px 12px; border: 2px solid var(--border-color);
  border-radius: 12px; background: var(--card-bg);
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  cursor: pointer; transition: all .15s; font-size: 13px; font-weight: 500; color: var(--text-primary);
}
.lesson-type-picker:hover { border-color: #6366f1; background: #ebf2ff; transform: translateY(-2px); }
.lesson-type-pick-icon { font-size: 1.8rem; }

/* Subtle badge helpers */
.bg-success-subtle { background:#d1fae5!important }
.bg-info-subtle    { background:#e0f7fa!important }
</style>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<input type="hidden" id="courseUuid" value="<?= $e($course['uuid']) ?>">

<script>
const CSRF      = document.getElementById('csrfToken').value;
const BASE      = '<?= rtrim(APP_URL, '/') ?>';
const courseUuid= document.getElementById('courseUuid').value;

// ── Tab switching ──────────────────────────────────────────────────────────
document.querySelectorAll('.ce-tab').forEach(tab => {
  tab.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('.ce-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ce-tab-content').forEach(c => c.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    if (this.dataset.tab === 'instructors') loadInstructors();
  });
});

// ── Toggle section body ────────────────────────────────────────────────────
function toggleSectionBody(id) {
  const body    = document.getElementById('section-body-' + id);
  const chevron = document.getElementById('sec-chevron-' + id);
  if (!body) return;
  const isOpen = body.classList.contains('open');
  body.classList.toggle('open', !isOpen);
  if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(-90deg)';
}

// ── Save course form ───────────────────────────────────────────────────────
document.getElementById('editCourseForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('saveEditBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…'; }
});

// ── Add Section ────────────────────────────────────────────────────────────
function doAddSection() {
  fetch(BASE + '/admin/courses/' + courseUuid + '/sections', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token=' + encodeURIComponent(CSRF) + '&title=New+Section',
  }).then(r=>r.json()).then(d => {
    if (d.success) { LMS.toast('success','Section added.'); location.reload(); }
    else LMS.toast('error', d.message);
  });
}
document.getElementById('addSectionBtn')?.addEventListener('click', doAddSection);
document.getElementById('addSectionBtnEmpty')?.addEventListener('click', doAddSection);

// ── Edit Section ───────────────────────────────────────────────────────────
document.querySelectorAll('.btn-edit-section').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('editSecId').value    = this.dataset.id;
    document.getElementById('editSecTitle').value = this.dataset.title;
    document.getElementById('editSecDesc').value  = this.dataset.description;
    document.getElementById('editSecDrip').value  = this.dataset.drip;
    new bootstrap.Modal(document.getElementById('editSectionModal')).show();
  });
});
document.getElementById('saveSecEdit')?.addEventListener('click', function() {
  const id = document.getElementById('editSecId').value;
  fetch(BASE + '/admin/sections/' + id + '/update', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token=' + encodeURIComponent(CSRF)
       + '&title='       + encodeURIComponent(document.getElementById('editSecTitle').value)
       + '&description=' + encodeURIComponent(document.getElementById('editSecDesc').value)
       + '&drip_days='   + encodeURIComponent(document.getElementById('editSecDrip').value),
  }).then(r=>r.json()).then(d => {
    if (d.success) { LMS.toast('success','Section saved.'); location.reload(); }
    else LMS.toast('error', d.message);
  });
});

// ── Delete Section ─────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-section').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.dataset.id, title = this.dataset.title;
    LMS.confirm('Delete section "' + title + '" and all its lessons?', function() {
      fetch(BASE + '/admin/sections/' + id + '/delete', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'csrf_token=' + encodeURIComponent(CSRF),
      }).then(r=>r.json()).then(d => { if (d.success) location.reload(); else LMS.toast('error',d.message); });
    });
  });
});

// ── Add Lesson ─────────────────────────────────────────────────────────────
let currentSectionId = null;
document.querySelectorAll('.btn-add-lesson').forEach(btn => {
  btn.addEventListener('click', function() {
    currentSectionId = this.dataset.sectionId;
    document.getElementById('addLessonSectionId').value = currentSectionId;
    new bootstrap.Modal(document.getElementById('addLessonModal')).show();
  });
});
document.querySelectorAll('.btn-pick-type').forEach(btn => {
  btn.addEventListener('click', function() {
    const type = this.dataset.type;
    const sid  = document.getElementById('addLessonSectionId').value;
    bootstrap.Modal.getInstance(document.getElementById('addLessonModal'))?.hide();
    fetch(BASE + '/admin/sections/' + sid + '/lessons', {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'csrf_token=' + encodeURIComponent(CSRF) + '&type=' + encodeURIComponent(type) + '&title=New+Lesson',
    }).then(r=>r.json()).then(d => {
      if (d.success) { LMS.toast('success','Lesson added.'); location.reload(); }
      else LMS.toast('error', d.message);
    });
  });
});

// ── Edit Lesson ────────────────────────────────────────────────────────────
let lessonQuill = null;

function mountLessonQuill() {
  if (lessonQuill) return;
  function doMount() {
    if (typeof Quill === 'undefined') return;
    lessonQuill = new Quill('#lessonTextEditor', {
      theme:'snow', placeholder:'Type lesson content here…',
      modules:{ toolbar:[[{header:[1,2,3,false]}],['bold','italic','underline'],
        [{list:'ordered'},{list:'bullet'}],['link','blockquote','code-block'],['clean']] }
    });
    lessonQuill.on('text-change', () => {
      document.getElementById('editLesContent').value = lessonQuill.root.innerHTML;
    });
  }
  if (typeof Quill !== 'undefined') { doMount(); return; }
  if (!document.querySelector('link[href*="quill"]')) {
    const css = document.createElement('link'); css.rel='stylesheet';
    css.href='https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css';
    document.head.appendChild(css);
  }
  const s = document.createElement('script');
  s.src='https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js';
  s.onload = () => setTimeout(doMount, 50);
  document.head.appendChild(s);
}

document.getElementById('editLessonModal')?.addEventListener('hidden.bs.modal', () => {
  if (lessonQuill) { lessonQuill = null; const c=document.getElementById('lessonTextEditor'); if(c) c.innerHTML=''; }
});

function updateLessonModalFields(type, videoType) {
  document.getElementById('videoTypeWrap').style.display   = type==='video' ? '' : 'none';
  document.getElementById('fileUploadWrap').style.display  =
    (type==='video'&&videoType==='upload')||type==='document'||type==='scorm' ? '' : 'none';
  document.getElementById('contentWrap').style.display     =
    type==='text'||(type==='video'&&videoType!=='upload') ? '' : 'none';
  document.getElementById('lessonTextEditorWrap').style.display = type==='text' ? '' : 'none';
  document.getElementById('lessonUrlWrap').style.display        = (type==='video'&&videoType!=='upload') ? '' : 'none';
  document.getElementById('contentLabel').textContent = type==='text' ? 'Content (HTML)' : 'Video URL';
  const qzBtn = document.getElementById('openQuizBuilder');
  if (qzBtn) qzBtn.classList.toggle('d-none', type!=='quiz');
  if (type==='text') setTimeout(mountLessonQuill, 80);
}

document.querySelectorAll('.btn-edit-lesson').forEach(btn => {
  btn.addEventListener('click', function() {
    const d = this.dataset;
    document.getElementById('editLesId').value          = d.id;
    document.getElementById('editLesTitle').value       = d.title;
    document.getElementById('editLesType').value        = d.type;
    document.getElementById('editLesVideoType').value   = d.videoType||'upload';
    document.getElementById('editLesContent').value     = d.content||'';
    document.getElementById('editLesDuration').value    = d.duration||'';
    document.getElementById('editLesDrip').value        = d.drip||'';
    document.getElementById('editLesPreviewable').checked = d.previewable==='1';
    document.getElementById('editLesMandatory').checked   = d.mandatory!=='0';
    document.getElementById('currentFile').textContent    = d.file ? 'Current: '+d.file : '';
    const urlEl = document.getElementById('editLesUrl');
    if (urlEl) urlEl.value = (d.type==='video'&&d.videoType!=='upload') ? (d.content||'') : '';
    const qzBtn = document.getElementById('openQuizBuilder');
    if (qzBtn) { qzBtn.classList.toggle('d-none', d.type!=='quiz'); qzBtn.href = BASE+'/admin/quizzes/lesson/'+d.id; }
    updateLessonModalFields(d.type, d.videoType);
    if (d.type==='text') setTimeout(()=>{ if(lessonQuill&&d.content) lessonQuill.root.innerHTML=d.content; }, 250);
    new bootstrap.Modal(document.getElementById('editLessonModal')).show();
  });
});
document.getElementById('editLesType')?.addEventListener('change', function() {
  updateLessonModalFields(this.value, document.getElementById('editLesVideoType').value);
});
document.getElementById('editLesVideoType')?.addEventListener('change', function() {
  updateLessonModalFields(document.getElementById('editLesType').value, this.value);
});
document.getElementById('saveLesEdit')?.addEventListener('click', function() {
  const id   = document.getElementById('editLesId').value;
  const type = document.getElementById('editLesType').value;
  const fd   = new FormData();
  if (lessonQuill&&type==='text') document.getElementById('editLesContent').value=lessonQuill.root.innerHTML;
  const urlEl=document.getElementById('editLesUrl');
  const vt=document.getElementById('editLesVideoType').value;
  const content=(type==='video'&&vt!=='upload'&&urlEl)?urlEl.value:document.getElementById('editLesContent').value;
  fd.append('csrf_token',    CSRF);
  fd.append('title',         document.getElementById('editLesTitle').value);
  fd.append('type',          type);
  fd.append('video_type',    vt);
  fd.append('content',       content);
  fd.append('duration_sec',  document.getElementById('editLesDuration').value);
  fd.append('drip_days',     document.getElementById('editLesDrip').value);
  fd.append('is_previewable',document.getElementById('editLesPreviewable').checked?'1':'0');
  fd.append('is_mandatory',  document.getElementById('editLesMandatory').checked?'1':'0');
  const fi=document.getElementById('editLesFile'); if(fi.files[0]) fd.append('lesson_file',fi.files[0]);
  this.disabled=true;
  fetch(BASE+'/admin/lessons/'+id+'/update',{method:'POST',body:fd})
  .then(r=>r.json()).then(d => {
    if(d.success){bootstrap.Modal.getInstance(document.getElementById('editLessonModal'))?.hide();LMS.toast('success','Lesson saved.');location.reload();}
    else LMS.toast('error',d.message);
  }).finally(()=>{ this.disabled=false; });
});

// ── Delete Lesson ──────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-lesson').forEach(btn => {
  btn.addEventListener('click', function() {
    const id=this.dataset.id, title=this.dataset.title;
    LMS.confirm('Delete lesson "'+title+'"?', function() {
      fetch(BASE+'/admin/lessons/'+id+'/delete',{
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'csrf_token='+encodeURIComponent(CSRF),
      }).then(r=>r.json()).then(d => { if(d.success) location.reload(); else LMS.toast('error',d.message); });
    });
  });
});

// ── Status change ──────────────────────────────────────────────────────────
document.querySelectorAll('.btn-change-status').forEach(btn => {
  btn.addEventListener('click', function() {
    fetch(BASE+'/admin/courses/'+this.dataset.uuid+'/toggle-status',{
      method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'csrf_token='+encodeURIComponent(CSRF)+'&status='+encodeURIComponent(this.dataset.status),
    }).then(r=>r.json()).then(d=>{ if(d.success){LMS.toast('success','Status updated to '+d.status+'.');location.reload();}else LMS.toast('error',d.message);});
  });
});

// ── SortableJS ─────────────────────────────────────────────────────────────
(function initSortable() {
  if (typeof Sortable==='undefined') {
    const s=document.createElement('script');
    s.src='https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js';
    s.onload=initSortable; document.head.appendChild(s); return;
  }
  Sortable.create(document.getElementById('sectionsList'),{
    handle:'.section-drag-handle',animation:150,ghostClass:'sortable-ghost',
    onEnd(){
      const ids=[...document.querySelectorAll('#sectionsList .section-card')].map(el=>el.dataset.sectionId);
      fetch(BASE+'/admin/sections/reorder',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'csrf_token='+encodeURIComponent(CSRF)+'&'+ids.map((id,i)=>'ids['+i+']='+id).join('&')});
    }
  });
  document.querySelectorAll('[id^="lessons-"]').forEach(el => {
    Sortable.create(el,{handle:'.lesson-drag-handle',animation:150,ghostClass:'sortable-ghost',
      onEnd(){
        const ids=[...el.querySelectorAll('.lesson-row')].map(r=>r.dataset.lessonId);
        fetch(BASE+'/admin/lessons/reorder',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:'csrf_token='+encodeURIComponent(CSRF)+'&'+ids.map((id,i)=>'ids['+i+']='+id).join('&')});
      }
    });
  });
})();

// ── Instructors tab ────────────────────────────────────────────────────────
function loadInstructors() {
  fetch(BASE+'/admin/courses/'+courseUuid+'/instructors')
  .then(r=>r.json()).then(d => {
    const list = document.getElementById('assignedList');
    const empty = document.getElementById('assignedEmpty');
    if (!d.users || !d.users.length) { if(empty) empty.style.display=''; return; }
    if(empty) empty.style.display='none';
    list.innerHTML = d.users.map(u => `
      <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom" id="instr-${u.user_id}">
        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <span style="color:#fff;font-size:13px;font-weight:700">${u.first_name[0]}${u.last_name[0]}</span>
        </div>
        <div class="flex-grow-1">
          <div style="font-weight:600;font-size:13.5px">${u.first_name} ${u.last_name}</div>
          <div style="font-size:12px;color:var(--text-muted)">${u.email}</div>
        </div>
        <span class="badge bg-${u.cm_role==='manager'?'success':'primary'}" style="font-size:11px">${u.cm_role}</span>
        <button class="btn btn-sm btn-outline-danger" onclick="removeInstructor(${u.user_id})">
          <i class="bi bi-x"></i>
        </button>
      </div>
    `).join('');
  });
}

let instrTimer;
document.getElementById('instrUserSearch')?.addEventListener('input', function() {
  clearTimeout(instrTimer);
  const q = this.value.trim();
  if (q.length < 2) { document.getElementById('instrSearchResults').innerHTML=''; return; }
  instrTimer = setTimeout(() => {
    fetch(BASE+'/admin/users/search?q='+encodeURIComponent(q))
    .then(r=>r.json()).then(data => {
      const ul = document.getElementById('instrSearchResults');
      ul.innerHTML = '';
      (data.users||[]).forEach(u => {
        const a = document.createElement('a'); a.href='#';
        a.className='list-group-item list-group-item-action py-2'; a.style.fontSize='13px';
        a.innerHTML='<strong>'+u.first_name+' '+u.last_name+'</strong> <span class="text-muted">('+u.email+')</span>';
        a.addEventListener('click', e => {
          e.preventDefault();
          document.getElementById('instrUserId').value = u.id;
          document.getElementById('instrUserDisplay').textContent = u.first_name+' '+u.last_name+' — '+u.email;
          document.getElementById('instrUserDisplay').classList.remove('d-none');
          document.getElementById('instrUserSearch').value = u.first_name+' '+u.last_name;
          ul.innerHTML = '';
        });
        ul.appendChild(a);
      });
    });
  }, 300);
});

document.getElementById('assignInstrBtn')?.addEventListener('click', function() {
  const userId = document.getElementById('instrUserId').value;
  const role   = document.querySelector('input[name="instrRole"]:checked')?.value || 'instructor';
  if (!userId) { LMS.toast('error','Please select a user.'); return; }
  fetch(BASE+'/admin/courses/'+courseUuid+'/instructors',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token='+encodeURIComponent(CSRF)+'&user_id='+userId+'&role='+role,
  }).then(r=>r.json()).then(d => {
    if(d.success){LMS.toast('success','User assigned.');loadInstructors();document.getElementById('instrUserId').value='';document.getElementById('instrUserDisplay').classList.add('d-none');document.getElementById('instrUserSearch').value='';}
    else LMS.toast('error',d.message);
  });
});

function removeInstructor(userId) {
  LMS.confirm('Remove this user from the course?', function() {
    fetch(BASE+'/admin/courses/'+courseUuid+'/instructors/remove',{
      method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'csrf_token='+encodeURIComponent(CSRF)+'&user_id='+userId,
    }).then(r=>r.json()).then(d=>{if(d.success){document.getElementById('instr-'+userId)?.remove();LMS.toast('success','Removed.');}else LMS.toast('error',d.message);});
  });
}
</script>

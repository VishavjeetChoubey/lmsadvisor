<?php
use App\Core\View;
$e    = fn(mixed $v): string => View::e($v);
$url  = fn(string $p = ''): string => View::url($p);
$asset= fn(string $p): string => View::asset($p);

$lessonTypeIcons = [
    'text'     => 'bi-file-text',
    'video'    => 'bi-play-circle',
    'document' => 'bi-file-pdf',
    'scorm'    => 'bi-box-seam',
    'quiz'     => 'bi-patch-question',
];
$lessonTypeColors = [
    'text'     => 'secondary',
    'video'    => 'danger',
    'document' => 'warning',
    'scorm'    => 'info',
    'quiz'     => 'success',
];
$statusColors = ['draft'=>'secondary','published'=>'success','archived'=>'warning'];
?>

<!-- Course status bar -->
<div class="course-status-bar mb-4">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <span class="badge bg-<?= $statusColors[$course['status']] ?? 'secondary' ?> px-3 py-2" style="font-size:13px">
      <?= ucfirst($e($course['status'])) ?>
    </span>
    <span class="text-muted" style="font-size:13px">
      <i class="bi bi-translate me-1"></i><?= $e($course['language']) ?>
      <?php if ($course['is_rtl']): ?><span class="badge bg-info ms-1">RTL</span><?php endif; ?>
    </span>
    <div class="ms-auto d-flex gap-2">
      <a href="<?= $url('admin/courses/' . $course['uuid'] . '/enrollments') ?>"
         class="btn btn-sm btn-outline-success">
        <i class="bi bi-people me-1"></i> Students
        <span class="badge bg-success ms-1" id="enrolledCountBadge"><?= $e($course['enrollment_count'] ?? 0) ?></span>
      </a>
      <a href="<?= $url('admin/courses/' . $course['uuid'] . '/preview') ?>"
         class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="bi bi-eye me-1"></i> Preview
      </a>
      <a href="<?= $url('admin/courses/' . $course['uuid'] . '/export') ?>"
         class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-download me-1"></i> Export JSON
      </a>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Left: Course form -->
  <div class="col-12 col-xl-6">
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Course Details</h5>
      </div>
      <div class="card-body p-4">
        <form action="<?= $url('admin/courses/' . $course['uuid'] . '/edit') ?>"
              method="POST" enctype="multipart/form-data" id="editCourseForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">

          <?php include __DIR__ . '/_form_fields.php'; ?>

          <hr class="my-4">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4" id="saveEditBtn">
              <i class="bi bi-check-circle me-1"></i> Save Changes
            </button>
            <a href="<?= $url('admin/courses') ?>" class="btn btn-outline-secondary">Back</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Right: Section/Lesson builder -->
  <div class="col-12 col-xl-6">
    <div class="card lms-card">
      <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-list-nested me-2"></i> Content Builder</h5>
        <button class="btn btn-sm btn-primary" id="addSectionBtn">
          <i class="bi bi-plus-circle me-1"></i> Add Section
        </button>
      </div>
      <div class="card-body p-3" id="builderContainer">

        <?php if (empty($sections)): ?>
          <div class="text-center py-5 text-muted" id="emptySectionMsg">
            <i class="bi bi-list-nested" style="font-size:2.5rem;opacity:.3"></i>
            <p class="mt-2 mb-0">No sections yet. Add a section to get started.</p>
          </div>
        <?php endif; ?>

        <!-- Sections list (SortableJS) -->
        <div id="sectionsList">
          <?php foreach ($sections as $sec): ?>
          <div class="section-block mb-3" data-section-id="<?= $sec['id'] ?>">
            <div class="section-header d-flex align-items-center gap-2">
              <span class="drag-handle section-drag-handle" title="Drag to reorder">
                <i class="bi bi-grip-vertical text-muted"></i>
              </span>
              <i class="bi bi-collection text-primary"></i>
              <span class="section-title fw-semibold flex-grow-1" style="font-size:14px">
                <?= $e($sec['title']) ?>
              </span>
              <span class="badge bg-secondary" style="font-size:10.5px">
                <?= count($sec['lessons']) ?> lessons
              </span>
              <?php if ($sec['drip_days']): ?>
                <span class="badge bg-info" title="Drip unlock">+<?= $sec['drip_days'] ?>d</span>
              <?php endif; ?>
              <div class="d-flex gap-1">
                <button class="btn btn-xs btn-outline-primary btn-edit-section"
                        data-id="<?= $sec['id'] ?>"
                        data-title="<?= $e($sec['title']) ?>"
                        data-description="<?= $e($sec['description'] ?? '') ?>"
                        data-drip="<?= $e($sec['drip_days'] ?? '') ?>">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-xs btn-outline-secondary btn-add-lesson"
                        data-section-id="<?= $sec['id'] ?>" title="Add lesson">
                  <i class="bi bi-plus"></i>
                </button>
                <button class="btn btn-xs btn-outline-danger btn-delete-section"
                        data-id="<?= $sec['id'] ?>"
                        data-title="<?= $e($sec['title']) ?>">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </div>

            <!-- Lessons list -->
            <div class="lessons-list ms-4 mt-2" id="lessons-<?= $sec['id'] ?>">
              <?php foreach ($sec['lessons'] as $les): ?>
              <?php
                $icon  = $lessonTypeIcons[$les['type']] ?? 'bi-file';
                $color = $lessonTypeColors[$les['type']] ?? 'secondary';
              ?>
              <div class="lesson-row d-flex align-items-center gap-2 py-1 px-2 rounded mb-1"
                   data-lesson-id="<?= $les['id'] ?>"
                   style="background:var(--content-bg)">
                <span class="drag-handle lesson-drag-handle" title="Drag">
                  <i class="bi bi-grip-vertical text-muted" style="font-size:12px"></i>
                </span>
                <i class="bi <?= $icon ?> text-<?= $color ?>" style="font-size:13px"></i>
                <span class="flex-grow-1" style="font-size:13px"><?= $e($les['title']) ?></span>
                <?php if ($les['is_previewable']): ?>
                  <span class="badge bg-success" style="font-size:9px">Preview</span>
                <?php endif; ?>
                <?php if ($les['drip_days']): ?>
                  <span class="badge bg-info" style="font-size:9px">+<?= $les['drip_days'] ?>d</span>
                <?php endif; ?>
                <div class="d-flex gap-1">
                  <button class="btn btn-xs btn-outline-primary btn-edit-lesson"
                          data-id="<?= $les['id'] ?>"
                          data-title="<?= $e($les['title']) ?>"
                          data-type="<?= $e($les['type']) ?>"
                          data-video-type="<?= $e($les['video_type'] ?? '') ?>"
                          data-content="<?= $e($les['content'] ?? '') ?>"
                          data-file="<?= $e($les['file_path'] ?? '') ?>"
                          data-duration="<?= $e($les['duration_sec'] ?? '') ?>"
                          data-drip="<?= $e($les['drip_days'] ?? '') ?>"
                          data-previewable="<?= $les['is_previewable'] ?>"
                          data-mandatory="<?= $les['is_mandatory'] ?>">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-xs btn-outline-danger btn-delete-lesson"
                          data-id="<?= $les['id'] ?>"
                          data-title="<?= $e($les['title']) ?>">
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div><!-- /#sectionsList -->

      </div><!-- /.card-body -->
    </div><!-- /.card -->
  </div>

</div><!-- /.row -->

<!-- Edit Section Modal -->
<div class="modal fade" id="editSectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
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
          <label class="form-label fw-semibold">Drip Days <small class="text-muted fw-normal">(0 = immediate)</small></label>
          <input type="number" class="form-control" id="editSecDrip" min="0" placeholder="e.g. 7">
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveSecEdit"><i class="bi bi-check-circle me-1"></i>Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Lesson Modal -->
<div class="modal fade" id="editLessonModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Edit Lesson</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <input type="hidden" id="editLesId">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" class="form-control" id="editLesTitle" maxlength="255">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Type</label>
            <select class="form-select" id="editLesType">
              <option value="text">Text</option>
              <option value="video">Video</option>
              <option value="document">Document</option>
              <option value="scorm">SCORM</option>
              <option value="quiz">Quiz</option>
            </select>
          </div>

          <!-- Video type (shown for video) -->
          <div class="col-12" id="videoTypeWrap" style="display:none">
            <label class="form-label fw-semibold">Video Source</label>
            <select class="form-select" id="editLesVideoType">
              <option value="upload">Upload MP4/WebM</option>
              <option value="youtube">YouTube URL</option>
              <option value="vimeo">Vimeo URL</option>
            </select>
          </div>

          <!-- Content (text/url) -->
          <div class="col-12" id="contentWrap">
            <label class="form-label fw-semibold" id="contentLabel">Content / URL</label>
            <!-- Shown for text lessons: Quill editor -->
            <div id="lessonTextEditorWrap" style="display:none">
              <div id="lessonTextEditor"
                   style="min-height:160px;border:1px solid var(--border-color);border-radius:var(--radius)"></div>
              <textarea id="editLesContent" class="d-none"></textarea>
            </div>
            <!-- Shown for video URL lessons -->
            <div id="lessonUrlWrap" style="display:none">
              <input type="text" class="form-control" id="editLesUrl"
                     placeholder="https://youtube.com/watch?v=… or https://vimeo.com/…">
            </div>
          </div>

          <!-- File upload (video/document/scorm) -->
          <div class="col-12" id="fileUploadWrap" style="display:none">
            <label class="form-label fw-semibold">Upload File</label>
            <input type="file" class="form-control" id="editLesFile">
            <div class="form-text" id="currentFile"></div>
          </div>

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
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-outline-success d-none" id="openQuizBuilder" target="_blank">
          <i class="bi bi-patch-question me-1"></i> Open Quiz Builder
        </a>
        <button class="btn btn-primary" id="saveLesEdit"><i class="bi bi-check-circle me-1"></i>Save Lesson</button>
      </div>
    </div>
  </div>
</div>

<!-- Add lesson type picker modal -->
<div class="modal fade" id="addLessonModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>Add Lesson</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 pb-4">
        <input type="hidden" id="addLessonSectionId">
        <p class="text-muted small mb-3">Choose a lesson type to add:</p>
        <div class="row g-2">
          <?php
          $types = [
            ['text',     'Text / Article',    'bi-file-text',     'secondary'],
            ['video',    'Video Lesson',       'bi-play-circle',   'danger'],
            ['document', 'PDF Document',       'bi-file-pdf',      'warning'],
            ['scorm',    'SCORM Package',      'bi-box-seam',      'info'],
            ['quiz',     'Quiz',               'bi-patch-question','success'],
          ];
          foreach ($types as [$type, $label, $icon, $color]):
          ?>
          <div class="col-6">
            <button class="btn btn-outline-<?= $color ?> w-100 py-3 btn-pick-type" data-type="<?= $type ?>">
              <i class="bi <?= $icon ?> d-block mb-1" style="font-size:1.4rem"></i>
              <span style="font-size:12.5px"><?= $label ?></span>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.section-block { border: 1px solid var(--border-color); border-radius: var(--radius); overflow: hidden; }
.section-header { background: var(--content-bg); padding: 10px 12px; }
.btn-xs { padding: 3px 7px; font-size: 12px; }
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }
.sortable-ghost { opacity: .4; }
</style>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<input type="hidden" id="courseUuid" value="<?= $e($course['uuid']) ?>">

<script>
const CSRF     = document.getElementById('csrfToken').value;
const BASE     = '<?= rtrim(APP_URL, '/') ?>';
const courseUuid = document.getElementById('courseUuid').value;

// ── Lesson Quill editor (text type) ──────────────────────────────────────────
let lessonQuill = null;

function mountLessonQuill() {
  if (lessonQuill) return; // already mounted

  function doMount() {
    if (typeof Quill === 'undefined') return;
    lessonQuill = new Quill('#lessonTextEditor', {
      theme: 'snow',
      placeholder: 'Type lesson content here…',
      modules: {
        toolbar: [
          [{ header: [1, 2, 3, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['link', 'blockquote', 'code-block'],
          ['clean'],
        ],
      },
    });
    lessonQuill.on('text-change', function () {
      document.getElementById('editLesContent').value = lessonQuill.root.innerHTML;
    });
  }

  if (typeof Quill !== 'undefined') {
    doMount();
    return;
  }

  // Load Quill CSS once
  if (!document.querySelector('link[href*="quill"]')) {
    const css = document.createElement('link');
    css.rel  = 'stylesheet';
    css.href = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css';
    document.head.appendChild(css);
  }
  const s = document.createElement('script');
  s.src   = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.umd.min.js';
  s.onload = function () { setTimeout(doMount, 50); };
  document.head.appendChild(s);
}

// Reset lesson quill when modal closes so it can be re-mounted next open
document.getElementById('editLessonModal')?.addEventListener('hidden.bs.modal', function () {
  if (lessonQuill) {
    // Destroy and reset so next lesson open gets a fresh instance
    lessonQuill = null;
    const container = document.getElementById('lessonTextEditor');
    if (container) container.innerHTML = '';
  }
});

// ── Save course form ──────────────────────────────────────────────────────────
document.getElementById('editCourseForm')?.addEventListener('submit', function () {
  const btn = document.getElementById('saveEditBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';
});

// ── Add Section ───────────────────────────────────────────────────────────────
document.getElementById('addSectionBtn').addEventListener('click', function () {
  fetch(BASE + '/admin/courses/' + courseUuid + '/sections', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) + '&title=New+Section',
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) { LMS.toast('success', 'Section added.'); location.reload(); }
    else LMS.toast('error', d.message);
  });
});

// ── Edit Section ──────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-edit-section').forEach(btn => {
  btn.addEventListener('click', function () {
    document.getElementById('editSecId').value    = this.dataset.id;
    document.getElementById('editSecTitle').value = this.dataset.title;
    document.getElementById('editSecDesc').value  = this.dataset.description;
    document.getElementById('editSecDrip').value  = this.dataset.drip;
    new bootstrap.Modal(document.getElementById('editSectionModal')).show();
  });
});

document.getElementById('saveSecEdit').addEventListener('click', function () {
  const id = document.getElementById('editSecId').value;
  fetch(BASE + '/admin/sections/' + id + '/update', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) +
          '&title='       + encodeURIComponent(document.getElementById('editSecTitle').value) +
          '&description=' + encodeURIComponent(document.getElementById('editSecDesc').value) +
          '&drip_days='   + encodeURIComponent(document.getElementById('editSecDrip').value),
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) { LMS.toast('success', 'Section saved.'); location.reload(); }
    else LMS.toast('error', d.message);
  });
});

// ── Delete Section ────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-section').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id, title = this.dataset.title;
    LMS.confirm('Delete section "' + title + '" and all its lessons?', function () {
      fetch(BASE + '/admin/sections/' + id + '/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(d => { if (d.success) location.reload(); else LMS.toast('error', d.message); });
    });
  });
});

// ── Add Lesson ────────────────────────────────────────────────────────────────
let currentSectionId = null;
document.querySelectorAll('.btn-add-lesson').forEach(btn => {
  btn.addEventListener('click', function () {
    currentSectionId = this.dataset.sectionId;
    document.getElementById('addLessonSectionId').value = currentSectionId;
    new bootstrap.Modal(document.getElementById('addLessonModal')).show();
  });
});

document.querySelectorAll('.btn-pick-type').forEach(btn => {
  btn.addEventListener('click', function () {
    const type      = this.dataset.type;
    const sectionId = document.getElementById('addLessonSectionId').value;
    bootstrap.Modal.getInstance(document.getElementById('addLessonModal'))?.hide();

    fetch(BASE + '/admin/sections/' + sectionId + '/lessons', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&type=' + encodeURIComponent(type) + '&title=New+Lesson',
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { LMS.toast('success', 'Lesson added.'); location.reload(); }
      else LMS.toast('error', d.message);
    });
  });
});

// ── Edit Lesson ───────────────────────────────────────────────────────────────
function updateLessonModalFields(type, videoType) {
  const videoWrap     = document.getElementById('videoTypeWrap');
  const contentWrap   = document.getElementById('contentWrap');
  const fileWrap      = document.getElementById('fileUploadWrap');
  const contentLbl    = document.getElementById('contentLabel');
  const textEditorWrap= document.getElementById('lessonTextEditorWrap');
  const urlWrap       = document.getElementById('lessonUrlWrap');

  // Hide all first
  if (videoWrap)      videoWrap.style.display   = 'none';
  if (fileWrap)       fileWrap.style.display     = 'none';
  if (textEditorWrap) textEditorWrap.style.display = 'none';
  if (urlWrap)        urlWrap.style.display      = 'none';
  if (contentWrap)    contentWrap.style.display  = 'none';

  if (type === 'video') {
    videoWrap.style.display  = '';
    contentWrap.style.display = '';
    if (videoType === 'upload') {
      fileWrap.style.display = '';
      contentLbl.textContent = 'Upload Video';
    } else {
      urlWrap.style.display  = '';
      contentLbl.textContent = 'Video URL (YouTube / Vimeo)';
    }
  } else if (type === 'text') {
    contentWrap.style.display    = '';
    textEditorWrap.style.display = '';
    contentLbl.textContent       = 'Lesson Content (HTML)';
    // Mount Quill when the editor becomes visible
    setTimeout(mountLessonQuill, 60);
  } else if (type === 'document' || type === 'scorm') {
    fileWrap.style.display    = '';
    contentLbl.textContent    = type === 'scorm' ? 'Upload SCORM Package (.zip)' : 'Upload PDF / Document';
  }
  // quiz: no content editor
}

document.querySelectorAll('.btn-edit-lesson').forEach(btn => {
  btn.addEventListener('click', function () {
    const d = this.dataset;
    document.getElementById('editLesId').value            = d.id;
    document.getElementById('editLesTitle').value         = d.title;
    document.getElementById('editLesType').value          = d.type;
    document.getElementById('editLesVideoType').value     = d.videoType || 'upload';
    document.getElementById('editLesContent').value       = d.content || ''; // hidden textarea
    document.getElementById('editLesDuration').value      = d.duration || '';
    document.getElementById('editLesDrip').value          = d.drip || '';
    document.getElementById('editLesPreviewable').checked = d.previewable === '1';
    document.getElementById('editLesMandatory').checked   = d.mandatory !== '0';
    document.getElementById('currentFile').textContent    = d.file ? 'Current: ' + d.file : '';

    // Pre-populate URL field for video lessons
    const urlEl = document.getElementById('editLesUrl');
    if (urlEl) urlEl.value = (d.type === 'video' && d.videoType !== 'upload') ? (d.content || '') : '';

    updateLessonModalFields(d.type, d.videoType);

    // Populate Quill editor after it is mounted (text type)
    if (d.type === 'text') {
      setTimeout(function () {
        if (lessonQuill && d.content) {
          lessonQuill.root.innerHTML = d.content;
        }
      }, 200);
    }

    // Show quiz builder button for quiz lessons
    const qzBtn = document.getElementById('openQuizBuilder');
    if (qzBtn) {
      qzBtn.classList.toggle('d-none', d.type !== 'quiz');
      qzBtn.href = BASE + '/admin/quizzes/lesson/' + d.id;
    }
    new bootstrap.Modal(document.getElementById('editLessonModal')).show();
  });
});

document.getElementById('editLesType').addEventListener('change', function () {
  updateLessonModalFields(this.value, document.getElementById('editLesVideoType').value);
  const qzBtn = document.getElementById('openQuizBuilder');
  if (qzBtn) qzBtn.classList.toggle('d-none', this.value !== 'quiz');
});
document.getElementById('editLesVideoType').addEventListener('change', function () {
  updateLessonModalFields(document.getElementById('editLesType').value, this.value);
});

document.getElementById('saveLesEdit').addEventListener('click', function () {
  const id   = document.getElementById('editLesId').value;
  const type = document.getElementById('editLesType').value;
  const fd   = new FormData();

  // Sync Quill to hidden field before reading
  if (lessonQuill && type === 'text') {
    document.getElementById('editLesContent').value = lessonQuill.root.innerHTML;
  }

  // For video URL lessons, content comes from the URL field
  const urlEl    = document.getElementById('editLesUrl');
  const videoType = document.getElementById('editLesVideoType').value;
  const content   = (type === 'video' && videoType !== 'upload' && urlEl)
    ? urlEl.value
    : document.getElementById('editLesContent').value;

  fd.append('csrf_token',    CSRF);
  fd.append('title',         document.getElementById('editLesTitle').value);
  fd.append('type',          type);
  fd.append('video_type',    videoType);
  fd.append('content',       content);
  fd.append('duration_sec',  document.getElementById('editLesDuration').value);
  fd.append('drip_days',     document.getElementById('editLesDrip').value);
  fd.append('is_previewable',document.getElementById('editLesPreviewable').checked ? '1' : '0');
  fd.append('is_mandatory',  document.getElementById('editLesMandatory').checked ? '1' : '0');
  const fileInput = document.getElementById('editLesFile');
  if (fileInput.files[0]) fd.append('lesson_file', fileInput.files[0]);

  fetch(BASE + '/admin/lessons/' + id + '/update', { method: 'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      bootstrap.Modal.getInstance(document.getElementById('editLessonModal'))?.hide();
      LMS.toast('success', 'Lesson saved.');
      location.reload();
    } else LMS.toast('error', d.message);
  });
});

// ── Delete Lesson ─────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-lesson').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id, title = this.dataset.title;
    LMS.confirm('Delete lesson "' + title + '"?', function () {
      fetch(BASE + '/admin/lessons/' + id + '/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(d => { if (d.success) location.reload(); else LMS.toast('error', d.message); });
    });
  });
});

// ── SortableJS — drag-and-drop reorder ───────────────────────────────────────
(function () {
  const initSortable = () => {
    if (typeof Sortable === 'undefined') return;

    // Section reorder
    Sortable.create(document.getElementById('sectionsList'), {
      handle: '.section-drag-handle',
      animation: 150,
      ghostClass: 'sortable-ghost',
      onEnd() {
        const ids = [...document.querySelectorAll('#sectionsList .section-block')]
          .map(el => el.dataset.sectionId);
        fetch(BASE + '/admin/sections/reorder', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: 'csrf_token=' + encodeURIComponent(CSRF) + '&' +
                ids.map((id, i) => 'ids[' + i + ']=' + id).join('&'),
        });
      },
    });

    // Lesson reorder per section
    document.querySelectorAll('[id^="lessons-"]').forEach(el => {
      const sectionId = el.id.replace('lessons-', '');
      Sortable.create(el, {
        handle: '.lesson-drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd() {
          const ids = [...el.querySelectorAll('.lesson-row')]
            .map(r => r.dataset.lessonId);
          fetch(BASE + '/admin/lessons/reorder', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'csrf_token=' + encodeURIComponent(CSRF) + '&' +
                  ids.map((id, i) => 'ids[' + i + ']=' + id).join('&'),
          });
        },
      });
    });
  };

  if (typeof Sortable !== 'undefined') {
    initSortable();
  } else {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js';
    s.onload = initSortable;
    document.head.appendChild(s);
  }
})();
</script>

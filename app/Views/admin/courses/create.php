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


<!-- AI Generation Modal -->
<div class="modal fade" id="aiModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-stars text-warning me-2"></i>Generate Complete Course with AI
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body px-4" id="aiModalBody">
        <div class="row g-4">

          <!-- LEFT: Config -->
          <div class="col-12 col-lg-5">

            <!-- Topic -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Course Topic <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="aiTopic"
                     placeholder="e.g. Complete PHP Development for Beginners">
              <div class="form-text">Be specific — a detailed topic produces better content</div>
            </div>

            <!-- Level + Language -->
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold">Level</label>
                <select class="form-select" id="aiLevel">
                  <option value="beginner">Beginner</option>
                  <option value="intermediate">Intermediate</option>
                  <option value="advanced">Advanced</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold">Language</label>
                <input type="text" class="form-control" id="aiLanguage" value="English">
              </div>
            </div>

            <!-- Sections + Lessons -->
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold">Sections</label>
                <input type="number" class="form-control" id="aiSections" value="5" min="2" max="15">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold">Lessons/Section</label>
                <input type="number" class="form-control" id="aiLessons" value="3" min="1" max="8">
                <div class="form-text">Incl. quiz per section</div>
              </div>
            </div>

            <!-- Content Type checkboxes -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Content Types to Include</label>
              <div class="p-3 border rounded-3" style="background:var(--content-bg)">
                <div class="row g-2">
                  <?php
                  $types = [
                    ['text',     'bi-file-text',          'secondary', 'Text Lessons',  'Theory, explanations, examples'],
                    ['video',    'bi-play-circle-fill',    'danger',    'Video Lessons', 'Demonstrations, walkthroughs'],
                    ['quiz',     'bi-patch-question-fill', 'success',   'Quizzes',       'MCQ questions with explanations'],
                    ['document', 'bi-file-pdf',            'warning',   'Documents',     'References, cheat sheets'],
                  ];
                  foreach ($types as [$val, $icon, $color, $label, $desc]):
                  ?>
                  <div class="col-12">
                    <label class="d-flex align-items-center gap-3 p-2 rounded-2 cursor-pointer"
                           style="cursor:pointer;border:1.5px solid transparent;transition:all .15s"
                           id="typeLabel_<?= $val ?>"
                           onmouseover="this.style.background='#f1f5f9'"
                           onmouseout="this.style.background=''">
                      <input class="form-check-input flex-shrink-0 ai-content-type" type="checkbox"
                             value="<?= $val ?>" id="type_<?= $val ?>"
                             <?= in_array($val, ['text','video','quiz']) ? 'checked' : '' ?>
                             style="width:18px;height:18px">
                      <i class="bi <?= $icon ?> text-<?= $color ?>" style="font-size:18px;flex-shrink:0"></i>
                      <div>
                        <div class="fw-semibold" style="font-size:13.5px"><?= $label ?></div>
                        <div class="text-muted" style="font-size:12px"><?= $desc ?></div>
                      </div>
                    </label>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Extra instructions -->
            <div class="mb-0">
              <label class="form-label fw-semibold">Extra Instructions <small class="text-muted fw-normal">(optional)</small></label>
              <textarea class="form-control" id="aiExtra" rows="3"
                        placeholder="e.g. Focus on practical projects. Include real-world examples. Add exercises after each concept."></textarea>
            </div>
          </div>

          <!-- RIGHT: Preview -->
          <div class="col-12 col-lg-7">
            <div id="aiIdleState" class="text-center py-5"
                 style="border:2px dashed var(--border-color);border-radius:14px;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:380px">
              <i class="bi bi-stars" style="font-size:3rem;color:#e3a008;opacity:.4"></i>
              <h6 class="mt-3 fw-semibold text-muted">Course outline will appear here</h6>
              <p class="text-muted small">Fill in the details and click Generate</p>
            </div>

            <!-- Generating spinner -->
            <div id="aiLoadingState" class="d-none text-center py-5"
                 style="min-height:380px;display:flex;flex-direction:column;align-items:center;justify-content:center">
              <div class="spinner-border text-warning mb-3" style="width:3rem;height:3rem"></div>
              <h6 class="fw-semibold">Generating your course…</h6>
              <p class="text-muted small" id="aiLoadingMsg">AI is crafting sections, lessons, and quiz questions</p>
              <div class="mt-3 d-flex gap-2 flex-wrap justify-content-center" id="aiProgressSteps">
                <span class="badge bg-secondary" id="step1">📋 Outline</span>
                <span class="badge bg-secondary" id="step2">📝 Content</span>
                <span class="badge bg-secondary" id="step3">❓ Quizzes</span>
                <span class="badge bg-secondary" id="step4">✅ Review</span>
              </div>
            </div>

            <!-- Result preview -->
            <div id="aiResult" class="d-none" style="max-height:520px;overflow-y:auto">
              <!-- Course header -->
              <div class="p-3 rounded-3 mb-3" style="background:linear-gradient(135deg,#6366f1,#1a56db);color:#fff">
                <div class="fw-bold" style="font-size:16px" id="previewTitle"></div>
                <div style="font-size:13px;opacity:.85;margin-top:4px" id="previewDesc"></div>
                <div class="d-flex gap-3 mt-2 flex-wrap" id="previewMeta" style="font-size:12px;opacity:.8"></div>
              </div>
              <!-- Sections accordion -->
              <div id="previewSections"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <div class="flex-grow-1 text-muted" style="font-size:12.5px" id="aiFooterMsg"></div>
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning" id="aiGenerateBtn">
          <i class="bi bi-stars me-1"></i> Generate Course
        </button>
        <button class="btn btn-success d-none px-4" id="aiSaveBtn">
          <i class="bi bi-cloud-upload me-1"></i> Save as Draft & Edit
        </button>
      </div>
    </div>
  </div>
</div>

<!-- AI button trigger -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Add Generate with AI button to the card header
  const header = document.querySelector('.card-header .d-flex') || document.querySelector('.card-header');
  if (header) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-warning ms-auto';
    btn.setAttribute('data-bs-toggle','modal');
    btn.setAttribute('data-bs-target','#aiModal');
    btn.innerHTML = '<i class="bi bi-stars me-1"></i> Generate with AI';
    // Insert at start of actions area
    const actionsEl = header.querySelector('.d-flex') || header;
    actionsEl.insertBefore(btn, actionsEl.firstChild);
  }
});

const CSRF = document.querySelector('input[name="csrf_token"]')?.value || '';
const BASE = '<?= rtrim(APP_URL, '/') ?>';
let aiGenerated = null;

// ── Content type checkbox visual feedback ────────────────────────────────────
document.querySelectorAll('.ai-content-type').forEach(cb => {
  cb.addEventListener('change', function() {
    const label = document.getElementById('typeLabel_' + this.value);
    if (label) {
      label.style.borderColor   = this.checked ? '#6366f1' : 'transparent';
      label.style.background    = this.checked ? '#ebf2ff' : '';
    }
  });
  // Init state
  const label = document.getElementById('typeLabel_' + cb.value);
  if (label && cb.checked) { label.style.borderColor='#6366f1'; label.style.background='#ebf2ff'; }
});

// ── Generate ─────────────────────────────────────────────────────────────────
document.getElementById('aiGenerateBtn')?.addEventListener('click', async function () {
  const topic = document.getElementById('aiTopic').value.trim();
  if (!topic) { LMS.toast('error','Please enter a course topic.'); return; }

  const types = [...document.querySelectorAll('.ai-content-type:checked')].map(i => i.value);
  if (!types.length) { LMS.toast('error','Select at least one content type.'); return; }

  // Show loading
  document.getElementById('aiIdleState').classList.add('d-none');
  document.getElementById('aiResult').classList.add('d-none');
  document.getElementById('aiLoadingState').classList.remove('d-none');
  document.getElementById('aiSaveBtn').classList.add('d-none');
  document.getElementById('aiFooterMsg').textContent = '';
  this.disabled = true;

  // Animate steps
  const steps = ['step1','step2','step3','step4'];
  let si = 0;
  const stepTimer = setInterval(() => {
    if (si > 0) document.getElementById(steps[si-1]).className = 'badge bg-success';
    if (si < steps.length) { document.getElementById(steps[si]).className = 'badge bg-warning text-dark'; si++; }
  }, 2000);

  // Build form data
  const body = new URLSearchParams({
    csrf_token:   CSRF,
    topic,
    level:        document.getElementById('aiLevel').value,
    language:     document.getElementById('aiLanguage').value,
    num_sections: document.getElementById('aiSections').value,
    num_lessons:  document.getElementById('aiLessons').value,
    extra_instructions: document.getElementById('aiExtra').value,
  });
  types.forEach(t => body.append('content_types[]', t));

  try {
    const res  = await fetch(BASE + '/admin/courses/ai-generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    body.toString(),
    });
    const data = await res.json();

    clearInterval(stepTimer);
    steps.forEach(id => document.getElementById(id).className = 'badge bg-success');

    if (!data.success) {
      document.getElementById('aiLoadingState').classList.add('d-none');
      document.getElementById('aiIdleState').classList.remove('d-none');
      LMS.toast('error', data.message || 'Generation failed.');
      return;
    }

    aiGenerated = data.course;
    renderPreview(data.course);

  } catch (e) {
    clearInterval(stepTimer);
    LMS.toast('error', 'Request failed: ' + e.message);
    document.getElementById('aiLoadingState').classList.add('d-none');
    document.getElementById('aiIdleState').classList.remove('d-none');
  } finally {
    this.disabled = false;
  }
});

// ── Render preview ────────────────────────────────────────────────────────────
function renderPreview(course) {
  document.getElementById('aiLoadingState').classList.add('d-none');
  document.getElementById('aiResult').classList.remove('d-none');
  document.getElementById('aiSaveBtn').classList.remove('d-none');

  const typeIcons  = { text:'bi-file-text', video:'bi-play-circle-fill', quiz:'bi-patch-question-fill', document:'bi-file-pdf', scorm:'bi-box-seam' };
  const typeColors = { text:'secondary', video:'danger', quiz:'success', document:'warning', scorm:'info' };

  document.getElementById('previewTitle').textContent = course.title;
  document.getElementById('previewDesc').textContent  = course.short_description;

  const totalLessons = (course.sections||[]).reduce((a,s)=>a+(s.lessons||[]).length,0);
  const quizCount    = (course.sections||[]).reduce((a,s)=>a+(s.lessons||[]).filter(l=>l.type==='quiz').length,0);
  const qCount       = (course.sections||[]).reduce((a,s)=>a+(s.lessons||[]).reduce((b,l)=>b+(l.questions||[]).length,0),0);
  document.getElementById('previewMeta').innerHTML =
    `<span>📚 ${(course.sections||[]).length} sections</span>
     <span>📖 ${totalLessons} lessons</span>
     <span>❓ ${quizCount} quizzes · ${qCount} questions</span>
     <span>⏱ ${course.duration_hours}h</span>
     <span>🏆 ${course.grade_points} pts</span>`;

  let html = '';
  (course.sections||[]).forEach((sec, si) => {
    const lesHtml = (sec.lessons||[]).map(les => {
      const icon  = typeIcons[les.type]  || 'bi-circle';
      const color = typeColors[les.type] || 'secondary';
      let extra = '';
      if (les.type === 'quiz' && les.questions?.length) {
        extra = `<div class="mt-1 ms-4 ps-1" style="font-size:11.5px;color:var(--text-muted)">
          <i class="bi bi-list-check me-1"></i>${les.questions.length} questions
          ${les.questions.slice(0,1).map(q=>`<div class="text-muted" style="font-size:11px">e.g. "${q.question}"</div>`).join('')}
        </div>`;
      }
      return `<div class="d-flex align-items-start gap-2 py-2 border-bottom">
        <i class="bi ${icon} text-${color} mt-1" style="font-size:14px;flex-shrink:0"></i>
        <div>
          <div style="font-size:13.5px;font-weight:600">${les.title}</div>
          <div style="font-size:12px;color:var(--text-muted)">${les.description||''}</div>
          ${extra}
        </div>
        <span class="ms-auto badge bg-${color}-subtle text-${color}" style="font-size:10.5px;white-space:nowrap">${les.type}</span>
      </div>`;
    }).join('');

    html += `<div class="card lms-card mb-2">
      <div class="card-header py-2 px-3 d-flex align-items-center gap-2" style="cursor:pointer" onclick="this.nextSibling.style.display=this.nextSibling.style.display==='none'?'':'none'">
        <span class="badge bg-primary" style="width:22px;height:22px;border-radius:50%;font-size:11px;display:flex;align-items:center;justify-content:center">${si+1}</span>
        <span class="fw-semibold" style="font-size:13.5px">${sec.title}</span>
        <span class="ms-auto text-muted" style="font-size:12px">${(sec.lessons||[]).length} lessons</span>
        <i class="bi bi-chevron-down ms-1" style="font-size:12px"></i>
      </div>
      <div class="card-body p-3">${lesHtml}</div>
    </div>`;
  });

  document.getElementById('previewSections').innerHTML = html;

  const typeBreakdown = {};
  (course.sections||[]).forEach(s=>(s.lessons||[]).forEach(l=>{ typeBreakdown[l.type]=(typeBreakdown[l.type]||0)+1; }));
  document.getElementById('aiFooterMsg').textContent =
    'Generated: ' + Object.entries(typeBreakdown).map(([t,n])=>n+' '+t).join(' · ');
}

// ── Save ─────────────────────────────────────────────────────────────────────
document.getElementById('aiSaveBtn')?.addEventListener('click', async function() {
  if (!aiGenerated) return;
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

  try {
    const res  = await fetch(BASE + '/admin/courses/ai-save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&course_data=' + encodeURIComponent(JSON.stringify(aiGenerated)),
    });
    const data = await res.json();
    if (data.success) {
      LMS.toast('success', 'Course saved! Opening builder…');
      setTimeout(() => window.location.href = BASE + '/admin/courses/' + data.uuid + '/edit', 800);
    } else {
      LMS.toast('error', data.message || 'Save failed.');
      this.disabled = false;
      this.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Save as Draft & Edit';
    }
  } catch (e) {
    LMS.toast('error', 'Request failed: ' + e.message);
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Save as Draft & Edit';
  }
});
</script>

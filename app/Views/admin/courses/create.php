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
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-stars text-warning me-2"></i>Generate Course with AI
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4" id="aiModalBody">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-semibold">Topic / Subject <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="aiTopic" placeholder="e.g. Introduction to Python Programming">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Level</label>
            <select class="form-select" id="aiLevel">
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Sections</label>
            <input type="number" class="form-control" id="aiSections" value="5" min="2" max="15">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Lessons per Section</label>
            <input type="number" class="form-control" id="aiLessons" value="3" min="1" max="8">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Language</label>
            <input type="text" class="form-control" id="aiLanguage" value="English">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Extra Instructions <small class="text-muted fw-normal">(optional)</small></label>
            <textarea class="form-control" id="aiExtra" rows="2" placeholder="e.g. Include practical projects, focus on web development…"></textarea>
          </div>
        </div>

        <!-- Result preview -->
        <div id="aiResult" class="mt-4 d-none">
          <hr>
          <h6 class="fw-semibold mb-3"><i class="bi bi-check-circle text-success me-1"></i>Generated Course Outline</h6>
          <div id="aiPreview" class="p-3 rounded" style="background:var(--content-bg);font-size:13.5px;max-height:320px;overflow-y:auto"></div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning" id="aiGenerateBtn">
          <i class="bi bi-stars me-1"></i> Generate Course
        </button>
        <button class="btn btn-success d-none" id="aiSaveBtn">
          <i class="bi bi-cloud-upload me-1"></i> Save as Draft & Edit
        </button>
      </div>
    </div>
  </div>
</div>

<!-- AI Generate button in card header -->
<script>
// Add AI button to page
document.addEventListener('DOMContentLoaded', function() {
  const header = document.querySelector('.card-header');
  if (header) {
    const btn = document.createElement('button');
    btn.className = 'btn btn-sm btn-warning ms-2';
    btn.dataset.bsToggle = 'modal';
    btn.dataset.bsTarget = '#aiModal';
    btn.innerHTML = '<i class="bi bi-stars me-1"></i> Generate with AI';
    header.appendChild(btn);
  }
});

const CSRF = document.querySelector('input[name="csrf_token"]')?.value || '';
const BASE = '<?= rtrim(APP_URL, '/') ?>';
let aiGenerated = null;

document.getElementById('aiGenerateBtn')?.addEventListener('click', function() {
  const topic = document.getElementById('aiTopic').value.trim();
  if (!topic) { alert('Please enter a topic.'); return; }

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating…';
  document.getElementById('aiResult').classList.add('d-none');
  document.getElementById('aiSaveBtn').classList.add('d-none');

  fetch(BASE+'/admin/courses/ai-generate', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token='+encodeURIComponent(CSRF)
        + '&topic='+encodeURIComponent(topic)
        + '&level='+encodeURIComponent(document.getElementById('aiLevel').value)
        + '&num_sections='+encodeURIComponent(document.getElementById('aiSections').value)
        + '&num_lessons='+encodeURIComponent(document.getElementById('aiLessons').value)
        + '&language='+encodeURIComponent(document.getElementById('aiLanguage').value)
        + '&extra_instructions='+encodeURIComponent(document.getElementById('aiExtra').value),
  }).then(r=>r.json()).then(d => {
    if (d.success) {
      aiGenerated = d.course;
      let html = '<strong style="font-size:15px">'+aiGenerated.title+'</strong><br>';
      html += '<em style="color:var(--text-muted)">'+aiGenerated.short_description+'</em><br><br>';
      (aiGenerated.sections||[]).forEach((s,si)=>{
        html += '<div style="margin-bottom:8px"><strong>'+(si+1)+'. '+s.title+'</strong><br>';
        (s.lessons||[]).forEach(l=>{
          html += '<span style="margin-left:16px;color:var(--text-muted)">• '+l.title+' ('+l.type+')</span><br>';
        });
        html += '</div>';
      });
      document.getElementById('aiPreview').innerHTML = html;
      document.getElementById('aiResult').classList.remove('d-none');
      document.getElementById('aiSaveBtn').classList.remove('d-none');
    } else {
      alert('Error: ' + d.message);
    }
  }).catch(()=>alert('Request failed. Check AI settings.')).finally(()=>{
    this.disabled=false;
    this.innerHTML='<i class="bi bi-stars me-1"></i> Generate Course';
  });
});

document.getElementById('aiSaveBtn')?.addEventListener('click', function() {
  if (!aiGenerated) return;
  this.disabled=true;
  this.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Saving…';
  fetch(BASE+'/admin/courses/ai-save', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token='+encodeURIComponent(CSRF)+'&course_data='+encodeURIComponent(JSON.stringify(aiGenerated)),
  }).then(r=>r.json()).then(d=>{
    if(d.success) window.location.href=BASE+'/admin/courses/'+d.uuid+'/edit';
    else { alert('Save failed: '+d.message); this.disabled=false; this.innerHTML='<i class="bi bi-cloud-upload me-1"></i> Save as Draft & Edit'; }
  });
});
</script>

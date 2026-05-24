<?php
use App\Core\View;
$e    = fn(mixed $v): string => View::e($v);
$url  = fn(string $p = ''): string => View::url($p);
$asset= fn(string $p): string => View::asset($p);

// $course is set when editing; empty array when creating
$c = $course ?? [];
$get = fn(string $k, mixed $d = '') => $c[$k] ?? $d;
?>

<!-- Bootstrap tabs for form sections -->
<ul class="nav nav-tabs mb-4" id="courseFormTabs">
  <li class="nav-item">
    <a class="nav-link active" data-bs-toggle="tab" href="#tabBasic">
      <i class="bi bi-info-circle me-1"></i> Basic Info
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-bs-toggle="tab" href="#tabSettings">
      <i class="bi bi-sliders me-1"></i> Settings
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-bs-toggle="tab" href="#tabMedia">
      <i class="bi bi-image me-1"></i> Media
    </a>
  </li>
</ul>

<div class="tab-content" id="courseFormTabContent">

  <!-- ── BASIC INFO ── -->
  <div class="tab-pane fade show active" id="tabBasic">
    <div class="row g-3">

      <div class="col-12">
        <label class="form-label fw-semibold">Course Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-lg" name="title"
               value="<?= $e($get('title')) ?>" placeholder="e.g. Complete Web Development Bootcamp"
               required maxlength="255">
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Short Description</label>
        <input type="text" class="form-control" name="short_description"
               value="<?= $e($get('short_description')) ?>"
               placeholder="One-line summary (shown on course cards)" maxlength="500">
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Full Description</label>
        <!-- Quill editor — border/radius applied via admin.css .ql-toolbar + .ql-container -->
        <div id="descriptionEditor"><?= $get('description') ?></div>
        <textarea name="description" id="descriptionHidden" class="d-none"><?= $e($get('description')) ?></textarea>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Category</label>
        <select class="form-select" name="category_id" id="categorySelect">
          <option value="">— None —</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (int)$get('category_id') === (int)$cat['id'] ? 'selected' : '' ?>>
            <?= $e($cat['label']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Level</label>
        <select class="form-select" name="level">
          <?php foreach (['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $get('level','beginner') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Language</label>
        <input type="text" class="form-control" name="language"
               value="<?= $e($get('language','English')) ?>" placeholder="English" maxlength="50">
      </div>

    </div>
  </div><!-- /#tabBasic -->

  <!-- ── SETTINGS ── -->
  <div class="tab-pane fade" id="tabSettings">
    <div class="row g-3">

      <div class="col-md-4">
        <label class="form-label fw-semibold">Status</label>
        <select class="form-select" name="status">
          <?php foreach (['draft'=>'Draft','published'=>'Published','archived'=>'Archived'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $get('status','draft') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Visibility</label>
        <select class="form-select" name="visibility" id="visibilitySelect">
          <?php foreach (['public'=>'Public','private'=>'Private','password'=>'Password Protected'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $get('visibility','public') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4" id="passwordWrap" style="<?= $get('visibility') === 'password' ? '' : 'display:none' ?>">
        <label class="form-label fw-semibold">Course Password</label>
        <input type="text" class="form-control" name="course_password"
               value="<?= $e($get('password')) ?>" placeholder="Password for access">
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold">Pass % <small class="text-muted fw-normal">(quizzes)</small></label>
        <input type="number" class="form-control" name="pass_percentage"
               value="<?= $e($get('pass_percentage', 80)) ?>" min="1" max="100">
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold">Grade Points</label>
        <input type="number" class="form-control" name="grade_points"
               value="<?= $e($get('grade_points', 0)) ?>" min="0">
        <div class="form-text">Awarded on completion</div>
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold">Duration (hours)</label>
        <input type="number" class="form-control" name="duration_hours" step="0.5"
               value="<?= $e($get('duration_hours')) ?>" placeholder="e.g. 8.5">
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold">End Date <small class="text-muted fw-normal">(optional)</small></label>
        <input type="date" class="form-control" name="end_date"
               value="<?= $e($get('end_date')) ?>">
        <div class="form-text">Leave blank = no deadline</div>
      </div>

      <!-- Toggles row -->
      <div class="col-12">
        <div class="row g-3 mt-1">
          <?php
          $toggles = [
            ['certificate_enabled', 'Certificate Enabled', 1],
            ['forum_enabled',       'Enable Forum',        0],
            ['forum_enrolled_only', 'Forum: Enrolled Only',1],
            ['drip_enabled',        'Drip Content',        0],
            ['is_rtl',              'RTL Layout',          0],
          ];
          foreach ($toggles as [$name, $label, $default]):
            $checked = isset($c['id'])
              ? (bool)(int)($c[$name] ?? $default)
              : (bool)$default;
          ?>
          <div class="col-md-4 col-lg-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="<?= $name ?>"
                     id="toggle_<?= $name ?>" value="1" <?= $checked ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="toggle_<?= $name ?>">
                <?= $e($label) ?>
              </label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div><!-- /#tabSettings -->

  <!-- ── MEDIA ── -->
  <div class="tab-pane fade" id="tabMedia">
    <div class="row g-3">

      <div class="col-md-6">
        <label class="form-label fw-semibold">Thumbnail</label>
        <?php if (!empty($c['thumbnail'])): ?>
          <div class="mb-2">
            <img src="<?= $e(APP_URL . '/storage/uploads/' . $c['thumbnail']) ?>"
                 alt="Thumbnail"
                 style="max-width:200px;border-radius:var(--radius);border:1px solid var(--border-color)">
          </div>
        <?php endif; ?>
        <input type="file" class="form-control" name="thumbnail" accept="image/*">
        <div class="form-text">Recommended: 1280×720 (16:9). Max 5MB.</div>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Preview Video URL <small class="text-muted fw-normal">(YouTube / Vimeo)</small></label>
        <input type="url" class="form-control" name="preview_video"
               value="<?= $e($get('preview_video')) ?>"
               placeholder="https://youtube.com/watch?v=…">
        <div class="form-text">Shown to non-enrolled users as a course teaser.</div>
      </div>

    </div>
  </div><!-- /#tabMedia -->

</div><!-- /.tab-content -->

<script>
// Visibility → password field
document.getElementById('visibilitySelect')?.addEventListener('change', function () {
  document.getElementById('passwordWrap').style.display =
    this.value === 'password' ? '' : 'none';
});

// ── Quill — Course Description ─────────────────────────────────────────────────
(function () {
  const editorEl  = document.getElementById('descriptionEditor');
  const hiddenEl  = document.getElementById('descriptionHidden');
  if (!editorEl || !hiddenEl) return;

  let quillInstance = null;

  function mountQuill() {
    if (quillInstance) return; // already mounted
    if (typeof Quill === 'undefined') return;

    quillInstance = new Quill('#descriptionEditor', {
      theme: 'snow',
      placeholder: 'Describe what students will learn…',
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

    // Sync on every keystroke
    quillInstance.on('text-change', function () {
      hiddenEl.value = quillInstance.root.innerHTML;
    });

    // Also sync on form submit (safety net)
    editorEl.closest('form')?.addEventListener('submit', function () {
      hiddenEl.value = quillInstance.root.innerHTML;
    }, { once: false });
  }

  function loadQuillAndMount() {
    if (typeof Quill !== 'undefined') {
      mountQuill();
      return;
    }

    // Load CSS
    if (!document.querySelector('link[href*="quill"]')) {
      const css = document.createElement('link');
      css.rel  = 'stylesheet';
      css.href = 'https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css';
      document.head.appendChild(css);
    }

    // Load JS
    const script = document.createElement('script');
    script.src   = 'https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js';
    script.onload = function () {
      // Small delay so the tab-pane is fully visible before Quill measures height
      setTimeout(mountQuill, 50);
    };
    document.head.appendChild(script);
  }

  // ── Strategy: init when the Basic Info tab is shown ───────────────────────
  // The editor sits in #tabBasic which is the default active tab on page load.
  // Bootstrap may not have finished rendering at inline-script time, so we
  // use both an immediate attempt and the shown.bs.tab event as a fallback.

  // Attempt immediately (works when tab is already visible)
  loadQuillAndMount();

  // Re-attempt whenever the Basic Info tab is shown (e.g. user clicked away then back)
  document.querySelector('[href="#tabBasic"], [data-bs-target="#tabBasic"]')
    ?.addEventListener('shown.bs.tab', function () {
      if (!quillInstance) loadQuillAndMount();
    });

  // Also init on first shown event of the tab content itself
  document.getElementById('tabBasic')?.addEventListener('shown.bs.tab', function () {
    if (!quillInstance) loadQuillAndMount();
  });

  // Final safety net: try after 800ms (DOM + Bootstrap fully settled)
  setTimeout(function () {
    if (!quillInstance) loadQuillAndMount();
  }, 800);
})();
</script>

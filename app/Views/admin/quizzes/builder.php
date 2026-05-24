<?php
use App\Core\View;
use App\Services\GradingService;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$typeLabels = [
    'single'     => ['label'=>'Single Choice',   'icon'=>'bi-record-circle',   'color'=>'primary'],
    'multiple'   => ['label'=>'Multiple Choice',  'icon'=>'bi-check2-square',   'color'=>'success'],
    'true_false' => ['label'=>'True / False',     'icon'=>'bi-toggles',         'color'=>'warning'],
    'fill_blank' => ['label'=>'Fill in the Blank','icon'=>'bi-input-cursor-text','color'=>'info'],
];
?>

<!-- Stats bar -->
<?php if (!empty($stats['total_attempts'])): ?>
<div class="row g-3 mb-4">
  <?php
  $statItems = [
    ['Attempts',  $stats['total_attempts'], 'bi-people',      'primary'],
    ['Avg Score', ($stats['avg_score']??0).'%', 'bi-bar-chart', 'info'],
    ['Pass Rate', $stats['total_attempts']
        ? round(($stats['passes']/$stats['total_attempts'])*100).'%' : '—', 'bi-check-circle','success'],
    ['Best Score',($stats['best_score']??0).'%','bi-trophy','warning'],
  ];
  foreach ($statItems as [$lbl,$val,$icon,$color]):
  ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon text-<?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $e($val) ?></div>
        <div class="stat-label"><?= $e($lbl) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-4">

  <!-- Left: Quiz settings -->
  <div class="col-12 col-lg-4">
    <div class="card lms-card mb-4">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-sliders me-2"></i> Quiz Settings</h5>
      </div>
      <div class="card-body p-4">

        <div class="mb-3">
          <label class="form-label fw-semibold">Quiz Title</label>
          <input type="text" class="form-control" id="quizTitle"
                 value="<?= $e($quiz['title']) ?>" maxlength="255">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Description</label>
          <textarea class="form-control" id="quizDesc" rows="2"><?= $e($quiz['description'] ?? '') ?></textarea>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Pass % <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="quizPass"
                   value="<?= $e($quiz['pass_percentage']) ?>" min="1" max="100">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Max Attempts</label>
            <input type="number" class="form-control" id="quizMaxAttempts"
                   value="<?= $e($quiz['max_attempts']) ?>" min="1" max="99">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Time Limit (seconds)</label>
          <input type="number" class="form-control" id="quizTimeLimit"
                 value="<?= $e($quiz['time_limit_sec'] ?? '') ?>"
                 placeholder="Leave blank = no limit">
          <div class="form-text">e.g. 1800 = 30 minutes</div>
        </div>

        <div class="mb-2">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="quizShuffle"
                   <?= $quiz['shuffle_questions'] ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="quizShuffle">Shuffle Questions</label>
          </div>
        </div>
        <div class="mb-2">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="quizShuffleOpts"
                   <?= $quiz['shuffle_options'] ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="quizShuffleOpts">Shuffle Options</label>
          </div>
        </div>
        <div class="mb-4">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="quizShowAnswers"
                   <?= $quiz['show_answers_after'] ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="quizShowAnswers">Show Answers After Submission</label>
          </div>
        </div>

        <button class="btn btn-primary w-100" id="saveSettingsBtn">
          <i class="bi bi-check-circle me-1"></i> Save Settings
        </button>

      </div>
    </div>

    <!-- Add Question panel -->
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i> Add Question</h5>
      </div>
      <div class="card-body p-3">
        <div class="d-grid gap-2">
          <?php foreach ($typeLabels as $type => $meta): ?>
          <button class="btn btn-outline-<?= $meta['color'] ?> text-start btn-add-question"
                  data-type="<?= $type ?>">
            <i class="bi <?= $meta['icon'] ?> me-2"></i><?= $meta['label'] ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Questions list -->
  <div class="col-12 col-lg-8">
    <div class="card lms-card">
      <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
          <i class="bi bi-list-ol me-2"></i> Questions
          <span class="badge bg-secondary ms-1" id="questionCount"><?= count($quiz['questions']) ?></span>
        </h5>
        <div class="d-flex gap-2">
          <a href="<?= $url('admin/quizzes/' . $quiz['id'] . '/preview') ?>"
             class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-eye me-1"></i> Preview Quiz
          </a>
          <a href="<?= $url('admin/courses/' . $courseUuid . '/edit') ?>"
             class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Course
          </a>
        </div>
      </div>

      <?php if (empty($quiz['questions'])): ?>
      <div class="card-body text-center py-5" id="emptyQuestionsMsg">
        <i class="bi bi-patch-question" style="font-size:3rem;color:var(--border-color)"></i>
        <h6 class="mt-3 text-muted">No questions yet</h6>
        <p class="text-muted small">Add a question type from the left panel to get started.</p>
      </div>
      <?php endif; ?>

      <div id="questionsList" class="p-3">
        <?php foreach ($quiz['questions'] as $qi => $q): ?>
        <?php
          $tmeta = $typeLabels[$q['type']] ?? ['label'=>$q['type'],'icon'=>'bi-question','color'=>'secondary'];
        ?>
        <div class="question-block mb-3 border rounded" data-question-id="<?= $q['id'] ?>"
             id="qblock-<?= $q['id'] ?>">
          <!-- Question header -->
          <div class="question-header d-flex align-items-center gap-2 p-3"
               style="background:var(--content-bg);border-radius:var(--radius) var(--radius) 0 0;cursor:pointer"
               onclick="toggleQuestion(<?= $q['id'] ?>)">
            <span class="drag-handle q-drag-handle" title="Drag to reorder">
              <i class="bi bi-grip-vertical text-muted"></i>
            </span>
            <span class="badge bg-<?= $tmeta['color'] ?>-subtle text-<?= $tmeta['color'] ?>">
              <i class="bi <?= $tmeta['icon'] ?> me-1"></i><?= $tmeta['label'] ?>
            </span>
            <span class="fw-semibold flex-grow-1 question-title-preview" style="font-size:13.5px">
              <?= $e($q['question']) ?>
            </span>
            <span class="badge bg-secondary" style="font-size:11px"><?= $e($q['points']) ?> pt<?= $q['points'] != 1 ? 's' : '' ?></span>
            <button class="btn btn-xs btn-outline-danger btn-delete-question ms-1"
                    data-id="<?= $q['id'] ?>"
                    onclick="event.stopPropagation()">
              <i class="bi bi-trash3"></i>
            </button>
            <i class="bi bi-chevron-down question-chevron" id="chevron-<?= $q['id'] ?>"></i>
          </div>

          <!-- Question editor (collapsed by default after first) -->
          <div class="question-body p-3 <?= $qi > 0 ? 'd-none' : '' ?>" id="qbody-<?= $q['id'] ?>">
            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label class="form-label fw-semibold">Question Text <span class="text-danger">*</span></label>
                <textarea class="form-control q-question-text" rows="2"
                          id="qtext-<?= $q['id'] ?>"><?= $e($q['question']) ?></textarea>
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">Points</label>
                <input type="number" class="form-control q-points"
                       id="qpts-<?= $q['id'] ?>" value="<?= $e($q['points']) ?>" min="1">
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">Type</label>
                <select class="form-select q-type" id="qtype-<?= $q['id'] ?>" disabled>
                  <?php foreach ($typeLabels as $tv => $tl): ?>
                  <option value="<?= $tv ?>" <?= $q['type'] === $tv ? 'selected' : '' ?>><?= $tl['label'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Options builder -->
            <div class="options-builder mb-3" id="options-<?= $q['id'] ?>">
              <label class="form-label fw-semibold">
                <?= $q['type'] === 'fill_blank' ? 'Accepted Answers' : 'Options' ?>
                <?php if ($q['type'] === 'single' || $q['type'] === 'true_false'): ?>
                  <small class="text-muted fw-normal">(select correct answer)</small>
                <?php elseif ($q['type'] === 'multiple'): ?>
                  <small class="text-muted fw-normal">(check all correct)</small>
                <?php endif; ?>
              </label>

              <div class="options-list" id="opt-list-<?= $q['id'] ?>">
                <?php foreach ($q['options'] as $oi => $opt): ?>
                <div class="option-row d-flex align-items-center gap-2 mb-2" data-opt-index="<?= $oi ?>">
                  <?php if ($q['type'] === 'single' || $q['type'] === 'true_false'): ?>
                    <input type="radio" name="correct_radio_<?= $q['id'] ?>"
                           class="form-check-input mt-0 q-correct-radio"
                           value="<?= $oi ?>" <?= $opt['is_correct'] ? 'checked' : '' ?>
                           style="width:16px;height:16px">
                  <?php elseif ($q['type'] === 'multiple'): ?>
                    <input type="checkbox" class="form-check-input mt-0 q-correct-check"
                           data-index="<?= $oi ?>" <?= $opt['is_correct'] ? 'checked' : '' ?>
                           style="width:16px;height:16px">
                  <?php else: ?>
                    <input type="checkbox" class="form-check-input mt-0 q-correct-check"
                           data-index="<?= $oi ?>" checked style="width:16px;height:16px" disabled>
                  <?php endif; ?>
                  <input type="text" class="form-control form-control-sm q-opt-text"
                         value="<?= $e($opt['option_text']) ?>"
                         <?= ($q['type'] === 'true_false') ? 'readonly' : '' ?>
                         placeholder="Option text">
                  <?php if ($q['type'] !== 'true_false'): ?>
                  <button type="button" class="btn btn-xs btn-outline-danger btn-remove-option"
                          onclick="removeOption(this)" <?= count($q['options']) <= 1 ? 'disabled' : '' ?>>
                    <i class="bi bi-x"></i>
                  </button>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>

              <?php if ($q['type'] !== 'true_false'): ?>
              <button type="button" class="btn btn-sm btn-outline-secondary mt-1 btn-add-option"
                      data-qid="<?= $q['id'] ?>" data-type="<?= $q['type'] ?>">
                <i class="bi bi-plus me-1"></i>
                <?= $q['type'] === 'fill_blank' ? 'Add Accepted Answer' : 'Add Option' ?>
              </button>
              <?php endif; ?>
            </div>

            <!-- Explanation -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Explanation <small class="text-muted fw-normal">(shown after submission)</small>
              </label>
              <textarea class="form-control form-control-sm q-explanation" rows="2"
                        id="qexpl-<?= $q['id'] ?>"><?= $e($q['explanation'] ?? '') ?></textarea>
            </div>

            <button class="btn btn-primary btn-sm btn-save-question" data-id="<?= $q['id'] ?>">
              <i class="bi bi-check-circle me-1"></i> Save Question
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div><!-- /#questionsList -->

    </div><!-- /.card -->
  </div>

</div><!-- /.row -->

<style>
.question-block { transition: box-shadow .2s; }
.question-block:hover { box-shadow: var(--shadow-md); }
.question-chevron { transition: transform .2s; font-size:14px; }
.question-chevron.open { transform: rotate(180deg); }
.btn-xs { padding:3px 7px;font-size:12px; }
.drag-handle { cursor:grab; }
.drag-handle:active { cursor:grabbing; }
.sortable-ghost { opacity:.4; }
.bg-primary-subtle{background:#ebf2ff!important}
.bg-success-subtle{background:#d1fae5!important}
.bg-warning-subtle{background:#fef9c3!important}
.bg-info-subtle{background:#e0f7fa!important}
</style>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<input type="hidden" id="quizId"   value="<?= $e($quiz['id']) ?>">

<script>
const CSRF   = document.getElementById('csrfToken').value;
const BASE   = '<?= rtrim(APP_URL, '/') ?>';
const QUIZ_ID = document.getElementById('quizId').value;

// ── Toggle question body ──────────────────────────────────────────────────────
function toggleQuestion(id) {
  const body    = document.getElementById('qbody-' + id);
  const chevron = document.getElementById('chevron-' + id);
  body.classList.toggle('d-none');
  chevron.classList.toggle('open');
}

// ── Save quiz settings ────────────────────────────────────────────────────────
document.getElementById('saveSettingsBtn').addEventListener('click', function () {
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

  const body = new URLSearchParams({
    csrf_token:          CSRF,
    title:               document.getElementById('quizTitle').value,
    description:         document.getElementById('quizDesc').value,
    pass_percentage:     document.getElementById('quizPass').value,
    max_attempts:        document.getElementById('quizMaxAttempts').value,
    time_limit_sec:      document.getElementById('quizTimeLimit').value,
    shuffle_questions:   document.getElementById('quizShuffle').checked ? '1' : '0',
    shuffle_options:     document.getElementById('quizShuffleOpts').checked ? '1' : '0',
    show_answers_after:  document.getElementById('quizShowAnswers').checked ? '1' : '0',
  });

  fetch(BASE + '/admin/quizzes/' + QUIZ_ID + '/settings', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString(),
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) LMS.toast('success', 'Quiz settings saved.');
    else LMS.toast('error', d.message);
  })
  .finally(() => {
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Settings';
  });
});

// ── Add question ──────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-add-question').forEach(btn => {
  btn.addEventListener('click', function () {
    const type = this.dataset.type;
    fetch(BASE + '/admin/quizzes/' + QUIZ_ID + '/questions', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF) + '&type=' + encodeURIComponent(type),
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        LMS.toast('success', 'Question added.');
        location.reload();
      } else LMS.toast('error', d.message);
    });
  });
});

// ── Save question ─────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-save-question').forEach(btn => {
  btn.addEventListener('click', function () {
    const id   = this.dataset.id;
    const type = document.getElementById('qtype-' + id).value;
    const fd   = new FormData();

    fd.append('csrf_token',  CSRF);
    fd.append('question',    document.getElementById('qtext-' + id).value);
    fd.append('explanation', document.getElementById('qexpl-' + id).value);
    fd.append('type',        type);
    fd.append('points',      document.getElementById('qpts-' + id).value);

    // Collect options
    const optList = document.getElementById('opt-list-' + id);
    const rows    = optList.querySelectorAll('.option-row');

    rows.forEach((row, i) => {
      const text = row.querySelector('.q-opt-text').value.trim();
      if (!text) return;
      fd.append('option_text[' + i + ']', text);

      if (type === 'single' || type === 'true_false') {
        const radio = optList.querySelector('.q-correct-radio:checked');
        fd.append('is_correct[radio]', radio ? radio.value : '0');
      } else {
        const cb = row.querySelector('.q-correct-check');
        if (cb && cb.checked) fd.append('is_correct[' + i + ']', '1');
      }
    });

    const saveBtn = this;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(BASE + '/admin/questions/' + id + '/save', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        LMS.toast('success', 'Question saved.');
        // Update preview title
        const preview = document.querySelector('#qblock-' + id + ' .question-title-preview');
        if (preview) preview.textContent = document.getElementById('qtext-' + id).value;
      } else LMS.toast('error', d.message);
    })
    .finally(() => {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Question';
    });
  });
});

// ── Delete question ───────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete-question').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id;
    LMS.confirm('Delete this question?', function () {
      fetch(BASE + '/admin/questions/' + id + '/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('qblock-' + id)?.remove();
          const cnt = document.getElementById('questionCount');
          cnt.textContent = Math.max(0, parseInt(cnt.textContent) - 1);
          LMS.toast('success', 'Question deleted.');
        } else LMS.toast('error', d.message);
      });
    });
  });
});

// ── Add option ────────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-add-option').forEach(btn => {
  btn.addEventListener('click', function () {
    const qid  = this.dataset.qid;
    const type = this.dataset.type;
    const list = document.getElementById('opt-list-' + qid);
    const idx  = list.querySelectorAll('.option-row').length;

    const row  = document.createElement('div');
    row.className = 'option-row d-flex align-items-center gap-2 mb-2';
    row.dataset.optIndex = idx;

    let inputHtml = '';
    if (type === 'multiple') {
      inputHtml = `<input type="checkbox" class="form-check-input mt-0 q-correct-check" data-index="${idx}" style="width:16px;height:16px">`;
    } else if (type === 'fill_blank') {
      inputHtml = `<input type="checkbox" class="form-check-input mt-0 q-correct-check" data-index="${idx}" checked style="width:16px;height:16px" disabled>`;
    } else {
      inputHtml = `<input type="radio" name="correct_radio_${qid}" class="form-check-input mt-0 q-correct-radio" value="${idx}" style="width:16px;height:16px">`;
    }

    row.innerHTML = inputHtml +
      `<input type="text" class="form-control form-control-sm q-opt-text" placeholder="Option text">` +
      `<button type="button" class="btn btn-xs btn-outline-danger btn-remove-option" onclick="removeOption(this)"><i class="bi bi-x"></i></button>`;

    list.appendChild(row);
  });
});

function removeOption(btn) {
  const row  = btn.closest('.option-row');
  const list = row.closest('.options-list');
  if (list.querySelectorAll('.option-row').length <= 1) return;
  row.remove();
}

// ── Drag reorder questions ────────────────────────────────────────────────────
(function () {
  const initSortable = () => {
    if (typeof Sortable === 'undefined') return;
    Sortable.create(document.getElementById('questionsList'), {
      handle: '.q-drag-handle',
      animation: 150,
      ghostClass: 'sortable-ghost',
      onEnd() {
        const ids = [...document.querySelectorAll('#questionsList .question-block')]
          .map(el => el.dataset.questionId);
        fetch(BASE + '/admin/quizzes/' + QUIZ_ID + '/reorder', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: 'csrf_token=' + encodeURIComponent(CSRF) + '&' +
                ids.map((id, i) => 'ids[' + i + ']=' + id).join('&'),
        });
      },
    });
  };

  if (typeof Sortable !== 'undefined') initSortable();
  else {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js';
    s.onload = initSortable;
    document.head.appendChild(s);
  }
})();
</script>

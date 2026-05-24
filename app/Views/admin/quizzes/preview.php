<?php
use App\Core\View;
use App\Services\GradingService;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$questions = $quiz['questions'];
if ($quiz['shuffle_questions']) shuffle($questions);
?>

<!-- Preview banner -->
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-eye-fill fs-5"></i>
  <div><strong>Admin Preview</strong> — This is how students see the quiz. Answers are not recorded.</div>
  <button onclick="history.back()" class="btn btn-sm btn-outline-dark ms-auto">
    <i class="bi bi-arrow-left me-1"></i> Back to Builder
  </button>
</div>

<div class="row justify-content-center">
  <div class="col-12 col-xl-8">

    <!-- Quiz header card -->
    <div class="card lms-card mb-4">
      <div class="card-body p-4">
        <h2 class="fw-bold mb-2"><?= $e($quiz['title']) ?></h2>
        <?php if ($quiz['description']): ?>
          <p class="text-muted mb-3"><?= $e($quiz['description']) ?></p>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-3" style="font-size:13.5px">
          <span><i class="bi bi-patch-question text-primary me-1"></i>
            <strong><?= count($questions) ?></strong> questions</span>
          <span><i class="bi bi-award text-success me-1"></i>
            Pass at <strong><?= $e($quiz['pass_percentage']) ?>%</strong></span>
          <span><i class="bi bi-arrow-repeat text-info me-1"></i>
            <strong><?= $e($quiz['max_attempts']) ?></strong> max attempts</span>
          <?php if ($quiz['time_limit_sec']): ?>
          <span class="text-warning fw-semibold">
            <i class="bi bi-clock me-1"></i>
            <strong><?= GradingService::formatTime((int)$quiz['time_limit_sec']) ?></strong> time limit
          </span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Timer (if time limit set) -->
    <?php if ($quiz['time_limit_sec']): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" id="timerBar">
      <i class="bi bi-clock-fill"></i>
      <span>Time remaining: <strong id="timerDisplay"><?= GradingService::formatTime((int)$quiz['time_limit_sec']) ?></strong></span>
    </div>
    <?php endif; ?>

    <!-- Questions -->
    <form id="previewForm">
      <?php foreach ($questions as $qi => $q): ?>
      <?php
        $opts = $q['options'];
        if ($quiz['shuffle_options'] && $q['type'] !== 'true_false') shuffle($opts);
      ?>
      <div class="card lms-card mb-3" id="q-card-<?= $q['id'] ?>">
        <div class="card-body p-4">
          <div class="d-flex gap-3 mb-3">
            <span class="quiz-q-num"><?= $qi + 1 ?></span>
            <div class="flex-grow-1">
              <div class="fw-semibold" style="font-size:15px"><?= $e($q['question']) ?></div>
              <div class="text-muted" style="font-size:12px">
                <?= $q['points'] ?> point<?= $q['points'] != 1 ? 's' : '' ?>
              </div>
            </div>
          </div>

          <!-- Options -->
          <?php if ($q['type'] === 'fill_blank'): ?>
            <input type="text" class="form-control" name="answers[<?= $q['id'] ?>]"
                   placeholder="Type your answer…">

          <?php elseif ($q['type'] === 'multiple'): ?>
            <?php foreach ($opts as $oi => $opt): ?>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox"
                     name="answers[<?= $q['id'] ?>][]"
                     value="<?= $opt['id'] ?>"
                     id="opt-<?= $q['id'] ?>-<?= $oi ?>">
              <label class="form-check-label" for="opt-<?= $q['id'] ?>-<?= $oi ?>">
                <?= $e($opt['option_text']) ?>
              </label>
            </div>
            <?php endforeach; ?>

          <?php else: /* single + true_false */ ?>
            <?php foreach ($opts as $oi => $opt): ?>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio"
                     name="answers[<?= $q['id'] ?>]"
                     value="<?= $opt['id'] ?>"
                     id="opt-<?= $q['id'] ?>-<?= $oi ?>">
              <label class="form-check-label" for="opt-<?= $q['id'] ?>-<?= $oi ?>">
                <?= $e($opt['option_text']) ?>
              </label>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </div>
      <?php endforeach; ?>

      <!-- Submit -->
      <div class="d-grid mt-4 mb-5">
        <button type="button" class="btn btn-primary btn-lg" id="submitPreviewBtn">
          <i class="bi bi-check2-circle me-2"></i> Submit Quiz (Preview)
        </button>
      </div>
    </form>

    <!-- Result panel (hidden until submit) -->
    <div id="resultPanel" class="card lms-card mb-5 d-none">
      <div class="card-body p-4 text-center">
        <div id="resultIcon" style="font-size:4rem;margin-bottom:12px"></div>
        <h3 id="resultTitle" class="fw-bold"></h3>
        <p id="resultScore" class="lead text-muted mb-4"></p>

        <?php if ($quiz['show_answers_after']): ?>
        <div id="answersReview" class="text-start mt-4"></div>
        <?php endif; ?>

        <button class="btn btn-outline-primary mt-3" onclick="location.reload()">
          <i class="bi bi-arrow-repeat me-1"></i> Retake (Preview)
        </button>
      </div>
    </div>

  </div>
</div>

<style>
.quiz-q-num {
  width:32px;height:32px;border-radius:50%;background:var(--primary);
  color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;
  font-size:14px;flex-shrink:0;
}
</style>

<script>
const PASS_PCT = <?= (int)$quiz['pass_percentage'] ?>;
<?php if ($quiz['time_limit_sec']): ?>
let timeLeft = <?= (int)$quiz['time_limit_sec'] ?>;
const timerEl = document.getElementById('timerDisplay');
const timerInterval = setInterval(() => {
  timeLeft--;
  const m = Math.floor(timeLeft / 60), s = timeLeft % 60;
  timerEl.textContent = m + ':' + String(s).padStart(2,'0');
  if (timeLeft <= 60) timerEl.style.color = '#e02424';
  if (timeLeft <= 0) { clearInterval(timerInterval); submitPreview(); }
}, 1000);
<?php endif; ?>

document.getElementById('submitPreviewBtn').addEventListener('click', submitPreview);

function submitPreview() {
  const form = document.getElementById('previewForm');
  // Gather answers from form
  const answers = {};
  form.querySelectorAll('[name^="answers"]').forEach(el => {
    const m = el.name.match(/answers\[(\d+)\](\[\])?/);
    if (!m) return;
    const qid = m[1];
    if (el.type === 'checkbox') {
      if (el.checked) {
        if (!answers[qid]) answers[qid] = [];
        answers[qid].push(el.value);
      }
    } else if (el.type === 'radio') {
      if (el.checked) answers[qid] = el.value;
    } else {
      answers[qid] = el.value;
    }
  });

  // Client-side grading for preview (admin only — no DB write)
  <?php
  $correctMap = [];
  foreach ($quiz['questions'] as $q) {
      $correctIds = array_column(
          array_filter($q['options'], fn($o) => (bool)$o['is_correct']),
          'id'
      );
      $correctTexts = array_column(
          array_filter($q['options'], fn($o) => (bool)$o['is_correct']),
          'option_text'
      );
      $correctMap[$q['id']] = [
          'type'        => $q['type'],
          'points'      => (int)$q['points'],
          'correct_ids' => $correctIds,
          'correct_texts'=> array_map('strtolower', array_map('trim', $correctTexts)),
          'question'    => $q['question'],
          'explanation' => $q['explanation'] ?? '',
          'options'     => $q['options'],
      ];
  }
  ?>
  const correctMap = <?= json_encode($correctMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  let earned = 0, possible = 0, resultsHtml = '';
  Object.entries(correctMap).forEach(([qid, q]) => {
    possible += q.points;
    const answer = answers[qid];
    let correct = false;

    if (q.type === 'single' || q.type === 'true_false') {
      correct = q.correct_ids.includes(parseInt(answer));
    } else if (q.type === 'multiple') {
      const subIds = (answer || []).map(Number).sort();
      const corIds = [...q.correct_ids].sort();
      correct = JSON.stringify(subIds) === JSON.stringify(corIds);
    } else if (q.type === 'fill_blank') {
      correct = q.correct_texts.includes((answer || '').toLowerCase().trim());
    }

    if (correct) earned += q.points;

    if (<?= $quiz['show_answers_after'] ? 'true' : 'false' ?>) {
      const icon = correct ? '✅' : '❌';
      resultsHtml += `<div class="mb-3 p-3 rounded" style="background:${correct?'#d1fae5':'#fde8e8'}">
        <div class="fw-semibold mb-1">${icon} ${q.question}</div>
        <div class="small text-muted">Correct answer: ${q.correct_texts.join(', ') || q.correct_ids.join(', ')}</div>
        ${q.explanation ? '<div class="small mt-1">💡 ' + q.explanation + '</div>' : ''}
      </div>`;
    }
  });

  const score  = possible > 0 ? Math.round((earned / possible) * 100) : 0;
  const passed = score >= PASS_PCT;

  document.getElementById('previewForm').classList.add('d-none');
  const panel = document.getElementById('resultPanel');
  panel.classList.remove('d-none');
  document.getElementById('resultIcon').textContent = passed ? '🎉' : '😞';
  document.getElementById('resultTitle').textContent = passed ? 'Quiz Passed!' : 'Quiz Failed';
  document.getElementById('resultTitle').style.color = passed ? '#0e9f6e' : '#e02424';
  document.getElementById('resultScore').textContent =
    `Score: ${score}% (${earned}/${possible} points) — Pass threshold: ${PASS_PCT}%`;
  const rev = document.getElementById('answersReview');
  if (rev) rev.innerHTML = resultsHtml;

  <?php if ($quiz['time_limit_sec']): ?>
  clearInterval(timerInterval);
  <?php endif; ?>
}
</script>

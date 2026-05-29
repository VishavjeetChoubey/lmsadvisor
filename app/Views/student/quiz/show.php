<?php
use App\Core\View;
use App\Services\GradingService;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>

<div class="quiz-shell">

  <!-- Quiz header -->
  <div class="quiz-header-card">
    <div class="d-flex align-items-start gap-4 flex-wrap">
      <div class="quiz-icon-wrap">
        <i class="bi bi-patch-question-fill"></i>
      </div>
      <div class="flex-grow-1">
        <div class="quiz-breadcrumb">
          <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $lessonId) ?>">
            <i class="bi bi-arrow-left me-1"></i><?= $e($course['title']) ?>
          </a>
        </div>
        <h2 class="quiz-title"><?= $e($quiz['title']) ?></h2>
        <?php if ($quiz['description']): ?>
          <p class="quiz-desc"><?= $e($quiz['description']) ?></p>
        <?php endif; ?>

        <!-- Meta pills -->
        <div class="quiz-meta-pills">
          <span class="quiz-pill">
            <i class="bi bi-patch-question me-1"></i>
            <?= count($quiz['questions']) ?> question<?= count($quiz['questions']) !== 1 ? 's' : '' ?>
          </span>
          <span class="quiz-pill">
            <i class="bi bi-bullseye me-1"></i>
            Pass at <?= $e($quiz['pass_percentage']) ?>%
          </span>
          <?php if ($quiz['time_limit_sec']): ?>
          <span class="quiz-pill quiz-pill-warn">
            <i class="bi bi-clock me-1"></i>
            <?= GradingService::formatTime((int)$quiz['time_limit_sec']) ?> time limit
          </span>
          <?php endif; ?>
          <?php if ($quiz['max_attempts'] > 0): ?>
          <span class="quiz-pill">
            <i class="bi bi-arrow-repeat me-1"></i>
            <?= $attemptCount ?> / <?= $maxAttempts ?> attempts used
          </span>
          <?php endif; ?>
          <?php if ($bestScore !== null): ?>
          <span class="quiz-pill quiz-pill-success">
            <i class="bi bi-trophy me-1"></i>
            Best: <?= round((float)$bestScore) ?>%
          </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if (!$canAttempt): ?>
  <!-- Max attempts reached -->
  <div class="quiz-locked-card">
    <i class="bi bi-lock-fill quiz-locked-icon"></i>
    <h4>No Attempts Remaining</h4>
    <p>You have used all <?= $maxAttempts ?> attempt<?= $maxAttempts !== 1 ? 's' : '' ?> for this quiz.</p>
    <?php if ($bestScore !== null): ?>
      <div class="quiz-best-score">Your best score: <strong><?= round((float)$bestScore) ?>%</strong></div>
    <?php endif; ?>
    <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $lessonId) ?>"
       class="quiz-btn quiz-btn-back mt-4">
      <i class="bi bi-arrow-left me-2"></i> Back to Course
    </a>
  </div>

  <?php elseif (empty($quiz['questions'])): ?>
  <!-- No questions yet -->
  <div class="quiz-locked-card">
    <i class="bi bi-patch-question quiz-locked-icon" style="color:#6366f1;opacity:.4"></i>
    <h4>Quiz Not Ready</h4>
    <p>This quiz has no questions yet. Please check back later.</p>
    <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $lessonId) ?>"
       class="quiz-btn quiz-btn-back mt-4">
      <i class="bi bi-arrow-left me-2"></i> Back to Lesson
    </a>
  </div>

  <?php else: ?>
  <!-- Quiz form -->
  <form method="POST"
        action="<?= $url('learn/courses/' . $course['uuid'] . '/quiz/' . $lessonId . '/submit') ?>"
        id="quizForm">
    <input type="hidden" name="csrf_token"    value="<?= $e($csrf_token) ?>">
    <input type="hidden" name="time_taken_sec" id="timeTakenSec" value="0">

    <!-- Timer bar (if time limit) -->
    <?php if ($quiz['time_limit_sec']): ?>
    <div class="quiz-timer-bar" id="timerBar">
      <i class="bi bi-clock-fill me-2"></i>
      Time remaining: <strong id="timerDisplay"><?= GradingService::formatTime((int)$quiz['time_limit_sec']) ?></strong>
      <div class="quiz-timer-track">
        <div class="quiz-timer-fill" id="timerFill"></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Progress indicator -->
    <div class="quiz-progress-bar-wrap">
      <div class="quiz-progress-bar-fill" id="quizProgressFill" style="width:0%"></div>
    </div>
    <div class="quiz-progress-label">
      <span id="answeredCount">0</span> of <?= count($quiz['questions']) ?> answered
    </div>

    <!-- Questions -->
    <div class="quiz-questions">
      <?php foreach ($quiz['questions'] as $qi => $q): ?>
      <div class="quiz-q-card" id="qcard<?= $qi ?>" data-qi="<?= $qi ?>">

        <!-- Question header -->
        <div class="quiz-q-header">
          <span class="quiz-q-num"><?= $qi + 1 ?></span>
          <div class="flex-grow-1">
            <div class="quiz-q-text"><?= $e($q['question']) ?></div>
            <div class="quiz-q-meta">
              <?php $typeLabels = [
                'single'       => 'Single choice',
                'multiple'     => 'Multiple choice',
                'true_false'   => 'True / False',
                'fill_blank'   => 'Fill in the blank',
                'ordering'     => 'Ordering',
                'short_answer' => 'Short answer',
                'matching'     => 'Matching',
              ]; ?>
              <span><?= $typeLabels[$q['type']] ?? $q['type'] ?></span>
              <span>·</span>
              <span><?= $q['points'] ?> pt<?= $q['points'] != 1 ? 's' : '' ?></span>
            </div>
          </div>
        </div>

        <!-- Options -->
        <div class="quiz-options">

          <?php if ($q['type'] === 'fill_blank'): ?>
            <input type="text"
                   class="quiz-fill-input"
                   name="answers[<?= $q['id'] ?>]"
                   placeholder="Type your answer…"
                   autocomplete="off"
                   onchange="updateProgress()">

          <?php elseif ($q['type'] === 'short_answer'): ?>
            <p class="quiz-hint"><i class="bi bi-pencil-square me-1"></i>Write your answer below</p>
            <textarea class="quiz-fill-input"
                      name="answers[<?= $q['id'] ?>]"
                      rows="3"
                      placeholder="Type your answer here…"
                      style="resize:vertical;width:100%;padding:12px;border-radius:10px;border:1.5px solid var(--border-color);font-size:14px;font-family:inherit;background:var(--content-bg);color:var(--text-primary)"
                      onchange="updateProgress()"></textarea>

          <?php elseif ($q['type'] === 'ordering'): ?>
            <?php $orderItems = json_decode($q['order_items'] ?? '[]', true) ?: []; ?>
            <?php if ($orderItems): ?>
            <p class="quiz-hint"><i class="bi bi-sort-numeric-down me-1"></i>Drag items into the correct order</p>
            <div class="ordering-list" id="order-<?= $q['id'] ?>" style="display:flex;flex-direction:column;gap:8px">
              <?php
              $shuffled = $orderItems;
              shuffle($shuffled);
              foreach ($shuffled as $item): ?>
              <div class="ordering-item d-flex align-items-center gap-3"
                   style="background:var(--card-bg);border:1.5px solid var(--border-color);border-radius:10px;padding:11px 14px;cursor:grab;user-select:none">
                <i class="bi bi-grip-vertical" style="color:var(--text-muted);font-size:16px;flex-shrink:0"></i>
                <span style="font-size:14px"><?= $e($item) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="answers[<?= $q['id'] ?>]" id="order-val-<?= $q['id'] ?>" value="">
            <?php else: ?>
            <p class="text-muted">No items added yet.</p>
            <?php endif; ?>

          <?php elseif ($q['type'] === 'matching'): ?>
            <?php $pairs = json_decode($q['match_pairs'] ?? '[]', true) ?: []; ?>
            <?php if ($pairs): ?>
            <p class="quiz-hint"><i class="bi bi-arrow-left-right me-1"></i>Match each item on the left to the correct item on the right</p>
            <div style="display:flex;flex-direction:column;gap:10px">
              <?php foreach ($pairs as $pi => $pair): ?>
              <div class="d-flex align-items-center gap-3 flex-wrap">
                <div style="flex:1;min-width:140px;background:var(--card-bg);border:1.5px solid var(--border-color);border-radius:10px;padding:10px 14px;font-size:14px;font-weight:600">
                  <?= $e($pair['left']) ?>
                </div>
                <i class="bi bi-arrow-right" style="color:var(--text-muted)"></i>
                <select name="answers[<?= $q['id'] ?>][<?= $pi ?>]"
                        style="flex:1;min-width:140px;padding:10px 12px;border:1.5px solid var(--border-color);border-radius:10px;font-size:14px;background:var(--content-bg);color:var(--text-primary);cursor:pointer"
                        onchange="updateProgress()">
                  <option value="">— Select —</option>
                  <?php
                  $rights = array_column($pairs, 'right');
                  shuffle($rights);
                  foreach ($rights as $right): ?>
                  <option value="<?= $e($right) ?>"><?= $e($right) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No pairs added yet.</p>
            <?php endif; ?>

          <?php elseif ($q['type'] === 'multiple'): ?>
            <p class="quiz-hint"><i class="bi bi-info-circle me-1"></i>Select all correct answers</p>
            <?php foreach ($q['options'] as $oi => $opt): ?>
            <label class="quiz-option" id="opt<?= $q['id'] ?>_<?= $oi ?>">
              <input type="checkbox"
                     name="answers[<?= $q['id'] ?>][]"
                     value="<?= $opt['id'] ?>"
                     class="quiz-check"
                     onchange="highlightOption(this); updateProgress()">
              <span class="quiz-option-box">
                <span class="quiz-option-letter"><?= chr(65 + $oi) ?></span>
                <?= $e($opt['option_text']) ?>
              </span>
            </label>
            <?php endforeach; ?>

          <?php else: // single + true_false ?>
            <?php foreach ($q['options'] as $oi => $opt): ?>
            <label class="quiz-option" id="opt<?= $q['id'] ?>_<?= $oi ?>">
              <input type="radio"
                     name="answers[<?= $q['id'] ?>]"
                     value="<?= $opt['id'] ?>"
                     class="quiz-radio"
                     onchange="highlightOption(this); updateProgress()">
              <span class="quiz-option-box">
                <?php if ($q['type'] !== 'true_false'): ?>
                  <span class="quiz-option-letter"><?= chr(65 + $oi) ?></span>
                <?php endif; ?>
                <?= $e($opt['option_text']) ?>
              </span>
            </label>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Submit bar -->
    <div class="quiz-submit-bar">
      <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $lessonId) ?>"
         class="quiz-btn quiz-btn-back">
        <i class="bi bi-arrow-left me-1"></i> Back
      </a>
      <div class="quiz-submit-info" id="submitInfo">
        Answer all questions before submitting
      </div>
      <button type="submit" class="quiz-btn quiz-btn-submit" id="submitBtn">
        <i class="bi bi-check2-circle me-2"></i>Submit Quiz
      </button>
    </div>
  </form>
  <?php endif; ?>

</div>

<style>
/* ── Quiz shell ──────────────────────────────────────────────────────────── */
.quiz-shell { max-width: 820px; margin: 0 auto; }

/* Header card */
.quiz-header-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 28px 28px 24px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.quiz-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px;
  background: linear-gradient(135deg,#6366f1,#1a56db);
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; color: #fff; flex-shrink: 0;
  box-shadow: 0 6px 20px rgba(99,102,241,.3);
}
.quiz-breadcrumb a {
  font-size: 12.5px; color: var(--text-muted);
  text-decoration: none; margin-bottom: 6px; display: inline-block;
}
.quiz-breadcrumb a:hover { color: #6366f1; }
.quiz-title { font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 4px 0 6px; }
.quiz-desc  { font-size: 14px; color: var(--text-muted); margin-bottom: 12px; }
.quiz-meta-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.quiz-pill {
  display: inline-flex; align-items: center;
  background: var(--content-bg); border: 1px solid var(--border-color);
  border-radius: 20px; padding: 4px 12px; font-size: 12.5px; color: var(--text-muted);
}
.quiz-pill-warn { background: #fef9c3; border-color: #e3a008; color: #92400e; }
.quiz-pill-success { background: #d1fae5; border-color: #0e9f6e; color: #065f46; }

/* Locked / empty */
.quiz-locked-card {
  background: var(--card-bg); border: 1px solid var(--border-color);
  border-radius: 16px; padding: 60px 32px; text-align: center;
}
.quiz-locked-icon { font-size: 4rem; color: var(--text-muted); opacity: .3; display: block; margin-bottom: 16px; }
.quiz-locked-card h4 { font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
.quiz-locked-card p  { color: var(--text-muted); }
.quiz-best-score { font-size: 15px; color: var(--text-muted); margin-top: 12px; }

/* Timer */
.quiz-timer-bar {
  background: #fef9c3; border: 1px solid #e3a008; border-radius: 12px;
  padding: 10px 18px; margin-bottom: 16px; display: flex; align-items: center;
  gap: 8px; font-size: 14px; font-weight: 600; color: #92400e;
}
.quiz-timer-track { flex: 1; height: 6px; background: #fde68a; border-radius: 3px; overflow: hidden; margin-left: auto; max-width: 150px; }
.quiz-timer-fill  { height: 100%; background: #e3a008; border-radius: 3px; transition: width 1s linear; }
.quiz-timer-bar.danger { background: #fde8e8; border-color: #e02424; color: #7f1d1d; }
.quiz-timer-bar.danger .quiz-timer-fill { background: #e02424; }

/* Progress indicator */
.quiz-progress-bar-wrap {
  height: 4px; background: var(--border-color); border-radius: 2px; margin-bottom: 6px; overflow: hidden;
}
.quiz-progress-bar-fill { height: 100%; background: #6366f1; border-radius: 2px; transition: width .3s ease; }
.quiz-progress-label { font-size: 12px; color: var(--text-muted); text-align: right; margin-bottom: 20px; }

/* Questions */
.quiz-questions { display: flex; flex-direction: column; gap: 16px; }

.quiz-q-card {
  background: var(--card-bg); border: 1.5px solid var(--border-color);
  border-radius: 14px; overflow: hidden; transition: border-color .2s, box-shadow .2s;
}
.quiz-q-card.answered { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.08); }

.quiz-q-header { display: flex; gap: 14px; padding: 20px 20px 14px; }
.quiz-q-num {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg,#6366f1,#1a56db); color: #fff;
  font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.quiz-q-text { font-size: 15.5px; font-weight: 600; color: var(--text-primary); line-height: 1.45; }
.quiz-q-meta { font-size: 12px; color: var(--text-muted); margin-top: 4px; display: flex; gap: 6px; }
.quiz-hint   { font-size: 12px; color: var(--text-muted); margin: 0 20px 8px; font-style: italic; }

.quiz-options { padding: 4px 20px 20px; display: flex; flex-direction: column; gap: 8px; }

/* Option labels */
.quiz-option { display: block; cursor: pointer; }
.quiz-option input { display: none; }
.quiz-option-box {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; border-radius: 10px;
  border: 1.5px solid var(--border-color); background: var(--content-bg);
  font-size: 14.5px; color: var(--text-primary);
  transition: border-color .15s, background .15s;
}
.quiz-option:hover .quiz-option-box { border-color: #6366f1; background: #ebf2ff; }
.quiz-option input:checked ~ .quiz-option-box {
  border-color: #6366f1; background: #ebf2ff; font-weight: 600;
}
.quiz-option-letter {
  width: 26px; height: 26px; border-radius: 50%; background: var(--border-color);
  font-size: 12px; font-weight: 700; color: var(--text-muted);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  transition: background .15s, color .15s;
}
.quiz-option input:checked ~ .quiz-option-box .quiz-option-letter {
  background: #6366f1; color: #fff;
}

/* Fill blank */
.quiz-fill-input {
  width: 100%; padding: 12px 16px; border-radius: 10px;
  border: 1.5px solid var(--border-color); background: var(--content-bg);
  font-size: 14.5px; color: var(--text-primary);
  transition: border-color .15s; outline: none;
}
.quiz-fill-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }

/* Submit bar */
.quiz-submit-bar {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  background: var(--card-bg); border: 1px solid var(--border-color);
  border-radius: 14px; padding: 16px 24px; margin-top: 24px;
  position: sticky; bottom: 80px; /* above bottom nav */
  box-shadow: 0 -4px 24px rgba(0,0,0,.08);
}
.quiz-submit-info { font-size: 13px; color: var(--text-muted); flex: 1; text-align: center; }

/* Buttons */
.quiz-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 22px; border-radius: 10px; font-size: 14px; font-weight: 700;
  cursor: pointer; border: none; text-decoration: none !important;
  transition: opacity .15s, transform .1s;
}
.quiz-btn:hover { opacity: .9; transform: translateY(-1px); }
.quiz-btn-back   { background: var(--content-bg); color: var(--text-muted) !important; border: 1.5px solid var(--border-color); }
.quiz-btn-submit { background: linear-gradient(135deg,#6366f1,#1a56db); color: #fff !important; box-shadow: 0 4px 14px rgba(99,102,241,.35); }
.quiz-btn-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; }
</style>

<script>
const TOTAL_Q = <?= count($quiz['questions']) ?>;
<?php if ($quiz['time_limit_sec']): ?>
const TIME_LIMIT = <?= (int)$quiz['time_limit_sec'] ?>;
<?php endif; ?>

// ── Progress tracking ─────────────────────────────────────────────────────────
function updateProgress() {
  let answered = 0;
  const qCards = document.querySelectorAll('.quiz-q-card');

  qCards.forEach(card => {
    const qi     = card.dataset.qi;
    const radio  = card.querySelector('input[type="radio"]:checked');
    const checks = card.querySelectorAll('input[type="checkbox"]:checked');
    const fill   = card.querySelector('.quiz-fill-input');
    const done   = radio || checks.length > 0 || (fill && fill.value.trim() !== '');
    if (done) {
      answered++;
      card.classList.add('answered');
    } else {
      card.classList.remove('answered');
    }
  });

  document.getElementById('answeredCount').textContent = answered;
  document.getElementById('quizProgressFill').style.width = (answered / TOTAL_Q * 100) + '%';

  const info = document.getElementById('submitInfo');
  const btn  = document.getElementById('submitBtn');
  if (answered === TOTAL_Q) {
    info.textContent = 'All questions answered — ready to submit!';
    info.style.color = '#0e9f6e';
    btn.disabled     = false;
  } else {
    info.textContent = (TOTAL_Q - answered) + ' question' + (TOTAL_Q - answered !== 1 ? 's' : '') + ' remaining';
    info.style.color = '';
  }
}

// ── Option highlight helper ───────────────────────────────────────────────────
function highlightOption(input) {
  // For radio — deselect siblings in same question visually (CSS handles it)
  updateProgress();
}

// ── Timer ─────────────────────────────────────────────────────────────────────
<?php if ($quiz['time_limit_sec']): ?>
let timeLeft = TIME_LIMIT;
const startedAt = Date.now();
const timerDisplay = document.getElementById('timerDisplay');
const timerFill    = document.getElementById('timerFill');
const timerBar     = document.getElementById('timerBar');

const tick = setInterval(() => {
  timeLeft--;
  document.getElementById('timeTakenSec').value = TIME_LIMIT - timeLeft;

  const m = Math.floor(timeLeft / 60);
  const s = timeLeft % 60;
  timerDisplay.textContent = m + ':' + String(s).padStart(2, '0');
  timerFill.style.width    = (timeLeft / TIME_LIMIT * 100) + '%';

  if (timeLeft <= 60) timerBar.classList.add('danger');
  if (timeLeft <= 0) {
    clearInterval(tick);
    document.getElementById('quizForm').submit();
  }
}, 1000);
<?php endif; ?>

// ── Track time taken (no timer) ───────────────────────────────────────────────
const quizStart = Date.now();
document.getElementById('quizForm')?.addEventListener('submit', function () {
  const elapsed = Math.floor((Date.now() - quizStart) / 1000);
  document.getElementById('timeTakenSec').value = elapsed;
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting…';
});

// Init
updateProgress();

// ── Ordering questions: drag to reorder ──────────────────────────────────────
(function() {
  document.querySelectorAll('.ordering-list').forEach(function(list) {
    var qid = list.id.replace('order-', '');
    var valInput = document.getElementById('order-val-' + qid);

    function updateOrderValue() {
      var items = list.querySelectorAll('.ordering-item span');
      var vals  = Array.from(items).map(function(s) { return s.textContent.trim(); });
      if (valInput) valInput.value = JSON.stringify(vals);
      updateProgress();
    }

    // Simple drag-and-drop without Sortable library
    var dragging = null;
    list.querySelectorAll('.ordering-item').forEach(function(item) {
      item.setAttribute('draggable', 'true');
      item.addEventListener('dragstart', function() {
        dragging = this;
        setTimeout(function() { dragging.style.opacity = '0.4'; }, 0);
      });
      item.addEventListener('dragend', function() {
        this.style.opacity = '1';
        dragging = null;
        updateOrderValue();
      });
      item.addEventListener('dragover', function(e) {
        e.preventDefault();
        var rect = this.getBoundingClientRect();
        var mid  = rect.top + rect.height / 2;
        if (dragging && dragging !== this) {
          if (e.clientY < mid) list.insertBefore(dragging, this);
          else list.insertBefore(dragging, this.nextSibling);
        }
      });
    });

    // Set initial value
    updateOrderValue();
  });
})();
</script>

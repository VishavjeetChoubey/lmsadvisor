<?php
use App\Core\View;
use App\Services\GradingService;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$r       = $result;
$passed  = (bool)$r['passed'];
$score   = round((float)$r['score']);
$passPct = (int)$r['pass_pct'];
?>

<div class="quiz-shell">

  <!-- Result hero -->
  <div class="qr-hero <?= $passed ? 'pass' : 'fail' ?>">
    <div class="qr-hero-icon">
      <?= $passed ? '🎉' : '😞' ?>
    </div>
    <h2 class="qr-hero-title">
      <?= $passed ? 'Quiz Passed!' : 'Quiz Failed' ?>
    </h2>
    <p class="qr-hero-subtitle">
      <?= $passed
        ? 'Great work! You passed with a score of ' . $score . '%.'
        : 'You scored ' . $score . '%. You need ' . $passPct . '% to pass.' ?>
    </p>

    <!-- Score circle -->
    <div class="qr-score-circle <?= $passed ? 'pass' : 'fail' ?>">
      <div class="qr-score-num"><?= $score ?>%</div>
      <div class="qr-score-label">Score</div>
    </div>

    <!-- Stats row -->
    <div class="qr-stats">
      <div class="qr-stat">
        <div class="qr-stat-val"><?= $r['points_earned'] ?>/<?= $r['points_possible'] ?></div>
        <div class="qr-stat-lbl">Points</div>
      </div>
      <div class="qr-stat">
        <div class="qr-stat-val"><?= $passPct ?>%</div>
        <div class="qr-stat-lbl">Pass Mark</div>
      </div>
      <div class="qr-stat">
        <div class="qr-stat-val"><?= GradingService::formatTime((int)$r['time_sec']) ?></div>
        <div class="qr-stat-lbl">Time Taken</div>
      </div>
      <div class="qr-stat">
        <div class="qr-stat-val"><?= $attemptCount ?><?= $maxAttempts > 0 ? '/'.$maxAttempts : '' ?></div>
        <div class="qr-stat-lbl">Attempt<?= $attemptCount !== 1 ? 's' : '' ?></div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="qr-actions">
      <?php if ($canRetry && !$passed): ?>
      <a href="<?= $url('learn/courses/' . $course['uuid'] . '/quiz/' . $lessonId) ?>"
         class="qr-btn qr-btn-retry">
        <i class="bi bi-arrow-repeat me-2"></i> Try Again
      </a>
      <?php endif; ?>
      <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $lessonId) ?>"
         class="qr-btn qr-btn-back">
        <i class="bi bi-arrow-left me-2"></i> Back to Lesson
      </a>
    </div>
  </div>

  <!-- Answer review (if enabled) -->
  <?php if ($r['show_answers'] && !empty($r['questions'])): ?>
  <div class="qr-review-header">
    <h4><i class="bi bi-card-list me-2"></i>Answer Review</h4>
    <p>Review your answers and see the correct solutions below.</p>
  </div>

  <?php foreach ($r['questions'] as $qi => $q):
    $qResult   = $r['results'][$q['id']] ?? null;
    $isCorrect = $qResult ? (bool)$qResult['correct'] : false;
    $submitted = $qResult['submitted'] ?? null;
    $correctIds = $qResult['correct_options'] ?? [];
  ?>
  <div class="qr-q-card <?= $isCorrect ? 'correct' : 'incorrect' ?>">

    <!-- Question -->
    <div class="qr-q-header">
      <span class="qr-q-num <?= $isCorrect ? 'pass' : 'fail' ?>">
        <?= $isCorrect ? '✓' : '✗' ?>
      </span>
      <div class="flex-grow-1">
        <div class="qr-q-text"><?= $e($q['question']) ?></div>
        <div class="qr-q-pts">
          <?= $isCorrect ? $q['points'] : 0 ?> / <?= $q['points'] ?> pt<?= $q['points'] != 1 ? 's' : '' ?>
        </div>
      </div>
    </div>

    <!-- Options with correct/wrong indicators -->
    <div class="qr-options">
      <?php if ($q['type'] === 'fill_blank' || $q['type'] === 'short_answer'): ?>
        <div class="qr-fill-answer">
          <div class="qr-your-answer <?= $isCorrect ? 'correct' : 'wrong' ?>">
            <span class="qr-answer-lbl">Your answer:</span>
            <span>"<?= $e(is_array($submitted) ? implode(', ', $submitted) : (string)$submitted) ?>"</span>
            <i class="bi <?= $isCorrect ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> ms-2"></i>
          </div>
          <?php if (!$isCorrect): ?>
          <div class="qr-correct-answer">
            <span class="qr-answer-lbl">Correct answer:</span>
            <?php if ($q['type'] === 'short_answer'): ?>
              <?php $acceptable = json_decode($q['acceptable_answers'] ?? '[]', true) ?: []; ?>
              <span><?= $e(implode(' or ', $acceptable)) ?></span>
            <?php else: ?>
              <?php $correctTexts = array_column(array_filter($q['options'], fn($o) => $o['is_correct']), 'option_text'); ?>
              <span><?= $e(implode(', ', $correctTexts)) ?></span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

      <?php elseif ($q['type'] === 'ordering'): ?>
        <?php
          $correctOrder  = json_decode($q['order_items'] ?? '[]', true) ?: [];
          $submittedOrder = is_string($submitted) ? json_decode($submitted, true) : (array)$submitted;
        ?>
        <div class="qr-fill-answer">
          <div class="qr-answer-lbl mb-2">Your order:</div>
          <?php foreach ((array)$submittedOrder as $si => $item): ?>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-secondary"><?= $si+1 ?></span>
            <span style="font-size:14px"><?= $e($item) ?></span>
            <?php if (isset($correctOrder[$si]) && $correctOrder[$si] === $item): ?>
              <i class="bi bi-check-circle-fill text-success"></i>
            <?php else: ?>
              <i class="bi bi-x-circle-fill text-danger"></i>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php if (!$isCorrect): ?>
          <div class="qr-correct-answer mt-2">
            <div class="qr-answer-lbl mb-1">Correct order:</div>
            <?php foreach ($correctOrder as $ci => $item): ?>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-success"><?= $ci+1 ?></span>
              <span style="font-size:14px"><?= $e($item) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

      <?php elseif ($q['type'] === 'matching'): ?>
        <?php $pairs = json_decode($q['match_pairs'] ?? '[]', true) ?: []; ?>
        <div class="qr-fill-answer">
          <?php foreach ($pairs as $pi => $pair):
            $given   = strtolower(trim((string)($submitted[$pi] ?? '')));
            $expected = strtolower(trim($pair['right']));
            $pairOk  = $given === $expected;
          ?>
          <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            <span style="min-width:120px;font-weight:600;font-size:13.5px"><?= $e($pair['left']) ?></span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span style="font-size:13.5px"><?= $e($submitted[$pi] ?? '(no answer)') ?></span>
            <?php if ($pairOk): ?>
              <i class="bi bi-check-circle-fill text-success"></i>
            <?php else: ?>
              <i class="bi bi-x-circle-fill text-danger"></i>
              <span style="font-size:12px;color:#059669">→ <?= $e($pair['right']) ?></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

      <?php else: // single, multiple, true_false ?>
        <?php foreach ($q['options'] as $opt):
          $isCorrectOpt = in_array($opt['id'], $correctIds, false);
          $wasSelected  = is_array($submitted)
            ? in_array((string)$opt['id'], array_map('strval', $submitted))
            : (string)$submitted === (string)$opt['id'];
          $cls = '';
          if ($isCorrectOpt) $cls = 'opt-correct';
          elseif ($wasSelected && !$isCorrectOpt) $cls = 'opt-wrong';
        ?>
        <div class="qr-option <?= $cls ?>">
          <span class="qr-opt-indicator">
            <?php if ($isCorrectOpt): ?>
              <i class="bi bi-check-circle-fill" style="color:#0e9f6e"></i>
            <?php elseif ($wasSelected): ?>
              <i class="bi bi-x-circle-fill" style="color:#e02424"></i>
            <?php else: ?>
              <i class="bi bi-circle" style="color:var(--border)"></i>
            <?php endif; ?>
          </span>
          <span class="qr-opt-text"><?= $e($opt['option_text']) ?></span>
          <?php if ($wasSelected): ?>
            <span class="qr-your-label">Your answer</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Explanation -->
    <?php if ($q['explanation']): ?>
    <div class="qr-explanation">
      <i class="bi bi-lightbulb-fill me-2" style="color:#f59e0b"></i>
      <strong>Explanation:</strong> <?= $e($q['explanation']) ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>

  <!-- Bottom nav -->
  <div class="qr-bottom-nav">
    <?php if ($canRetry && !$passed): ?>
    <a href="<?= $url('learn/courses/' . $course['uuid'] . '/quiz/' . $lessonId) ?>"
       class="qr-btn qr-btn-retry">
      <i class="bi bi-arrow-repeat me-2"></i> Try Again
    </a>
    <?php endif; ?>
    <a href="<?= $url('learn/courses/' . $course['uuid'] . '/learn?lesson=' . $lessonId) ?>"
       class="qr-btn qr-btn-back">
      <i class="bi bi-arrow-left me-2"></i> Back to Lesson
    </a>
    <?php if ($passed): ?>
    <a href="<?= $url('learn/courses') ?>" class="qr-btn qr-btn-courses">
      <i class="bi bi-grid me-2"></i> My Courses
    </a>
    <?php endif; ?>
  </div>

</div>

<style>
.quiz-shell { max-width: 820px; margin: 0 auto; }

/* Hero */
.qr-hero {
  border-radius: 20px; padding: 40px 32px; text-align: center; margin-bottom: 24px;
}
.qr-hero.pass { background: linear-gradient(135deg,#d1fae5,#a7f3d0); border: 1px solid #0e9f6e; }
.qr-hero.fail { background: linear-gradient(135deg,#fde8e8,#fecaca); border: 1px solid #e02424; }
.qr-hero-icon { font-size: 4rem; margin-bottom: 12px; line-height: 1; }
.qr-hero-title { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
.qr-hero-subtitle { font-size: 15px; color: #475569; margin-bottom: 24px; }

/* Score circle */
.qr-score-circle {
  width: 120px; height: 120px; border-radius: 50%;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  margin: 0 auto 24px; border: 6px solid;
}
.qr-score-circle.pass { border-color: #0e9f6e; background: rgba(14,159,110,.1); }
.qr-score-circle.fail { border-color: #e02424; background: rgba(224,36,36,.08); }
.qr-score-num { font-size: 30px; font-weight: 800; color: #0f172a; line-height: 1; }
.qr-score-label { font-size: 12px; color: #64748b; margin-top: 3px; }

/* Stats row */
.qr-stats { display: flex; justify-content: center; gap: 32px; flex-wrap: wrap; margin-bottom: 28px; }
.qr-stat { text-align: center; }
.qr-stat-val { font-size: 20px; font-weight: 700; color: #0f172a; }
.qr-stat-lbl { font-size: 12px; color: #64748b; margin-top: 2px; }

/* Action buttons */
.qr-actions { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }

/* Review section */
.qr-review-header { margin-bottom: 16px; }
.qr-review-header h4 { font-weight: 700; color: var(--text-1); margin-bottom: 4px; }
.qr-review-header p  { font-size: 13.5px; color: var(--text-2); }

.qr-q-card {
  background: var(--card); border: 1.5px solid var(--border);
  border-radius: 14px; margin-bottom: 14px; overflow: hidden;
}
.qr-q-card.correct { border-color: #0e9f6e; }
.qr-q-card.incorrect { border-color: #e02424; }

.qr-q-header { display: flex; gap: 14px; padding: 18px 20px 10px; }
.qr-q-num {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 15px; flex-shrink: 0;
}
.qr-q-num.pass { background: #d1fae5; color: #0e9f6e; }
.qr-q-num.fail { background: #fde8e8; color: #e02424; }
.qr-q-text { font-size: 15px; font-weight: 600; color: var(--text-1); }
.qr-q-pts  { font-size: 12px; color: var(--text-2); margin-top: 3px; }

.qr-options { padding: 8px 20px 16px; display: flex; flex-direction: column; gap: 8px; }
.qr-option {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; border-radius: 8px; font-size: 14px;
  color: var(--text-1); border: 1px solid transparent;
}
.qr-option.opt-correct { background: #d1fae5; border-color: #0e9f6e; }
.qr-option.opt-wrong   { background: #fde8e8; border-color: #e02424; }
.qr-opt-indicator { flex-shrink: 0; font-size: 16px; }
.qr-opt-text { flex: 1; }
.qr-your-label {
  font-size: 10.5px; font-weight: 700; padding: 2px 8px;
  border-radius: 10px; background: rgba(0,0,0,.07); color: #64748b;
  white-space: nowrap;
}

/* Fill blank */
.qr-fill-answer { display: flex; flex-direction: column; gap: 8px; }
.qr-your-answer, .qr-correct-answer {
  display: flex; align-items: center; gap: 8px; font-size: 14px;
  padding: 10px 14px; border-radius: 8px;
}
.qr-your-answer.correct { background: #d1fae5; }
.qr-your-answer.wrong   { background: #fde8e8; }
.qr-correct-answer { background: #d1fae5; }
.qr-answer-lbl { font-weight: 600; color: var(--text-2); white-space: nowrap; }

/* Explanation */
.qr-explanation {
  margin: 0 20px 16px;
  padding: 12px 16px;
  background: #fef9c3; border-left: 3px solid #f59e0b;
  border-radius: 0 8px 8px 0; font-size: 13.5px; color: #92400e;
}

/* Bottom nav */
.qr-bottom-nav {
  display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;
  padding: 24px 0 8px;
}

/* Buttons */
.qr-btn {
  display: inline-flex; align-items: center;
  padding: 11px 24px; border-radius: 10px;
  font-size: 14px; font-weight: 700; text-decoration: none !important;
  transition: opacity .15s, transform .1s; cursor: pointer; border: none;
}
.qr-btn:hover { opacity: .9; transform: translateY(-1px); }
.qr-btn-retry   { background: linear-gradient(135deg,#6366f1,#1a56db); color: #fff !important; box-shadow: 0 4px 14px rgba(99,102,241,.3); }
.qr-btn-back    { background: var(--card); color: var(--text-2) !important; border: 1.5px solid var(--border); }
.qr-btn-courses { background: linear-gradient(135deg,#0e9f6e,#059669); color: #fff !important; box-shadow: 0 4px 14px rgba(14,159,110,.25); }
</style>

<?php
use App\Core\View;
use App\Services\AuthService;
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user();
$medals   = ['🥇','🥈','🥉'];
?>

<!-- My rank card -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="student-stat-card">
      <div class="student-stat-icon" style="color:#e3a008"><i class="bi bi-trophy"></i></div>
      <div class="student-stat-value"><?= number_format((int)$myPoints) ?></div>
      <div class="student-stat-label">My Points</div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="student-stat-card">
      <div class="student-stat-icon" style="color:#6366f1"><i class="bi bi-bar-chart-steps"></i></div>
      <div class="student-stat-value"><?= $myRank > 0 ? '#' . $myRank : '—' ?></div>
      <div class="student-stat-label">My Rank</div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="student-stat-card">
      <div class="student-stat-icon" style="color:#0e9f6e"><i class="bi bi-people"></i></div>
      <div class="student-stat-value"><?= count($top) ?></div>
      <div class="student-stat-label">Total Earners</div>
    </div>
  </div>
</div>

<div class="card lms-card">
  <div class="card-header lms-card-header">
    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i> Leaderboard</h5>
  </div>
  <?php if (empty($top)): ?>
    <div class="card-body text-center py-5">
      <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-trophy"></i></div>
      <h6 class="mt-3 text-muted">No rankings yet</h6>
      <p class="small text-muted">Complete courses to earn points and appear here.</p>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table lms-table mb-0">
      <thead>
        <tr>
          <th style="width:56px">Rank</th>
          <th>Student</th>
          <th class="text-center">Courses Done</th>
          <th class="text-end">Points</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($top as $i => $u): ?>
        <?php
          $rank   = $i + 1;
          $isMe   = (int)$u['id'] === (int)($authUser['id'] ?? 0);
        ?>
        <tr class="<?= $isMe ? 'table-primary fw-semibold' : '' ?>">
          <td>
            <?php if ($rank <= 3): ?>
              <span style="font-size:20px"><?= $medals[$rank-1] ?></span>
            <?php else: ?>
              <span class="text-muted" style="font-size:13px">#<?= $rank ?></span>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-size:13.5px">
              <?= $e($u['first_name'] . ' ' . $u['last_name']) ?>
              <?php if ($isMe): ?><span class="badge bg-primary ms-1" style="font-size:10px">You</span><?php endif; ?>
            </div>
          </td>
          <td class="text-center">
            <span class="badge bg-success-subtle text-success" style="font-size:11.5px;border-radius:20px;padding:3px 10px">
              <?= (int)$u['courses_completed'] ?>
            </span>
          </td>
          <td class="text-end fw-bold" style="font-size:15px;color:var(--primary)">
            <?= number_format((int)$u['total_points']) ?>
            <span class="text-muted fw-normal" style="font-size:11px"> pts</span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<style>.bg-success-subtle{background:#d1fae5!important}</style>

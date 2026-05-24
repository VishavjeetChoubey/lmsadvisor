<?php
use App\Core\View;
use App\Services\AuthService;
use App\Middleware\CsrfMiddleware;
$e          = fn(mixed $v): string => View::e($v);
$url        = fn(string $p = ''): string => View::url($p);
$isSA       = AuthService::isSuperAdmin();
$isAdmin    = AuthService::isAdmin();

$medalColors = ['#ffd700','#c0c0c0','#cd7f32'];
$medalIcons  = ['🥇','🥈','🥉'];

// Prepare chart data
$labels = array_map(fn($u) => $u['first_name'] . ' ' . $u['last_name'], array_slice($top, 0, 10));
$values = array_map(fn($u) => (int)$u['total_points'], array_slice($top, 0, 10));
?>

<div class="row g-4">

  <!-- Left: Leaderboard podium + table -->
  <div class="col-12 col-xl-8">

    <!-- Top 3 podium -->
    <?php if (count($top) >= 1): ?>
    <div class="card lms-card mb-4">
      <div class="card-body py-4 px-4">
        <h6 class="fw-semibold text-center text-muted mb-4">
          <i class="bi bi-trophy-fill text-warning me-2"></i>Top Performers
        </h6>
        <div class="d-flex justify-content-center align-items-end gap-4 flex-wrap">
          <?php
          // Reorder: 2nd, 1st, 3rd for podium visual
          $podium = [];
          if (isset($top[1])) $podium[] = ['pos'=>1,'user'=>$top[1]];
          if (isset($top[0])) $podium[] = ['pos'=>0,'user'=>$top[0]];
          if (isset($top[2])) $podium[] = ['pos'=>2,'user'=>$top[2]];
          foreach ($podium as $slot):
            $u    = $slot['user'];
            $pos  = $slot['pos'];
            $h    = $pos === 0 ? '100px' : ($pos === 1 ? '72px' : '56px');
            $bg   = $pos === 0 ? '#fdf3c6' : ($pos === 1 ? '#f1f5f9' : '#fde8e0');
            $medal= $medalIcons[$pos];
          ?>
          <div class="text-center" style="min-width:110px">
            <div class="mb-2" style="font-size:28px"><?= $medal ?></div>
            <div class="fw-bold" style="font-size:13.5px"><?= $e($u['first_name'] . ' ' . $u['last_name']) ?></div>
            <div class="text-muted" style="font-size:11.5px"><?= number_format((int)$u['total_points']) ?> pts</div>
            <div style="background:<?= $bg ?>;height:<?= $h ?>;border-radius:8px 8px 0 0;margin-top:8px;
                        display:flex;align-items:center;justify-content:center;
                        font-size:1.2rem;font-weight:700;color:#1e293b;">
              #<?= $pos + 1 ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Full leaderboard table -->
    <div class="card lms-card">
      <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-trophy me-2"></i> Full Rankings
          <span class="badge bg-secondary ms-1"><?= count($top) ?></span>
        </h5>
        <?php if ($isAdmin): ?>
        <button class="btn btn-sm btn-primary"
                data-bs-toggle="modal" data-bs-target="#awardModal">
          <i class="bi bi-plus-circle me-1"></i> Award Points
        </button>
        <?php endif; ?>
      </div>

      <?php if (empty($top)): ?>
        <div class="card-body text-center py-5">
          <div style="font-size:3rem;color:var(--border-color)"><i class="bi bi-trophy"></i></div>
          <h6 class="mt-3 text-muted">No points awarded yet</h6>
          <p class="text-muted small">Points are awarded when students complete courses.</p>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover lms-table mb-0">
          <thead>
            <tr>
              <th style="width:48px">Rank</th>
              <th>Student</th>
              <th class="text-center">Courses Done</th>
              <th class="text-end">Points</th>
              <?php if ($isSA): ?>
              <th class="text-end">Actions</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top as $i => $u): ?>
            <?php $rank = $i + 1; ?>
            <tr id="lb-row-<?= $u['id'] ?>"
                class="<?= $rank <= 3 ? 'fw-semibold' : '' ?>">
              <td>
                <?php if ($rank <= 3): ?>
                  <span style="font-size:18px"><?= $medalIcons[$rank-1] ?></span>
                <?php else: ?>
                  <span class="text-muted" style="font-size:13px">#<?= $rank ?></span>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-size:13.5px"><?= $e($u['first_name'] . ' ' . $u['last_name']) ?></div>
                <div style="font-size:11.5px;color:var(--text-muted)"><?= ucfirst(str_replace('_',' ',$u['role_name'])) ?></div>
              </td>
              <td class="text-center">
                <span class="badge bg-success-subtle text-success" style="font-size:11.5px;border-radius:20px;padding:3px 10px">
                  <?= (int)$u['courses_completed'] ?>
                </span>
              </td>
              <td class="text-end">
                <span class="fw-bold" style="font-size:15px;color:var(--primary)">
                  <?= number_format((int)$u['total_points']) ?>
                </span>
                <span class="text-muted" style="font-size:11px"> pts</span>
              </td>
              <?php if ($isSA): ?>
              <td class="text-end">
                <button class="btn btn-xs btn-outline-danger btn-reset-user"
                        data-id="<?= $u['id'] ?>"
                        data-name="<?= $e($u['first_name'] . ' ' . $u['last_name']) ?>"
                        title="Reset points">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: Chart + Recent awards + Course stats -->
  <div class="col-12 col-xl-4">

    <!-- Points bar chart -->
    <?php if (count($top) > 0): ?>
    <div class="card lms-card mb-4">
      <div class="card-header lms-card-header">
        <h6 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i> Top 10 by Points</h6>
      </div>
      <div class="card-body p-3">
        <canvas id="pointsChart" height="260"></canvas>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent awards -->
    <div class="card lms-card mb-4">
      <div class="card-header lms-card-header">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i> Recent Awards</h6>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="p-4 text-center text-muted small">No awards yet.</div>
        <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach (array_slice($recent, 0, 10) as $award): ?>
          <div class="list-group-item d-flex align-items-center gap-3 py-2 px-3">
            <span class="badge <?= $award['points'] > 0 ? 'bg-success' : 'bg-danger' ?>"
                  style="min-width:52px;text-align:center">
              <?= $award['points'] > 0 ? '+' : '' ?><?= $award['points'] ?>
            </span>
            <div class="flex-grow-1" style="min-width:0">
              <div class="fw-semibold" style="font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= $e($award['first_name'] . ' ' . $award['last_name']) ?>
              </div>
              <div class="text-muted" style="font-size:11px">
                <?= $e($award['reason']) ?>
                <?php if ($award['course_title']): ?>
                  · <?= $e(mb_strimwidth($award['course_title'], 0, 28, '…')) ?>
                <?php endif; ?>
              </div>
            </div>
            <span class="text-muted" style="font-size:11px;white-space:nowrap">
              <?= date('d M', strtotime($award['created_at'])) ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Course stats -->
    <?php if (!empty($courseStats)): ?>
    <div class="card lms-card">
      <div class="card-header lms-card-header">
        <h6 class="mb-0"><i class="bi bi-book me-2"></i> Points by Course</h6>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php foreach ($courseStats as $cs): ?>
          <div class="list-group-item d-flex align-items-center gap-3 py-2 px-3">
            <div class="flex-grow-1" style="min-width:0">
              <div style="font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= $e($cs['title']) ?>
              </div>
              <div class="text-muted" style="font-size:11px">
                <?= (int)$cs['earners'] ?> earner<?= $cs['earners'] != 1 ? 's' : '' ?>
              </div>
            </div>
            <span class="badge bg-primary-subtle text-primary" style="font-size:11.5px">
              <?= number_format((int)$cs['total_points_awarded']) ?> pts
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Award Points Modal -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="awardModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>Award Points</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <div class="mb-3">
          <label class="form-label fw-semibold">Find User</label>
          <input type="text" class="form-control" id="awardUserSearch"
                 placeholder="Type name or email…" autocomplete="off">
          <div id="awardUserResults" class="list-group mt-1" style="max-height:160px;overflow-y:auto"></div>
          <input type="hidden" id="awardUserId">
          <div id="awardUserDisplay" class="mt-2 p-2 rounded d-none"
               style="background:var(--content-bg);font-size:13px"></div>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">
              Points <small class="text-muted fw-normal">(negative to deduct)</small>
            </label>
            <input type="number" class="form-control" id="awardPoints"
                   value="10" min="-9999" max="9999">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Reason</label>
            <input type="text" class="form-control" id="awardReason"
                   placeholder="e.g. Participation" maxlength="191">
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="confirmAwardBtn">
          <i class="bi bi-check-circle me-1"></i> Award
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
.bg-success-subtle{background:#d1fae5!important}
.bg-primary-subtle{background:#ebf2ff!important}
.btn-xs{padding:3px 7px;font-size:12px}
</style>

<input type="hidden" id="csrfToken" value="<?= $e($csrf_token) ?>">
<script>
const CSRF    = document.getElementById('csrfToken').value;
const BASE    = '<?= rtrim(APP_URL, '/') ?>';
const IS_SA   = <?= $isSA ? 'true' : 'false' ?>;
const IS_ADMIN= <?= $isAdmin ? 'true' : 'false' ?>;

// ── Chart ─────────────────────────────────────────────────────────────────────
const chartEl = document.getElementById('pointsChart');
if (chartEl) {
  (function loadChart() {
    if (typeof Chart !== 'undefined') {
      renderChart();
    } else {
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
      s.onload = renderChart;
      document.head.appendChild(s);
    }
  })();
}

function renderChart() {
  const labels = <?= json_encode($labels) ?>;
  const values = <?= json_encode($values) ?>;

  new Chart(chartEl, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Points',
        data: values,
        backgroundColor: '#1a56db',
        borderRadius: 6,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: '#e2e8f0' }, ticks: { font: { size: 11 } } },
        y: { grid: { display: false }, ticks: { font: { size: 11 } } },
      }
    }
  });
}

// ── User search (award modal) ─────────────────────────────────────────────────
let timer;
document.getElementById('awardUserSearch')?.addEventListener('input', function () {
  const q = this.value.trim();
  clearTimeout(timer);
  if (q.length < 2) { document.getElementById('awardUserResults').innerHTML = ''; return; }
  timer = setTimeout(() => {
    fetch(BASE + '/admin/users/search?q=' + encodeURIComponent(q))
    .then(r=>r.json())
    .then(data => {
      const ul = document.getElementById('awardUserResults');
      ul.innerHTML = '';
      (data.users || []).forEach(u => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action py-2';
        a.style.fontSize = '13px';
        a.innerHTML = '<strong>' + u.first_name + ' ' + u.last_name + '</strong> <span class="text-muted">(' + u.email + ')</span>';
        a.addEventListener('click', function(e) {
          e.preventDefault();
          document.getElementById('awardUserId').value = u.id;
          document.getElementById('awardUserDisplay').textContent = u.first_name + ' ' + u.last_name + ' — ' + u.email;
          document.getElementById('awardUserDisplay').classList.remove('d-none');
          document.getElementById('awardUserSearch').value = u.first_name + ' ' + u.last_name;
          ul.innerHTML = '';
        });
        ul.appendChild(a);
      });
    });
  }, 300);
});

// ── Confirm award ─────────────────────────────────────────────────────────────
document.getElementById('confirmAwardBtn')?.addEventListener('click', function () {
  const userId = document.getElementById('awardUserId').value;
  const points = document.getElementById('awardPoints').value;
  const reason = document.getElementById('awardReason').value;

  if (!userId) { LMS.toast('error', 'Please select a user.'); return; }
  if (!points || points == 0) { LMS.toast('error', 'Enter a non-zero point value.'); return; }

  this.disabled = true;
  fetch(BASE + '/admin/leaderboard/award', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF) +
          '&user_id=' + encodeURIComponent(userId) +
          '&points='  + encodeURIComponent(points) +
          '&reason='  + encodeURIComponent(reason),
  })
  .then(r=>r.json())
  .then(d => {
    if (d.success) {
      bootstrap.Modal.getInstance(document.getElementById('awardModal'))?.hide();
      LMS.toast('success', d.message);
      setTimeout(() => location.reload(), 800);
    } else LMS.toast('error', d.message);
  })
  .finally(() => { this.disabled = false; });
});

// ── Reset user points ─────────────────────────────────────────────────────────
document.querySelectorAll('.btn-reset-user').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id, name = this.dataset.name;
    LMS.confirm('Reset ALL points for "' + name + '"? This cannot be undone.', function () {
      fetch(BASE + '/admin/leaderboard/reset', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF) + '&user_id=' + id,
      })
      .then(r=>r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('lb-row-' + id)?.remove();
          LMS.toast('success', 'Points reset for ' + name + '.');
        } else LMS.toast('error', d.message);
      });
    });
  });
});
</script>

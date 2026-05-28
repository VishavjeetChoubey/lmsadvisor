<?php use App\Core\View; $e = fn($v) => View::e($v); $url = fn($p='') => View::url($p); ?>

<div class="adm-page-header mb-4">
  <div>
    <h2 class="adm-page-title">Database Upgrader</h2>
    <p class="adm-page-sub">Run migrations, check schema status, manage database tables.</p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'info' ? 'info' : 'danger') ?> mb-4">
  <?= $e($flash['message']) ?>
</div>
<?php endif; ?>

<!-- ── Status bar ───────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <?php
  $pending = count(array_filter($migrations, fn($m) => !$m['applied']));
  $applied = count(array_filter($migrations, fn($m) => $m['applied']));
  $cards = [
    ['bi-server',        'DB Version',        $dbVersion['server'],                         '#6366f1'],
    ['bi-check-circle',  'Applied',           $applied . ' / ' . $dbVersion['total'],       '#059669'],
    ['bi-clock-history', 'Pending',           $pending . ' migration' . ($pending !== 1 ? 's' : ''), $pending > 0 ? '#d97706' : '#059669'],
    ['bi-table',         'Tables',            count($tableStats),                            '#0891b2'],
  ];
  foreach ($cards as [$icon,$label,$value,$color]): ?>
  <div class="col-6 col-xl-3">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div style="width:42px;height:42px;border-radius:12px;background:<?= $color ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:18px"></i>
        </div>
        <div>
          <div style="font-size:18px;font-weight:800;color:var(--bs-body-color)"><?= $e($value) ?></div>
          <div style="font-size:12px;color:var(--bs-secondary-color)"><?= $e($label) ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Run all button ───────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <div class="fw-bold mb-1">
        <?php if ($pending > 0): ?>
          <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i></span>
          <?= $pending ?> pending migration<?= $pending !== 1 ? 's' : '' ?> — upgrade recommended
        <?php else: ?>
          <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i></span>
          Database is up to date
        <?php endif; ?>
      </div>
      <div style="font-size:13px;color:var(--bs-secondary-color)">
        Migrations already applied are skipped automatically.
      </div>
    </div>
    <form method="POST" action="<?= $url('admin/database/run-all') ?>" onsubmit="return confirm('Run all pending migrations?')">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Middleware\CsrfMiddleware::token()) ?>">
      <button type="submit" class="btn btn-primary" <?= $pending === 0 ? 'disabled' : '' ?>>
        <i class="bi bi-lightning-charge me-1"></i>
        Run <?= $pending ?> Pending Migration<?= $pending !== 1 ? 's' : '' ?>
      </button>
    </form>
  </div>
</div>

<!-- ── Migrations table ─────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
    <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Migrations</h6>
    <span class="badge bg-secondary"><?= count($migrations) ?> total</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle" style="font-size:13.5px">
      <thead class="table-light">
        <tr>
          <th style="width:28px">#</th>
          <th>File</th>
          <th style="width:90px">Status</th>
          <th style="width:150px">Applied At</th>
          <th style="width:80px">Lines</th>
          <th style="width:140px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($migrations as $i => $m): ?>
        <tr>
          <td class="text-muted"><?= $i + 1 ?></td>
          <td>
            <code style="font-size:12.5px"><?= $e($m['file']) ?></code>
          </td>
          <td>
            <?php if ($m['applied']): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-check2"></i> Applied
              </span>
            <?php else: ?>
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                <i class="bi bi-clock"></i> Pending
              </span>
            <?php endif; ?>
          </td>
          <td style="color:var(--bs-secondary-color)">
            <?= $m['applied_at'] ? date('d M Y H:i', strtotime($m['applied_at'])) : '—' ?>
          </td>
          <td style="color:var(--bs-secondary-color)"><?= number_format($m['lines']) ?></td>
          <td>
            <div class="d-flex gap-1">
              <!-- Run / Re-run -->
              <form method="POST" action="<?= $url('admin/database/run-one') ?>"
                    onsubmit="return confirm('<?= $m['applied'] ? 'Force re-run this migration? This may cause errors on already-applied changes.' : 'Run this migration?' ?>')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Middleware\CsrfMiddleware::token()) ?>">
                <input type="hidden" name="file" value="<?= $e($m['file']) ?>">
                <?php if ($m['applied']): ?>
                  <input type="hidden" name="force" value="1">
                  <button type="submit" class="btn btn-sm btn-outline-secondary" title="Force re-run">
                    <i class="bi bi-arrow-clockwise"></i>
                  </button>
                <?php else: ?>
                  <button type="submit" class="btn btn-sm btn-outline-primary" title="Run now">
                    <i class="bi bi-play-fill"></i> Run
                  </button>
                <?php endif; ?>
              </form>

              <!-- View SQL -->
              <button class="btn btn-sm btn-outline-secondary"
                      onclick="viewSql('<?= $e($m['file']) ?>')" title="View SQL">
                <i class="bi bi-code-slash"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Tables list ──────────────────────────────────────────────────────── -->
<?php if (!empty($tableStats)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
    <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>Database Tables</h6>
    <span class="badge bg-secondary"><?= count($tableStats) ?> tables</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle" style="font-size:13px">
      <thead class="table-light">
        <tr>
          <th>Table</th>
          <th style="width:100px">Rows</th>
          <th style="width:100px">Size</th>
          <th style="width:160px">Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tableStats as $t): ?>
        <tr>
          <td><code style="font-size:12.5px"><?= $e($t['TABLE_NAME']) ?></code></td>
          <td style="color:var(--bs-secondary-color)"><?= number_format((int)$t['TABLE_ROWS']) ?></td>
          <td style="color:var(--bs-secondary-color)"><?= $t['size_kb'] ?> KB</td>
          <td style="color:var(--bs-secondary-color)"><?= $t['CREATE_TIME'] ? date('d M Y', strtotime($t['CREATE_TIME'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── SQL viewer modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="sqlModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="sqlModalTitle">Migration SQL</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <pre id="sqlModalContent" style="margin:0;padding:20px;font-size:12.5px;background:#0f172a;color:#e2e8f0;border-radius:0;min-height:200px;overflow-x:auto"></pre>
      </div>
    </div>
  </div>
</div>

<script>
var sqlFiles = <?= json_encode(array_column($migrations, 'file')) ?>;

function viewSql(filename) {
  fetch('<?= $url('admin/database/view-sql') ?>?file=' + encodeURIComponent(filename), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(d => {
    document.getElementById('sqlModalTitle').textContent = filename;
    document.getElementById('sqlModalContent').textContent = d.sql || 'Could not load file.';
    new bootstrap.Modal(document.getElementById('sqlModal')).show();
  })
  .catch(() => alert('Could not load SQL file.'));
}
</script>

<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$pu  = $profile_user;
?>
<div class="mb-3 d-flex align-items-center gap-3">
  <a href="<?= $url('admin/users/' . $pu['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back to User
  </a>
  <span style="font-size:15px;font-weight:700"><?= $e($pu['first_name'] . ' ' . $pu['last_name']) ?></span>
  <span class="text-muted"><?= $e($pu['email']) ?></span>
</div>

<div class="card lms-card">
  <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Session History</h5>
    <span class="badge bg-secondary"><?= count($sessions) ?> sessions</span>
  </div>
  <?php if (empty($sessions)): ?>
    <div class="card-body text-center py-5 text-muted">
      <i class="bi bi-clock-history" style="font-size:3rem;opacity:.2"></i>
      <h6 class="mt-3">No sessions recorded</h6>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead><tr>
        <th>Session ID</th><th>IP Address</th><th>User Agent</th><th>Created</th><th>Last Active</th>
      </tr></thead>
      <tbody>
        <?php foreach ($sessions as $s): ?>
        <tr>
          <td style="font-family:monospace;font-size:12px"><?= $e(substr($s['id'] ?? '', 0, 12)) ?>…</td>
          <td style="font-size:13px"><?= $e($s['ip_address'] ?? '—') ?></td>
          <td style="font-size:12px;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= $e($s['user_agent'] ?? '') ?>">
            <?= $e(mb_strimwidth($s['user_agent'] ?? '—', 0, 50, '…')) ?>
          </td>
          <td style="font-size:12.5px"><?= $s['created_at'] ? date('d M Y H:i', strtotime($s['created_at'])) : '—' ?></td>
          <td style="font-size:12.5px"><?= $s['last_activity_at'] ? date('d M Y H:i', strtotime($s['last_activity_at'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

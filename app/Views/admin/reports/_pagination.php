<?php
use App\Core\View;
$url = fn(string $p = ''): string => View::url($p);
if (($totalPages ?? 1) <= 1) return;

// Build base query string preserving all current filters except page
$params = $_GET;
unset($params['page']);
$base = '?' . http_build_query($params) . '&page=';
?>
<div class="card-footer d-flex justify-content-between align-items-center py-3 px-4">
  <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
  <nav>
    <ul class="pagination pagination-sm mb-0">
      <?php if ($page > 1): ?>
      <li class="page-item"><a class="page-link" href="<?= $base . ($page-1) ?>"><i class="bi bi-chevron-left"></i></a></li>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="<?= $base . $p ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <li class="page-item"><a class="page-link" href="<?= $base . ($page+1) ?>"><i class="bi bi-chevron-right"></i></a></li>
      <?php endif; ?>
    </ul>
  </nav>
</div>

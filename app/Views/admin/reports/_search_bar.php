<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>
<div class="card lms-card mb-4">
  <div class="card-body py-3 px-4">
    <form method="GET" action="<?= $url('admin/reports') ?>" id="reportSearchForm">
      <input type="hidden" name="tab" value="<?= $e($activeTab) ?>">
      <div class="input-group" style="max-width:400px">
        <span class="input-group-text bg-transparent border-end-0">
          <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" class="form-control border-start-0 ps-0"
               name="search" id="reportSearch"
               placeholder="Search…" value="<?= $e($search) ?>">
        <?php if ($search): ?>
        <a href="<?= $url('admin/reports?tab=' . $activeTab) ?>"
           class="btn btn-outline-secondary">
          <i class="bi bi-x"></i>
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<script>
let _rst;
document.getElementById('reportSearch')?.addEventListener('input', function () {
  clearTimeout(_rst);
  _rst = setTimeout(() => document.getElementById('reportSearchForm').submit(), 400);
});
</script>

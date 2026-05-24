<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>
<div class="admin-breadcrumb">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item">
        <a href="<?= $url('admin/dashboard') ?>"><i class="bi bi-house-door"></i></a>
      </li>
      <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <?php $last = ($i === count($breadcrumbs) - 1); ?>
        <li class="breadcrumb-item <?= $last ? 'active' : '' ?>">
          <?php if (!$last && isset($crumb['url'])): ?>
            <a href="<?= $e($crumb['url']) ?>"><?= $e($crumb['label']) ?></a>
          <?php else: ?>
            <?= $e($crumb['label']) ?>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </nav>
</div>

<?php
use App\Core\View;
$e = fn(mixed $v): string => View::e($v);

$flash    = $flash ?? [];
$typeMap  = [
    'success' => ['bg-success', 'bi-check-circle'],
    'error'   => ['bg-danger',  'bi-x-circle'],
    'warning' => ['bg-warning', 'bi-exclamation-triangle'],
    'info'    => ['bg-primary', 'bi-info-circle'],
];
?>
<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999" id="toastContainer">
  <?php foreach ($flash as $type => $message): ?>
    <?php [$bgClass, $icon] = $typeMap[$type] ?? ['bg-secondary', 'bi-bell']; ?>
    <div class="toast show align-items-center text-white <?= $bgClass ?> border-0 mb-2"
         role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi <?= $icon ?>"></i>
          <?= $e($message) ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  <?php endforeach; ?>
</div>

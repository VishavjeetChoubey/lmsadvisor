<?php
use App\Services\ImpersonationService;
use App\Core\View;
use App\Middleware\CsrfMiddleware;

if (!ImpersonationService::isImpersonating()) return;

$url  = fn(string $p = ''): string => View::url($p);
$name = ImpersonationService::impersonatorName();
?>
<div class="impersonation-banner">
  <i class="fas fa-user-secret me-2"></i>
  You are viewing as <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
  &nbsp;·&nbsp; Originally signed in as <strong><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></strong>
  <form action="<?= $url('admin/impersonate/revert') ?>" method="POST" class="d-inline ms-3">
    <?= CsrfMiddleware::field() ?>
    <button type="submit" class="btn btn-sm btn-warning fw-semibold">
      <i class="bi bi-arrow-left-circle me-1"></i> Return to Admin
    </button>
  </form>
</div>

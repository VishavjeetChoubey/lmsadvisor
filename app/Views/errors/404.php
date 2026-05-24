<?php
use App\Core\View;
$url = fn(string $p = ''): string => View::url($p);
?>
<div class="auth-card text-center">
  <div style="font-size:5rem;font-weight:700;color:var(--primary);line-height:1">404</div>
  <h2 class="mt-2 mb-1" style="color:var(--text-primary)">Page Not Found</h2>
  <p style="color:var(--text-muted)">The page you're looking for doesn't exist or has been moved.</p>
  <a href="<?= $url('admin/dashboard') ?>" class="btn btn-primary mt-3">
    <i class="bi bi-house-door me-2"></i> Back to Dashboard
  </a>
</div>

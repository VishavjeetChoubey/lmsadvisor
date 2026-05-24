<?php
use App\Core\View;
use App\Services\AuthService;
$url      = fn(string $p = ''): string => View::url($p);
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user() ?? [];
$firstName = explode(' ', $authUser['name'] ?? 'Student')[0];
?>
<header class="student-topbar">

  <!-- Hamburger — always visible on desktop, triggers sidebar collapse -->
  <button class="topbar-toggle" id="studentSidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
  </button>

  <!-- Brand (mobile — shown when sidebar is hidden) -->
  <div class="d-flex align-items-center gap-2 d-lg-none">
    <div style="width:28px;height:28px;background:#6366f1;border-radius:8px;display:flex;align-items:center;justify-content:center">
      <i class="fas fa-graduation-cap" style="font-size:13px;color:#fff"></i>
    </div>
    <span style="font-weight:700;font-size:15px;color:var(--text-primary)">LMSAdvisor</span>
  </div>

  <div class="topbar-title d-none d-lg-block"><?= $e($page_title ?? 'Dashboard') ?></div>
  <div class="flex-grow-1"></div>

  <!-- Dark mode -->
  <button class="topbar-icon-btn" id="darkModeToggle" title="Toggle dark mode">
    <i class="bi bi-moon-stars"></i>
  </button>

  <!-- User dropdown (desktop) -->
  <div class="dropdown d-none d-lg-block">
    <button class="topbar-user-btn dropdown-toggle" data-bs-toggle="dropdown">
      <span class="topbar-avatar">
        <i class="fas fa-graduation-cap" style="font-size:.7rem;color:#6366f1"></i>
      </span>
      <span class="topbar-username"><?= $e($firstName) ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end topbar-dropdown shadow-sm">
      <li class="px-3 py-2 border-bottom">
        <div style="font-size:12.5px;font-weight:600"><?= $e($authUser['name'] ?? '') ?></div>
        <div style="font-size:11px;color:var(--text-muted)"><?= $e($authUser['email'] ?? '') ?></div>
      </li>
      <li><a class="dropdown-item mt-1" href="<?= $url('learn/profile') ?>">
        <i class="bi bi-person me-2"></i> Profile & Settings
      </a></li>
      <li><hr class="dropdown-divider my-1"></li>
      <li><a class="dropdown-item text-danger" href="<?= $url('logout') ?>">
        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
      </a></li>
    </ul>
  </div>
</header>

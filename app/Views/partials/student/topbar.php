<?php
use App\Core\View;
use App\Services\AuthService;
$url      = fn(string $p = ''): string => View::url($p);
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user() ?? [];
$firstName = explode(' ', $authUser['name'] ?? 'Student')[0];
?>
<header class="student-topbar">
  <button class="topbar-toggle" id="studentSidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
  </button>
  <div class="topbar-title"><?= $e($page_title ?? 'My Dashboard') ?></div>
  <div class="flex-grow-1"></div>

  <button class="topbar-icon-btn" id="darkModeToggle" title="Toggle dark mode">
    <i class="bi bi-moon-stars"></i>
  </button>
  <button class="topbar-icon-btn position-relative" title="Notifications">
    <i class="bi bi-bell"></i>
  </button>

  <div class="dropdown">
    <button class="topbar-user-btn dropdown-toggle" data-bs-toggle="dropdown">
      <span class="topbar-avatar">
        <i class="fas fa-graduation-cap" style="font-size:.7rem;color:#0e9f6e"></i>
      </span>
      <span class="topbar-username d-none d-sm-inline"><?= $e($firstName) ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end topbar-dropdown shadow-sm">
      <li class="px-3 py-2 border-bottom">
        <div style="font-size:12.5px;font-weight:600;color:var(--text-primary)"><?= $e($authUser['name'] ?? '') ?></div>
        <div style="font-size:11px;color:var(--text-muted)"><?= $e($authUser['email'] ?? '') ?></div>
      </li>
      <li><a class="dropdown-item mt-1" href="<?= $url('learn/profile') ?>">
        <i class="bi bi-person me-2"></i> My Profile
      </a></li>
      <li><hr class="dropdown-divider my-1"></li>
      <li><a class="dropdown-item text-danger" href="<?= $url('logout') ?>">
        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
      </a></li>
    </ul>
  </div>
</header>

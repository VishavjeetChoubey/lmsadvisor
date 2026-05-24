<?php
use App\Core\View;
use App\Services\AuthService;
$url      = fn(string $p = ''): string => View::url($p);
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user() ?? [];
$role     = $authUser['role'] ?? 'admin';

$roleIcon = match($role) {
    'super_admin' => '<i class="fas fa-crown" style="font-size:.7rem;color:#e3a008"></i>',
    'admin'       => '<i class="fas fa-shield-alt" style="font-size:.7rem;color:#1a56db"></i>',
    'manager'     => '<i class="fas fa-briefcase" style="font-size:.7rem;color:#0891b2"></i>',
    default       => '<i class="fas fa-graduation-cap" style="font-size:.7rem;color:#0e9f6e"></i>',
};
$firstName = explode(' ', $authUser['name'] ?? 'Admin')[0];
?>
<header class="admin-topbar">
  <button class="topbar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
  </button>

  <div class="topbar-title"><?= $e($page_title ?? 'Dashboard') ?></div>
  <div class="flex-grow-1"></div>

  <!-- Notification bell with live unread count -->
  <div class="dropdown">
    <button class="topbar-icon-btn position-relative" id="notifBell" data-bs-toggle="dropdown" title="Notifications">
      <i class="bi bi-bell"></i>
      <span class="notif-badge d-none" id="notifBadge">0</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow" id="notifDropdown"
         style="width:340px;max-height:420px;overflow:hidden;border-radius:14px;margin-top:8px">
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
        <span class="fw-semibold" style="font-size:14px">Notifications</span>
        <button class="btn btn-xs btn-outline-secondary" id="markAllRead" style="font-size:11px;border-radius:6px;padding:2px 8px">
          Mark all read
        </button>
      </div>
      <div id="notifList" style="max-height:340px;overflow-y:auto">
        <div class="text-center py-4 text-muted" style="font-size:13px" id="notifEmpty">
          <i class="bi bi-bell-slash" style="font-size:1.5rem;opacity:.3"></i><br>No notifications
        </div>
      </div>
    </div>
  </div>

  <div class="topbar-divider"></div>

  <div class="dropdown">
    <button class="topbar-user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
      <span class="topbar-avatar"><?= $roleIcon ?></span>
      <span class="topbar-username d-none d-sm-inline"><?= $e($firstName) ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end topbar-dropdown shadow-sm">
      <li class="px-3 py-2 border-bottom">
        <div style="font-size:12.5px;font-weight:600;color:var(--text-primary)"><?= $e($authUser['name'] ?? '') ?></div>
        <div style="font-size:11px;color:var(--text-muted)"><?= $e($authUser['email'] ?? '') ?></div>
      </li>
      <li><a class="dropdown-item mt-1" href="<?= $url('admin/settings') ?>">
        <i class="bi bi-gear me-2"></i> Settings
      </a></li>
      <li><hr class="dropdown-divider my-1"></li>
      <li><a class="dropdown-item text-danger" href="<?= $url('logout') ?>">
        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
      </a></li>
    </ul>
  </div>
</header>

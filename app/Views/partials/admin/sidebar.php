<?php
use App\Core\View;
use App\Services\AuthService;
$url      = fn(string $p = ''): string => View::url($p);
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user() ?? [];
$role     = $authUser['role'] ?? 'admin';

// Role icon map
$roleIcon = match($role) {
    'super_admin' => '<i class="fas fa-crown text-warning"></i>',
    'admin'       => '<i class="fas fa-shield-alt text-primary"></i>',
    'manager'     => '<i class="fas fa-briefcase text-info"></i>',
    default       => '<i class="fas fa-graduation-cap text-success"></i>',
};

// Active detection
$path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = fn(string $seg): string => str_contains($path, $seg) ? 'active' : '';
?>
<aside class="admin-sidebar" id="adminSidebar">

  <div class="sidebar-brand">
    <div class="brand-logo"><i class="fas fa-graduation-cap"></i></div>
    <span class="brand-name">LMSAdvisor</span>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group-label">MAIN</div>
    <a href="<?= $url('admin/dashboard') ?>" class="nav-item <?= $isActive('/admin/dashboard') ?>">
      <i class="bi bi-speedometer2"></i><span>Dashboard</span>
    </a>

    <div class="nav-group-label">CONTENT</div>
    <a href="<?= $url('admin/courses') ?>" class="nav-item <?= $isActive('/admin/courses') ?>">
      <i class="bi bi-book"></i><span>Courses</span>
    </a>
    <a href="<?= $url('admin/enrollments') ?>" class="nav-item <?= $isActive('/admin/enrollments') ?>">
      <i class="bi bi-person-check"></i><span>Enrollments</span>
    </a>

    <div class="nav-group-label">LEARNING</div>
    <a href="<?= $url('admin/users') ?>" class="nav-item <?= $isActive('/admin/users') ?>">
      <i class="bi bi-people"></i><span>Users</span>
    </a>
    <a href="<?= $url('admin/forum') ?>" class="nav-item <?= $isActive('/admin/forum') ?>">
      <i class="bi bi-chat-dots"></i><span>Forum</span>
    </a>
    <a href="<?= $url('admin/reviews') ?>" class="nav-item <?= $isActive('/admin/reviews') ?>">
      <i class="bi bi-star"></i><span>Reviews</span>
    </a>
    <a href="<?= $url('admin/leaderboard') ?>" class="nav-item <?= $isActive('/admin/leaderboard') ?>">
      <i class="bi bi-trophy"></i><span>Leaderboard</span>
    </a>
    <a href="<?= $url('admin/knowledge-base') ?>" class="nav-item <?= $isActive('/admin/knowledge-base') ?>">
      <i class="bi bi-journals"></i><span>Knowledge Base</span>
    </a>

    <div class="nav-group-label">ANALYTICS</div>
    <a href="<?= $url('admin/reports') ?>" class="nav-item <?= $isActive('/admin/reports') ?>">
      <i class="bi bi-bar-chart-line"></i><span>Reports</span>
    </a>

    <div class="nav-group-label">SYSTEM</div>
    <a href="<?= $url('admin/api') ?>" class="nav-item <?= $isActive('/admin/api') ?>">
      <i class="bi bi-braces-asterisk"></i><span>API Management</span>
    </a>
    <a href="<?= $url('admin/settings') ?>" class="nav-item <?= $isActive('/admin/settings') ?>">
      <i class="bi bi-gear"></i><span>Settings</span>
    </a>
  </nav>

  <div class="sidebar-user">
    <div class="user-avatar"><?= $roleIcon ?></div>
    <div class="user-info">
      <div class="user-name"><?= $e($authUser['name'] ?? 'Admin') ?></div>
      <div class="user-role"><?= $e($authUser['role_display'] ?? ucfirst($role)) ?></div>
    </div>
    <a href="<?= $url('logout') ?>" class="sidebar-logout" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>

</aside>

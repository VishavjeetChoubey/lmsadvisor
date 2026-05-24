<?php
use App\Core\View;
use App\Services\AuthService;
$url      = fn(string $p = ''): string => View::url($p);
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user() ?? [];
$path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = fn(string $seg): string => str_contains($path, $seg) ? 'active' : '';
?>
<aside class="student-sidebar" id="studentSidebar">

  <div class="sidebar-brand">
    <div class="brand-logo"><i class="fas fa-graduation-cap"></i></div>
    <span class="brand-name">LMSAdvisor</span>
  </div>

  <nav class="sidebar-nav">
    <a href="<?= $url('learn/dashboard') ?>" class="nav-item <?= $isActive('/learn/dashboard') ?>">
      <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
    </a>
    <a href="<?= $url('learn/courses') ?>" class="nav-item <?= $isActive('/learn/courses') ?>">
      <i class="bi bi-book-half"></i><span>My Courses</span>
    </a>
    <a href="<?= $url('learn/calendar') ?>" class="nav-item <?= $isActive('/learn/calendar') ?>">
      <i class="bi bi-calendar3"></i><span>Calendar</span>
    </a>
    <a href="<?= $url('learn/leaderboard') ?>" class="nav-item <?= $isActive('/learn/leaderboard') ?>">
      <i class="bi bi-trophy"></i><span>Leaderboard</span>
    </a>
    <a href="<?= $url('learn/profile') ?>" class="nav-item <?= $isActive('/learn/profile') ?>">
      <i class="bi bi-person-circle"></i><span>Profile</span>
    </a>
  </nav>

  <div class="sidebar-user">
    <div class="user-avatar">
      <i class="fas fa-graduation-cap text-success" style="font-size:13px"></i>
    </div>
    <div class="user-info">
      <div class="user-name"><?= $e($authUser['name'] ?? 'Student') ?></div>
      <div class="user-role">Learner</div>
    </div>
    <a href="<?= $url('logout') ?>" class="sidebar-logout" title="Sign Out">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>

</aside>

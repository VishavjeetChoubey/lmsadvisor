<?php
use App\Core\View;
use App\Services\AuthService;
$url      = fn(string $p = ''): string => View::url($p);
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user() ?? [];
$role     = $authUser['role'] ?? 'admin';
$fullName = $authUser['name'] ?? 'Admin';
$initials = strtoupper(substr($fullName, 0, 1));
$path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = fn(string $seg): string => str_contains($path, $seg) ? 'active' : '';

$navItems = [
  // [label, icon, url, match-segment]
  ['Dashboard',     'bi-speedometer2',      'admin/dashboard',     '/admin/dashboard'],
  ['Analytics',    'bi-bar-chart-fill',    'admin/analytics',    '/admin/analytics'],
  ['Courses',       'bi-book-fill',         'admin/courses',       '/admin/courses'],
  ['Learning Paths','bi-signpost-2-fill',   'admin/learning-paths','/admin/learning-paths'],
  ['Groups',        'bi-people-fill',       'admin/groups',        '/admin/groups'],
  ['Assignments',   'bi-clipboard-check-fill','admin/courses',     '/admin/courses'],
  ['Badges',        'bi-award-fill',        'admin/badges',        '/admin/badges'],
  ['Email',         'bi-envelope-fill',     'admin/email',         '/admin/email'],
  ['Enrollments',   'bi-person-check-fill', 'admin/enrollments',   '/admin/enrollments'],
  ['Users',         'bi-person-lines-fill', 'admin/users',         '/admin/users'],
  ['Categories',    'bi-grid-fill',         'admin/categories',    '/admin/categories'],
  ['Quizzes',       'bi-patch-question-fill','admin/quizzes',      '/admin/quizzes'],
  ['Forum',         'bi-chat-dots-fill',    'admin/forum',         '/admin/forum'],
  ['Reviews',       'bi-star-fill',         'admin/reviews',       '/admin/reviews'],
  ['Leaderboard',   'bi-trophy-fill',       'admin/leaderboard',   '/admin/leaderboard'],
  ['Knowledge Base','bi-journals',          'admin/knowledge-base','/admin/knowledge-base'],
  ['Webinars',      'bi-camera-video-fill', 'admin/webinars',      '/admin/webinars'],
  ['Reports',       'bi-bar-chart-line-fill','admin/reports',      '/admin/reports'],
  ['API',           'bi-braces-asterisk',   'admin/api',           '/admin/api'],
  ['Settings',      'bi-gear-fill',         'admin/settings',      '/admin/settings'],
];
?>
<aside class="adm-sidebar" id="adminSidebar">

  <!-- Brand logo at very top -->
  <div class="adm-brand">
    <div class="adm-brand-icon"><i class="fas fa-graduation-cap"></i></div>
    <span class="adm-brand-name">LMSAdvisor</span>
  </div>

  <!-- Nav items — icon + label centered -->
  <nav class="adm-nav" id="adminNav">
    <?php foreach ($navItems as [$label, $icon, $href, $seg]): ?>
    <a href="<?= $url($href) ?>"
       class="adm-nav-btn <?= $isActive($seg) ?>"
       title="<?= $e($label) ?>">
      <i class="bi <?= $icon ?>"></i>
      <span><?= $e($label) ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- User + logout at bottom -->
  <div class="adm-sidebar-bottom">
    <div class="adm-user-row">
      <div class="adm-user-avatar"><?= $initials ?></div>
      <div class="adm-user-info">
        <div class="adm-user-name"><?= $e($fullName) ?></div>
        <div class="adm-user-role"><?= $e($authUser['role_display'] ?? ucfirst($role)) ?></div>
      </div>
    </div>
    <a href="<?= $url('logout') ?>" class="adm-nav-btn adm-logout" title="Sign out">
      <i class="bi bi-box-arrow-right"></i>
      <span>Sign Out</span>
    </a>
  </div>

</aside>

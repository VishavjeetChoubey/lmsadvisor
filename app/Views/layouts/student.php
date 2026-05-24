<?php
use App\Core\View;
use App\Services\AuthService;
$e         = fn(mixed $v): string => View::e($v);
$asset     = fn(string $p): string => View::asset($p);
$url       = fn(string $p = ''): string => View::url($p);
$authUser  = AuthService::user() ?? [];
$firstName = explode(' ', $authUser['name'] ?? 'Student')[0];
$initials  = strtoupper(substr($authUser['name'] ?? 'S', 0, 1));

$path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = fn(string $seg): string => str_contains($path, $seg) ? 'active' : '';

// Is this the lesson player page? (mini sidebar mode)
$isPlayer = preg_match('#/learn/courses/[^/]+/learn#', $path);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#1e1b4b">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title><?= $e($title ?? 'LMSAdvisor') ?></title>
  <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
  <link rel="icon" type="image/png" href="<?= $asset('icons/favicon.png') ?>">
  <link rel="apple-touch-icon" href="<?= $asset('icons/icon-192.png') ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
</head>
<body class="student-body <?= $isPlayer ? 'player-mode' : '' ?>">

<!-- ══════════════════════════════════════════════════════
     LEFT SIDEBAR
     - Full (220px) on normal pages
     - Mini icon-only (60px) on lesson player
     - Hidden on mobile (bottom nav replaces it)
══════════════════════════════════════════════════════ -->
<aside class="st-sidebar <?= $isPlayer ? 'st-sidebar--mini' : '' ?>" id="stSidebar">

  <!-- Brand -->
  <div class="st-brand">
    <div class="st-brand-logo">
      <i class="fas fa-graduation-cap"></i>
    </div>
    <span class="st-brand-name">LMSAdvisor</span>
  </div>

  <!-- Navigation -->
  <nav class="st-nav">
    <?php
    $navItems = [
      ['/learn/dashboard', 'bi-grid-1x2-fill', 'Dashboard',   '/learn/dashboard', '/learn/'],
      ['/learn/courses',   'bi-book-half',      'My Courses',  '/learn/courses'],
      ['/learn/calendar',  'bi-calendar3',      'Calendar',    '/learn/calendar'],
      ['/learn/leaderboard','bi-trophy',         'Rankings',    '/learn/leaderboard'],
      ['/learn/profile',   'bi-person-circle',  'Profile',     '/learn/profile'],
    ];
    foreach ($navItems as $nav):
      $href       = $nav[0];
      $icon       = $nav[1];
      $label      = $nav[2];
      $activeSegs = array_slice($nav, 3);
      $active = array_reduce($activeSegs, fn($carry, $seg) =>
        $carry || str_contains($path, $seg) || $path === $seg, false);
      if ($path === '/learn' || $path === '/learn/') $active = $href === '/learn/dashboard';
    ?>
    <a href="<?= $url(ltrim($href,'/')) ?>"
       class="st-nav-item <?= $active ? 'active' : '' ?>"
       title="<?= $label ?>">
      <i class="bi <?= $icon ?> st-nav-icon"></i>
      <span class="st-nav-label"><?= $label ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- User section at bottom -->
  <div class="st-sidebar-footer">
    <div class="st-user">
      <div class="st-user-avatar"><?= $initials ?></div>
      <div class="st-user-info">
        <div class="st-user-name"><?= $e($firstName) ?></div>
        <div class="st-user-role">Learner</div>
      </div>
    </div>
    <a href="<?= $url('logout') ?>" class="st-logout" title="Sign Out">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>

</aside>

<!-- ══════════════════════════════════════════════════════
     MAIN WRAPPER (shifts right of sidebar)
══════════════════════════════════════════════════════ -->
<div class="st-main" id="stMain">

  <!-- Top bar (inside main wrapper) -->
  <header class="st-topbar" id="stTopbar">
    <!-- Mobile hamburger -->
    <button class="st-hamburger d-lg-none" id="stHamburger">
      <i class="bi bi-list"></i>
    </button>

    <!-- Page title -->
    <div class="st-topbar-title"><?= $e($page_title ?? '') ?></div>

    <div class="st-topbar-right">
      <!-- Dark mode toggle -->
      <button class="st-icon-btn" id="darkModeToggle" title="Toggle dark mode">
        <i class="bi bi-moon-stars"></i>
      </button>

      <!-- Notification bell (placeholder) -->
      <button class="st-icon-btn" title="Notifications">
        <i class="bi bi-bell"></i>
      </button>

      <!-- User dropdown -->
      <div class="dropdown">
        <button class="st-user-btn dropdown-toggle" data-bs-toggle="dropdown">
          <div class="st-topbar-avatar"><?= $initials ?></div>
          <span class="d-none d-md-inline st-topbar-name"><?= $e($firstName) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:220px;font-size:13.5px;border-radius:12px;margin-top:8px;border:1px solid var(--border-color)">
          <li class="px-3 py-2 border-bottom">
            <div style="font-weight:700;font-size:14px"><?= $e($authUser['name'] ?? '') ?></div>
            <div style="font-size:11.5px;color:var(--text-muted)"><?= $e($authUser['email'] ?? '') ?></div>
          </li>
          <li><a class="dropdown-item py-2" href="<?= $url('learn/profile') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
          <li><a class="dropdown-item py-2" href="<?= $url('learn/courses') ?>"><i class="bi bi-book-half me-2"></i>My Courses</a></li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><a class="dropdown-item py-2 text-danger" href="<?= $url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- Page content -->
  <main class="st-content" id="stContent">
    <?= $content ?>
  </main>

</div><!-- /.st-main -->

<!-- ══════════════════════════════════════════════════════
     BOTTOM NAV — mobile only
══════════════════════════════════════════════════════ -->
<nav class="st-bottom-nav" id="bottomNav">
  <div class="st-bottom-items">
    <?php
    $bottomItems = [
      ['/learn/dashboard', 'bi-grid-1x2-fill','bi-grid-1x2',    'Home'],
      ['/learn/courses',   'bi-book-fill',    'bi-book-half',   'Courses'],
      ['/learn/calendar',  'bi-calendar-fill','bi-calendar3',   'Calendar'],
      ['/learn/leaderboard','bi-trophy-fill', 'bi-trophy',      'Rankings'],
      ['/learn/profile',   'bi-person-fill',  'bi-person-circle','Profile'],
    ];
    foreach ($bottomItems as [$href, $activeIcon, $inactiveIcon, $label]):
      $isAct = str_contains($path, parse_url($href, PHP_URL_PATH));
      if ($href === '/learn/dashboard' && ($path === '/learn' || $path === '/learn/')) $isAct = true;
    ?>
    <a href="<?= $url(ltrim($href,'/')) ?>" class="st-bottom-btn <?= $isAct?'active':'' ?>">
      <i class="bi <?= $isAct ? $activeIcon : $inactiveIcon ?>"></i>
      <span><?= $label ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</nav>

<!-- Mobile sidebar overlay -->
<div class="st-overlay" id="stOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $asset('js/app.js') ?>"></script>
<script>
if ('serviceWorker' in navigator) navigator.serviceWorker.register('<?= APP_URL ?>/sw.js');
</script>
</body>
</html>

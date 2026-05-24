<?php
use App\Core\View;
$e     = fn(mixed $v): string => View::e($v);
$asset = fn(string $p): string => View::asset($p);
$url   = fn(string $p = ''): string => View::url($p);
use App\Services\AuthService;
$authUser  = AuthService::user() ?? [];
$firstName = explode(' ', $authUser['name'] ?? 'Student')[0];

$path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = fn(string $seg): string => str_contains($path, $seg) ? 'active' : '';

// Detect lesson player page — it needs no padding, special shell
$isPlayerPage = str_contains($path, '/learn') && str_contains($path, '/learn/courses/') && str_contains($path, '/learn');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#6366f1">
  <meta name="apple-mobile-web-app-capable" content="yes">
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
<body class="student-body">

<!-- ── TOP NAV ─────────────────────────────────────────────────────────────── -->
<header class="student-topnav" id="studentTopnav">
  <div class="topnav-left">
    <a href="<?= $url('learn/dashboard') ?>" class="topnav-brand">
      <div class="topnav-logo"><i class="fas fa-graduation-cap"></i></div>
      <span class="topnav-brand-name">LMSAdvisor</span>
    </a>
    <!-- Page title for non-player pages -->
    <span class="topnav-divider d-none d-lg-block"></span>
    <span class="topnav-page-title d-none d-lg-block"><?= $e($page_title ?? '') ?></span>
  </div>

  <!-- Nav links — desktop -->
  <nav class="topnav-links d-none d-lg-flex">
    <a href="<?= $url('learn/dashboard') ?>" class="topnav-link <?= $isActive('/learn/dashboard') || $path === '/learn' || $path === '/learn/' ? 'active' : '' ?>">
      <i class="bi bi-grid-1x2"></i> Dashboard
    </a>
    <a href="<?= $url('learn/courses') ?>" class="topnav-link <?= str_contains($path,'/learn/courses') && !str_contains($path,'/learn') ? 'active' : (str_contains($path,'/learn/courses') ? 'active' : '') ?>">
      <i class="bi bi-book-half"></i> My Courses
    </a>
    <a href="<?= $url('learn/calendar') ?>" class="topnav-link <?= $isActive('/learn/calendar') ?>">
      <i class="bi bi-calendar3"></i> Calendar
    </a>
    <a href="<?= $url('learn/leaderboard') ?>" class="topnav-link <?= $isActive('/learn/leaderboard') ?>">
      <i class="bi bi-trophy"></i> Rankings
    </a>
  </nav>

  <div class="topnav-right">
    <!-- Dark mode -->
    <button class="topnav-icon-btn" id="darkModeToggle" title="Toggle dark mode">
      <i class="bi bi-moon-stars"></i>
    </button>
    <!-- User menu -->
    <div class="dropdown">
      <button class="topnav-user-btn dropdown-toggle" data-bs-toggle="dropdown">
        <div class="topnav-avatar">
          <?= strtoupper(substr($authUser['name'] ?? 'S', 0, 1)) ?>
        </div>
        <span class="d-none d-lg-inline"><?= $e($firstName) ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:220px;font-size:13.5px;border-radius:12px;margin-top:8px">
        <li class="px-3 py-2 border-bottom">
          <div style="font-weight:700;font-size:14px"><?= $e($authUser['name'] ?? '') ?></div>
          <div style="font-size:12px;color:var(--text-muted)"><?= $e($authUser['email'] ?? '') ?></div>
        </li>
        <li><a class="dropdown-item py-2" href="<?= $url('learn/profile') ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
        <li><a class="dropdown-item py-2" href="<?= $url('learn/courses') ?>"><i class="bi bi-book-half me-2"></i>My Courses</a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item py-2 text-danger" href="<?= $url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
      </ul>
    </div>
  </div>
</header>

<!-- ── MAIN CONTENT ─────────────────────────────────────────────────────────── -->
<main class="student-main-content" id="studentMainContent">
  <?= $content ?>
</main>

<!-- ── BOTTOM NAV (mobile / PWA) ────────────────────────────────────────────── -->
<nav class="student-bottom-nav" id="bottomNav">
  <div class="nav-items">
    <a href="<?= $url('learn/dashboard') ?>" class="nav-item-btn <?= str_contains($path,'/learn/dashboard')||$path==='/learn'||$path==='/learn/'?'active':'' ?>">
      <i class="bi <?= str_contains($path,'/learn/dashboard')?'bi-grid-fill':'bi-grid-1x2' ?>"></i>
      <span>Home</span>
    </a>
    <a href="<?= $url('learn/courses') ?>" class="nav-item-btn <?= str_contains($path,'/learn/courses')?'active':'' ?>">
      <i class="bi <?= str_contains($path,'/learn/courses')?'bi-book-fill':'bi-book-half' ?>"></i>
      <span>Courses</span>
    </a>
    <a href="<?= $url('learn/calendar') ?>" class="nav-item-btn <?= $isActive('/learn/calendar') ?>">
      <i class="bi <?= str_contains($path,'/learn/calendar')?'bi-calendar-fill':'bi-calendar3' ?>"></i>
      <span>Calendar</span>
    </a>
    <a href="<?= $url('learn/leaderboard') ?>" class="nav-item-btn <?= $isActive('/learn/leaderboard') ?>">
      <i class="bi <?= str_contains($path,'/learn/leaderboard')?'bi-trophy-fill':'bi-trophy' ?>"></i>
      <span>Rankings</span>
    </a>
    <a href="<?= $url('learn/profile') ?>" class="nav-item-btn <?= $isActive('/learn/profile') ?>">
      <i class="bi <?= str_contains($path,'/learn/profile')?'bi-person-fill':'bi-person-circle' ?>"></i>
      <span>Profile</span>
    </a>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $asset('js/app.js') ?>"></script>
<script>
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('<?= APP_URL ?>/sw.js');
</script>
</body>
</html>

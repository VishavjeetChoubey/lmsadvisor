<?php
use App\Core\View;
use App\Services\AuthService;
use App\Models\Setting;
$e         = fn(mixed $v): string => View::e($v);
$asset     = fn(string $p): string => View::asset($p);
$url       = fn(string $p = ''): string => View::url($p);
$authUser  = AuthService::user() ?? [];
$fullName  = $authUser['name'] ?? 'Student';
$firstName = explode(' ', $fullName)[0];
$initials  = strtoupper(substr($fullName, 0, 1));

// Dynamic branding
$siteName    = Setting::get('site_name', 'LMSAdvisor');
$siteFavicon = Setting::get('site_favicon', '');
$siteLogoVal = Setting::get('site_logo', '');
$faviconUrl  = $siteFavicon ? $asset($siteFavicon) : $asset('icons/favicon.png');
$logoUrl     = $siteLogoVal ? $asset($siteLogoVal) : null;

$path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isPlayer = (bool)preg_match('#/learn/courses/[^/]+/learn#', $path);

$navItems = [
  ['href' => '/learn/dashboard',   'icon' => 'bi-house-fill',           'label' => 'Home',      'segs' => ['/learn/dashboard', '/learn/', '/learn']],
  ['href' => '/learn/courses',     'icon' => 'bi-journal-bookmark-fill','label' => 'Courses',   'segs' => ['/learn/courses']],
  ['href' => '/learn/paths',       'icon' => 'bi-signpost-2-fill',      'label' => 'Paths',     'segs' => ['/learn/paths']],
  ['href' => '/learn/calendar',    'icon' => 'bi-calendar-check-fill',  'label' => 'Calendar',  'segs' => ['/learn/calendar']],
  ['href' => '/learn/leaderboard', 'icon' => 'bi-trophy-fill',          'label' => 'Rankings',  'segs' => ['/learn/leaderboard']],
  ['href' => '/learn/profile',     'icon' => 'bi-person-fill',          'label' => 'Profile',   'segs' => ['/learn/profile']],
];

foreach ($navItems as &$nav) {
  $nav['active'] = array_reduce($nav['segs'],
    fn($c, $s) => $c || str_contains($path, $s) || $path === $s, false);
}
if ($path === '/learn' || $path === '/learn/') {
  foreach ($navItems as &$nav) $nav['active'] = ($nav['href'] === '/learn/dashboard');
}
unset($nav);

$avatarUrl = null;
if (!empty($authUser['id'])) {
    static $__avatarCache = null;
    if ($__avatarCache === null) {
        $__pdo = \App\Core\Database::getInstance();
        $__row = $__pdo->prepare('SELECT avatar FROM users WHERE id=? LIMIT 1');
        $__row->execute([$authUser['id']]);
        $__avatarCache = $__row->fetchColumn() ?: '';
    }
    if ($__avatarCache) {
        $avatarUrl = APP_URL . '/storage/uploads/avatars/' . $__avatarCache;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#6366f1">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <title><?= $e($title ?? $siteName) ?></title>
  <link rel="icon" type="image/x-icon" href="<?= $faviconUrl ?>">
  <link rel="shortcut icon" href="<?= $faviconUrl ?>">
  <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
<?php
  $customCss    = Setting::get('custom_css', '');
  $customJsHead = Setting::get('custom_js_head', '');
?>
<?php if ($customCss):    ?><style id="custom-css"><?= $customCss ?></style><?php endif; ?>
<!-- Global BASE in <head> so all inline scripts have it -->
<script>window.LMS = window.LMS || {}; window.LMS.BASE = '<?= rtrim(APP_URL, '/') ?>';</script>
<?php if ($customJsHead): ?><script id="custom-js-head"><?= $customJsHead ?></script><?php endif; ?>
</head>
<body class="stu-body <?= $isPlayer ? 'player-mode' : '' ?>">
<?php $customJsBody = Setting::get('custom_js_body', ''); ?>
<?php if ($customJsBody): ?><script id="custom-js-body"><?= $customJsBody ?></script><?php endif; ?>

<!-- ══════════════════════════════════════════════
     ICON SIDEBAR  (reference-style)
══════════════════════════════════════════════ -->
<aside class="stu-sidebar <?= $isPlayer ? 'stu-sidebar--player' : '' ?>" id="stuSidebar">

  <!-- Site logo / brand -->
  <div class="stu-brand-top">
    <a href="<?= $url('learn/dashboard') ?>" class="stu-brand-link" title="<?= $e($siteName) ?>">
      <?php if ($logoUrl): ?>
        <img src="<?= $e($logoUrl) ?>" alt="<?= $e($siteName) ?>" class="stu-brand-logo">
      <?php else: ?>
        <div class="stu-brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
      <?php endif; ?>
    </a>
  </div>

  <!-- Profile photo / avatar at top -->
  <div class="stu-profile-top">
    <a href="<?= $url('learn/profile') ?>" class="stu-avatar-wrap" title="<?= $e($fullName) ?> — click to update profile">
      <?php if ($avatarUrl): ?>
        <img src="<?= $e($avatarUrl) ?>" class="stu-avatar-img" alt="">
      <?php else: ?>
        <div class="stu-avatar-initials"><?= $initials ?></div>
      <?php endif; ?>
      <div class="stu-avatar-upload-hint" title="Update photo"><i class="bi bi-camera-fill"></i></div>
    </a>
  </div>

  <!-- Nav items -->
  <nav class="stu-nav">
    <?php foreach ($navItems as $nav): ?>
    <a href="<?= $url(ltrim($nav['href'],'/')) ?>"
       class="stu-nav-btn <?= $nav['active'] ? 'active' : '' ?>"
       title="<?= $e($nav['label']) ?>">
      <i class="bi <?= $nav['icon'] ?>"></i>
      <span><?= $nav['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- Logout at bottom -->
  <div class="stu-sidebar-bottom">
    <button class="stu-nav-btn" id="stuDarkToggle" title="Toggle dark mode">
      <i class="bi bi-moon-stars-fill" id="stuDarkIcon"></i>
      <span>Theme</span>
    </button>
    <a href="<?= $url('logout') ?>" class="stu-nav-btn stu-logout" title="Sign out">
      <i class="bi bi-box-arrow-right"></i>
      <span>Sign Out</span>
    </a>
  </div>

</aside>

<!-- ══════════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════════ -->
<div class="stu-main" id="stuMain">

  <!-- Top bar -->
  <header class="stu-topbar" id="stuTopbar">
    <!-- Mobile hamburger -->
    <button class="stu-hamburger d-lg-none" id="stuHamburger">
      <i class="bi bi-list"></i>
    </button>

    <!-- Page / breadcrumb title -->
    <div class="stu-topbar-title"><?= $e($page_title ?? '') ?></div>

    <div class="stu-topbar-right">
      <!-- Notification bell with live count -->
      <div class="dropdown">
        <button class="stu-topbar-btn position-relative" id="stuNotifBell" title="Notifications" data-bs-toggle="dropdown">
          <i class="bi bi-bell"></i>
          <span class="notif-badge d-none" id="stuNotifBadge">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow" id="stuNotifDropdown"
             style="width:340px;max-height:420px;overflow:hidden;border-radius:14px;margin-top:8px;border:1px solid var(--border)">
          <div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom:1px solid var(--border)">
            <span class="fw-semibold" style="font-size:14px">Notifications</span>
            <button class="btn btn-xs btn-outline-secondary" id="stuMarkAllRead" style="font-size:11px;border-radius:6px;padding:2px 8px">Mark all read</button>
          </div>
          <div id="stuNotifList" style="max-height:340px;overflow-y:auto">
            <div class="text-center py-4 text-muted" style="font-size:13px" id="stuNotifEmpty">
              <i class="bi bi-bell-slash" style="font-size:1.5rem;opacity:.3"></i><br>No notifications
            </div>
          </div>
        </div>
      </div>

      <!-- User pill -->
      <div class="dropdown">
        <button class="stu-user-pill dropdown-toggle" data-bs-toggle="dropdown">
          <?php if ($avatarUrl): ?>
            <img src="<?= $e($avatarUrl) ?>" class="stu-pill-avatar" alt="">
          <?php else: ?>
            <div class="stu-pill-initials"><?= $initials ?></div>
          <?php endif; ?>
          <span class="d-none d-md-inline"><?= $e($firstName) ?></span>
          <i class="bi bi-chevron-down stu-pill-caret d-none d-md-inline"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:230px;font-size:13.5px;border-radius:14px;margin-top:8px">
          <li class="px-3 pt-3 pb-2">
            <div class="d-flex align-items-center gap-2">
              <?php if ($avatarUrl): ?>
                <img src="<?= $e($avatarUrl) ?>" style="width:38px;height:38px;border-radius:50%;object-fit:cover">
              <?php else: ?>
                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#1a56db);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff"><?= $initials ?></div>
              <?php endif; ?>
              <div>
                <div style="font-weight:700;font-size:14px"><?= $e($fullName) ?></div>
                <div style="font-size:11.5px;color:var(--text-muted)"><?= $e($authUser['email'] ?? '') ?></div>
              </div>
            </div>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><a class="dropdown-item py-2" href="<?= $url('learn/profile') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
          <li><a class="dropdown-item py-2" href="<?= $url('learn/courses') ?>"><i class="bi bi-journal-bookmark me-2"></i>My Courses</a></li>
          <li><a class="dropdown-item py-2" href="<?= $url('learn/leaderboard') ?>"><i class="bi bi-trophy me-2"></i>Rankings</a></li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><a class="dropdown-item py-2 text-danger" href="<?= $url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- Page content -->
  <main class="stu-content" id="stuContent">
    <?= $content ?>
  </main>

</div>

<!-- Mobile bottom nav -->
<nav class="stu-bottom-nav" id="stuBottomNav">
  <?php foreach ($navItems as $nav): ?>
  <a href="<?= $url(ltrim($nav['href'],'/')) ?>" class="stu-bottom-item <?= $nav['active'] ? 'active' : '' ?>">
    <i class="bi <?= $nav['icon'] ?>"></i>
    <span><?= $nav['label'] ?></span>
  </a>
  <?php endforeach; ?>
</nav>

<!-- Mobile overlay -->
<div class="stu-overlay" id="stuOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $asset('js/app.js') ?>"></script>
<?php $customJsFooter = Setting::get('custom_js_footer', ''); ?>
<?php if ($customJsFooter): ?><script id="custom-js-footer"><?= $customJsFooter ?></script><?php endif; ?>
<script>
if ('serviceWorker' in navigator) navigator.serviceWorker.register('<?= APP_URL ?>/sw.js');
</script>
</body>
</html>

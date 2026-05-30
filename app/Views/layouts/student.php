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

  <!-- Preconnect to external origins early -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Critical CSS inline — prevents FOUC by hiding body until styles load -->
  <style>
    html { visibility: hidden; }
    /* Ensure scrollbar always shown to prevent layout shift */
    html { overflow-y: scroll; }
  </style>

  <!-- Core CSS — loaded synchronously (render-blocking is acceptable for these) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $asset('css/app.css') ?>">

  <!-- Non-critical CSS — loaded async to avoid extra render blocking -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        media="print" onload="this.media='all'">
  <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></noscript>

  <!-- Reveal page once CSS is ready -->
  <script>document.documentElement.style.visibility = 'visible';</script>

  <!-- Google Fonts — async, font-display:swap prevents invisible text flash -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap"
        media="print" onload="this.media='all'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"></noscript>
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

      <!-- ── Desktop-only tools (SOC2: browser APIs only, no PII) ── -->
      <div class="d-none d-lg-flex align-items-center gap-2 me-1" style="font-size:12px">

        <?php if (in_array($authUser['role'] ?? '', ['super_admin','admin','manager'], true)): ?>
        <!-- Admin View button — only for admins viewing learner portal -->
        <a href="<?= $url('admin/dashboard') ?>"
           title="Back to Admin Panel"
           style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;border:1px solid #6366f1;color:#6366f1;background:transparent;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s;white-space:nowrap"
           onmouseover="this.style.background='#6366f1';this.style.color='#fff'"
           onmouseout="this.style.background='transparent';this.style.color='#6366f1'">
          <i class="bi bi-shield-fill" style="font-size:12px"></i>
          Admin View
        </a>
        <div style="width:1px;height:18px;background:var(--border);opacity:.5"></div>
        <?php endif; ?>

        <!-- Page load speed -->
        <div title="Page load time" style="display:inline-flex;align-items:center;gap:4px;cursor:default">
          <i class="bi bi-lightning-charge-fill" style="font-size:13px;color:#f59e0b"></i>
          <span id="stuLoadTime" style="font-weight:600;color:var(--text-1);font-size:12px">—</span>
        </div>

        <!-- Network status -->
        <div id="stuNetWrap" title="Network status" style="display:inline-flex;align-items:center;gap:4px;cursor:default">
          <i class="bi bi-wifi" id="stuWifiIcon" style="font-size:15px;color:#9ca3af"></i>
          <span id="stuNetLabel" style="font-weight:600;color:var(--text-2);font-size:11.5px">—</span>
        </div>

        <!-- Local time -->
        <div title="Your local time" style="display:inline-flex;align-items:center;gap:4px">
          <i class="bi bi-clock-fill" style="font-size:13px;color:#6366f1"></i>
          <span id="stuLocalTime" style="font-weight:600;color:var(--text-1);font-size:12px;font-variant-numeric:tabular-nums">—</span>
        </div>

        <div style="width:1px;height:18px;background:var(--border);opacity:.5"></div>
      </div>

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

<!-- ── Desktop tools JS (SOC2: browser-native APIs only, no PII) ─────────── -->
<script>
(function() {
  // Page load time — Navigation Timing API (browser built-in, no external calls)
  window.addEventListener('load', function() {
    var el = document.getElementById('stuLoadTime');
    if (!el || !window.performance) return;
    setTimeout(function() {
      var nav = performance.getEntriesByType('navigation')[0];
      var ms  = nav ? Math.round(nav.duration) : Math.round(performance.now());
      var color = ms < 800 ? '#059669' : ms < 2000 ? '#d97706' : '#dc2626';
      el.textContent = ms + 'ms';
      el.style.color = color;
    }, 100);
  });

  // Network status — Network Information API (browser built-in, no external calls)
  function updateNet() {
    var icon  = document.getElementById('stuWifiIcon');
    var label = document.getElementById('stuNetLabel');
    if (!icon || !label) return;
    if (!navigator.onLine) {
      icon.style.color  = '#dc2626';
      label.textContent = 'Offline';
      label.style.color = '#dc2626';
      return;
    }
    var conn  = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    var speed = conn ? conn.downlink : null;
    var color, text;
    if (speed === null)  { color = '#6366f1'; text = 'Online'; }
    else if (speed >= 5) { color = '#059669'; text = speed.toFixed(0) + ' Mbps'; }
    else if (speed >= 1) { color = '#d97706'; text = speed.toFixed(1) + ' Mbps'; }
    else                 { color = '#dc2626'; text = 'Slow'; }
    icon.style.color  = color;
    label.textContent = text;
    label.style.color = color;
  }
  updateNet();
  window.addEventListener('online',  updateNet);
  window.addEventListener('offline', updateNet);
  if (navigator.connection) navigator.connection.addEventListener('change', updateNet);

  // Local clock — browser Date (no external calls, no PII)
  function tick() {
    var el = document.getElementById('stuLocalTime');
    if (!el) return;
    el.textContent = new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit', second:'2-digit' });
  }
  tick();
  setInterval(tick, 1000);
})();
</script>
</body>
</html>

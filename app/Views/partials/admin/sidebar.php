<?php
use App\Core\View;
use App\Services\AuthService;
use App\Services\MenuService;
$url      = fn(string $p = ''): string => View::url($p);
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user() ?? [];
$role     = $authUser['role_name'] ?? $authUser['role'] ?? 'admin';
$fullName = $authUser['name'] ?? 'Admin';
$initials = strtoupper(substr($fullName, 0, 1));
$path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = fn(string $seg): string => str_contains($path, $seg) ? 'active' : '';

$navItems = MenuService::forRole($role);
?>
<aside class="adm-sidebar" id="adminSidebar">

  <!-- Brand logo at very top -->
  <div class="adm-brand">
    <?php if (!empty($logoUrl)): ?>
      <img src="<?= $e($logoUrl) ?>" alt="<?= $e($siteName) ?>" style="height:32px;max-width:120px;object-fit:contain">
    <?php else: ?>
      <div class="adm-brand-icon"><i class="fas fa-graduation-cap"></i></div>
      <span class="adm-brand-name"><?= $e($siteName) ?></span>
    <?php endif; ?>
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

  <script>
  (function() {
    var nav    = document.getElementById('adminNav');
    var active = nav && nav.querySelector('.adm-nav-btn.active');

    // Scroll active item into centre of nav on page load
    if (active && nav) {
      // Wait for layout then scroll
      requestAnimationFrame(function() {
        var navTop    = nav.getBoundingClientRect().top;
        var itemTop   = active.getBoundingClientRect().top;
        var itemH     = active.offsetHeight;
        var navH      = nav.clientHeight;
        var target    = nav.scrollTop + (itemTop - navTop) - (navH / 2) + (itemH / 2);
        nav.scrollTo({ top: target, behavior: 'smooth' });
      });
    }

    // On click: add active class immediately + scroll into view before navigate
    if (nav) {
      nav.querySelectorAll('.adm-nav-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          // Remove active from all
          nav.querySelectorAll('.adm-nav-btn').forEach(function(b) {
            b.classList.remove('active');
          });
          // Add to clicked
          this.classList.add('active');
          // Scroll clicked item to centre
          var navH   = nav.clientHeight;
          var itemH  = this.offsetHeight;
          var target = this.offsetTop - (navH / 2) + (itemH / 2);
          nav.scrollTo({ top: target, behavior: 'smooth' });
        });
      });
    }
  })();
  </script>

  <!-- User + logout at bottom -->
  <div class="adm-sidebar-bottom">
    <!-- Version badge -->
    <div style="padding:6px 12px 10px;text-align:center">
      <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(91,94,246,.12);color:#a5b4fc;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;letter-spacing:.03em">
        <i class="bi bi-stars" style="font-size:10px"></i>
        LMSAdvisor v<?= defined('APP_VERSION') ? APP_VERSION : '3.0.0' ?>
      </span>
    </div>
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

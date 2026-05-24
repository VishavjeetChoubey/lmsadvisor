<?php
use App\Core\View;
$e     = fn(mixed $v): string => View::e($v);
$asset = fn(string $p): string => View::asset($p);
$url   = fn(string $p = ''): string => View::url($p);

// Determine active bottom nav item
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = function(string $seg) use ($path): string {
    return str_contains($path, $seg) ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#6366f1">
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
<body class="student-body">

<!-- Sidebar (desktop) -->
<?php require VIEW_PATH . '/partials/student/sidebar.php'; ?>

<!-- Page wrapper -->
<div class="student-main" id="studentMain">
  <!-- Topbar -->
  <?php require VIEW_PATH . '/partials/student/topbar.php'; ?>
  <!-- Main content -->
  <main class="student-content">
    <?= $content ?>
  </main>
  <!-- Footer -->
  <?php require VIEW_PATH . '/partials/student/footer.php'; ?>
</div>

<!-- ── Bottom Navigation (mobile / PWA) ────────────────────────────────────── -->
<nav class="student-bottom-nav" id="bottomNav" role="navigation" aria-label="Main navigation">
  <div class="nav-items">

    <a href="<?= $url('learn/dashboard') ?>"
       class="nav-item-btn <?= str_contains($path, '/learn/dashboard') || $path === '/learn' || $path === '/learn/' ? 'active' : '' ?>">
      <i class="bi <?= str_contains($path, '/learn/dashboard') ? 'bi-grid-fill' : 'bi-grid-1x2' ?>"></i>
      <span>Home</span>
    </a>

    <a href="<?= $url('learn/courses') ?>"
       class="nav-item-btn <?= $isActive('/learn/courses') ?>">
      <i class="bi <?= str_contains($path, '/learn/courses') ? 'bi-book-fill' : 'bi-book-half' ?>"></i>
      <span>Courses</span>
    </a>

    <a href="<?= $url('learn/calendar') ?>"
       class="nav-item-btn <?= $isActive('/learn/calendar') ?>">
      <i class="bi <?= str_contains($path, '/learn/calendar') ? 'bi-calendar-fill' : 'bi-calendar3' ?>"></i>
      <span>Calendar</span>
    </a>

    <a href="<?= $url('learn/leaderboard') ?>"
       class="nav-item-btn <?= $isActive('/learn/leaderboard') ?>">
      <i class="bi <?= str_contains($path, '/learn/leaderboard') ? 'bi-trophy-fill' : 'bi-trophy' ?>"></i>
      <span>Ranking</span>
    </a>

    <a href="<?= $url('learn/profile') ?>"
       class="nav-item-btn <?= $isActive('/learn/profile') ?>">
      <i class="bi <?= str_contains($path, '/learn/profile') ? 'bi-person-fill' : 'bi-person-circle' ?>"></i>
      <span>Profile</span>
    </a>

  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $asset('js/app.js') ?>"></script>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js');
  }
</script>
</body>
</html>
